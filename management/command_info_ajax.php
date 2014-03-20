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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

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
               
               $smartyHelper->display(ActivityIndicator::getSmartySubFilename());
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
               foreach ($data as $smartyKey => $smartyVariable) {
                  $smartyHelper->assign($smartyKey, $smartyVariable);
               }
               $smartyHelper->display(ProgressHistoryIndicator::getSmartyFilename());
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if($_GET['action'] == 'getBudgetDriftHistoryIndicator') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);
               $data = CommandTools::getBudgetDriftHistoryIndicator($cmd);
               foreach ($data as $smartyKey => $smartyVariable) {
                  $smartyHelper->assign($smartyKey, $smartyVariable);
               }
               $smartyHelper->display(BudgetDriftHistoryIndicator::getSmartyFilename());
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if($_GET['action'] == 'getReopenedRateIndicator') {
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);
               $data = CommandTools::getReopenedRateIndicator($cmd);
               foreach ($data as $smartyKey => $smartyVariable) {
                  $smartyHelper->assign($smartyKey, $smartyVariable);
               }
               $smartyHelper->display(ReopenedRateIndicator::getSmartyFilename());
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }
      } else if ($_GET['action'] == 'updateDetailedCharges') {


         $cmdid = Tools::getSecureGETIntValue('selectFiltersSrcId');
         $selectedFilters = Tools::getSecureGETStringValue('selectedFilters', '');


         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $session_user->setCommandFilters($selectedFilters, $cmdid);

         $cmd = CommandCache::getInstance()->getCommand($cmdid);
         $isManager = $session_user->isTeamManager($cmd->getTeamid());
         $isObserver = $session_user->isTeamObserver($cmd->getTeamid());

         // DetailedChargesIndicator
         $data = CommandTools::getDetailedCharges($cmd, ($isManager || $isObserver), $selectedFilters);
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $smartyHelper->display(DetailedChargesIndicator::getSmartySubFilename());

      } else if ($_GET['action'] == 'updateStatusHistory') {


         $cmd = CommandCache::getInstance()->getCommand($cmdid);

         // StatusHistoryIndicator
         $data = CommandTools::getStatusHistory($cmd);
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $smartyHelper->display(StatusHistoryIndicator::getSmartyFilename());

      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>
