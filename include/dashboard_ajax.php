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

if (Tools::isConnectedUser() && (isset($_POST['action']))) {

   $logger = Logger::getLogger("dashboardAjax");

   if ($_POST['action'] == 'getPluginConfigInfo') {

      $pluginClassName = Tools::getSecurePOSTStringValue('pluginClassName');


      // TODO : check $pluginClassName & catch exceptions
      $reflectionMethod = new ReflectionMethod($pluginClassName, 'getDesc');
      $pDesc = $reflectionMethod->invoke(NULL);
      $reflectionMethod2 = new ReflectionMethod($pluginClassName, 'getCfgFilemame');
      $cfgFilename = $reflectionMethod2->invoke(NULL);

      $smartyHelper = new SmartyHelper();
      $html = $smartyHelper->fetch($cfgFilename);

      $data = array(
         'description'    => htmlspecialchars($pDesc),
         'attributesHtml' => $html,
      );

      // return html & description string
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if ($_POST['action'] == 'addDashboardPlugin') {

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

      // TODO : check $pluginClassName & catch exceptions

      $pluginDataProvider = unserialize($_SESSION['pluginDataProvider_xxx']);
      $smartyHelper = new SmartyHelper();
      $widget = Dashboard::getWidget($pluginDataProvider, $smartyHelper, $pluginAttributes, 999);

      // return the plugin created plugin instance to the dashboard
      $data = array(
         'widget'    => $widget,
      );
      $jsonData = json_encode($data);
      echo $jsonData;
      
   } else if($_POST['action'] == 'saveDashboardSettings') {

      $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      
      //$logger->error("saveDashboardSettings: dashboardId = " . $dashboardId);
      
      //$dashboardSettingsJsonStr = Tools::getSecurePOSTStringValue('dashboardSettingsJsonStr');
      //$dashboardSettingsJsonArray = json_decode(stripslashes($dashboardSettingsJsonStr), true);
      
      
      // convert $dashboardSettingsJsonArray to a Dashboard compatible format
      /*
       *  settings = array (
       *     'dashboardTitle' => 'dashboard title'
       *     'displayedPlugins' => array(
       *        array(
       *           'pluginClassName' => <pluginClassName>,
       *           'plugin_attr1' => 'val',
       *           'plugin_attr2' => 'val',
       *        )
       *     )
       *  )
       * 
       */      
      $settings = array(); // TODO
      
      // $settings is a json string containing dashboard & indicator settings.
      //$dashboard = new Dashboard($dashboardId);
      //$dashboard->saveSettings($settings, $teamid, $userid);

      // TODO
      // if user is team admin or manager, save also settings for [team]
      // so that team users will have a default setting for the team.
      //$dashboard->saveSettings($settings, $teamid);

   } 
   
}


?>
