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

   $logger = Logger::getLogger("ResetDashboard_ajax");
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

   $displayedUserid  = Tools::getSecurePOSTIntValue("ResetDashboard_userid", 0);
   $displayedTeamid  = Tools::getSecurePOSTIntValue("ResetDashboard_teamid", 0);

   // override plugin settings with current attributes
   $indicator = new ResetDashboard($pluginDataProvider);
   $indicator->setPluginSettings(array(
       ResetDashboard::OPTION_DISPLAYED_USERID => $displayedUserid,
       ResetDashboard::OPTION_DISPLAYED_TEAMID => $displayedTeamid,
   ));

   $smartyHelper = new SmartyHelper();
   if('getResetDashboardData' == $action) {

      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax();

      // construct the html table
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
      $html = $smartyHelper->fetch(ResetDashboard::getSmartySubFilename());
      $data['ResetDashboard_htmlContent'] = $html;

      // set JS libraries that must be load
      $data['ResetDashboard_jsFiles'] = ResetDashboard::getJsFiles();
      $data['ResetDashboard_cssFiles'] = ResetDashboard::getCssFiles();
      $data['statusMsg'] = 'SUCCESS';

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('execResetActions' == $action) {
      $optionsJsonStr = Tools::getSecurePOSTStringValue('ResetDashboardJsonStr');
      $dboardToResetList = json_decode(stripslashes($optionsJsonStr), true);

      foreach ($dboardToResetList as $dboardToReset => $isChecked) {
         $indicator->removeDashboardSettings($dboardToReset);
      }

      // construct the html table
      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax();

      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
      $html = $smartyHelper->fetch(ResetDashboard::getSmartySubFilename());
      $data['ResetDashboard_htmlContent'] = $html;

      // set JS libraries that must be load
      $data['ResetDashboard_jsFiles'] = ResetDashboard::getJsFiles();
      $data['ResetDashboard_cssFiles'] = ResetDashboard::getCssFiles();
      $data['statusMsg'] = 'SUCCESS';

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else {
      Tools::sendNotFoundAccess();
   }

} else {
   Tools::sendUnauthorizedAccess();
}


