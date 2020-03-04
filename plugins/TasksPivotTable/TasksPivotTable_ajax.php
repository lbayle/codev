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

$logger = Logger::getLogger("TasksPivotTable_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action', 'none');

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
   
      $smartyHelper = new SmartyHelper();
      $team = TeamCache::getInstance()->getTeam($teamid);
      
      // ================================================================
      if('updateDetailedCharges' == $action) {

         // get plugin settings saved in the dashboard
         $attributesJsonStr = Tools::getSecurePOSTStringValue('attributesJsonStr');
         $attributesArray = json_decode(stripslashes($attributesJsonStr), true);
         $selectedFilters = $attributesArray['selectedFilters'];

         $indicator = new TasksPivotTable($pluginDataProvider);

         // override plugin settings with current attributes
         $indicator->setPluginSettings(array(
             TasksPivotTable::OPTION_SELECTED_FILTERS => $selectedFilters,
         ));

         $indicator->execute();
         $smartyData = $indicator->getSmartyVariablesForAjax();

         // construct the html table
         foreach ($smartyData as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
            //$logger->error("key $smartyKey = ".var_export($smartyVariable, true));
         }
         $html  = $smartyHelper->fetch(TasksPivotTable::getSmartySubFilename());
         $html2 = $smartyHelper->fetch(TasksPivotTable::getSmartySubFilename2());


         $data = array(
            'statusMsg' => 'SUCCESS',
            'taskPivotTable_htmlContent' => $html,
            'taskPivotTable_htmlContent2' => $html2,
            'taskPivotTable_jsFiles'  => TimetrackDetailsIndicator::getJsFiles(),
            'taskPivotTable_cssFiles' => TimetrackDetailsIndicator::getCssFiles(),
         );

         // return html & other data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else {
         $logger->error("Unknown action: $action");
         Tools::sendNotFoundAccess();
      }
} else {
   $logger->error("User not connected, or action not set");
   Tools::sendUnauthorizedAccess();
}


