<?php
require('../include/session.inc.php');

/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

require('include/super_header.inc.php');

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

require_once('productivity_report_tools.php');

require_once('classes/issue_cache.class.php');
require_once('classes/jobs.class.php');
include_once('classes/period_stats.class.php');
require_once('classes/project_cache.class.php');
require_once('classes/sqlwrapper.class.php');
require_once('classes/team.class.php');
require_once('classes/team_cache.class.php');
require_once('classes/time_tracking.class.php');
require_once('classes/timetrack_cache.class.php');

$logger = Logger::getLogger("productivity_report");

/**
 * @param int $teamid
 * @param int $defaultProjectid
 * @return mixed[]
 */
function getTeamProjects($teamid, $defaultProjectid) {
   // --- Project List
   $query  = "SELECT mantis_project_table.id, mantis_project_table.name ".
      "FROM `codev_team_project_table`, `mantis_project_table` ".
      "WHERE codev_team_project_table.team_id = $teamid ".
      "AND codev_team_project_table.project_id = mantis_project_table.id ".
      "ORDER BY mantis_project_table.name";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }
   if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $projList[$row->id] = $row->name;
      }
   }

   $projects[] = array(
      'id' => 0,
      'name' => T_("All sideTasks Projects"),
   );
   foreach ($projList as $pid => $pname) {
      $projects[] = array(
         'id' => $pid,
         'name' => $pname,
         'selected' => $pid == $defaultProjectid
      );
   }
   return $projects;
}

/**
 * Get the reopened taks for Smarty
 * @param TimeTracking $timeTracking
 * @return string[]
 */
function getFormattedReopenedTaks(TimeTracking $timeTracking) {
   $formatedTasks = NULL;
   foreach ($timeTracking->getReopened() as $bug_id) {
      $issue = IssueCache::getInstance()->getIssue($bug_id);
      $formatedTasks[] = issueInfoURL($issue->bugId, '['.$issue->getProjectName().'] '.$issue->summary);
   }
   return $formatedTasks;
}

/**
 * @param TimeTracking $timeTracking
 * @return string
 */
function getProductionDaysUrl(TimeTracking $timeTracking) {
   $managementDay = $timeTracking->getManagementDays();
   $formatedValues = $timeTracking->getProdDays().':'.$managementDay.':'.($timeTracking->getProdDaysSideTasks(false) - $managementDay);
   $formatedLegends = T_('Projects').':'.T_('Project Management').':'.T_('Other SideTasks');
   $colors = '#92C5FC'.':'.'#FFC16B'.':'.'#FFF494';
   return SmartUrlEncode('colors='.$colors.'&legends='.$formatedLegends.'&values='.$formatedValues);
}

/**
 * @param TimeTracking $timeTracking
 * @param int $teamid
 * @return mixed[]
 */
function getWorkingDaysPerJob(TimeTracking $timeTracking, $teamid) {
   // find out which jobs must be displayed
   $projList = Team::getProjectList($teamid);
   $team = TeamCache::getInstance()->getTeam($teamid);
   $jobList  = array();
   foreach ($projList as $id => $pname) {
      $p = ProjectCache::getInstance()->getProject($id);
      $jl = $p->getJobList($team->getProjectType($id));
      $jobList += $jl;
   }

   $jobs = new Jobs();
   $workingDaysPerJob = NULL;
   foreach ($jobList as $id => $jname) {
      if (Jobs::JOB_NA != $id) {
         $workingDaysPerJob[] = array(
            "name" => $jname,
            "nbDays" => $timeTracking->getWorkingDaysPerJob($id),
            "color" => $jobs->getJobColor($id)
         );
      }
   }

   return $workingDaysPerJob;
}

/**
 * @param array $workingDaysPerJobs
 * @return string
 */
