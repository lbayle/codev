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

   $logger = Logger::getLogger("AdminTools_ajax");
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);

      // ================================================================
      if('execAdminActions' == $action) {

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
         //$logger->error("attributesArray = ".var_export($attributesArray, true));

         $adminActionsJsonStr = Tools::getSecurePOSTStringValue('adminActionsJsonStr');
         $adminActions = json_decode(stripslashes($adminActionsJsonStr), true);
         //$logger->error("adminActions = ".var_export($adminActions, true));

         // update dataProvider
         //$pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);

         $indicator = new AdminTools($pluginDataProvider);

         // --- do the job
         $actionLogs = '';
         $nbActionsExecuted = 0;
         $nbActionsFailed = 0;

         if (0 != $adminActions["isRestoreBlogPlugin"]) {
            $actionLogs .= $indicator->restoreBlogPlugin();
            $actionLogs .= "\n";
            $nbActionsExecuted += 1;
         }

         if (0 != $adminActions["isChangeIssueId"]) {
            $sourceIssueId = Tools::getSecurePOSTIntValue('adminAction_ChangeIssueId_src');
            $destIssueId = Tools::getSecurePOSTIntValue('adminAction_ChangeIssueId_dest');

            $aData = $indicator->changeIssueId($sourceIssueId, $destIssueId);
            $actionLogs .= $aData['actionLogs']."\n";
            if ('SUCCESS' == $aData['statusMsg']) {
               $nbActionsExecuted += 1;
            } else {
               $nbActionsFailed += 1;
            }
         }
         // Here : check for more actions (RemoveDashboardSettings, ...)

         // --- update display
         $indicator->execute();
         $data = $indicator->getSmartyVariablesForAjax();

         // construct the html table
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
            #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
         }
         $html = $smartyHelper->fetch(AdminTools::getSmartySubFilename());
         $data['AdminTools_htmlTable'] = $html;

         // set JS libraries that must be load
         $data['AdminTools_jsFiles'] = TimetrackDetailsIndicator::getJsFiles();
         $data['AdminTools_cssFiles'] = TimetrackDetailsIndicator::getCssFiles();

         $data['actionLogs'] = $actionLogs;
         $data['nbActionsExecuted'] = $nbActionsExecuted;
         $data['nbActionsFailed'] = $nbActionsFailed;
         $data['statusMsg'] = (0 == $nbActionsFailed) ? 'SUCCESS' : 'ERROR: '.$nbActionsFailed.' action(s) failed !';

         // return html data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else {
         $logger->error("Unknown action: $action");
         $data = array();
         $data['statusMsg'] = "ERROR - Unknown action: $action";
         $jsonData = json_encode($data);
         echo $jsonData;

      }
   } else {
      Tools::sendUnauthorizedAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


