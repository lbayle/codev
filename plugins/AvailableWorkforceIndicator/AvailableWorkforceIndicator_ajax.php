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

   $logger = Logger::getLogger("AvailableWorkforceIndicator_ajax");
   $action = Tools::getSecureGETStringValue('action', 'none');
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
   
   if('getAvailableWorkforceRangeValue' == $action) {

      $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("availableWorkforce_startdate"));
      $endTimestamp   = Tools::date2timestamp(Tools::getSecureGETStringValue("availableWorkforce_enddate"));

      $attributesJsonStr = Tools::getSecureGETStringValue('attributesJsonStr');
      $attributesArray = json_decode(stripslashes($attributesJsonStr), true);
      $userSettings = $attributesArray['userSettings'];
      
      // Note: no need to update dataProvider
      $indicator = new AvailableWorkforceIndicator($pluginDataProvider);
      $indicator->setPluginSettings(array(
          AvailableWorkforceIndicator::OPTION_USER_SETTINGS => $userSettings,
      ));
      $availWorkforce = $indicator->getTeamAvailWorkforce($startTimestamp, $endTimestamp);
      $data['availableWorkforceIndicator_rangeValue'] = $availWorkforce;

      // return data (just an integer value)
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('updateUserSettings' == $action) {
      
      $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("availableWorkforce_startdate"));
      $endTimestamp   = Tools::date2timestamp(Tools::getSecureGETStringValue("availableWorkforce_enddate"));

      $attributesJsonStr = Tools::getSecureGETStringValue('attributesJsonStr');
      $attributesArray = json_decode(stripslashes($attributesJsonStr), true);

      $userSettings = $attributesArray['userSettings'];
      $interval = $attributesArray['interval'];
      
      // update dataProvider
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);
      
      // override plugin settings with current attributes
      $indicator = new AvailableWorkforceIndicator($pluginDataProvider);
      $indicator->setPluginSettings(array(
          AvailableWorkforceIndicator::OPTION_USER_SETTINGS => $userSettings,
          AvailableWorkforceIndicator::OPTION_INTERVAL => $interval,
      ));

      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax(); 

      // construct the html table
      $smartyHelper = new SmartyHelper();
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
         #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
      }
      $html = $smartyHelper->fetch(AvailableWorkforceIndicator::getSmartySubFilename());
      $data['availableWorkforceIndicator_htmlContent'] = $html;

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;
      
   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


