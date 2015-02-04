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

   $logger = Logger::getLogger("ImportIssueCsvBasic_ajax");
   $action = filter_input(INPUT_GET, 'action');
   $dashboardId = filter_input(INPUT_GET, 'dashboardId');

   if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }
   
   if ('uploadCsvFile' === $action) {
      $projectid = Tools::getSecureGETIntValue('projectid');

      $logger->error('ajax $_FILES='.  var_export($_FILES, true));
      
      // Note: no need to update dataProvider
      
      $smartyHelper = new SmartyHelper();
      try {
         $indicator = new ImportIssueCsvBasic($pluginDataProvider);
         $indicator->setPluginSettings(array(
             PluginDataProviderInterface::PARAM_PROJECT_ID => $projectid,
             ImportIssueCsvBasic::OPTION_CSV_FILENAME => ImportIssueCsvBasic::getSourceFile(),
         ));
         $data = $indicator->getSmartyVariablesForAjax();
         
      } catch (Exception $e) {
         $data = array('importIssueCsvBasic_errorMsg' => $e->getMessage());
      }
      
      // construct the html table
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
         #$logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
      }
      $html = $smartyHelper->fetch(ImportIssueCsvBasic::getSmartySubFilename());
      $data['importIssueCsvBasic_htmlContent'] = $html;

      // return data (just an integer value)
      $jsonData = json_encode($data);
      echo $jsonData;
   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}