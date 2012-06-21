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

require('super_header.inc.php');

include('../smarty_tools.php');

include_once "issue.class.php";
include_once "project.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
include_once "holidays.class.php";

$logger = Logger::getLogger("team_activity");

function getDaysDetails($i, Holidays $holidays, $weekDates, $duration) {
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

   return array("color" => $bgColor,
      "title" => $title,
      "duration" => $duration
   );
}

function getWeekDetails($teamid, TimeTracking $timeTracking, $isDetailed, $weekDates) {
   global $logger;

   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.realname " .
      "FROM  `codev_team_user_table`, `mantis_user_table` " .
      "WHERE  codev_team_user_table.team_id = $teamid " .
      "AND    codev_team_user_table.user_id = mantis_user_table.id " .
      "ORDER BY mantis_user_table.realname";

   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return NULL;
   }

   $weekDetails = array();
   while ($row = mysql_fetch_object($result)) {
      // if user was working on the project during the timestamp
      $user = UserCache::getInstance()->getUser($row->user_id);

      if (($user->isTeamDeveloper($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp)) ||
         ($user->isTeamManager($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp))) {

         // PERIOD week
         //$thisWeekId=date("W");

         $weekTracks = $timeTracking->getWeekDetails($row->user_id, !$isDetailed);
         $holidays = Holidays::getInstance();

         $weekJobDetails = array();
         foreach ($weekTracks as $bugid => $jobList) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            if ($isDetailed) {
               foreach ($jobList as $jobid => $dayList) {
                  $query = 'SELECT name FROM `codev_job_table` WHERE id='.$jobid.';';
                  $result2 = mysql_query($query);
                  if (!$result2) {
                     $logger->error("Query FAILED: $query");
                     $logger->error(mysql_error());
                     continue;
                  }
                  $jobName = mysql_result($result2, 0);

                  $daysDetails = array();
                  for ($i = 1; $i <= 7; $i++) {
                     $daysDetails[] = getDaysDetails($i, $holidays, $weekDates, $dayList[$i]);
                  }

                  $weekJobDetails[] = array(
                     "url" => mantisIssueURL($bugid, NULL, TRUE) . ' ' . issueInfoURL($bugid) . " / " . $issue->tcId . " : " . $issue->summary,
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
                  foreach ($jobList as $jobid => $dayList) {
                     $duration += $dayList[$i];
                  }
                  if($duration == 0) {
                     $duration = "";
                  }
                  $daysDetails[] = getDaysDetails($i, $holidays, $weekDates, $duration);
               }

               $weekJobDetails[] = array(
                  "url" => mantisIssueURL($bugid, NULL, TRUE) . ' ' . issueInfoURL($bugid) . " / " . $issue->tcId . " : " . $issue->summary,
                  "duration" => $issue->getDuration(),
                  "progress" => round(100 * $issue->getProgress()),
                  "projectName" => $issue->getProjectName(),
                  "targetVersion" => $issue->getTargetVersion(),
                  "daysDetails" => $daysDetails
               );
            }
         }

         $weekDetails[] = array(
            'realname' => $row->realname,
            'forecastWorkload' => $user->getForecastWorkload(),
            'weekDates' => array(formatDate("%A %d %B", $weekDates[1]),formatDate("%A %d %B", $weekDates[2]),
               formatDate("%A %d %B", $weekDates[3]),formatDate("%A %d %B", $weekDates[4]),
               formatDate("%A %d %B", $weekDates[5])),
            'weekEndDates' => array(formatDate("%A %d %B", $weekDates[6]),formatDate("%A %d %B", $weekDates[7])),
            'weekJobDetails' => $weekJobDetails
         );
      }
   }
   return $weekDetails;
}

function displayCheckWarnings(TimeTracking $timeTracking) {
   global $logger;

   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
      "FROM  `codev_team_user_table`, `mantis_user_table` ".
      "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
      "AND    codev_team_user_table.user_id = mantis_user_table.id ".
      "ORDER BY mantis_user_table.username";

   // FIXME AND user is not Observer

   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      return NULL;
   }

   $warnings = array();
   while($row = mysql_fetch_object($result)) {
      $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
      foreach ($incompleteDays as $date => $value) {

         if ($date > time()) {
            continue;
         } // skip dates in the future

         $formatedDate = date("Y-m-d", $date);

         $label = NULL;
         if ($value < 1) {
            $label = T_("incomplete (missing ").(1-$value).T_(" days").")";
         } else {
            $label = T_("inconsistent")." (".($value)." ".T_("days").")";
         }

         $warnings[] = array("username" => $row->username,
            "date" => $formatedDate,
            "label" => $label
         );
      }

      $missingDays = $timeTracking->checkMissingDays($row->user_id);
      foreach ($missingDays as $date) {
         if ($date > time()) {
            continue;
         } // skip dates in the future

         $formatedDate = date("Y-m-d", $date);

         $warnings[] = array("username" => $row->username,
            "date" => $formatedDate,
            "label" => T_("not defined.")
         );
      }
   }

   return $warnings;
}

// ================ MAIN =================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Weekly activities'));

if(isset($_SESSION['userid'])) {
   $user = UserCache::getInstance()->getUser($_SESSION['userid']);
   // are team members allowed to see other member's timeTracking ?
   $teamList = $user->getTeamList();

   if (count($teamList) > 0) {
      // use the teamid set in the form, if not defined (first page call) use session teamid
      if (isset($_POST['teamid'])) {
         $teamid = $_POST['teamid'];
      } else {
         $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      }

      $smartyHelper->assign('teams',getTeams($teamList,$teamid));

      $year = getSecurePOSTIntValue('year', date('Y'));
      $weekid = getSecurePOSTIntValue('weekid', date('W'));

      $smartyHelper->assign('weeks', getWeeks($weekid, $year));
      $smartyHelper->assign('years', getYears($year,1));

      $isDetailed = isset($_POST['cb_detailed']) ? TRUE : FALSE;

      $smartyHelper->assign('isChecked', $isDetailed);

      if (array_key_exists($teamid,$teamList)) {
         $_SESSION['teamid'] = $teamid;

         $weekDates = week_dates($weekid,$year);
         $startTimestamp = $weekDates[1];
         $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $smartyHelper->assign('weekDetails', getWeekDetails($teamid, $timeTracking, $isDetailed, $weekDates));

         $smartyHelper->assign('warnings', displayCheckWarnings($timeTracking));
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
