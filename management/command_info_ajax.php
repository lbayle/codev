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

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'getActivityIndicator') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmdset = CommandCache::getInstance()->getCommand($cmdid);

               $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("startdate"));
               $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("enddate"));
               $data = CommandTools::getCommandActivity($cmdset, $startTimestamp, $endTimestamp);
               $smartyHelper->assign('activityIndic_data', $data[0]);
               $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
               $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
               $smartyHelper->assign('workdays', Holidays::getInstance()->getWorkdays($data[1], $data[2]));

               $smartyHelper->display('plugin/activity_indicator_ajax1');
            } else {
               Tools::sendBadRequest("Command equals 0");
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if($_GET['action'] == 'getActivityIndicatorData') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmdset = CommandCache::getInstance()->getCommand($cmdid);

               $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("startdate"));
               $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("enddate"));
               $data = CommandTools::getCommandActivity($cmdset, $startTimestamp, $endTimestamp);
               echo $data[0]['jqplotData'];
            } else {
               Tools::sendBadRequest("Command equals 0");
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if($_GET['action'] == 'getProgressHistoryIndicator') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);
               $data = CommandTools::getProgressHistory($cmd);
               $start = Tools::formatDate("%Y-%m-01", $data[1]);
               $end = Tools::formatDate("%Y-%m-01", strtotime(date("Y-m-d",$data[2])." +1 month"));
               $smartyHelper->assign('progress_history_data', $data[0]);
               $smartyHelper->assign('progress_history_plotMinDate', $start);
               $smartyHelper->assign('progress_history_plotMaxDate', $end);
               $smartyHelper->assign('progress_history_interval', $data[3]);
               $smartyHelper->display('plugin/progress_history_indicator');
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if ($_GET['action'] == 'updateDetailedCharges') {


         $cmdid = Tools::getSecureGETIntValue('selectFiltersSrcId');
         $selectedFilters = Tools::getSecureGETStringValue('selectedFilters', '');


         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $cmd = CommandCache::getInstance()->getCommand($cmdid);
         $isManager = $session_user->isTeamManager($cmd->getTeamid());

         // DetailedChargesIndicator
         $data = CommandTools::getDetailedCharges($cmd, $isManager, $selectedFilters);
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $smartyHelper->display('plugin/detailed_charges_indicator_data.html');

      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>
