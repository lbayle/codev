<?php
require('../../include/session.inc.php');

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
require('../../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $logger = Logger::getLogger("OngoingTasks_ajax");
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

      // ================================================================
      if('getOngoingTasks' == $action) {
         $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("ongoingTasks_startdate"));
         $endTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("ongoingTasks_enddate"));

         // get dataProvider
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

         $attributesJsonStr = Tools::getSecurePOSTStringValue('attributesJsonStr');
         $attributesArray = json_decode(stripslashes($attributesJsonStr), true);

         $isOnlyActiveTeamMembers = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_ONLY_TEAM_MEMBERS]) ? false : true;
         $isDisplayInvolvedUsers = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_INVOLVED_USERS]) ? false : true;
         $isDisplayCommands = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_COMMANDS]) ? false : true;
         $isDisplayProject = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_PROJECT]) ? false : true;
         $isDisplayCategory = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_CATEGORY]) ? false : true;
         $isDisplayTaskSummary = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_SUMMARY]) ? false : true;
         $isDisplayTaskExtID = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_EXTID]) ? false : true;
         $isDisplayWbsPath = ('on' !== $attributesArray[OngoingTasks::OPTION_IS_DISPLAY_WBS_PATH]) ? false : true;

         //$logger->error("attributesArray = ".var_export($attributesArray, true));

         // update dataProvider
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

         $indicator = new OngoingTasks($pluginDataProvider);

         // override plugin settings with current attributes
         $indicator->setPluginSettings(array(
             OngoingTasks::OPTION_IS_ONLY_TEAM_MEMBERS => $isOnlyActiveTeamMembers,
             OngoingTasks::OPTION_IS_DISPLAY_INVOLVED_USERS => $isDisplayInvolvedUsers,
             OngoingTasks::OPTION_IS_DISPLAY_COMMANDS => $isDisplayCommands,
             OngoingTasks::OPTION_IS_DISPLAY_PROJECT => $isDisplayProject,
             OngoingTasks::OPTION_IS_DISPLAY_CATEGORY => $isDisplayCategory,
             OngoingTasks::OPTION_IS_DISPLAY_SUMMARY => $isDisplayTaskSummary,
             OngoingTasks::OPTION_IS_DISPLAY_EXTID => $isDisplayTaskExtID,
             OngoingTasks::OPTION_IS_DISPLAY_WBS_PATH => $isDisplayWbsPath,
         ));

         $indicator->execute();
         $data = $indicator->getSmartyVariablesForAjax();

         // construct the html table
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
            #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
         }
         $html = $smartyHelper->fetch(OngoingTasks::getSmartySubFilename());
         $data['ongoingTasks_htmlTable'] = $html;

         // set JS libraries that must be load
         $data['ongoingTasks_jsFiles'] = TimetrackDetailsIndicator::getJsFiles();
         $data['ongoingTasks_cssFiles'] = TimetrackDetailsIndicator::getCssFiles();

         // return html & chart data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else {
         $logger->error("Unknown action: $action");
         Tools::sendNotFoundAccess();
      }
   } else {
      Tools::sendUnauthorizedAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


