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
   $logger = Logger::getLogger("ImportIssueCsv_ajax");
   $action = filter_input(INPUT_POST, 'action');
   $dashboardId = filter_input(INPUT_POST, 'dashboardId');

   if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }
   
   if ('uploadCsvFile' === $action) { // Upload file
      $projectid = Tools::getSecurePOSTIntValue('projectId');
      
      $smartyHelper = new SmartyHelper();
      try {
         $indicator = new ImportIssueCsv($pluginDataProvider);
         
         $indicator->setPluginSettings(array(
             PluginDataProviderInterface::PARAM_PROJECT_ID => $projectid,
             ImportIssueCsv::OPTION_CSV_FILENAME => ImportIssueCsv::getSourceFile(),
         ));
         $indicator->execute();
         $data = $indicator->getSmartyVariablesForAjax();
         
      } catch (Exception $e) {
         $data = array('importIssueCsv_errorMsg' => $e->getMessage());
      }
      
      
      // construct the html table
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
      $html = $smartyHelper->fetch(ImportIssueCsv::getSmartySubFilename());
      $data['importIssueCsv_htmlContent'] = $html;
      
      // set JS libraries that must be load
      $data['importIssueCsv_jsFiles'] = ImportIssueCsv::getJsFiles();
      $data['importIssueCsv_cssFiles'] = ImportIssueCsv::getCssFiles();

      // return data (just an integer value)
      $jsonData = json_encode($data);
      echo $jsonData;

   } else if ("importRow" == $action) { // Import
      $projectid = isset($_POST['projectid']) ? $_POST['projectid'] : '0';

      $extRef = isset($_POST['extRef']) ? $_POST['extRef'] : NULL;
      $summary = isset($_POST['summary']) ? $_POST['summary'] : NULL;
      $mgrEffortEstim = isset($_POST['mgrEffortEstim']) ? $_POST['mgrEffortEstim'] : NULL;
      $effortEstim = isset($_POST['effortEstim']) ? $_POST['effortEstim'] : NULL;
      $type = isset($_POST['type']) ? $_POST['type'] : NULL;
      $commandid = isset($_POST['commandid']) ? $_POST['commandid'] : NULL;
      $categoryid = isset($_POST['categoryid']) ? $_POST['categoryid'] : NULL;
      $targetversionid = isset($_POST['targetversionid']) ? $_POST['targetversionid'] : NULL;
      $userid = isset($_POST['userid']) ? $_POST['userid'] : NULL;
      $description = (isset($_POST['description']) && '' != $_POST['description']) ? $_POST['description'] : "...";
      $formatedDeadline = isset($_POST['deadline']) ? $_POST['deadline'] : NULL;
      $wbsTree = isset($_POST['wbsTree']) ? $_POST['wbsTree'] : NULL;
      
      $proj = ProjectCache::getInstance()->getProject($projectid);
      $bugid = $proj->addIssue($categoryid, $summary, $description, Constants::$status_new);

      $issue = IssueCache::getInstance()->getIssue($bugid);

      if ($extRef)          { $issue->setExternalRef($extRef); }
      if ($mgrEffortEstim)  { $issue->setMgrEffortEstim($mgrEffortEstim); }
      if ($effortEstim)     { $issue->setEffortEstim($effortEstim); }
      if ($targetversionid) { $issue->setTargetVersion($targetversionid); }
      if ($userid)          { $issue->setHandler($userid); }
      if ($type)            { $issue->setType($type); }
      if ($formatedDeadline) {
         $timestamp = Tools::date2timestamp($formatedDeadline);
         $issue->setDeadline($timestamp);
      }

      if ($commandid) {
         $command = CommandCache::getInstance()->getCommand($commandid);
         $wbsRootId = $command->getWbsid();
         $parentId = $wbsRootId;
         try {
            if ($wbsTree) {
               $titles = explode('/', $wbsTree);
               foreach ($titles as $title) {
                  if (0 != strlen($title)) {
                     $subFolderId = WBSElement::getIdByTitle($title, $wbsRootId, $parentId, TRUE);
                     if (NULL !== $subFolderId) {
                        $parentId = $subFolderId;
                     } else {
                        // subFolder does not exist, create it.
                        $newSubFolder = new WBSElement(null, $wbsRootId, null, $parentId, NULL, $title);
                        $parentId = $newSubFolder->getId();
                     }
                  }
               }
            }
         } catch (Exception $e) {
            $logger->error('ImportIssueCsv.importRow: Command '.$command->getId().', Could not create wbsTree: '.$wbsTree);
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());

            // wbs tree could not be created, add issue at root
            $parentId = $wbsRootId;
         }
         $command->addIssue($bugid, true, $parentId); // DBonly
      }

      // RETURN VALUE
      echo Tools::mantisIssueURL($bugid, NULL, TRUE)." ".Tools::issueInfoURL($bugid, NULL);
   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   Tools::sendUnauthorizedAccess();
}