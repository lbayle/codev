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

if(Tools::isConnectedUser() && filter_input(INPUT_GET, 'action')) {

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;

   $logger = Logger::getLogger("FdjSopNbCasTests_ajax");
   $action = Tools::getSecureGETStringValue('action');
   $dashboardId = Tools::getSecureGETStringValue('dashboardId');

   if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }

   $smartyHelper = new SmartyHelper();
   if('getFdjSopNbCasTests' == $action) {

      $attributesJsonStr = Tools::getSecureGETStringValue('attributesJsonStr');
      $attributesArray = json_decode(stripslashes($attributesJsonStr), true);
      $selectedTaskType = $attributesArray['taskType'];
      $userSettings = $attributesArray['userSettings'];
      //$logger->error(var_export($userSettings, true));

      $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("fdjSopNbCasTests_startdate"));
      $endTimestamp   = Tools::date2timestamp(Tools::getSecureGETStringValue("fdjSopNbCasTests_enddate"));

      // update dataProvider
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

      $indicator = new FdjSopNbCasTests($pluginDataProvider);

      // override plugin default settings with current attributes
      $indicator->setPluginSettings(array(
          FdjSopNbCasTests::OPTION_TASK_TYPE => $selectedTaskType,
          FdjSopNbCasTests::OPTION_USER_SETTINGS => $userSettings,
      ));

      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax();

      // construct the html content (FdjSopNbCasTests_ajax.html)
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
         #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
      }
      $html = $smartyHelper->fetch(FdjSopNbCasTests::getSmartySubFilename());
      $data['fdjSopNbCasTests_htmlContent'] = $html;

      // so that ajax can reload libraries (tabs, datatable)
      $data['fdjSopNbCasTests_jsFiles'] = FdjSopNbCasTests::getJsFiles();

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}

