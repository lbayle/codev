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
require_once('tools.php');

class TeamActivityReportController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         // are team members allowed to see other member's timeTracking ?
         $teamList = $user->getTeamList();

         if (count($teamList) > 0) {
            // use the teamid set in the form, if not defined (first page call) use session teamid
            if (isset($_POST['teamid'])) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
            } else {
               $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
            }

            $this->smartyHelper->assign('teams',SmartyTools::getSmartyArray($teamList,$teamid));

            $year = Tools::getSecurePOSTIntValue('year', date('Y'));
            $weekid = Tools::getSecurePOSTIntValue('weekid', date('W'));

            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));
            $this->smartyHelper->assign('years', SmartyTools::getYears($year,1));

            $isDetailed = isset($_POST['cb_detailed']) ? TRUE : FALSE;

            $this->smartyHelper->assign('isChecked', $isDetailed);

            if (array_key_exists($teamid,$teamList) || $teamid == 0) {
               // If no team selected, select the first one, but not set the session with it.
               if($teamid == 0) {
                  $teamidList = array_keys($teamList);
                  $teamid = $teamidList[0];
               } else {
                  $_SESSION['teamid'] = $teamid;
               }

               $weekDates = Tools::week_dates($weekid,$year);
               $startTimestamp = $weekDates[1];
               $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

               $this->smartyHelper->assign('weekDetails', $this->getWeekDetails($timeTracking, $isDetailed, $weekDates));

               // ConsistencyCheck
               $consistencyErrors = $this->getConsistencyErrors($timeTracking);
               if(count($consistencyErrors) > 0) {
                  $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                  $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                  $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
               }
            }
         }
      }
   }

   /**
    * @param int $i
    * @param Holidays $holidays
    * @param int[] $weekDates
    * @param int $duration
    * @return mixed[]
    */
   private function getDaysDetails($i, Holidays $holidays, array $weekDates, $duration) {
      $bgColor = NULL;
      $title = NULL;
      if ($i < 6) {
         $h = $holidays->isHoliday($weekDates[$i]);
         if ($h) {
            $bgColor = $h->color;
            //$bgColor = "style='background-color: #".Holidays::$defaultColor.";'";
            $title = $h->description;
         }
      }
      else {
         $bgColor = Holidays::$defaultColor;
      }

      return array(
         "color" => $bgColor,
         "title" => $title,
         "duration" => $duration
      );
   }

   /**
    * @param TimeTracking $timeTracking
    * @param bool $isDetailed
    * @param int[] $weekDates
    * @return mixed[]
    */
   private function getWeekDetails(TimeTracking $timeTracking, $isDetailed, $weekDates) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());

      $weekDetails = array();
      $users = $team->getUsers();
      foreach($users as $user) {
         // if user was working on the project during the timestamp

         if (($user->isTeamDeveloper($timeTracking->getTeamid(), $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp())) ||
            ($user->isTeamManager($timeTracking->getTeamid(), $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp()))) {

            // PERIOD week
            //$thisWeekId=date("W");

            $weekTracks = $timeTracking->getWeekDetails($user->id, !$isDetailed);
            $holidays = Holidays::getInstance();

            $weekJobDetails = array();
            foreach ($weekTracks as $bugid => $jobList) {
               $issue = IssueCache::getInstance()->getIssue($bugid);
               if ($isDetailed) {
                  $formatedJobList = implode(', ', array_keys($jobList));
                  $query = 'SELECT id, name FROM `codev_job_table` WHERE id IN ('.$formatedJobList.');';
                  $result2 = SqlWrapper::getInstance()->sql_query($query);
                  if (!$result2) {
                     continue;
                  }
                  while($row2 = SqlWrapper::getInstance()->sql_fetch_object($result2)) {
                     $jobName = $row2->name;
                     $dayList = $jobList[$row2->id];

                     $daysDetails = array();
                     for ($i = 1; $i <= 7; $i++) {
                        $daysDetails[] = $this->getDaysDetails($i, $holidays, $weekDates, $dayList[$i]);
                     }

                     $weekJobDetails[] = array(
                        "url" => Tools::mantisIssueURL($bugid, NULL, TRUE) . ' ' . Tools::issueInfoURL($bugid) . " / " . $issue->tcId . " : " . $issue->summary,
                        "duration" => $issue->getDuration(),
                        "progress" => round(100 * $issue->getProgress()),
                        "projectName" => $issue->getProjectName(),
                        "targetVersion" => $issue->getTargetVersion(),
                        "jobName" => $jobName,
                        "daysDetails" => $daysDetails
                     );
                  }
               } else {
                  // for each day, concat jobs duration
                  $daysDetails = array();
                  for ($i = 1; $i <= 7; $i++) {
                     $duration = 0;
                     foreach ($jobList as $dayList) {
                        $duration += $dayList[$i];
                     }
                     if($duration == 0) {
                        $duration = "";
                     }
                     $daysDetails[] = $this->getDaysDetails($i, $holidays, $weekDates, $duration);
                  }

                  $weekJobDetails[] = array(
                     "url" => Tools::mantisIssueURL($bugid, NULL, TRUE) . ' ' . Tools::issueInfoURL($bugid) . " / " . $issue->tcId . " : " . $issue->summary,
                     "duration" => $issue->getDuration(),
                     "progress" => round(100 * $issue->getProgress()),
                     "projectName" => $issue->getProjectName(),
                     "targetVersion" => $issue->getTargetVersion(),
                     "daysDetails" => $daysDetails
                  );
               }
            }

            $weekDetails[] = array(
               'name' => $user->getName(),
               'realname' => $user->getRealname(),
               'forecastWorkload' => $user->getForecastWorkload(),
               'weekDates' => array(Tools::formatDate("%A %d %B", $weekDates[1]),Tools::formatDate("%A %d %B", $weekDates[2]),
                  Tools::formatDate("%A %d %B", $weekDates[3]),Tools::formatDate("%A %d %B", $weekDates[4]),
                  Tools::formatDate("%A %d %B", $weekDates[5])),
               'weekEndDates' => array(Tools::formatDate("%A %d %B", $weekDates[6]),Tools::formatDate("%A %d %B", $weekDates[7])),
               'weekJobDetails' => $weekJobDetails
            );
         }
      }
      return $weekDetails;
   }

   /**
    * Get consistency errors
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getConsistencyErrors(TimeTracking $timeTracking) {
      $consistencyErrors = array(); // if null, array_merge fails !

      $cerrList = ConsistencyCheck2::checkIncompleteDays($timeTracking);

      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $consistencyErrors[] = array(
               'date' => date("Y-m-d", $cerr->timestamp),
               'user' => $user->getName(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);
         }
      }

      return $consistencyErrors;
   }

}

// ========== MAIN ===========
TeamActivityReportController::staticInit();
$controller = new TeamActivityReportController('Weekly activities','TimeTracking');
$controller->execute();

?>
