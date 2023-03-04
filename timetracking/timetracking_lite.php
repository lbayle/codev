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

class TimeTrackingLiteController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {
         $now = time();
         $midnightTimestamp = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
        // only teamMembers can access this page
        if ((0 == $this->teamid) ||
            ($this->session_user->isTeamCustomer($this->teamid)) ||
            ($this->session_user->isTeamObserver($this->teamid)) ||
            (!$this->session_user->isTeamMember($this->teamid, NULL, $midnightTimestamp, $midnightTimestamp))) {

            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $teamMembers = $team->getActiveMembers(NULL, NULL, TRUE);

            $managed_userid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);

            // read from formReloadTimetrackingPage
            $action = Tools::getSecurePOSTStringValue('action','');
            $weekid = Tools::getSecurePOSTIntValue('weekid',date('W'));
            $year   = Tools::getSecurePOSTIntValue('year',date('Y'));

            $managed_user = UserCache::getInstance()->getUser($managed_userid);

            // Display user name
            $this->smartyHelper->assign('managedUser_realname', $managed_user->getRealname());
            $this->smartyHelper->assign('userid', $managed_userid);

            $this->smartyHelper->assign('status_new', Constants::$status_new);

            // All projects except disabled
            $projList = $team->getProjects(true, false);
            $defaultProjectid = 0 ; // $_SESSION['projectid'];
            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));

            $this->smartyHelper->assign('tt_weekid', $weekid);
            $this->smartyHelper->assign('tt_year', $year);

            $isOnlyAssignedTo = ('0' == $managed_user->getTimetrackingFilter('onlyAssignedTo')) ? false : true;
            $this->smartyHelper->assign('isOnlyAssignedTo', $isOnlyAssignedTo);

            $isHideResolved = ('0' == $managed_user->getTimetrackingFilter('hideResolved')) ? false : true;
            $this->smartyHelper->assign('isHideResolved', $isHideResolved);

            $isHideForbidenStatus = ('0' == $managed_user->getTimetrackingFilter('hideForbidenStatus')) ? false : true;
            $this->smartyHelper->assign('isHideForbidenStatus', $isHideForbidenStatus);

            $durations = TimeTrackingTools::getDurationList($this->teamid);
            $this->smartyHelper->assign('durationList', json_encode($durations));

            # In ISO-8601 specification, it says that December 28th is always in the last week of its year.
            $this->smartyHelper->assign('nbWeeksPrevYear', date("W", strtotime("28 December ".($year-1))));
            $this->smartyHelper->assign('nbWeeksThisYear', date("W", strtotime("28 December $year")));

            $weekDates = Tools::week_dates($weekid,$year);
            $startTimestamp = $weekDates[1];
            $endTimestamp = mktime(23, 59, 59, date("m", $weekDates[7]), date("d", $weekDates[7]), date("Y", $weekDates[7]));
            $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

            $incompleteDays = array_keys($timeTracking->checkCompleteDays($managed_userid, TRUE));
            $missingDays = $timeTracking->checkMissingDays($managed_userid);
            $errorDays = array_merge($incompleteDays,$missingDays);
            $smartyWeekDates = TimeTrackingTools::getSmartyWeekDates($weekDates,$errorDays);

            // UTF8 problems in smarty, date encoding needs to be done in PHP
            $this->smartyHelper->assign('weekDates', array(
               $smartyWeekDates[1], $smartyWeekDates[2], $smartyWeekDates[3], $smartyWeekDates[4], $smartyWeekDates[5]
            ));
            $this->smartyHelper->assign('weekEndDates', array(
               $smartyWeekDates[6], $smartyWeekDates[7]
            ));

            $weekTasks_lite = TimeTrackingTools::getWeekTasks_lite($managed_userid, $this->teamid, $startTimestamp, $endTimestamp, $weekDates, $errorDays);
            $this->smartyHelper->assign('weekTasksLite', $weekTasks_lite["weekTasks"]);
            $this->smartyHelper->assign('dayTotalElapsedLite', $weekTasks_lite["totalElapsed"]);

            // ConsistencyCheck
            $consistencyErrors = $this->getConsistencyErrors($managed_userid, $this->teamid);
            if(count($consistencyErrors) > 0) {
               $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
               $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
               $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("days are incomplete or undefined"));
            }
         }
      }
   }

   /**
    * display missing imputations
    *
    * @param int $userid
    * @param int $team_id
    * @return mixed[] consistencyErrors
    */
   private function getConsistencyErrors($userid, $team_id = NULL) {

      $user = UserCache::getInstance()->getUser($userid);

      $startTimestamp = $user->getArrivalDate($team_id);
      $endTimestamp = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $team_id);

      $cerrList = ConsistencyCheck2::checkIncompleteDays($timeTracking, $userid);

      $consistencyErrors = array();
      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {

            // skip alerts on today
            if ($endTimestamp == $cerr->timestamp) { continue; }

            if ($userid == $cerr->userId) {
               $consistencyErrors[] = array(
                  'date' => date("Y-m-d", $cerr->timestamp),
                  'severity' => $cerr->getLiteralSeverity(),
                  'severityColor' => $cerr->getSeverityColor(),
                  'desc' => $cerr->desc);
               }
         }
      }
      return $consistencyErrors;
   }
}

// ========== MAIN ===========
TimeTrackingLiteController::staticInit();
$controller = new TimeTrackingLiteController('../', 'Time Tracking (lite)','TimeTracking');
$controller->execute();


