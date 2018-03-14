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



if (Tools::isConnectedUser() && null !== filter_input_array(INPUT_GET)) {
   $logger = Logger::getLogger("WBSExportAjax");
   $action = Tools::getSecureGETStringValue('action');
   $dashboardId = Tools::getSecureGETStringValue('dashboardId');

   $logger->error("dashboardId = $dashboardId");

   if (!isset($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }

   $indicator = new WBSExport($pluginDataProvider);
   $indicator->execute();
   $data = $indicator->getSmartyVariablesForAjax();

   $smartyHelper = new SmartyHelper();
   // construct the html table
   foreach ($data as $smartyKey => $smartyVariable) {
      $smartyHelper->assign($smartyKey, $smartyVariable);
      $logger->debug("key $smartyKey = ".var_export($smartyVariable, true));
   }
   $html = $smartyHelper->fetch(WBSExport::getSmartySubFilename());
   $data['wbsExportHTMLContent'] = $html;

   echo json_encode($data);
} else {
   Tools::sendUnauthorizedAccess();
}

function getStructure($wbsElement) {
   $subFolders = $wbsElement->getChildFolders();
   if (!empty($subFolders)) {
      foreach ($subFolders as $subFolder) {
         $subFolder->getIssues();
         getStructure($subFolder);
      }
   }
}

function buildRows($wbsElement) {
   static $rows = [];
   static $path = [];

   array_push($path, $wbsElement->getTitle());
   foreach ($wbsElement->getIssueList() as $issue) {
      array_push($rows, [
          'path' => $path,
          'issue' => $issue
      ]);
   }
   if ($wbsElement->hasSubFolders()) {
      foreach ($wbsElement->getSubFolders() as $subFolder) {

         buildRows($subFolder);
         array_pop($path);
      }
   }
   return $rows;
}