function getWorkingDaysPerJobUrl(array $workingDaysPerJobs) {
   $formatedValues = NULL;
   $formatedLegends = NULL;
   $formatedColors = NULL;

   foreach ($workingDaysPerJobs as $id => $workingDaysPerJob) {
      if (0 != $workingDaysPerJob['nbDays']) {
         if (NULL != $formatedValues) { $formatedValues .= ":"; $formatedLegends .= ":"; $formatedColors .= ":"; }
         $formatedValues .= $workingDaysPerJob['nbDays'];
         $formatedLegends .= $workingDaysPerJob['name'];
         $formatedColors .= "#".$workingDaysPerJob['color'];
      }
   }

   if (NULL != $formatedValues) {
      return SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues.'&colors='.$formatedColors);
   }
}

/**
 * @param TimeTracking $timeTracking
 * @return mixed[]
 */
function getWorkingDaysPerProject(TimeTracking $timeTracking) {
   $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);
   $query = "SELECT mantis_project_table.id, mantis_project_table.name ".
      "FROM `mantis_project_table`, `codev_team_project_table` ".
      "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
      "AND codev_team_project_table.team_id = $team->id ".
      " ORDER BY name";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $workingDaysPerProject = NULL;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $nbDays = $timeTracking->getWorkingDaysPerProject($row->id);

      $proj = ProjectCache::getInstance()->getProject($row->id);
      if ((! $team->isSideTasksProject($proj->id)) && (! $team->isNoStatsProject($proj->id))) {
         $progress = round(100 * $proj->getProgress()).'%';
         $progressMgr = round(100 * $proj->getProgressMgr()).'%';
      } else {
         $progress = '';
         $progressMgr = '';
      }

      $workingDaysPerProject[] = array(
         'name' => $row->name,
         'nbDays' => $nbDays,
         'progress' => $progress,
         'progressMgr' => $progressMgr
      );
   }

   return $workingDaysPerProject;
}

/**
 * @param array $workingDaysPerProject
 * @return string
 */
function getWorkingDaysPerProjectUrl(array $workingDaysPerProject) {
   $formatedValues = NULL;
   $formatedLegends = NULL;
   foreach ($workingDaysPerProject as $id => $workingDays) {
      if (0 != $workingDays['nbDays']) {
         if (NULL != $formatedValues) {
            $formatedValues .= ":"; $formatedLegends .= ":";
         }
         $formatedValues .= $workingDays['nbDays'];
         $formatedLegends .= $workingDays['name'];
      }
   }

   if (NULL != $formatedValues) {
      return SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues);
   } else {
      return NULL;
   }
}

/**
 * display Drifts for Issues that have been marked as 'Resolved' durung the timestamp
 * @param Issue[] $issueList
 * @param bool $withSupport
 * @return mixed[]
 */
