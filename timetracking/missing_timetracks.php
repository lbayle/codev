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

class MissingTimetracksController extends Controller {

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
        } else {

            $isManager = $this->session_user->isTeamManager($this->teamid);
            $isObserver = $this->session_user->isTeamObserver($this->teamid);
            if ($isManager || $isObserver) {
               // observers have access to the same info
               $this->smartyHelper->assign('isManager', true);
            }

            // dates
            $month = date('m');
            $year = date('Y');

            $startdate = Tools::getSecurePOSTStringValue("startdate", Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year)));
            $this->smartyHelper->assign('startDate', $startdate);
            $startTimestamp = Tools::date2timestamp($startdate);

            $nbDaysInMonth = date("t", $startTimestamp);
            $enddate = Tools::getSecurePOSTStringValue("enddate",Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, $nbDaysInMonth, $year)));
            $this->smartyHelper->assign('endDate', $enddate);
            $endTimestamp = Tools::date2timestamp($enddate);

            if ('getMissingTimetracks' == $_POST['action']) {

               $this->smartyHelper->assign('workdays',Holidays::getInstance()->getWorkdays($startTimestamp, $endTimestamp));

               // ConsistencyCheck
               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);
               $consistencyData = $this->getConsistencyErrors($timeTracking);
               $nbErrors = count($consistencyData['consistencyErrors']);
               if($nbErrors > 0) {
                  $this->smartyHelper->assign('ccheckBoxTitle', $nbErrors.' '.T_("days are incomplete or undefined"));
                  $this->smartyHelper->assign('ccheckErrList', $consistencyData['consistencyErrors']);
                  $this->smartyHelper->assign('ccheckErrList', $consistencyData['consistencyErrors']);
                  $this->smartyHelper->assign('missingPerUserList', $consistencyData['missingPerUser']);
                  $this->smartyHelper->assign('totalErrors', $nbErrors);
                  $this->smartyHelper->assign('totalMissingDays', $consistencyData['totalMissingDays']);
               }
            }
        }
      }
   }

   /**
    * Get consistency errors
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   private function getConsistencyErrors(TimeTracking $timeTracking) {
      $consistencyErrors = array();
      $missingPerUser = array();
      $totalMissingDays = 0;

      $cerrList = ConsistencyCheck2::checkIncompleteDays($timeTracking);

      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $consistencyErrors[] = array(
               'date' => date("Y-m-d", $cerr->timestamp),
               'userId' => $cerr->userId,
               'userName' => $user->getRealname(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);

            if (!array_key_exists($cerr->userId, $missingPerUser)) {
               $missingPerUser[$cerr->userId] = array(
                  'userId' => $cerr->userId,
                  'userName' => $user->getRealname(),
                  'missingDays' => (float)$cerr->rawValue,
                  'missingTT' => (int)1,
               );
            } else {
               $missingPerUser[$cerr->userId]['missingDays'] += (float)$cerr->rawValue;
               $missingPerUser[$cerr->userId]['missingTT'] += (int)1;
            }
            $totalMissingDays += (float)$cerr->rawValue;
         }

      }
      $data = array(
         'consistencyErrors' => $consistencyErrors,
         'missingPerUser' => $missingPerUser,
         'totalMissingDays' => $totalMissingDays,
      );
      return $data;
   }

}

// ========== MAIN ===========
MissingTimetracksController::staticInit();
$controller = new MissingTimetracksController('../', 'Missing Timetracks','TimeTracking');
$controller->execute();

