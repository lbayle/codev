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

   if($_POST['action'] == 'saveDashboardSettings') {

      $dashboard = new Dashboard($id);

      // $settings is a json string containing dashboard & indicator settings.
      $dashboard->saveSettings($settings, $teamid, $userid);

      // TODO
      // if user is team admin or manager, save also settings for [team]
      // so that team users will have a default setting for the team.
      $dashboard->saveSettings($settings, $teamid);

   } else if ($_POST['action'] == 'getIndicatorConfigInfo') {

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

   }
   
}


?>
