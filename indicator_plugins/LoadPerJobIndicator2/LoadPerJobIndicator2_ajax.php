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

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   
   $logger = Logger::getLogger("LoadPerJobIndicator2_ajax");

   if(isset($_GET['action'])) {
#echo "action = ".$_GET['action']."<br>";
      $smartyHelper = new SmartyHelper();
      if($_GET['action'] == 'getLoadPerJobIndicator2') {
         
         
         // TODO WARN: the cmdid should be retrieved from the session, 
         // it must be furnished by the ajax call !
         // 
         
         // WARN: how should the LoadPerJobIndicator know that the issue list is to be
         // retrieved from the Command ?! it could be MacroComd or Team or ???
         // can the user specific dashboard settings be used ?
         
         
         if(isset($_SESSION['cmdid'])) {
            $cmdid = $_SESSION['cmdid'];
            if (0 != $cmdid) {
               $cmd = CommandCache::getInstance()->getCommand($cmdid);
               // TODO check teamid ?
               // TODO check cmdid access rights for user

               $startTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("loadPerJob_startdate"));
               $endTimestamp = Tools::date2timestamp(Tools::getSecureGETStringValue("loadPerJob_enddate"));
               $params = array(
                  'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
                  'endTimestamp' => $endTimestamp,
                  'teamid' => $cmd->getTeamid()
               );
               
               // feed the PluginDataProvider
               $pluginDataProvider = PluginDataProvider::getInstance();
               $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $cmd->getIssueSelection());
               $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $cmd->getTeamid());
               //$pluginMgr->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
               //$pluginMgr->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);
                       
               $indicator = new LoadPerJobIndicator2($pluginDataProvider);
               $indicator->execute($cmd->getIssueSelection(), $params);
               $data = $indicator->getSmartyVariablesForAjax(); 

               // construct the html table
               foreach ($data as $smartyKey => $smartyVariable) {
                  $smartyHelper->assign($smartyKey, $smartyVariable);
                  #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
               }
               $html = $smartyHelper->fetch(LoadPerJobIndicator2::getSmartySubFilename());
               $data['loadPerJob_htmlTable'] = $html;

               // return html & chart data
               $jsonData = json_encode($data);
               echo $jsonData;

            } else {
               Tools::sendBadRequest("Command equals 0");
            }
         } else {
            Tools::sendBadRequest("Command not set");
         }

      } else {
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}

?>
