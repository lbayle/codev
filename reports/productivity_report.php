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
      if(Tools::isConnectedUser()) {

         if (0 != $this->teamid) {
            $weekDates = Tools::week_dates(date('W'),date('Y'));

            $startdate = Tools::getSecurePOSTStringValue('startdate', date("Y-m-d", $weekDates[1]));
            $this->smartyHelper->assign('startDate', $startdate);

            $enddate = Tools::getSecurePOSTStringValue('enddate', date("Y-m-d", $weekDates[5]));
            $this->smartyHelper->assign('endDate', $enddate);

            if ('computeProdReport' == $_POST['action']) {
               $team = TeamCache::getInstance()->getTeam($this->teamid);
               if(count($team->getProjects(false)) > 0) {
                  $startTimestamp = Tools::date2timestamp($startdate);
                  $endTimestamp = Tools::date2timestamp($enddate);
                  $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

                  $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

                  $this->smartyHelper->assign('timeTracking', $timeTracking);

                  $managementDay = $timeTracking->getManagementDays();
                  $prodDays = $timeTracking->getProdDays();
                  $prodDaysSideTasks = $timeTracking->getProdDaysSideTasks(false);
                  if (0 != ($prodDays + $managementDay + ($prodDaysSideTasks - $managementDay))) {
                     $data = array(
                         T_('Projects') => $prodDays,
                         T_('Project Management') => $managementDay,
                         T_('Other SideTasks') => $prodDaysSideTasks - $managementDay
                     );
                     $this->smartyHelper->assign('productionDays_jqplotData', Tools::array2plot($data));
                  }


                  $workingDaysPerJobs = $this->getWorkingDaysPerJob($timeTracking, $this->teamid);
                  $this->smartyHelper->assign('workingDaysPerJob', $workingDaysPerJobs);
                  if($workingDaysPerJobs != NULL) {
                     $data = array();
                     $formatedColors = array();
                     foreach ($workingDaysPerJobs as $workingDays) {
                        if (0 != $workingDays['nbDays']) {
                           $data[$workingDays['name']] = $workingDays['nbDays'];
                           $formatedColors[] = "#".$workingDays['color'];
                        }
                     }

                     if (count($data) > 0) {
                        $this->smartyHelper->assign('workingDaysPerJob_jqplotData', Tools::array2plot($data));
                        $this->smartyHelper->assign('workingDaysPerJob_colors', Tools::array2json($formatedColors));
                     }
                  }

                  $defaultProjectid = Tools::getSecurePOSTIntValue('projectid', 0);
                  $getTeamProjects = SmartyTools::getSmartyArray($this->getTeamProjects($this->teamid),$defaultProjectid);
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
                     $data = ProductivityReportTools::getProjectDetailsChart($projectDetails);
                     if (count($data) > 0) {
                        $this->smartyHelper->assign('projectDetails_jqplotData', Tools::array2plot($data));
                     }
                  }

                  $workingDaysPerProject = $this->getWorkingDaysPerProject($timeTracking);
                  $this->smartyHelper->assign('workingDaysPerProject', $workingDaysPerProject);
                  if($workingDaysPerProject != NULL) {
                     $data = array();
                     foreach ($workingDaysPerProject as $workingDays) {
                        if (0 != $workingDays['nbDays']) {
                           $data[$workingDays['name']] = $workingDays['nbDays'];
                        }
                     }

                     if (count($data) > 0) {
                        $this->smartyHelper->assign('workingDaysPerProject_jqplotData', Tools::array2plot($data));
                     }
                  }

                  // --- BEGIN FDJ SPECIFIC ---

                  $prodRateIndic = new ProductivityRateIndicator();
                  $prodRate = $prodRateIndic->getProductivityRate(
                          array_keys($team->getProjects(FALSE, FALSE, FALSE)),
                          $timeTracking->getStartTimestamp(),
                          $timeTracking->getEndTimestamp());
                  $this->smartyHelper->assign('productivityRateMgr', round($prodRate['MEE'], 2));
                  $this->smartyHelper->assign('productivityRate',    round($prodRate['EE'], 2));

                  // --- END   FDJ SPECIFIC ---

                  $this->smartyHelper->assign('efficiencyRate', round($timeTracking->getEfficiencyRate(), 2));
                  $this->smartyHelper->assign('systemDisponibilityRate', round($timeTracking->getSystemDisponibilityRate(), 3));

                  $timeDriftStats = $timeTracking->getTimeDriftStats();
                  $this->smartyHelper->assign('timeDriftStats', $timeDriftStats);
                  $nbDriftsNeg = 0;
                  if(array_key_exists("nbDriftsNeg", $timeDriftStats)) {
                     $nbDriftsNeg = $timeDriftStats["nbDriftsNeg"];
                  }
                  $nbDriftsPos = 0;
                  if(array_key_exists("nbDriftsPos", $timeDriftStats)) {
                     $nbDriftsPos = $timeDriftStats["nbDriftsPos"];
                  }
                  $nbTasks = $nbDriftsNeg + $nbDriftsPos;
                  $percent = (0 != $nbTasks) ? $nbDriftsNeg * 100 / $nbTasks : 100;
                  $this->smartyHelper->assign('percent', round($percent, 1));

                  // isManager
                  $managedTeamList = $this->session_user->getManagedTeamList();
                  $isManager = array_key_exists($this->teamid, $managedTeamList);
                  $this->smartyHelper->assign('isManager', $isManager);

                  // dirft stats
                  $resolvedIssues = $timeTracking->getResolvedIssues();
                  if (0 != count($resolvedIssues)) {
                     $withSupport = true;
                     $this->smartyHelper->assign('resolvedDeviationStats', $this->getResolvedDeviationStats ($resolvedIssues, $withSupport));
                     $this->smartyHelper->assign('resolvedIssuesInDrift', $this->getResolvedIssuesInDrift($resolvedIssues, $isManager));
                  }

                  // dirft stats extRefOnly
                  if (0 != count($resolvedIssues)) {
                     $iselResolvedIssues = new IssueSelection();
                     $iselResolvedIssues->addIssueList($resolvedIssues);
                     $extIdFilter = new IssueExtIdFilter('extIdFilter');
                     $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_with_extRef);
                     $outputList2 = $extIdFilter->execute($iselResolvedIssues);
                     if (empty($outputList2)) {
                        #echo "noExtRef not found !<br>";
                        return array();
                     }
                     $iselResolvedIssuesExtRefOnly = $outputList2[IssueExtIdFilter::tag_with_extRef];
                     $resolvedIssuesExtRefOnly = $iselResolvedIssuesExtRefOnly->getIssueList();
                     if (0 != count($resolvedIssuesExtRefOnly)) {
                        $withSupport = true;
                        $this->smartyHelper->assign('resolvedDeviationStatsExtRefOnly', $this->getResolvedDeviationStats ($resolvedIssuesExtRefOnly, $withSupport));
                        $this->smartyHelper->assign('resolvedIssuesInDriftExtRefOnly', $this->getResolvedIssuesInDrift($resolvedIssuesExtRefOnly, $isManager));
                     }
                  }

                  $this->smartyHelper->assign('reopenedBugsRate_nbResolved', count($timeTracking->getResolvedIssues(TRUE, TRUE))); // extRefOnly=true, withReopened=true
                  $this->smartyHelper->assign('reopenedBugsRate_nbReopened', count($timeTracking->getReopened()));
                  $this->smartyHelper->assign('reopenedBugsRate', round($timeTracking->getReopenedRateResolved() * 100, 1));
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
         $titleAttr = array(
               T_('Project') => $issue->getProjectName(),
               T_('Summary') => $issue->getSummary(),
         );
         $formatedTasks[] = Tools::issueInfoURL($issue->getId(), $titleAttr);
      }
      return $formatedTasks;
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
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getWorkingDaysPerProject(TimeTracking $timeTracking) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());

      $workingDaysPerProject = NULL;
      $projects = $team->getTrueProjects();
      foreach($projects as $project) {
         $nbDays = $timeTracking->getWorkingDaysPerProject($project->getId());

         // do not display disabled projects, EXCEPT if some timetracks exist
         if ($project->isEnabled() || ($nbDays > 0)) {
            $workingDaysPerProject[] = array(
               'name' => $project->getName(),
               'nbDays' => $nbDays,
            );
         }
      }

      return $workingDaysPerProject;
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
         "type" => T_("Tasks in drift"),
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
               "issueURL" => Tools::issueInfoURL($issue->getId()),
               "extRef" => $issue->getTcId(),
               "projectName" => $issue->getProjectName(),
               "driftMgrEE" => $driftMgrEE,
               "driftEE" => $driftEE,
               "currentStatusName" => $issue->getCurrentStatusName(),
               "summary" => $issue->getSummary()
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
