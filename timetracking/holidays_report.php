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

require('include/display.inc.php');

require('../smarty_tools.php');

include_once("classes/sqlwrapper.class.php");
include_once("classes/user_cache.class.php");
include_once("classes/holidays.class.php");

$logger = Logger::getLogger("holidays_report");

/**
 * Get teams
 * @param int $teamid The selected team
 * @return mixed[int]
 */
function getTeamList($teamid) {
   $query = "SELECT id, name FROM `codev_team_table` ORDER BY name";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $teams = array();
   while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $teams[$row->id] = array(
         "id" => $row->id,
         "name" => $row->name,
         "selected" => $row->id == $teamid
      );
   }
   return $teams;
}

/**
 * Get days of a month
 * @param int $nbDaysInMonth The number of days in a month
 * @param int $month The month
 * @param int $year The year
 * @return mixed[int]
 */
function getDays($nbDaysInMonth, $month, $year) {
   $today = date("d-m-Y");
   $days = array();
   for ($i = 1; $i <= $nbDaysInMonth; $i++) {
      $curDate = mktime(0, 0, 0, $month, $i, $year);
      if ($today == date("d-m-Y", $curDate)) {
         $title = 'today';
      } else {
         $title = formatDate("%A", $curDate);
      }
      $days[sprintf("%02d", $i)] = array(
         'title' => $title,
         'selected' => $today == date("d-m-Y", $curDate)
      );
   }
   return $days;
}

/**
 * Get days for each users
 * @param int $month The month
 * @param int $year The year
 * @param int $teamid The team
 * @param bool $isExternalTasks True if external tasks wanted, else false
 * @param int $nbDaysInMonth The number of days in a month
 * @return mixed[string]
 */
function getDaysUsers($month, $year, $teamid, $isExternalTasks = FALSE, $nbDaysInMonth) {
   // USER
   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username, mantis_user_table.realname ".
            "FROM  `codev_team_user_table`, `mantis_user_table` ".
            "WHERE  codev_team_user_table.team_id = $teamid ".
            "AND    codev_team_user_table.user_id = mantis_user_table.id ".
            "ORDER BY mantis_user_table.username";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $holidays = Holidays::getInstance();

   $green = "A8FFBD";
   $green2 = "75FFDA";
   $yellow = "F8FFA8";

   $startT = mktime(0, 0, 0, $month, 1, $year);
   $endT = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

   $users = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $user1 = UserCache::getInstance()->getUser($row->user_id);

      // if user was working on the project within the timestamp
      if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
         ($user1->isTeamManager($teamid, $startT, $endT))) {

         $daysOf = $user1->getDaysOfInPeriod($startT, $endT);

         $astreintes = $user1->getAstreintesInMonth($startT, $endT);

         if ($isExternalTasks) {
            $externalTasks = $user1->getExternalTasksInPeriod($startT, $endT);
         } else {
            $externalTasks = array();
         }

         $days = array();
         for ($i = 1; $i <= $nbDaysInMonth; $i++) {
            $timestamp = mktime(0,0,0,$month,$i,$year);

            if (isset($externalTasks[$timestamp]) && (NULL != $externalTasks[$timestamp])) {
               $days[$i] = array(
                  "color" => $green2,
                  "align" => true,
                  "value" => $externalTasks[$timestamp],
                  "title" => T_("ExternalTask"),
               );
            } elseif (isset($astreintes[$i]) && (NULL != $astreintes[$i])) {
               $days[$i] = array(
                  "color" => $yellow,
                  "align" => true,
                  "value" => $daysOf[$timestamp],
                  "title" => T_("OnDuty"),
               );
            } elseif (isset($daysOf[$timestamp]) && (NULL != $daysOf[$timestamp])) {
               $days[$i] = array(
                  "color" => $green,
                  "align" => true,
                  "value" => $daysOf[$timestamp]
               );
            } else {
               // If weekend or holiday, display gray
               $timestamp = mktime(0, 0, 0, $month, $i, $year);
               $h = $holidays->isHoliday($timestamp);
               if (NULL != $h) {
                  $days[$i] = array(
                     "color" => $h->color,
                     "title" => $h->description,
                  );
               } else {
                  $days[$i] = array();
               }
            }

         }
         $users[$row->user_id] = array(
            'realname' => $row->realname,
            'username' => $row->username,
            'days' => $days
         );
      }
   }
   return $users;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Holidays Report');

if (isset($_SESSION['userid'])) {
   $year = getSecurePOSTIntValue('year',date('Y'));

   $teamid = 0;
   if(isset($_POST['teamid'])) {
      $teamid = getSecurePOSTIntValue('teamid',0);
      $_SESSION['teamid'] = $teamid;
   } elseif(isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }

   // 'teamid' is used because it's not possible to make a difference
   // between an unchecked checkBox and an unset checkbox variable
   if (isset($_POST['teamid'])) {
      $isExternalTasks = isset($_POST['cb_extTasks']) ? TRUE : FALSE;
   } else {
      $isExternalTasks = TRUE; // default
   }

   $teams = getTeamList($teamid);
   $smartyHelper->assign('teams', $teams);
   $smartyHelper->assign('years', getYears($year,2));
   $smartyHelper->assign('isExternalTasks', $isExternalTasks);

   if($teamid == 0 && count(teams) > 0) {
      $teamids = array_keys($teams);
      $teamid = $teamids[0];
   }

   $months = array();
   for ($i = 1; $i <= 12; $i++) {
      $monthTimestamp = mktime(0, 0, 0, $i, 1, $year);
      $monthFormated = formatDate("%B %Y", $monthTimestamp);
      $nbDaysInMonth = date("t", $monthTimestamp);
      $months[$i] = array(
         "name" => $monthFormated,
         "days" => getDays($nbDaysInMonth, $i, $year),
         "users" => getDaysUsers($i, $year, $teamid, $isExternalTasks, $nbDaysInMonth)
      );
   }
   $smartyHelper->assign('months', $months);
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
