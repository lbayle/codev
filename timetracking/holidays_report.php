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

class HolidaysReportController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
            return;
        }

         $year = Tools::getSecurePOSTIntValue('year',date('Y'));

         $displayed_teamid = 0;
         if(isset($_POST['displayed_teamid'])) {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid',0);
         } else {
            $displayed_teamid = $this->teamid;
         }

         // --- Filters
         $filtersStr = Tools::getSecurePOSTStringValue('checkedFilters', '');
         if (!empty($filtersStr)) {
           $filters = Tools::doubleExplode(':', ',', $filtersStr);
         } else {
            $filters = array(
                'isExternalTasks' => 1,
                'isSidetasksInactivity' => 1,
                );
         }

         $filterInfo = array();
         $filterInfo[] = array(
               'filterId' => 'isExternalTasks',
               'filterName' => T_('External Tasks'),
               'isChecked' => $filters['isExternalTasks'],
            );
         $filterInfo[] = array(
               'filterId' => 'isSidetasksInactivity',
               'filterName' => T_('Sidetasks Inactivity'),
               'isChecked' => $filters['isSidetasksInactivity'],
            );
         $this->smartyHelper->assign('filterInfo', $filterInfo);
         $this->smartyHelper->assign('checkedFilters', $filtersStr);
         
         // ---
         $teams = SmartyTools::getSmartyArray($this->teamList,$displayed_teamid);
         #$teams = SmartyTools::getSmartyArray(Team::getTeams(),$displayed_teamid);
         $this->smartyHelper->assign('availableTeams', $teams);
         $this->smartyHelper->assign('years', SmartyTools::getYears($year,2));

         if($displayed_teamid == 0 && count($teams) > 0) {
            $teamids = array_keys($teams);
            $displayed_teamid = $teamids[0];
         }

         $team = TeamCache::getInstance()->getTeam($displayed_teamid);
         $users = $team->getUsers();

         $months = array();
         for ($i = 1; $i <= 12; $i++) {
            $monthTimestamp = mktime(0, 0, 0, $i, 1, $year);
            $nbDaysInMonth = date("t", $monthTimestamp);
            $endMonthTimestamp = mktime(0, 0, 0, $i, $nbDaysInMonth, $year);
            $months[$i] = array(
               "name" => Tools::formatDate("%B %Y", $monthTimestamp),
               "idcaption" => Tools::formatDate("%B", $monthTimestamp),
               "days" => $this->getDays($nbDaysInMonth, $i, $year),
               "users" => $this->getDaysUsers($i, $year, $displayed_teamid, $users, $nbDaysInMonth, $filters),
               "workdays" => Holidays::getInstance()->getWorkdays($monthTimestamp, $endMonthTimestamp),
               "filename_csv" => date("Ym", $monthTimestamp).'_'.str_replace(' ', '_', $team->getName()).'_holidays.csv',
            );
         }
         $this->smartyHelper->assign('months', $months);
      }
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
            $title = T_('today');
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
   function getDaysUsers($month, $year, $teamid, array $users, $nbDaysInMonth, $filters) {
      $holidays = Holidays::getInstance();
      
      $isExternalTasks = $filters['isExternalTasks'];
      $isSidetasksInactivity = $filters['isSidetasksInactivity'];
      
      $startT = mktime(0, 0, 0, $month, 1, $year);
      $endT = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

      $smartyUsers = array();
      foreach($users as $user) {
         // if user was working on the project within the timestamp
         if (($user->isTeamDeveloper($teamid, $startT, $endT)) ||
            ($user->isTeamManager($teamid, $startT, $endT))) {

            $arrivalDate = $user->getArrivalDate($teamid);
            $departureDate = $user->getDepartureDate($teamid);

            $timeTracks = $user->getTimeTracks($startT, $endT);
            $issueIds = array();
            foreach ($timeTracks as $timeTrack) {
               $issueIds[] = $timeTrack->getIssueId();
            }

            $daysOf = $user->getDaysOfInPeriod($timeTracks, $issueIds);

            $astreintes = $user->getOnDutyTaskInMonth($teamid, $timeTracks, $issueIds);

            $externalTasks = $user->getExternalTasksInPeriod($timeTracks, $issueIds);

            $days = array();
            for ($i = 1; $i <= $nbDaysInMonth; $i++) {
               $timestamp = mktime(0,0,0,$month,$i,$year);

               if (($timestamp < $arrivalDate) ||
                   ((0 != $departureDate) && ($timestamp > $departureDate)))  {
                     $days[$i] = array(
                        "color" => 'D8D8D8', // light grey
                        "title" => T_('User not in team'),
                     );
               } elseif (isset($externalTasks[$timestamp]) && (NULL != $externalTasks[$timestamp])) {
                  // always show inactivity tasks,
                  // other externalTasks must only be displayed if $isExternalTasks
                  if (('Inactivity' == $externalTasks[$timestamp]['type']) ||
                      ($isExternalTasks)) {

                     $days[$i] = array(
                        "color" => $externalTasks[$timestamp]['color'],
                        "align" => true,
                        "title" => htmlentities($externalTasks[$timestamp]['title']),
                        "value" => $externalTasks[$timestamp]['duration'],
                     );
                  }
               } elseif (isset($astreintes[$timestamp]) && (NULL != $astreintes[$timestamp])) {
                  $days[$i] = array(
                     "color" => $astreintes[$timestamp]['color'],
                     "align" => true,
                     "value" => $astreintes[$timestamp]['duration'],
                     "title" => htmlentities((T_($astreintes[$timestamp]['type']))),
                  );
               } elseif (isset($daysOf[$timestamp]) && (NULL != $daysOf[$timestamp])) {
                  
                  if ($isSidetasksInactivity) {
                     $days[$i] = array(
                        "color" => $daysOf[$timestamp]['color'],
                        "align" => true,
                        "title" => htmlentities($daysOf[$timestamp]['title']),
                        "value" => $daysOf[$timestamp]['duration']
                     );
                  }
               }

               if(!isset($days[$i]) ) {
                  // If weekend or holiday, display gray
                  $timestamp = mktime(0, 0, 0, $month, $i, $year);
                  $h = $holidays->isHoliday($timestamp);
                  if (NULL != $h) {
                     $days[$i] = array(
                        "color" => $h->color,
                        "title" => htmlentities($h->description),
                     );
                  } else {
                     $days[$i] = array();
                  }
               }

            }
            $smartyUsers[$user->getId()] = array(
               'realname' => $user->getRealname(),
               'username' => $user->getName(),
               'days' => $days
            );
         }
      }
      return $smartyUsers;
   }

}

// ========== MAIN ===========
HolidaysReportController::staticInit();
$controller = new HolidaysReportController('../', 'Holidays Report','Holiday');
$controller->execute();

?>
