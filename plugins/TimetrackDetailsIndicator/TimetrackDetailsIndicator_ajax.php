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

   $logger = Logger::getLogger("TimetrackDetailsIndicator_ajax");
   $action = Tools::getSecureGETStringValue('action', 'none');
   
   $smartyHelper = new SmartyHelper();
   if('getTimetrackDetailsIndicator' == $action) {

      if(isset($_SESSION[PluginDataProviderInterface::SESSION_ID])) {

         $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID]);
         if (FALSE != $pluginDataProvider) {

            $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("timetrackDetails_startdate"));
            $endTimestamp   = Tools::date2timestamp(Tools::getSecureGETStringValue("timetrackDetails_enddate"));
            $displayedTeam  = Tools::getSecureGETIntValue("timetrackDetails_displayedTeam", 0);
                    
            // update dataProvider
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

            $indicator = new TimetrackDetailsIndicator($pluginDataProvider);

            // override plugin settings with current attributes
            $indicator->setPluginSettings(array(
                TimetrackDetailsIndicator::OPTION_DISPLAYED_TEAM => $displayedTeam,
            ));

            $indicator->execute();
            $data = $indicator->getSmartyVariablesForAjax();

            // construct the html table
            foreach ($data as $smartyKey => $smartyVariable) {
               $smartyHelper->assign($smartyKey, $smartyVariable);
               #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
            }
            $html = $smartyHelper->fetch(TimetrackDetailsIndicator::getSmartySubFilename());
            $data['timetrackDetails_htmlContent'] = $html;

            // return html & chart data
            $jsonData = json_encode($data);
            echo $jsonData;

         } else {
            Tools::sendBadRequest("PluginDataProvider unserialize error");
         }
      } else {
         Tools::sendBadRequest("PluginDataProvider not set");
      }

   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}


