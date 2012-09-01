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

if(isset($_SESSION['userid']) && (isset($_GET['action']) || isset($_POST['action']))) {
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

               $smartyHelper->display('plugin/activity_indicator');
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
