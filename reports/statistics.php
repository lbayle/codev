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
      if(isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $teamList = $session_user->getTeamList();
         if (count($teamList) > 0) {
            if(isset($_POST['teamid']) && array_key_exists($_POST['teamid'],$teamList)) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
               $_SESSION['teamid'] = $_POST['teamid'];
            }
            else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
               $teamid = $_SESSION['teamid'];
            }
            else {
               $teamsid = array_keys($teamList);
               $teamid = $teamsid[0];
            }

            if($teamid != 0) {
               // if 'support' is set in the URL, display graphs for 'with/without Support'
               $displayNoSupport  = isset($_GET['support']) ? TRUE : FALSE;
               $this->smartyHelper->assign('displayNoSupport', $displayNoSupport);

               $team = TeamCache::getInstance()->getTeam($teamid);
               $min_year = date("Y", $team->date);
               $year = isset($_POST['year']) && $_POST['year'] > $min_year ? $_POST['year'] : $min_year;

               $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));
               $this->smartyHelper->assign('years', SmartyTools::getYearsToNow($min_year, $year));

               if (isset($_POST['teamid'])) {
                  $month = ($year == $min_year) ? date("m", $team->date) : 1;
                  $day = ($year == $min_year) ? date("d", $team->date) : 1;

                  if(count($team->getProjects(FALSE)) > 0) {
                     $timeTrackingTable = $this->createTimeTrackingList($day, $month, $year, $teamid);

                     $this->generateSubmittedResolvedGraph($timeTrackingTable);

                     $this->generateTimeDriftGraph($timeTrackingTable);

                     $this->generateResolvedDriftGraph($timeTrackingTable, $displayNoSupport);

                     $this->generateEfficiencyGraph($timeTrackingTable);

                     $this->generateReopenedRateGraph($timeTrackingTable);

                     $this->generateDevelopersWorkloadGraph($timeTrackingTable);
                  } else {
                     $this->smartyHelper->assign('error', 'No projects in this team');
                  }
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

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateSubmittedResolvedGraph(array $timeTrackingTable) {
      $formattedSubmittedList = array();
      $formattedResolvedList = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         $formattedSubmittedList[$startTimestamp] = $timeTracking->getSubmitted(); // returns bug_id !
         $formattedResolvedList[$startTimestamp] = count($timeTracking->getResolvedIssues()); // returns Issue instances !
      }

      $valuesOne = array();
      $valuesTwo = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-%d", $date)] = $formattedSubmittedList[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-%d", $date)] = $formattedResolvedList[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "nbSubmitted" => $formattedSubmittedList[$date],
            "nbResolvedIssues" => $formattedResolvedList[$date]
         );
      }
      $values = array($valuesOne,$valuesTwo);

      $this->smartyHelper->assign('submittedResolved_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($valuesOne);
      $this->smartyHelper->assign('submittedResolved_plotMinDate', $start);
      $this->smartyHelper->assign('submittedResolved_plotMaxDate', $end);
      $this->smartyHelper->assign('submittedResolved_Legend', $legend);
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
         $nbTasks = $timeDriftStats["nbDriftsNeg"] + $timeDriftStats["nbDriftsPos"];
         $formattedTimetracks[$startTimestamp] = (0 != $nbTasks) ? $timeDriftStats["nbDriftsNeg"] * 100 / $nbTasks : 100;
      }

      $values = array();
      $legend = array();
      foreach ($formattedTimetracks as $date => $value) {
         $values[Tools::formatDate("%Y-%m-%d", $date)] = $value;
         $legend[Tools::formatDate("%B %Y", $date)] = round($value, 1);
      }

      $this->smartyHelper->assign('timeDrift_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($values);
      $this->smartyHelper->assign('timeDrift_plotMinDate', $start);
      $this->smartyHelper->assign('timeDrift_plotMaxDate', $end);
      $this->smartyHelper->assign('timeDrift_Legend', $legend);
   }

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @param bool $displayNoSupport
    * @return string
    */
   private function generateResolvedDriftGraph(array $timeTrackingTable, $displayNoSupport = FALSE) {
      $val1 = array();
      $val2 = array();
      $val3 = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         // REM: the 'normal' drifts DO include support
         $formattedSubmittedList[$startTimestamp] = $timeTracking->getSubmitted(); // returns bug_id !
         $formattedResolvedList[$startTimestamp] = count($timeTracking->getResolvedIssues()); // returns Issue instances !

         $driftStats_new1 = $timeTracking->getResolvedDriftStats(TRUE);
         $val1[$startTimestamp] = $driftStats_new1["totalDriftETA"] ? $driftStats_new1["totalDriftETA"] : 0;
         $val2[$startTimestamp] = $driftStats_new1["totalDrift"] ? $driftStats_new1["totalDrift"] : 0;
         if ($displayNoSupport) {
            $driftStats_noSupport1 = $timeTracking->getResolvedDriftStats(FALSE);
            $val3[$startTimestamp] = $driftStats_noSupport1["totalDrift"] ? $driftStats_noSupport1["totalDrift"] : 0;
         }
      }

      $valuesOne = array();
      $valuesTwo = array();
      $valuesThree = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-%d", $date)] = $val1[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-%d", $date)] = $val2[$date];
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

      $this->smartyHelper->assign('resolvedDrift_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($valuesOne);
      $this->smartyHelper->assign('resolvedDrift_plotMinDate', $start);
      $this->smartyHelper->assign('resolvedDrift_plotMaxDate', $end);
      $this->smartyHelper->assign('resolvedDrift_Legend', $legend);
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
         $valuesOne[Tools::formatDate("%Y-%m-%d", $date)] = $values1[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-%d", $date)] = $values2[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "efficiencyRate" => round($values1[$date],2),
            "systemDisponibilityRate" => round($values2[$date],2)
         );
      }
      $values = array($valuesOne,$valuesTwo);

      $this->smartyHelper->assign('efficiency_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($valuesOne);
      $this->smartyHelper->assign('efficiency_plotMinDate', $start);
      $this->smartyHelper->assign('efficiency_plotMaxDate', $end);
      $this->smartyHelper->assign('efficiency_Legend', $legend);
   }

   /**
    * @param TimeTracking[] $timeTrackingTable
    * @return string
    */
   private function generateReopenedRateGraph(array $timeTrackingTable) {
      $val1 = array();
      $val2 = array();
      $val3 = array();
      foreach ($timeTrackingTable as $startTimestamp => $timeTracking) {
         $val1[$startTimestamp] = $timeTracking->getReopenedRate() * 100; // x100 to get a percentage
         $val2[$startTimestamp] = $timeTracking->getReopenedRateResolved() * 100; // x100 to get a percentage;
         $val3[$startTimestamp] = count($timeTracking->getReopened());
      }

      $valuesOne = array();
      $valuesTwo = array();
      $valuesThree = array();
      $legend = array();
      foreach ($timeTrackingTable as $date => $timeTracking) {
         $valuesOne[Tools::formatDate("%Y-%m-%d", $date)] = $val1[$date];
         $valuesTwo[Tools::formatDate("%Y-%m-%d", $date)] = $val2[$date];
         $valuesThree[Tools::formatDate("%Y-%m-%d", $date)] = $val3[$date];
         $legend[Tools::formatDate("%B %Y", $date)] = array(
            "reopenedRate" => round($val1[$date], 1),
            "reopenedRateResolved" => round($val2[$date], 1),
            "reopened" => count($val3[$date])
         );
      }
      $values = array($valuesOne,$valuesTwo);

      $this->smartyHelper->assign('reopenedRate_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($valuesOne);
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
         $values[Tools::formatDate("%Y-%m-%d", $date)] = $value;
         $legend[Tools::formatDate("%B %Y", $date)] = round($value, 1);
      }

      $this->smartyHelper->assign('workload_jqplotData', Tools::array2plot($values));
      list($start, $end) = Tools::getStartEndKeys($values);
      $this->smartyHelper->assign('workload_plotMinDate', $start);
      $this->smartyHelper->assign('workload_plotMaxDate', $end);
      $this->smartyHelper->assign('workload_Legend', $legend);
   }

}

// ========== MAIN ===========
StatisticsController::staticInit();
$controller = new StatisticsController('Statistics','ProdReports');
$controller->execute();

?>
