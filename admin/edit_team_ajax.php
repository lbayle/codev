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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

$logger = Logger::getLogger("editTeam_ajax");

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

if(Tools::isConnectedUser() &&
   (filter_input(INPUT_POST, 'action') || filter_input(INPUT_GET, 'action'))) {

   // INPUT_GET  for action getItemSelectionLists
   // INPUT_GET  for action processPostSelectionAction
   // INPUT_POST for action addUserDailyCost
   $action = filter_input(INPUT_POST, 'action');
   if (empty($action)) {
      $action = filter_input(INPUT_GET, 'action');
   }

   if(!empty($action)) {
      $smartyHelper = new SmartyHelper();

      if ('getItemSelectionLists' == $action) {
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

      } else if ('processPostSelectionAction' == $action) {
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
      } else if ('updateTeamADR' == $action) {
         // using POST
         $data = array();
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $teamADR = Tools::getSecurePOSTNumberValue('teamAverageDailyCost');
            $teamCurrency = Tools::getSecurePOSTStringValue('teamCurrency');

            // TOTO check ADR and currency values...
            
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $team->setAverageDailyCost($teamADR);
            $team->setTeamCurrency($teamCurrency);

            // return status & data
            $data["statusMsg"] = "SUCCESS";
            $data["teamCurrency"] = $teamCurrency;

         } catch (Exception $e) {
            $logger->error("EXCEPTION updateTeamADR: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('getUserArrivalDate' == $action) {
         // using POST
         $data = array();
         try {
            $userid = Tools::getSecurePOSTIntValue('udrUserid');
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');

            $team = TeamCache::getInstance()->getTeam($displayed_teamid);

            $arrivalTimestamp = $team->getMemberArrivalDate($userid);
            if ((NULL != $arrivalTimestamp) && ($arrivalTimestamp > 0)) {
               $data["statusMsg"] = "SUCCESS";
               $data["arrivalDate"] = date('Y-m-d', $arrivalTimestamp);
            } else {
               $user = UserCache::getInstance()->getUser($userid);
               $data["statusMsg"] = "ERROR: no arrival date found for userid $displayed_teamid";
               $logger->error("no arrival date found for user $userid on team $displayed_teamid");
            }
         } catch (Exception $e) {
            $logger->error("EXCEPTION getUserArrivalDate: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('addUserDailyCost' == $action) {
         // using POST
         $data = array();
         try {
            $userid = Tools::getSecurePOSTIntValue('udrUserid');
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $date = Tools::getSecurePOSTStringValue('udrStartDate');
            $userDailyCost = Tools::getSecurePOSTNumberValue('userDailyCost');
            $currency = Tools::getSecurePOSTStringValue('udrCurrency');
            #$udrDescription = Tools::getSecurePOSTStringValue('udrDescription');
            $timestamp = Tools::date2timestamp($date);

            // add UDC for user
            $user = UserCache::getInstance()->getUser($userid);
            if ($user->isTeamMember($displayed_teamid)) {
               $team = TeamCache::getInstance()->getTeam($displayed_teamid);

               $udrStruct = $team->existsUserDailyCost($userid, $timestamp);
               if (FALSE !== $udrStruct) {
                  $data["statusMsg"] = "ERROR: UDC already defined at ".date('Y-m-d', $udrStruct['timestamp']);
               } else {
                  $team->setUserDailyCost($userid, $timestamp, $userDailyCost, $currency); // $udrDescription

                  $udrStruct = $team->getUserDailyCost($userid, $timestamp);
                  if (NULL !== $udrStruct) {
                     $udrStruct['userid'] = $userid;
                     $udrStruct['userName'] = $user->getRealname();
                     $udrStruct['startDate'] = date('Y-m-d', $timestamp);
                     $udrStruct['description'] = ''; // $udrDescription
                     $data["statusMsg"] = "SUCCESS";
                     $data["udrStruct"] = $udrStruct;
                  } else {
                     $data["statusMsg"] = "ERROR: getUserDailyCost returned NULL";
                  }
               }
            }
         } catch (Exception $e) {
            $logger->error("EXCEPTION addUserDailyCost: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('deleteUDC' == $action) {
         // using POST
         $data = array();
         try {
            $udrId = Tools::getSecurePOSTIntValue('udrId');
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');

            // delete
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $team->deleteUserDailyCost($udrId);

            $data["statusMsg"] = "SUCCESS";
            $data["udrId"] = $udrId;

         } catch (Exception $e) {
            $logger->error("EXCEPTION addUserDailyCost: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;
      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

