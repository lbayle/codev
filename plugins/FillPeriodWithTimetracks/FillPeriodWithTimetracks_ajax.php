<?php
require('../../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/
require('../../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $logger = Logger::getLogger("FillPeriodWithTimetracks_ajax");
   $action = Tools::getSecurePOSTStringValue('action');
   $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');

   if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }

   $prefix='FillPeriodWithTimetracks_';
   $smartyHelper = new SmartyHelper();

   if('getAvailableOnPeriod' == $action) {
      $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue($prefix."startdate"));
      $endTimestamp   = Tools::date2timestamp(Tools::getSecurePOSTStringValue($prefix."enddate"));
      $managedUserid  = Tools::getSecurePOSTIntValue($prefix."managedUser", 0);

      $indicator = new FillPeriodWithTimetracks($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
          FillPeriodWithTimetracks::OPTION_START_TIMESTAMP => $startTimestamp,
          FillPeriodWithTimetracks::OPTION_END_TIMESTAMP => $endTimestamp,
          FillPeriodWithTimetracks::OPTION_MANAGED_USERID => $managedUserid,
      ));

      $availableOnPeriod = $indicator->getAvailableOnPeriod();
      $data = array();
      $data['availableOnPeriod'] = $availableOnPeriod;
      $data['statusMsg'] = 'SUCCESS';

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('getJobs' == $action) {
      $issueId = Tools::getSecurePOSTIntValue($prefix."issueId");
      $jobId = Tools::getSecurePOSTIntValue($prefix."jobId", 0);

      $indicator = new FillPeriodWithTimetracks($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
          FillPeriodWithTimetracks::OPTION_ISSUE_ID => $issueId,
      ));

      $data = $indicator->getIssueInfo();
      $data['selectedJobId'] = $jobId;
      $data['statusMsg'] = 'SUCCESS';

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('addTimetracks' == $action) {

      $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue($prefix."startdate"));
      $endTimestamp   = Tools::date2timestamp(Tools::getSecurePOSTStringValue($prefix."enddate"));
      $managedUserid  = Tools::getSecurePOSTIntValue($prefix."managedUser");
      $issueId = Tools::getSecurePOSTIntValue($prefix."issueId");
      $jobId = Tools::getSecurePOSTIntValue($prefix."jobId");
      $elapsedTarget = Tools::getSecurePOSTNumberValue($prefix."elapsedTarget");
      $finalBacklog = Tools::getSecurePOSTNumberValue($prefix."finalBacklog", null);
      $ttNote = Tools::getSecurePOSTStringValue($prefix."ttNote", '');

      $indicator = new FillPeriodWithTimetracks($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
         FillPeriodWithTimetracks::OPTION_START_TIMESTAMP => $startTimestamp,
         FillPeriodWithTimetracks::OPTION_END_TIMESTAMP => $endTimestamp,
         FillPeriodWithTimetracks::OPTION_MANAGED_USERID => $managedUserid,
         FillPeriodWithTimetracks::OPTION_ISSUE_ID => $issueId,
         FillPeriodWithTimetracks::OPTION_JOB_ID => $jobId,
         FillPeriodWithTimetracks::OPTION_ELAPSED_TARGET => $elapsedTarget,
         FillPeriodWithTimetracks::OPTION_FINAL_BACKLOG => $finalBacklog,
         FillPeriodWithTimetracks::OPTION_TIMETRACK_NOTE => $ttNote,
      ));

      // --- update display
      $data = $indicator->addTimetracks();

      if ($data['elapsedTarget'] == $data['realElapsed']) {
         $data['statusMsg'] = 'SUCCESS';
      } else {
         $data['statusMsg'] = 'ERROR';
      }

      // return html data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