function getResolvedDeviationStats(array $issueList, $withSupport = true) {
   $issueSelection = new IssueSelection("resolved issues");
   $issueSelection->addIssueList($issueList);

   $allDriftMgr = $issueSelection->getDriftMgr();
   $allDrift = $issueSelection->getDrift();

   $deviationGroupsMgr = $issueSelection->getDeviationGroupsMgr(1, $withSupport);
   $deviationGroups    = $issueSelection->getDeviationGroups(1, $withSupport);

   $posDeviationGroupMgr = $deviationGroupsMgr['positive'];
   $posDeviationGroup = $deviationGroups['positive'];
   $posDriftMgr = $posDeviationGroupMgr->getDriftMgr();
   $posDrift    = $posDeviationGroup->getDrift();
   $detailsResolvedDeviationStats[] = array(
      "type" => "Tasks in drift",
      "nbIssuesMgr" => $posDeviationGroupMgr->getNbIssues(),
      "nbDaysMgr" => $posDriftMgr['nbDays'],
      "nbIssues" => $posDeviationGroup->getNbIssues(),
      "nbDays" => $posDrift['nbDays'],
      "formattedIssueListMgr" => $posDeviationGroupMgr->getFormattedIssueList(),
      "formattedIssueList" => $posDeviationGroup->getFormattedIssueList()
   );

   $equalDeviationGroupMgr = $deviationGroupsMgr['equal'];
   $equalDeviationGroup = $deviationGroups['equal'];
   $equalDriftMgr = $equalDeviationGroupMgr->getDriftMgr();
   $equalDrift = $equalDeviationGroup->getDrift();
   $detailsResolvedDeviationStats[] = array(
      "type" => "Tasks in time",
      "nbIssuesMgr" => $equalDeviationGroupMgr->getNbIssues(),
      "nbDaysMgr" => $equalDriftMgr['nbDays'],
      "nbIssues" => $equalDeviationGroup->getNbIssues(),
      "nbDays" => $equalDrift['nbDays'],
      "formattedIssueListMgr" => $equalDeviationGroupMgr->getFormattedIssueList(),
      "formattedIssueList" => $equalDeviationGroup->getFormattedIssueList()
   );

   $negDeviationGroupMgr = $deviationGroupsMgr['negative'];
   $negDeviationGroup = $deviationGroups['negative'];
   $negDriftMgr = $negDeviationGroupMgr->getDriftMgr();
   $negDrift    = $negDeviationGroup->getDrift();
   $detailsResolvedDeviationStats[] = array(
      "type" => "Tasks ahead",
      "nbIssuesMgr" => $negDeviationGroupMgr->getNbIssues(),
      "nbDaysMgr" => $negDriftMgr['nbDays'],
      "nbIssues" => $negDeviationGroup->getNbIssues(),
      "nbDays" => $negDrift['nbDays'],
      "formattedIssueListMgr" => $negDeviationGroupMgr->getFormattedIssueList(),
      "formattedIssueList" => $negDeviationGroup->getFormattedIssueList()
   );

   $resolvedDeviationStats = array(
      "driftMgr" => round($allDriftMgr['nbDays'], 2),
      "drift" => round($allDrift['nbDays'], 2),
      "detailsResolvedDeviationStats" => $detailsResolvedDeviationStats
   );

   return $resolvedDeviationStats;
}

/**
 * @param Issue[] $issueList
 * @param bool $isManager
 * @param bool $withSupport
 * @return mixed[]
 */
function getResolvedIssuesInDrift(array $issueList, $isManager=false, $withSupport=true) {
   foreach ($issueList as $issue) {
      // TODO: check if issue in team project list ?
      if ($isManager) {
         $driftMgrEE = $issue->getDriftMgr($withSupport);
      }
      $driftEE = $issue->getDrift($withSupport);

      if (($isManager && $driftMgrEE > 0) || ($driftEE > 0)) {
         $resolvedIssuesInDrift[] = array(
            "issueURL" => issueInfoURL($issue->bugId),
            "projectName" => $issue->getProjectName(),
            "driftMgrEE" => $driftMgrEE,
            "driftEE" => $driftEE,
            "currentStatusName" => $issue->getCurrentStatusName(),
            "summary" => $issue->summary
         );
      }
   }

   return $resolvedIssuesInDrift;
}

/**
 * @param TimeTracking $timeTracking
 * @return mixed[]
 */
