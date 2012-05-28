<?php
include_once('../include/session.inc.php');

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

require('super_header.inc.php');

require('../smarty_tools.php');

include_once "period_stats.class.php";
include_once "team.class.php";
include_once "project.class.php";
include_once "jobs.class.php";
include_once "time_tracking.class.php";

include "productivity_report_tools.php";

$logger = Logger::getLogger("productivity_report");

/**
 * @param int $teamid
 * @param int $defaultProjectid
 * @return array
 */
function getTeamProjects($teamid, $defaultProjectid) {
   global $logger;

   // --- Project List
   $query  = "SELECT mantis_project_table.id, mantis_project_table.name ".
      "FROM `codev_team_project_table`, `mantis_project_table` ".
      "WHERE codev_team_project_table.team_id = $teamid ".
      "AND codev_team_project_table.project_id = mantis_project_table.id ".
      "ORDER BY mantis_project_table.name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return;
   }
   if (0 != mysql_num_rows($result)) {
      while($row = mysql_fetch_object($result)) {
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
 * @return array
 */
function getFormattedReopenedTaks($timeTracking) {
   $formatedTasks = NULL;
   foreach ($timeTracking->getReopened() as $bug_id) {
      $issue = IssueCache::getInstance()->getIssue($bug_id);
      $formatedTasks[] = issueInfoURL($issue->bugId, $issue->summary);
   }
   return $formatedTasks;
}

function getProductionDaysUrl($timeTracking) {
   $formatedValues = $timeTracking->getProdDays().':'.$timeTracking->getManagementDays().':'.($timeTracking->getProdDaysSideTasks(false) - $timeTracking->getManagementDays());
   $formatedLegends = T_('Projects').':'.T_('Project Management').':'.T_('Other SideTasks');
   $colors = '#92C5FC'.':'.'#FFC16B'.':'.'#FFF494';
   return SmartUrlEncode('colors='.$colors.'&legends='.$formatedLegends.'&values='.$formatedValues);
}

/**
 * @param TimeTracking $timeTracking
 * @param int $teamid
 * @return array
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

function getWorkingDaysPerProject(TimeTracking $timeTracking) {
   global $logger;

   $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);
   $query = "SELECT mantis_project_table.id, mantis_project_table.name ".
      "FROM `mantis_project_table`, `codev_team_project_table` ".
      "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
      "AND codev_team_project_table.team_id = $team->id ".
      " ORDER BY name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return;
   }

   $workingDaysPerProject = NULL;
   while($row = mysql_fetch_object($result)) {
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
   }
}

/**
 * display Drifts for Issues that have been marked as 'Resolved' durung the timestamp
 * @param $timeTracking
 * @param bool $withSupport
 * @return string
 */
function getResolvedDeviationStats(TimeTracking $timeTracking, $withSupport = true) {
   $issueList = $timeTracking->getResolvedIssues();
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
 * @param TimeTracking $timeTracking
 * @param bool $isManager
 * @param bool $withSupport
 * @return array
 */
function getResolvedIssuesInDrift(TimeTracking $timeTracking, $isManager=false, $withSupport=true) {
   $issueList = $timeTracking->getResolvedIssues();
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

function getCheckWarnings($timeTracking) {
   global $logger;

   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
      "FROM  `codev_team_user_table`, `mantis_user_table` ".
      "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
      "AND    codev_team_user_table.user_id = mantis_user_table.id ".
      "ORDER BY mantis_user_table.username";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return;
   }

   $warnings = NULL;
   while($row = mysql_fetch_object($result)) {
      $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
      foreach ($incompleteDays as $date => $value) {
         if ($value < 1) {
            $warnings[] = array('username' => $row->username,
               'date' => date("Y-m-d", $date),
               'details' => T_("incomplete").' ('.T_('missing').' '.(1-$value).' '.T_('day')
            );
         } else {
            $warnings[] = array('username' => $row->username,
               'date' => date("Y-m-d", $date),
               'details' => T_("inconsistent").' ('.$value.' jour)'
            );
         }
      }

      $missingDays = $timeTracking->checkMissingDays($row->user_id);
      foreach ($missingDays as $date) {
         $warnings[] = array('username' => $row->username,
            'date' => date("Y-m-d", $date),
            'details' => T_("not defined.")
         );
      }
   }

   return $warnings;
}

// =========== MAIN ==========
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Period Statistics');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $mTeamList = $session_user->getDevTeamList();
   $lTeamList = $session_user->getLeadedTeamList();
   $oTeamList = $session_user->getObservedTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

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

         $smartyHelper->assign('timeDriftStats', $timeTracking->getTimeDriftStats());
         $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
         $percent = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;
         $smartyHelper->assign('percent', round($percent, 1));

         if (0 != count($timeTracking->getResolvedIssues())) {
            $withSupport = true;
            $smartyHelper->assign('resolvedDeviationStats', getResolvedDeviationStats ($timeTracking, $withSupport));

            $isManager = array_key_exists($teamid, $managedTeamList);
            $smartyHelper->assign('isManager', $isManager);
            $smartyHelper->assign('resolvedIssuesInDrift', getResolvedIssuesInDrift($timeTracking, $isManager));
         }

         $smartyHelper->assign('reopenedBugsRate', round($timeTracking->getReopenedRate() * 100, 1));
         $smartyHelper->assign('formattedReopenedTaks', getFormattedReopenedTaks($timeTracking));

         $smartyHelper->assign('warnings', getCheckWarnings($timeTracking));
      }
   }

   // log stats
   IssueCache::getInstance()->logStats();
   ProjectCache::getInstance()->logStats();
   UserCache::getInstance()->logStats();
   TimeTrackCache::getInstance()->logStats();

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
