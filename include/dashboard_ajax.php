<?php

require('../include/session.inc.php');

/*
   This file is part of CodevTT.

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

/*
 * this files allows to save dashboard settings
 */

require('../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if (Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $logger = Logger::getLogger("dashboardAjax");

   $action = filter_input(INPUT_POST, 'action');

   if ($action == 'getPluginConfigInfo') {

      $pluginClassName = Tools::getSecurePOSTStringValue('pluginClassName');

      try {
         $reflectionMethod = new ReflectionMethod($pluginClassName, 'getDesc');
         $pDesc = $reflectionMethod->invoke(NULL);
         $reflectionMethod2 = new ReflectionMethod($pluginClassName, 'getCfgFilemame');
         $cfgFilename = $reflectionMethod2->invoke(NULL);

         $smartyHelper = new SmartyHelper();
         $html = $smartyHelper->fetch($cfgFilename);

         $data = array(
            'description'    => htmlspecialchars($pDesc),
            'attributesHtml' => $html,
            'statusMsg'      => 'SUCCESS',
         );

         // return html & description string
         $jsonData = json_encode($data);

      } catch (Exception $e) {
         $logger->error("addDashboardPlugin error: ".$e->getMessage());
         $jsonData=json_encode(array('statusMsg' => T_('ERROR: could not get plugin configuration info')));
      }
      echo $jsonData;

   } else if ($action == 'addDashboardPlugin') {

      $pluginAttributesJsonStr = Tools::getSecurePOSTStringValue('pluginAttributesJsonStr');
      $pluginAttributesJsonArray = json_decode(stripslashes($pluginAttributesJsonStr), true);

      //$logger->error("pluginAttributes = " . var_export($pluginAttributesJsonArray, true));

      // convert to a Dashboard compatible format
      $pluginAttributes = array();
      foreach ($pluginAttributesJsonArray as $attData) {
         $attName = $attData['name'];
         $attValue = $attData['value'];
         $pluginAttributes[$attName] = $attValue;
      }
      //$logger->error("pluginClassName = " . $pluginAttributes['pluginClassName']);

      try {

         if (!isset($_SESSION[PluginDataProviderInterface::SESSION_ID])) {
            $logger->error("DataProvider not found in _SESSION !");
         } else {

            $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID]);

            $smartyHelper = new SmartyHelper();
            $widget = Dashboard::getWidget($pluginDataProvider, $smartyHelper, $pluginAttributes, 'idx_'.time());
         }

         // return the created plugin instance to the dashboard
         $data = array(
            'widget'    => $widget,
            'statusMsg'      => 'SUCCESS',
         );
         $jsonData = json_encode($data);
      } catch (Exception $e) {
         $logger->error("addDashboardPlugin error: ".$e->getMessage());
         $logger->error("addDashboardPlugin stacktrace: ".$e->getTraceAsString());
         $jsonData=json_encode(array('statusMsg' => T_('ERROR: could not get plugin widget')));
      }
      echo $jsonData;
      
   } else if($action == 'saveDashboardSettings') {

      $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      
      try {
         // dashboardSettingsJsonStr is a json string containing dashboard & indicator settings.
         $dashboardSettingsJsonStr = Tools::getSecurePOSTStringValue('dashboardSettingsJsonStr');
         $dashboardSettings = json_decode(stripslashes($dashboardSettingsJsonStr), true);

         //$logger->error("dashboardSettings = " . var_export($dashboardSettings, true));

         $dashboard = new Dashboard($dashboardId);
         $dashboard->saveSettings($dashboardSettings, $teamid, $userid);

         // TODO
         // if user is team admin or manager, save also settings for [team]
         // so that team users will have a default setting for the team.
         //$dashboard->saveSettings($settings, $teamid);

         $jsonData=json_encode(array('statusMsg' => 'SUCCESS'));
         
      } catch (Exception $e) {
         $logger->error("saveDashboardSettings error: ".$e->getMessage());
         $logger->error("saveDashboardSettings stacktrace: ".$e->getTraceAsString());
         $jsonData=json_encode(array('statusMsg' => T_('ERROR: could not save dashboard settings, please contact your administrator.')));
      }
      echo $jsonData;
   } 
}

