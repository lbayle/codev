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

   $logger = Logger::getLogger("LoadPerUserGroups_ajax");
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $sessionUserId = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

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

      // ================================================================
      if('getLoadPerUserGroups' == $action) {
         $startTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("LoadPerUserGroups_startdate"));
         $endTimestamp = Tools::date2timestamp(Tools::getSecurePOSTStringValue("LoadPerUserGroups_enddate"));

         $attributesJsonStr = Tools::getSecurePOSTStringValue('attributesJsonStr');
         $attributesArray = json_decode(stripslashes($attributesJsonStr), true);

         $isOnlyActiveTeamMembers = ('on' !== $attributesArray[LoadPerUserGroups::OPTION_IS_ONLY_TEAM_MEMBERS]) ? false : true;

         $isDisplayInvolvedUsers = ('on' !== $attributesArray[LoadPerUserGroups::OPTION_IS_DISPLAY_INVOLVED_USERS]) ? false : true;
         $isDisplayTasks = ('on' !== $attributesArray[LoadPerUserGroups::OPTION_IS_DISPLAY_TASKS]) ? false : true;
         //$logger->error("attributesArray = ".var_export($attributesArray, true));

         // update dataProvider
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

         $plugin = new LoadPerUserGroups($pluginDataProvider);

         // override plugin settings with current attributes
         $plugin->setPluginSettings(array(
             LoadPerUserGroups::OPTION_IS_ONLY_TEAM_MEMBERS => $isOnlyActiveTeamMembers,
             LoadPerUserGroups::OPTION_IS_DISPLAY_INVOLVED_USERS => $isDisplayInvolvedUsers,
             LoadPerUserGroups::OPTION_IS_DISPLAY_TASKS => $isDisplayTasks,
         ));

         $plugin->execute();
         $data = $plugin->getSmartyVariablesForAjax();

         // construct the html table
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
            #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
         }
         $html = $smartyHelper->fetch(LoadPerUserGroups::getSmartySubFilename());
         $data['LoadPerUserGroups_htmlTable'] = $html;

         // set JS libraries that must be load
         $data['LoadPerUserGroups_jsFiles'] = TimetrackDetailsIndicator::getJsFiles();
         $data['LoadPerUserGroups_cssFiles'] = TimetrackDetailsIndicator::getCssFiles();

         // return html & chart data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('uploadCsvFile' === $action) {
         $filename = null;
         $userGroups = null;
         $statusMsg = 'SUCCESS';
         $userDataArray = array();

         // If user is allowed to import
         $sessionUser = UserCache::getInstance()->getUser($sessionUserId);
         if (($sessionUser->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) ||
             ($sessionUser->isTeamManager($teamid)) ||
             ($sessionUser->isTeamLeader($teamid))) {

            $plugin = new LoadPerUserGroups($pluginDataProvider);

            if (isset($_FILES['uploaded_csv'])) {
               try {
                  // Get file datas
                  $filename = $plugin->getSourceFile();
                  $userGroups = $plugin->getUserGroupsFromCSV($filename);
                  $plugin->setUserGroups($userGroups);
                  $userDataArray = $plugin->updateUserDataArray();

               } catch (Exception $ex) {
                  $statusMsg = $ex->getMessage();
               }
            }

            $data = array(
               'statusMsg' => $statusMsg,
               'LoadPerUserGroups_userDataArray' => $userDataArray,
            );
         } else {
            $statusMsg = T_("Please contact your team leader to update UserGroups settings");
            $data = array(
               'statusMsg' => $statusMsg,
            );
         }

         // return data
         $jsonData = json_encode($data);
         echo $jsonData;
      } else if ('validateNewUserGroups' === $action) {

         $userGroupsJsonStr = Tools::getSecurePOSTStringValue('userGroups');
         $userGroups = json_decode(stripslashes($userGroupsJsonStr), true);

         $statusMsg = 'SUCCESS';
         $team->setUserGroups($userGroups);

         // TODO error handling !

         $data = array(
            'statusMsg' => $statusMsg,
         );
         // return data
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


