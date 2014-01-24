<?php
require('../include/session.inc.php');

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
require('../path.inc.php');


function getAvailableTooltipFields($project) {

   $fields = array('project_id', 'category_id', 'status', 'summary', 
       'handler_id', 'priority', 'severity', 'target_version', 'version',
       'eta', 'fixed_in_version', 
       'codevtt_elapsed', 'codevtt_commands', 'codevtt_drift', 'codevtt_driftMgr',
       'mantis_tags');

   $availItemList = array();
   foreach ($fields as $field) {
      $availItemList[$field] = Tools::getTooltipFieldDisplayName($field);
   }

   // find all Mantis Issue fields
   $customFieldsList = $project->getCustomFieldsList();
   foreach ($customFieldsList as $id => $name) {
      $availItemList['custom_'.$id] = $name;
   }

   return $availItemList;
}






// ========== MAIN ===========

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {
   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();

      if ($_GET['action'] == 'getItemSelectionLists') {
         try {
            $implodedSrcRef = Tools::getSecureGETStringValue('itemSelection_srcRef');
            $srcRefList = Tools::doubleExplode(':', ',', $implodedSrcRef);
            $project = ProjectCache::getInstance()->getProject($srcRefList['projectid']);
            $teamid = $srcRefList['teamid'];
            #$team = TeamCache::getInstance()->getTeam($srcRefList['teamid']);

            $response = array();
            $response['itemSelection_srcRef'] = $implodedSrcRef;
            $response['itemSelection_dialogTitle'] = T_('Issue tooltips for project').' "'.$project->getName().'"';
            $response['itemSelection_dialogDesc'] = T_('Drag & drop the items to be displayed in the Issue tooltip');
            $response['itemSelection_availItemListLabel'] = T_('Available fields');
            $response['itemSelection_selectedItemListLabel'] = T_('Tooltip');

            $availItemList = getAvailableTooltipFields($project);

            $selectedItems = $project->getIssueTooltipFields($teamid);
            $selectedItemList = array();
            foreach ($selectedItems as $field) {
               $selectedItemList["$field"] = Tools::getTooltipFieldDisplayName($field);
               unset($availItemList["$field"]);
            }
            $response['availItemList'] =  $availItemList;
            $response['selectedItemList'] =  $selectedItemList;
            $response['selectedItems'] =  $selectedItems;

            // json encode
            $jsonResponse = Tools::array2json($response);
            echo "$jsonResponse";
         } catch (Exception $e) {
            Tools::sendBadRequest($e->getMessage());
         }

      } else if ($_GET['action'] == 'processPostSelectionAction') {
         try {
         
            $selectedTooltips = Tools::getSecureGETStringValue('selectedItems', NULL);
            if (strlen($selectedTooltips) == 0) {
            	$selectedTooltips = null;
            }

            $implodedSrcRef = Tools::getSecureGETStringValue('itemSelection_srcRef');
            $srcRefList = Tools::doubleExplode(':', ',', $implodedSrcRef);
            $projectid = $srcRefList['projectid'];
            $teamid = $srcRefList['teamid'];

            // save user preferances
            $tooltips = NULL;
            if ($selectedTooltips != NULL) {
           		$tooltips = explode(',', $selectedTooltips);
            }
            $project = ProjectCache::getInstance()->getProject($projectid);
            $project->setIssueTooltipFields($tooltips, $teamid);

            $formattedFields = array();
            if ($tooltips != NULL) {
	            foreach ($tooltips as $f) {
	               $formattedFields[] = Tools::getTooltipFieldDisplayName($f);
	            }
            }
            $strFields = implode(', ', $formattedFields);

            // return row to add/replace in issueTooltipsTable
            $response = array();
            $response['projectid'] = $projectid;
            $response['projectName'] = $project->getName();
            $response['tooltipFields'] = $strFields;

            // json encode
            $jsonResponse = Tools::array2json($response);
            echo "$jsonResponse";

         } catch (Exception $e) {
            Tools::sendBadRequest($e->getMessage());
         }
         
      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>