function getCheckWarnings(TimeTracking $timeTracking) {
   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
      "FROM  `codev_team_user_table`, `mantis_user_table` ".
      "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
      "AND    codev_team_user_table.user_id = mantis_user_table.id ".
      "ORDER BY mantis_user_table.username";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $warnings = NULL;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
      foreach ($incompleteDays as $date => $value) {
         if ($value < 1) {
            $warnings[] = array(
                'user' => $row->username,
               'date' => date("Y-m-d", $date),
               'desc' => T_("incomplete").' ('.T_('missing').' '.(1-$value).' '.T_('day').')',
               'severity' => T_("Error"),
               'severityColor' => 'color:red'
            );
         } else {
            $warnings[] = array(
               'user' => $row->username,
               'date' => date("Y-m-d", $date),
               'desc' => T_("inconsistent").' ('.$value.' '.T_('day').')',
               'severity' => T_("Error"),
               'severityColor' => 'color:red'
            );
         }
      }

      $missingDays = $timeTracking->checkMissingDays($row->user_id);
      foreach ($missingDays as $date) {
         $warnings[] = array(
             'user' => $row->username,
            'date' => date("Y-m-d", $date),
            'desc' => T_("not defined."),
            'severity' => T_("Error"),
            'severityColor' => 'color:red'
         );
      }
   }

   return $warnings;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Period Statistics');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $teamList = $session_user->getTeamList();
   if (0 != count($teamList)) {
      $weekDates = week_dates(date('W'),date('Y'));

      $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
      $_SESSION['teamid'] = $teamid;

      $smartyHelper->assign('teams', getTeams($teamList,$teamid));

      $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : date("Y-m-d", $weekDates[1]);
      $smartyHelper->assign('startDate', $startdate);

      $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : date("Y-m-d", $weekDates[5]);
      $smartyHelper->assign('endDate', $enddate);

      if (0 != $teamid) {
         $startTimestamp = date2timestamp($startdate);
         $endTimestamp = date2timestamp($enddate);
         $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $smartyHelper->assign('timeTracking', $timeTracking);

         if (0 != $timeTracking->getProdDays() + $timeTracking->getManagementDays() + ($timeTracking->getProdDaysSideTasks(false) - $timeTracking->getManagementDays())) {
            $smartyHelper->assign('productionDaysUrl', getProductionDaysUrl($timeTracking));
         }

         $workingDaysPerJobs = getWorkingDaysPerJob($timeTracking, $teamid);
         $smartyHelper->assign('workingDaysPerJob', $workingDaysPerJobs);
         if($workingDaysPerJobs != NULL) {
            $smartyHelper->assign('workingDaysPerJobUrl', getWorkingDaysPerJobUrl($workingDaysPerJobs));
         }

         $defaultProjectid = $_POST['projectid'];
         $getTeamProjects = getTeamProjects($teamid, $defaultProjectid);
         $smartyHelper->assign('projects', $getTeamProjects);

         $projectid = 0;
         foreach($getTeamProjects as $key => $value) {
            if($value['id'] == $defaultProjectid) {
               $projectid = $defaultProjectid;
            }
         }
         $smartyHelper->assign('projectid', $projectid);

         $projectDetails = NULL;
         if (0 != $projectid) {
            $projectDetails = getProjectDetails($timeTracking, $projectid);
         } else {
            // all sideTasks
            $projectDetails = getSideTasksProjectDetails($timeTracking);
         }
         $smartyHelper->assign('projectDetails', $projectDetails);
         if($projectDetails != NULL) {
            $smartyHelper->assign('projectDetailsUrl', getProjectDetailsUrl($projectDetails));
         }

         $workingDaysPerProject = getWorkingDaysPerProject($timeTracking);
         $smartyHelper->assign('workingDaysPerProject', $workingDaysPerProject);
         if($workingDaysPerProject != NULL) {
            $smartyHelper->assign('workingDaysPerProjectUrl', getWorkingDaysPerProjectUrl($workingDaysPerProject));
         }

         $smartyHelper->assign('efficiencyRate', round($timeTracking->getEfficiencyRate(), 2));
         $smartyHelper->assign('systemDisponibilityRate', round($timeTracking->getSystemDisponibilityRate(), 3));

         $timeDriftStats = $timeTracking->getTimeDriftStats();
         $smartyHelper->assign('timeDriftStats', $timeDriftStats);
         $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
         $percent = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;
         $smartyHelper->assign('percent', round($percent, 1));

         if (0 != count($timeTracking->getResolvedIssues())) {
            $issueList = $timeTracking->getResolvedIssues();
            $withSupport = true;
            $smartyHelper->assign('resolvedDeviationStats', getResolvedDeviationStats ($issueList, $withSupport));

            $managedTeamList = $session_user->getManagedTeamList();
            $isManager = array_key_exists($teamid, $managedTeamList);
            $smartyHelper->assign('isManager', $isManager);
            $smartyHelper->assign('resolvedIssuesInDrift', getResolvedIssuesInDrift($issueList, $isManager));
         }

         $smartyHelper->assign('reopenedBugsRate', round($timeTracking->getReopenedRate() * 100, 1));
         $smartyHelper->assign('formattedReopenedTaks', getFormattedReopenedTaks($timeTracking));

         // --- warnings
         $consistencyErrors = getCheckWarnings($timeTracking);
         $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('ccheckErrList', $consistencyErrors);

      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
