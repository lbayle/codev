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

include_once('classes/holidays.class.php');
include_once('classes/team.class.php');
include_once('classes/team_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("holidays_report");

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
         $title = Tools::formatDate("%A", $curDate);
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
 * @param User[] $users The users (User[id])
 * @param int $nbDaysInMonth The number of days in a month
 * @param bool $isExternalTasks True if external tasks wanted, else false
 * @return mixed[string]
 */
function getDaysUsers($month, $year, $teamid, array $users, $nbDaysInMonth, $isExternalTasks = FALSE) {
   $holidays = Holidays::getInstance();

   $startT = mktime(0, 0, 0, $month, 1, $year);
   $endT = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

   $smartyUsers = array();
   foreach($users as $id => $user) {
      // if user was working on the project within the timestamp
      if (($user->isTeamDeveloper($teamid, $startT, $endT)) ||
         ($user->isTeamManager($teamid, $startT, $endT))) {

         $daysOf = $user->getDaysOfInPeriod($startT, $endT);

         $astreintes = $user->getAstreintesInMonth($startT, $endT);

         $externalTasks = $user->getExternalTasksInPeriod($startT, $endT);

         $days = array();
         for ($i = 1; $i <= $nbDaysInMonth; $i++) {
            $timestamp = mktime(0,0,0,$month,$i,$year);

            if (isset($externalTasks[$timestamp]) && (NULL != $externalTasks[$timestamp])) {

               if ('Inactivity' == $externalTasks[$timestamp]['type']) {
                  $days[$i] = array(
                     "color" => $externalTasks[$timestamp]['color'],
                     "align" => true,
                     "title" => T_('Inactivity'),
                     "value" => $externalTasks[$timestamp]['duration'],
                  );
               } elseif ($isExternalTasks) {
                  $days[$i] = array(
                     "color" => $externalTasks[$timestamp]['color'],
                     "align" => true,
                     "title" => $externalTasks[$timestamp]['title'],
                     "value" => $externalTasks[$timestamp]['duration'],
                  );
               }
            } elseif (isset($astreintes[$timestamp]) && (NULL != $astreintes[$timestamp])) {
               $days[$i] = array(
                  "color" => $astreintes[$timestamp]['color'],
                  "align" => true,
                  "value" => $astreintes[$timestamp]['duration'],
                  "title" => T_($astreintes[$timestamp]['type']),
               );
            } elseif (isset($daysOf[$timestamp]) && (NULL != $daysOf[$timestamp])) {
               $days[$i] = array(
                  "color" => $daysOf[$timestamp]['color'],
                  "align" => true,
                  "title" => $astreintes[$timestamp]['title'],
                  "value" => $daysOf[$timestamp]['duration']
               );
            }

            if(!isset($days[$i]) ) {
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
         $smartyUsers[$user->id] = array(
            'realname' => $user->getRealname(),
            'username' => $user->getName(),
            'days' => $days
         );
      }
   }
   return $smartyUsers;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Holidays Report');

if (isset($_SESSION['userid'])) {
   $year = Tools::getSecurePOSTIntValue('year',date('Y'));

   $teamid = 0;
   if(isset($_POST['teamid'])) {
      $teamid = Tools::getSecurePOSTIntValue('teamid',0);
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

   $teams = SmartyTools::getSmartyArray(Team::getTeams(),$teamid);
   $smartyHelper->assign('teams', $teams);
   $smartyHelper->assign('years', SmartyTools::getYears($year,2));
   $smartyHelper->assign('isExternalTasks', $isExternalTasks);

   if($teamid == 0 && count($teams) > 0) {
      $teamids = array_keys($teams);
      $teamid = $teamids[0];
   }

   $team = TeamCache::getInstance()->getTeam($teamid);
   $users = $team->getUsers();

   $months = array();
   for ($i = 1; $i <= 12; $i++) {
      $monthTimestamp = mktime(0, 0, 0, $i, 1, $year);
      $nbDaysInMonth = date("t", $monthTimestamp);
      $months[$i] = array(
         "name" => Tools::formatDate("%B %Y", $monthTimestamp),
         "days" => getDays($nbDaysInMonth, $i, $year),
         "users" => getDaysUsers($i, $year, $teamid, $users, $nbDaysInMonth, $isExternalTasks)
      );
   }
   $smartyHelper->assign('months', $months);
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
