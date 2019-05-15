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

if(Tools::isConnectedUser() && filter_input(INPUT_GET, 'action')) {
   
   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   
   $logger = Logger::getLogger("LoadPerProjCategoryIndicator_ajax");
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
   if('getLoadPerProjCategoryIndicator' == $action) {
      
      $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("loadPerProjCategory_startdate"));
      $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("loadPerProjCategory_enddate"));

      $attributesJsonStr = Tools::getSecureGETStringValue('attributesJsonStr');
      $attributesArray = json_decode(stripslashes($attributesJsonStr), true);

      $selectedProject = $attributesArray['projectid'];

      $isDisplayTasks = ('on' !== $attributesArray[LoadPerProjCategoryIndicator::OPTION_DISPLAY_TASKS]) ? false : true;

      // update dataProvider
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_PROJECT_ID, $selectedProject);

      $indicator = new LoadPerProjCategoryIndicator($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
          LoadPerProjCategoryIndicator::OPTION_DISPLAY_TASKS => $isDisplayTasks,
      ));

      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax(); 

      // construct the html table
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
         #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
      }
      $html = $smartyHelper->fetch(LoadPerProjCategoryIndicator::getSmartySubFilename());
      $data['loadPerProjCategory_htmlContent'] = $html;

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}

