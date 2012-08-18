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

class ProductivityReportsController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamList = $session_user->getTeamList();
         if (0 != count($teamList)) {
            $weekDates = Tools::week_dates(date('W'),date('Y'));

            if(isset($_POST['teamid'])) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
               $_SESSION['teamid'] = $teamid;
            } else {
               $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
            }

            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

            $startdate = Tools::getSecurePOSTStringValue('startdate', date("Y-m-d", $weekDates[1]));
            $this->smartyHelper->assign('startDate', $startdate);

            $enddate = Tools::getSecurePOSTStringValue('enddate', date("Y-m-d", $weekDates[5]));
            $this->smartyHelper->assign('endDate', $enddate);

            if (0 != $teamid) {
               $team = TeamCache::getInstance()->getTeam($teamid);
               if(count($team->getProjects(false)) > 0) {
                  $startTimestamp = Tools::date2timestamp($startdate);
                  $endTimestamp = Tools::date2timestamp($enddate);
                  $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

                  $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

                  $this->smartyHelper->assign('timeTracking', $timeTracking);

                  if (0 != $timeTracking->getProdDays() + $timeTracking->getManagementDays() + ($timeTracking->getProdDaysSideTasks(false) - $timeTracking->getManagementDays())) {
                     $this->smartyHelper->assign('productionDaysUrl', $this->getProductionDaysUrl($timeTracking));
                  }

                  $workingDaysPerJobs = $this->getWorkingDaysPerJob($timeTracking, $teamid);
                  $this->smartyHelper->assign('workingDaysPerJob', $workingDaysPerJobs);
                  if($workingDaysPerJobs != NULL) {
                     $this->smartyHelper->assign('workingDaysPerJobUrl', $this->getWorkingDaysPerJobUrl($workingDaysPerJobs));
                  }

                  $defaultProjectid = Tools::getSecurePOSTIntValue('projectid', 0);
                  $getTeamProjects = SmartyTools::getSmartyArray($this->getTeamProjects($teamid),$defaultProjectid);
                  $this->smartyHelper->assign('projects', $getTeamProjects);

                  $projectid = 0;
                  if(array_key_exists($defaultProjectid, $getTeamProjects)) {
                     $projectid = $defaultProjectid;
                  }
                  $this->smartyHelper->assign('projectid', $projectid);

                  $projectDetails = NULL;
                  if (0 != $projectid) {
                     $projectDetails = ProductivityReportTools::getProjectDetails($timeTracking, $projectid);
                  } else {
                     // all sideTasks
                     $projectDetails = ProductivityReportTools::getSideTasksProjectDetails($timeTracking);
                  }
                  $this->smartyHelper->assign('projectDetails', $projectDetails);
                  if($projectDetails != NULL) {
                     $this->smartyHelper->assign('projectDetailsUrl', ProductivityReportTools::getProjectDetailsUrl($projectDetails));
                  }

                  $workingDaysPerProject = $this->getWorkingDaysPerProject($timeTracking);
                  $this->smartyHelper->assign('workingDaysPerProject', $workingDaysPerProject);
                  if($workingDaysPerProject != NULL) {
                     $this->smartyHelper->assign('workingDaysPerProjectUrl', $this->getWorkingDaysPerProjectUrl($workingDaysPerProject));
                  }

                  $this->smartyHelper->assign('efficiencyRate', round($timeTracking->getEfficiencyRate(), 2));
                  $this->smartyHelper->assign('systemDisponibilityRate', round($timeTracking->getSystemDisponibilityRate(), 3));

                  $timeDriftStats = $timeTracking->getTimeDriftStats();
                  $this->smartyHelper->assign('timeDriftStats', $timeDriftStats);
                  $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
                  $percent = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;
                  $this->smartyHelper->assign('percent', round($percent, 1));

                  $resolvedIssues = $timeTracking->getResolvedIssues();
                  if (0 != count($resolvedIssues)) {
                     $withSupport = true;
                     $this->smartyHelper->assign('resolvedDeviationStats', $this->getResolvedDeviationStats ($resolvedIssues, $withSupport));

                     $managedTeamList = $session_user->getManagedTeamList();
                     $isManager = array_key_exists($teamid, $managedTeamList);
                     $this->smartyHelper->assign('isManager', $isManager);
                     $this->smartyHelper->assign('resolvedIssuesInDrift', $this->getResolvedIssuesInDrift($resolvedIssues, $isManager));
                  }

                  $this->smartyHelper->assign('reopenedBugsRate', round($timeTracking->getReopenedRate() * 100, 1));
                  $this->smartyHelper->assign('formattedReopenedTaks', $this->getFormattedReopenedTaks($timeTracking));

                  // warnings
                  $consistencyErrors = $this->getCheckWarnings($timeTracking);
                  $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
                  $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               }
            }
         }
      }
   }

   /**
    * @param int $teamid
    * @return mixed[]
    */
   private function getTeamProjects($teamid) {
      $team = TeamCache::getInstance()->getTeam($teamid);
      $projList = $team->getProjects();
      $projList[0] = T_("All sideTasks Projects");
      return $projList;
   }

   /**
    * Get the reopened taks for Smarty
    * @param TimeTracking $timeTracking
    * @return string[]
    */
   private function getFormattedReopenedTaks(TimeTracking $timeTracking) {
      $formatedTasks = NULL;
      foreach ($timeTracking->getReopened() as $issue) {
         $formatedTasks[] = Tools::issueInfoURL($issue->bugId, '['.$issue->getProjectName().'] '.$issue->summary);
      }
      return $formatedTasks;
   }

   /**
    * @param TimeTracking $timeTracking
    * @return string
    */
   private function getProductionDaysUrl(TimeTracking $timeTracking) {
      $managementDay = $timeTracking->getManagementDays();
      $formatedValues = $timeTracking->getProdDays().':'.$managementDay.':'.($timeTracking->getProdDaysSideTasks(false) - $managementDay);
      $formatedLegends = T_('Projects').':'.T_('Project Management').':'.T_('Other SideTasks');
      $colors = '#92C5FC'.':'.'#FFC16B'.':'.'#FFF494';
      return Tools::SmartUrlEncode('colors='.$colors.'&legends='.$formatedLegends.'&values='.$formatedValues);
   }


   /**
    * @param TimeTracking $timeTracking
    * @param int $teamid
    * @return mixed[]
    */
   private function getWorkingDaysPerJob(TimeTracking $timeTracking, $teamid) {
      // find out which jobs must be displayed
      $team = TeamCache::getInstance()->getTeam($teamid);
      $projList = $team->getProjects();
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
    * @param array[] $workingDaysPerJobs
    * @return string
    */
   private function getWorkingDaysPerJobUrl(array $workingDaysPerJobs) {
      $formatedValues = NULL;
      $formatedLegends = NULL;
      $formatedColors = NULL;

      foreach ($workingDaysPerJobs as $workingDaysPerJob) {
         if (0 != $workingDaysPerJob['nbDays']) {
            if (NULL != $formatedValues) { $formatedValues .= ":"; $formatedLegends .= ":"; $formatedColors .= ":"; }
            $formatedValues .= $workingDaysPerJob['nbDays'];
            $formatedLegends .= $workingDaysPerJob['name'];
            $formatedColors .= "#".$workingDaysPerJob['color'];
         }
      }

      if (NULL != $formatedValues) {
         return Tools::SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues.'&colors='.$formatedColors);
      }
      return NULL;
   }

   /**
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getWorkingDaysPerProject(TimeTracking $timeTracking) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());

      $workingDaysPerProject = NULL;
      $projects = $team->getTrueProjects();
      foreach($projects as $project) {
         $nbDays = $timeTracking->getWorkingDaysPerProject($project->id);
         if ((! $team->isSideTasksProject($project->id)) && (! $team->isNoStatsProject($project->id))) {
            $progress = round(100 * $project->getProgress()).'%';
            $progressMgr = round(100 * $project->getProgressMgr()).'%';
         } else {
            $progress = '';
            $progressMgr = '';
         }

         $workingDaysPerProject[] = array(
            'name' => $project->name,
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
   private function getWorkingDaysPerProjectUrl(array $workingDaysPerProject) {
      $formatedValues = NULL;
      $formatedLegends = NULL;
      foreach ($workingDaysPerProject as $workingDays) {
         if (0 != $workingDays['nbDays']) {
            if (NULL != $formatedValues) {
               $formatedValues .= ":"; $formatedLegends .= ":";
            }
            $formatedValues .= $workingDays['nbDays'];
            $formatedLegends .= $workingDays['name'];
         }
      }

      if (NULL != $formatedValues) {
         return Tools::SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues);
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
   private function getResolvedDeviationStats(array $issueList, $withSupport = true) {
      $issueSelection = new IssueSelection("resolved issues");
      $issueSelection->addIssueList($issueList);

      $allDriftMgr = $issueSelection->getDriftMgr();
      $allDrift = $issueSelection->getDrift();

      $deviationGroupsMgr = $issueSelection->getDeviationGroupsMgr(1, $withSupport);
      $deviationGroups = $issueSelection->getDeviationGroups(1, $withSupport);

      $posDeviationGroupMgr = $deviationGroupsMgr['positive'];
      $posDeviationGroup = $deviationGroups['positive'];
      $posDriftMgr = $posDeviationGroupMgr->getDriftMgr();
      $posDrift = $posDeviationGroup->getDrift();
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
   private function getResolvedIssuesInDrift(array $issueList, $isManager=false, $withSupport=true) {
      $resolvedIssuesInDrift = NULL;
      foreach ($issueList as $issue) {
         // TODO: check if issue in team project list ?
         $driftMgrEE = 0;
         if ($isManager) {
            $driftMgrEE = $issue->getDriftMgr($withSupport);
         }
         $driftEE = $issue->getDrift($withSupport);

         if (($isManager && $driftMgrEE > 0) || ($driftEE > 0)) {
            $resolvedIssuesInDrift[] = array(
               "issueURL" => Tools::issueInfoURL($issue->bugId),
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
   private function getCheckWarnings(TimeTracking $timeTracking) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());
      $warnings = NULL;
      foreach($team->getMembers() as $userid => $username) {
         $incompleteDays = $timeTracking->checkCompleteDays($userid, TRUE);
         foreach ($incompleteDays as $date => $value) {
            if ($value < 1) {
               $warnings[] = array(
                  'user' => $username,
                  'date' => date("Y-m-d", $date),
                  'desc' => T_("incomplete").' ('.T_('missing').' '.(1-$value).' '.T_('day').')',
                  'severity' => T_("Error"),
                  'severityColor' => 'color:red'
               );
            } else {
               $warnings[] = array(
                  'user' => $username,
                  'date' => date("Y-m-d", $date),
                  'desc' => T_("inconsistent").' ('.$value.' '.T_('day').')',
                  'severity' => T_("Error"),
                  'severityColor' => 'color:red'
               );
            }
         }

         $missingDays = $timeTracking->checkMissingDays($userid);
         foreach ($missingDays as $date) {
            $warnings[] = array(
               'user' => $username,
               'date' => date("Y-m-d", $date),
               'desc' => T_("not defined."),
               'severity' => T_("Error"),
               'severityColor' => 'color:red'
            );
         }
      }

      return $warnings;
   }

}

// ========== MAIN ===========
ProductivityReportsController::staticInit();
$controller = new ProductivityReportsController('Period Statistics','ProdReports');
$controller->execute();

?>
