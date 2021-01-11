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

   $logger = Logger::getLogger("ImportRelationshipTreeToCommand_ajax");
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

   $prefix='ImportRelationshipTreeToCommand_';
   $smartyHelper = new SmartyHelper();

   if('getCommandSettings' == $action) {

      $commandId = Tools::getSecurePOSTIntValue("commandId");

      $keyExists =  Config::keyExists(Config::id_importRelationshipTreeToCommandOptions, array(0, 0, 0, 0, 0, $commandId));
      if (false != $keyExists) {
         $jsonOptions = Config::getValue(Config::id_importRelationshipTreeToCommandOptions, array(0, 0, 0, 0, 0, $commandId), true);
         if (null != $jsonOptions) {
            $options = json_decode($jsonOptions, true);
            if (is_null($options)) {
               $data['statusMsg'] = 'ERROR: could not read settings for this command';
            } else {
               //$logger->error("options = ".var_export($options, true));
               $data = $options;
               $data['statusMsg'] = 'SUCCESS';
               $data['isRootTaskList'] = 1; // use this option even if only one issue
            }
         }
      } else {
         $data['statusMsg'] = 'NOT_FOUND';
      }
      // return html data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('importRelationshipTreeToCommand' == $action) {

      $commandId = Tools::getSecurePOSTIntValue($prefix."commandId");

      $optionsJsonStr = Tools::getSecurePOSTStringValue('optionsJsonStr');
      $options = json_decode(stripslashes($optionsJsonStr), true);
      //$logger->error("options = ".var_export($options, true));

      if (0 == $options['isRootTaskList']) {
         $issueId = Tools::getSecurePOSTIntValue($prefix."issueId");
         $bugidList = $issueId;
      } else {
         $issueId = 0;
         $bugidList = Tools::getSecurePOSTStringValue($prefix."rootTaskList");
      }

      $indicator = new ImportRelationshipTreeToCommand($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
         ImportRelationshipTreeToCommand::OPTION_ISSUE_ID => $issueId,
         ImportRelationshipTreeToCommand::OPTION_BUGID_LIST => $bugidList,
         ImportRelationshipTreeToCommand::OPTION_CMD_ID => $commandId,
         ImportRelationshipTreeToCommand::OPTION_IS_ROOT_TASK_LIST => $options['isRootTaskList'],
         ImportRelationshipTreeToCommand::OPTION_IS_INCLUDE_PARENT_ISSUE => $options['isIncludeParentIssue'],
         ImportRelationshipTreeToCommand::OPTION_IS_INCLUDE_PARENT_IN_ITS_OWN_WBS => $options['isIncludeParentInItsOwnWbsFolder'],
      ));

      // --- update display
      try {
         $data = $indicator->importIssues();
         $data['statusMsg'] = 'SUCCESS';

         // save options for this command
         $options['bugidList'] = $bugidList;
         $newOptionsJsonStr = json_encode($options);
         Config::setValue(Config::id_importRelationshipTreeToCommandOptions, $newOptionsJsonStr, Config::configType_string, NULL, 0, 0, 0, $commandId);
         //$logger->error("save options $commandId = ".var_export($options, true));

      } catch (Exception $e) {
         $data['statusMsg'] = $e->getMessage();
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


