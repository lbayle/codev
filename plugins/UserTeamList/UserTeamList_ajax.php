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

   $logger = Logger::getLogger("UserTeamList_ajax");
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

   $smartyHelper = new SmartyHelper();
   if('getUserTeamList' == $action) {

      $displayedUserid  = Tools::getSecurePOSTIntValue("userTeamList_userid", 0);

      $indicator = new UserTeamList($pluginDataProvider);

      // override plugin settings with current attributes
      $indicator->setPluginSettings(array(
          UserTeamList::OPTION_DISPLAYED_USERID => $displayedUserid,
      ));

      $indicator->execute();
      $data = $indicator->getSmartyVariablesForAjax();

      // construct the html table
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
      $html = $smartyHelper->fetch(UserTeamList::getSmartySubFilename());
      $data['userTeamList_htmlContent'] = $html;

      // set JS libraries that must be load
      $data['userTeamList_jsFiles'] = UserTeamList::getJsFiles();
      $data['userTeamList_cssFiles'] = UserTeamList::getCssFiles();

      // return html & chart data
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if('updateUserTeamInfo' == $action) {

         try {
            $displayed_userid = Tools::getSecurePOSTIntValue('displayed_userid');
            $teamid = Tools::getSecurePOSTIntValue('teamid');
            $arrivalDate = Tools::getSecurePOSTStringValue('arrivalDate');
            $departureDate = Tools::getSecurePOSTStringValue('departureDate');
            $accessLevel = Tools::getSecurePOSTIntValue('accessLevelId');
            $arrivalTimestamp   = Tools::date2timestamp($arrivalDate);
            $departureTimestamp = (empty($departureDate)) ? 0 : Tools::date2timestamp($departureDate);

            if (empty($arrivalDate)) {
               $data = array();
               $data['statusMsg'] = "ERROR: arrivalDate must be set !";
            } else if (!empty($departureDate) && $arrivalTimestamp > $departureTimestamp) {
               $data = array();
               $data['statusMsg'] = "ERROR: arrivalDate > departureDate !";
            } else {
               $team = TeamCache::getInstance()->getTeam($teamid);
               $team->updateMember($displayed_userid, $arrivalTimestamp, $departureTimestamp, $accessLevel);

               // fetch values from DB (check & return real values)
               $data = $team->getTeamMemberData($displayed_userid);

               $data["statusMsg"] = "SUCCESS";
               if ($arrivalTimestamp   != $data['arrivalTimestamp'])   {$data["statusMsg"] = "ERROR: team member update failed ! (arrivalDate)";}
               if ($departureTimestamp != $data['departureTimestamp']) {$data["statusMsg"] = "ERROR: team member update failed ! (departureDate)";}
               if ($accessLevel        != $data['accessLevelId'])      {$data["statusMsg"] = "ERROR: team member update failed ! (accessLevel)";}
            }
         } catch (Exception $e) {
            $logger->error("EXCEPTION editTeamMember: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


