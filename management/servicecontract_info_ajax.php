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
         if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
            if (0 != $servicecontractid) {
               $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
            
               $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("startdate"));
               $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("enddate"));
               $data = ServiceContractTools::getSContractActivity($servicecontract, $startTimestamp, $endTimestamp);
               $smartyHelper->assign('activityIndic_data', $data[0]);
               $smartyHelper->assign('startDate', Tools::formatDate("%Y-%m-%d", $data[1]));
               $smartyHelper->assign('endDate', Tools::formatDate("%Y-%m-%d", $data[2]));
               $smartyHelper->assign('workdays', Holidays::getInstance()->getWorkdays($data[1], $data[2]));

               $smartyHelper->display('plugin/activity_indicator_ajax1');
            } else {
               Tools::sendBadRequest("Service contract equals 0");
            }
         } else {
            Tools::sendBadRequest("Service contract not set");
         }
      } else if($_GET['action'] == 'getActivityIndicatorData') {
         if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
            if (0 != $servicecontractid) {
               $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

               $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("startdate"));
               $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("enddate"));
               $data = ServiceContractTools::getSContractActivity($servicecontract, $startTimestamp, $endTimestamp);
               echo $data[0]['jqplotData'];
               SqlWrapper::getInstance()->logStats();
            } else {
               Tools::sendBadRequest("Service contract equals 0");
            }
         } else {
            Tools::sendBadRequest("Service contract not set");
         }
      } else if($_GET['action'] == 'getProgressHistoryIndicator') {
         if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
            if (0 != $servicecontractid) {
               $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
               $timestamp = ServiceContractTools::getSContractProgressHistoryTimestamp($servicecontract);
               $start = Tools::formatDate("%Y-%m-01", $timestamp[0]);
               $end = Tools::formatDate("%Y-%m-01", strtotime(date("Y-m-d",$timestamp[1])." +1 month"));
               $smartyHelper->assign('progress_history_plotMinDate', $start);
               $smartyHelper->assign('progress_history_plotMaxDate', $end);
               $smartyHelper->display('plugin/progress_history_indicator');
            }
         } else {
            Tools::sendBadRequest("Service contract not set");
         }
      } else if($_GET['action'] == 'getProgressHistoryIndicatorData') {
         if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
            if (0 != $servicecontractid) {
               $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
               $data = ServiceContractTools::getSContractProgressHistory($servicecontract);
               header('Content-type: application/json');
               echo $data;
               SqlWrapper::getInstance()->logStats();
            } else {
               Tools::sendBadRequest("Service contract equals 0");
            }
         } else {
            Tools::sendBadRequest("Service contract not set");
         }
      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>
