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

class StatisticsController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            // if 'support' is set in the URL, display graphs for 'with/without Support'
            $displayNoSupport  = isset($_GET['support']) ? TRUE : FALSE;
            $this->smartyHelper->assign('displayNoSupport', $displayNoSupport);

            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $min_year = date("Y", $team->getDate());
            $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;

            $this->smartyHelper->assign('years', SmartyTools::getYearsToNow($min_year, $year));
            
            //plugins
            $this->smartyHelper->assign('statusHistoryIndicatorFile', (new StatusHistoryIndicator())->getSmartyFilename());
            $this->smartyHelper->assign('effortEstimReliabilityIndicatorFile', (new EffortEstimReliabilityIndicator())->getSmartyFilename());

            if ('computeTeamHistory' == $_POST['action']) {

               $month = ($year == $min_year) ? date("m", $team->getDate()) : 1;
               $day = ($year == $min_year) ? date("d", $team->getDate()) : 1;

               if(count($team->getProjects(FALSE)) > 0) {
                  $timeTrackingTable = $this->createTimeTrackingList($day, $month, $year, $this->teamid);

                  $this->generateSubmittedResolvedGraph($timeTrackingTable, FALSE);

                  $this->generateSubmittedResolvedGraph($timeTrackingTable, TRUE); // ExtRefOnly

                  $this->generateTimeDriftGraph($timeTrackingTable);

                  $this->generateResolvedDriftGraph($timeTrackingTable, $displayNoSupport, FALSE);

                  $this->generateResolvedDriftGraph($timeTrackingTable, $displayNoSupport, TRUE); // ExtRefOnly

                  $this->generateEfficiencyGraph($timeTrackingTable);

                  $this->generateReopenedRateGraph($timeTrackingTable);

                  $this->generateDevelopersWorkloadGraph($timeTrackingTable);

  // --- BEGIN FDJ SPECIFIC ---
                     $this->generateEffortEstimReliabilityGraph($this->teamid, $timeTrackingTable);
  // --- END FDJ SPECIFIC ---

                  #$this->generateStatusHistoryGraph($teamid);
               } else {
                  $this->smartyHelper->assign('error', T_('No projects in this team'));
               }
            }
         }
      }
   }

   /**
    * @param int $start_day
    * @param int $start_month
    * @param int $start_year
    * @param int $teamid
    * @return TimeTracking[]
    */
   private function createTimeTrackingList($start_day, $start_month, $start_year, $teamid) {
      $timeTrackingTable = array();

      $day = $start_day;
      $now = time();
      for ($y = $start_year; $y <= date('Y'); $y++) {
         for ($month=$start_month ; $month <= 12 ; $month++) {
            $startTimestamp = mktime(0, 0, 0, $month, $day, $y);
            $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
            $endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);

            if ($startTimestamp > $now) {
               break;
            }

            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
            $timeTrackingTable[$startTimestamp] = $timeTracking;

            $day = 1;
         }
         $start_month = 1;
      }
      return $timeTrackingTable;
   }

   private function generateStatusHistoryGraph($teamid) {
      $team = TeamCache::getInstance()->getTeam($teamid);

      $issueList = $team->getTeamIssueList(true, false);

      $issueSel = new IssueSelection('Team '.$team->getName().' issues');
      $issueSel->addIssueList($issueList);

      $startTimestamp = $team->getDate();
      $endTimestamp =  time();

      $params = array(
         'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
         'endTimestamp' => $endTimestamp,
         'interval' => 10
      );

      $statusHistoryIndicator = new StatusHistoryIndicator();
      $statusHistoryIndicator->execute($issueSel, $params);
      $smartyobj = $statusHistoryIndicator->getSmartyObject();
      foreach ($smartyobj as $smartyKey => $smartyVariable) {
         $this->smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }


   /**
    *
    * @param TimeTracking[] $timeTrackingTable
    * @param type $ExtRefOnly if TRUE, exclude issues having no ExtId.
    */
   private function generateSubmittedResolvedGraph(array $timeTrackingTable, $ExtRefOnly=FALSE) {
      $formattedSubmittedList = array();
      $formattedResolvedList = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         $formattedSubmittedList[$startTimestamp] = $timeTracking->getSubmitted($ExtRefOnly); // returns bug_id !
         $formattedResolvedList[$startTimestamp] = count($timeTracking->getResolvedIssues($ExtRefOnly)); // returns Issue instances !
      }

      $valuesOne = array();
      $valuesTwo = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-01", $date)] = $formattedSubmittedList[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-01", $date)] = $formattedResolvedList[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "nbSubmitted" => $formattedSubmittedList[$date],
            "nbResolvedIssues" => $formattedResolvedList[$date]
         );
      }
      $values = array($valuesOne,$valuesTwo);

      $smartyPrefix = 'submittedResolved';
      if ($ExtRefOnly) { $smartyPrefix .= 'ExtRefOnly'; }

      $this->smartyHelper->assign($smartyPrefix.'_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($valuesOne);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign($smartyPrefix.'_plotMinDate', $start);
      $this->smartyHelper->assign($smartyPrefix.'_plotMaxDate', $end);
      $this->smartyHelper->assign($smartyPrefix.'_Legend', $legend);
   }

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateTimeDriftGraph(array $timeTrackingTable) {
      $formattedTimetracks = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         $timeDriftStats = $timeTracking->getTimeDriftStats();
         $nbDriftsNeg = 0;
         if(array_key_exists("nbDriftsNeg", $timeDriftStats)) {
            $nbDriftsNeg = $timeDriftStats["nbDriftsNeg"];
         }
         $nbDriftsPos = 0;
         if(array_key_exists("nbDriftsPos", $timeDriftStats)) {
            $nbDriftsPos = $timeDriftStats["nbDriftsPos"];
         }
         $nbTasks = $nbDriftsNeg + $nbDriftsPos;
         $formattedTimetracks[$startTimestamp] = (0 != $nbTasks) ? $nbDriftsNeg * 100 / $nbTasks : 100;
      }

      $values = array();
      $legend = array();
      foreach ($formattedTimetracks as $date => $value) {
         $values[Tools::formatDate("%Y-%m-01", $date)] = $value;
         $legend[Tools::formatDate("%B %Y", $date)] = round($value, 1);
      }

      $this->smartyHelper->assign('timeDrift_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($values);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign('timeDrift_plotMinDate', $start);
      $this->smartyHelper->assign('timeDrift_plotMaxDate', $end);
      $this->smartyHelper->assign('timeDrift_Legend', $legend);
   }

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @param bool $displayNoSupport
    * @return string
    */
   private function generateResolvedDriftGraph(array $timeTrackingTable, $displayNoSupport = FALSE, $extRefOnly=FALSE) {
      $val1 = array();
      $val2 = array();
      $val3 = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         #$formattedSubmittedList[$startTimestamp] = $timeTracking->getSubmitted($extRefOnly); // returns bug_id !
         #$formattedResolvedList[$startTimestamp] = count($timeTracking->getResolvedIssues($extRefOnly)); // returns Issue instances !

         $driftStats_new1 = $timeTracking->getResolvedDriftStats(TRUE, $extRefOnly);
         $val1[$startTimestamp] = array_key_exists("totalDriftETA", $driftStats_new1) ? $driftStats_new1["totalDriftETA"] : 0;
         $val2[$startTimestamp] = array_key_exists("totalDrift", $driftStats_new1) ? $driftStats_new1["totalDrift"] : 0;
         if ($displayNoSupport) {
            $driftStats_noSupport1 = $timeTracking->getResolvedDriftStats(FALSE, $extRefOnly);
            $val3[$startTimestamp] = $driftStats_noSupport1["totalDrift"] ? $driftStats_noSupport1["totalDrift"] : 0;
         }
      }

      $valuesOne = array();
      $valuesTwo = array();
      $valuesThree = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-01", $date)] = $val1[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-01", $date)] = $val2[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "totalDriftETA" => round($val1[$date],2),
            "totalDrift" => round($val2[$date],2),
         );
         if($displayNoSupport) {
            $valuesThree[Tools::formatDate("%Y-%m-%d", $date)] = round($val3[$date],2);
            $legend[Tools::formatDate("%B %Y", $date)]["totalDriftWithoutSupport"] = $val3[$date];
         }
      }
      $values = array($valuesOne,$valuesTwo,$valuesThree);

      $smartyPrefix = 'resolvedDrift';
      if ($extRefOnly) { $smartyPrefix .= 'ExtRefOnly'; }

      $this->smartyHelper->assign($smartyPrefix.'_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($valuesOne);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign($smartyPrefix.'_plotMinDate', $start);
      $this->smartyHelper->assign($smartyPrefix.'_plotMaxDate', $end);
      $this->smartyHelper->assign($smartyPrefix.'_Legend', $legend);
   }

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateEfficiencyGraph(array $timeTrackingTable) {
      $values1 = array();
      $values2 = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         $values1[$startTimestamp] = $timeTracking->getEfficiencyRate();
         $values2[$startTimestamp] = $timeTracking->getSystemDisponibilityRate();
      }

      $valuesOne = array();
      $valuesTwo = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-01", $date)] = $values1[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-01", $date)] = $values2[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "efficiencyRate" => round($values1[$date],2),
            "systemDisponibilityRate" => round($values2[$date],2)
         );
      }
      $values = array($valuesOne,$valuesTwo);

      $this->smartyHelper->assign('efficiency_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($valuesOne);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign('efficiency_plotMinDate', $start);
      $this->smartyHelper->assign('efficiency_plotMaxDate', $end);
      $this->smartyHelper->assign('efficiency_Legend', $legend);
   }

   /**
    *
    * Note: internal tasks (tasks having no ExternalReference) NOT INCLUDED
    *
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateReopenedRateGraph(array $timeTrackingTable) {
      $val2 = array();
      $val3 = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         $val2[$startTimestamp] = $timeTracking->getReopenedRateResolved() * 100; // x100 to get a percentage;

         $nbReopened = count($timeTracking->getReopened());
         $val3[$startTimestamp] = $nbReopened;
      }
      $valuesTwo = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesTwo[Tools::formatDate("%Y-%m-01", $date)] = $val2[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "reopenedRateResolved" => round($val2[$date], 1),
            "reopened" => $val3[$date]
         );
      }
      $values = array($valuesTwo);

      $this->smartyHelper->assign('reopenedRate_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($valuesTwo);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign('reopenedRate_plotMinDate', $start);
      $this->smartyHelper->assign('reopenedRate_plotMaxDate', $end);
      $this->smartyHelper->assign('reopenedRate_Legend', $legend);
   }

   /**
    * Display 'Developers Workload'
    * nb of days.: (holidays & externalTasks not included, developers only)
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateDevelopersWorkloadGraph($timeTrackingTable) {
      $formattedTimetracks = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         $formattedTimetracks[$startTimestamp] = $timeTracking->getAvailableWorkload();
      }

      $values = array();
      $legend = array();
      foreach ($formattedTimetracks as $date => $value) {
         $values[Tools::formatDate("%Y-%m-01", $date)] = $value;
         $legend[Tools::formatDate("%B %Y", $date)] = round($value, 1);
      }

      $this->smartyHelper->assign('workload_jqplotData', Tools::array2plot($values));
      $timestamp = Tools::getStartEndKeys($values);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));
      $this->smartyHelper->assign('workload_plotMinDate', $start);
      $this->smartyHelper->assign('workload_plotMaxDate', $end);
      $this->smartyHelper->assign('workload_Legend', $legend);
   }

  // --- BEGIN FDJ SPECIFIC ---
   private function generateEffortEstimReliabilityGraph($teamid, $timeTrackingTable) {

      $prodRateIndic = new EffortEstimReliabilityIndicator();
      $params = array(
          'teamid' => $teamid,
          'timeTrackingTable' => $timeTrackingTable);
      $prodRateIndic->execute(new IssueSelection('FAKE_UNUSED'), $params);

      $smartyObj = $prodRateIndic->getSmartyObject();
      foreach ($smartyObj as $smartyKey => $smartyVariable) {
         $this->smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }
  // --- END FDJ SPECIFIC ---

}

// ========== MAIN ===========
StatisticsController::staticInit();
$controller = new StatisticsController('../', 'History','ProdReports');
$controller->execute();

?>
