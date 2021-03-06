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
   // INPUT_POST for action addTeamAdmin
   $action = filter_input(INPUT_POST, 'action');
   if (empty($action)) {
      $action = filter_input(INPUT_GET, 'action');
   }

/*   // TODO check user is agranted to do that !
    $sessionUserId = $_SESSION['userid'];
    $sessionUser = UserCache::getInstance()->getUser($sessionUserId);
    $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
   if (!$sessionUser->isTeamLeader($displayed_teamid) &&
       !$sessionUser->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
      $data = array();
      $data["statusMsg"] = "ERROR : You must be a team administrator !";
      $jsonData = json_encode($data);
      echo $jsonData;
      $action = NULL;
   }
*/

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
            $projectId = $srcRefList['projectid'];
            $teamid = $srcRefList['teamid'];

            // save user preferances
            $tooltips = NULL;
            if ($selectedTooltips != NULL) {
           		$tooltips = explode(',', $selectedTooltips);
            }
            $project = ProjectCache::getInstance()->getProject($projectId);
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
            $response['projectid'] = $projectId;
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

      } else if ('addTeamAdmin' == $action) {
         // using POST
         $data = array();
         try {
            $adminId = Tools::getSecurePOSTIntValue('adminId');
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');

            $admin = UserCache::getInstance()->getUser($adminId);
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $team->addAdministrator($adminId);
            $data["adminId"] = $adminId;
            $data["adminName"] = $admin->getRealname();
            $data["statusMsg"] = "SUCCESS";

         } catch (Exception $e) {
            $logger->error("EXCEPTION addTeamAdmin: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('deleteTeamAdmin' == $action) {
         // using POST
         $data = array();
         try {
            $adminId = Tools::getSecurePOSTIntValue('adminId');
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');

            $admin = UserCache::getInstance()->getUser($adminId);

            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $team->removeAdministrator($adminId);
            $data["adminId"] = $adminId;
            $data["adminName"] = $admin->getRealname();
            $data["statusMsg"] = "SUCCESS";

         } catch (Exception $e) {
            $logger->error("EXCEPTION deleteTeamAdmin: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }

         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('editTeamMember' == $action) {
         // using POST
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $userId = Tools::getSecurePOSTIntValue('userId');
            $arrivalDate = Tools::getSecurePOSTStringValue('arrivalDate');
            $departureDate = Tools::getSecurePOSTStringValue('departureDate');
            $accessLevel = Tools::getSecurePOSTIntValue('accessLevelId');
            $arrivalTimestamp   = Tools::date2timestamp($arrivalDate);
            $departureTimestamp = (empty($departureDate)) ? 0 : Tools::date2timestamp($departureDate);

            $userGroup = Tools::getSecurePOSTStringValue('userGroup', '');
            if ('undefined' == $userGroup) { $userGroup = NULL; }

            if (empty($arrivalDate)) {
               $data = array();
               $data['statusMsg'] = "ERROR: arrivalDate must be set !";
            } else if (!empty($departureDate) && $arrivalTimestamp > $departureTimestamp) {
               $data = array();
               $data['statusMsg'] = "ERROR: arrivalDate > departureDate !";
            } else {
               $team = TeamCache::getInstance()->getTeam($displayed_teamid);

               $team->updateMember($userId, $arrivalTimestamp, $departureTimestamp, $accessLevel, $userGroup);

               // fetch values from DB (check & return real values)
               $data = $team->getTeamMemberData($userId);
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
      } else if ('removeTeamMember' == $action) {
         // using POST
         $data = array();
         try {
            $sql = AdodbWrapper::getInstance();
            //$displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $deleteRowId = Tools::getSecurePOSTIntValue('removeMemberRowId');
            $query = "DELETE FROM codev_team_user_table WHERE id = ".$sql->db_param();
            $sql->sql_query($query, array($deleteRowId));

            // TODO check if realy deleted ?
            $data['statusMsg'] = 'SUCCESS';
            $data['rowId'] = $deleteRowId;

         } catch (Exception $e) {
            $logger->error("EXCEPTION editTeamMember: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            Tools::sendBadRequest($e->getMessage());
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('getPrjJobAsso' == $action) {
         $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
         $projectId = Tools::getSecurePOSTIntValue('projectId');

         $team = TeamCache::getInstance()->getTeam($displayed_teamid);
         $project = ProjectCache::getInstance()->getProject($projectId);
         $teamProjTypes = $team->getProjectsType();
         $projectJobs = $project->getJobList($teamProjTypes[$projectId], $displayed_teamid);

         // get complete job list compatible with project type
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_job_table";
         $result = $sql->sql_query($query);

         // if Regular project or sideTasksProject
         // - display all jobs
         // if externalTasksProject : only 'N/A" job, disable dialogBox

         $jobList = array();
         while($row = $sql->fetchObject($result)) {
            $j = new Job($row->id, $row->name, $row->type, $row->color);
            $jobList[$row->id] = array(
               'id' => $row->id,
               'name' => $row->name,
               'type' => Job::$typeNames[$row->type],
               'checked' => array_key_exists($row->id, $projectJobs),
               // 'disabled' => false, // no reason yet to hide a job
            );
         }

         $data['statusMsg'] = 'SUCCESS';
         $data['jobList'] = $jobList;

         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('savePrjJobAsso' == $action) {
         $data = array();
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $projectId = Tools::getSecurePOSTIntValue('projectId');
            $jobListStr = Tools::getSecurePOSTStringValue('jobListStr');
            $jobList = json_decode(stripslashes($jobListStr), true);

            $nbJobs = 0;
            foreach ($jobList as $job_id => $ischecked) {
               if ($ischecked) { $nbJobs += 1; }
            }

            if (0 == $nbJobs) {
               $data['statusMsg'] = T_('ERROR: joblist cannot be empty !');
            } else {
               // delete all previous job associations
               $sql = AdodbWrapper::getInstance();
               $query = "DELETE FROM codev_project_job_table ".
                        " WHERE project_id = ".$sql->db_param().
                        " AND team_id = ".$sql->db_param();
               $sql->sql_query($query, array($projectId, $displayed_teamid));

               // set new project-job associations
               foreach ($jobList as $job_id => $ischecked) {
                  if ($ischecked) {
                     Jobs::addJobProjectAssociation($projectId, $job_id, $displayed_teamid);
                  }
               }
               $data['statusMsg'] = 'SUCCESS';
            }
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $project = ProjectCache::getInstance()->getProject($projectId);
            $teamProjTypes = $team->getProjectsType();
            $projectJobs = $project->getJobList($teamProjTypes[$projectId], $displayed_teamid);

            $jobNameListStr = implode(', ', $projectJobs);
            $data['projectId'] = $projectId;
            $data['jobNameListStr'] = $jobNameListStr;

         } catch (Exception $e) {
            $logger->error("EXCEPTION savePrjJobAsso: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $data['statusMsg'] = 'ERROR';
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('updateTeamInfo' == $action) {
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $teamName = trim(Tools::getSecurePOSTStringValue('teamName'));
            $isTeamEnabled = (0 == Tools::getSecurePOSTIntValue("isTeamEnabled")) ? false : true;
            $formatedDate = Tools::getSecurePOSTStringValue("date_createTeam");
            $date_create = Tools::date2timestamp($formatedDate);

            $errMsg = '';
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);

            if ('' !== $teamName) {
               $team->setName($teamName);
            } else {
               $errMsg .= T_("Invalid team name").', ';
            }

            if(!$team->setEnabled($isTeamEnabled)) {
               $errMsg .= T_("Couldn't enable/disable team").', ';
            }
            if(!$team->setCreationDate($date_create)) {
               $errMsg .= T_("Couldn't update the creation date");
            }
            if ('' === $errMsg) {
               $data['statusMsg'] = 'SUCCESS';
            } else {
               $data['statusMsg'] = "ERROR: ".$errMsg;
            }
         } catch (Exception $e) {
            $logger->error("EXCEPTION setTeamName: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $data['statusMsg'] = 'ERROR: '.$e->getMessage();
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('getForbidenStatusList' == $action) {
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $projectId = Tools::getSecurePOSTIntValue('projectId');
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);

            $statusNames = Constants::$statusNames;
            $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($projectId);

            $prjStatusList = array();
            foreach ($statusNames as $sid => $sName) {
               $prjStatusList[$sid] = array(
                  'id' => $sid,
                  'name' => "$sName",
                  'checked' => array_key_exists($sid, $ttForbidenStatusList),
                  'disabled' => 0, //(Constants::$status_new == $sid) ? 1 : 0,
               );
               // Note: tasks with forbidenStatus will not be displayed in taskList for timetracking.
               // - on regular projects, 'new' tasks must be displayed (CodevTT will force user to change status on addTimetrack)
               // - but you may want to hide 'new' if users should only work on 'assigned' or 'decided' tasks
               // - sidetasks & externalTasks should always be 'closed', so 'new' status don't need to be displayed
            }
            $data['statusMsg'] = 'SUCCESS';
            $data['prjStatusList'] = $prjStatusList;

         } catch (Exception $e) {
            $logger->error("EXCEPTION getForbidenStatusList: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $data['statusMsg'] = 'ERROR: '.$e->getMessage();
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('saveForbidenStatusList' == $action) {
         $data = array();
         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $projectId = Tools::getSecurePOSTIntValue('projectId');
            $prjStatusListStr = Tools::getSecurePOSTStringValue('prjStatusListStr');
            $prjStatusList = json_decode(stripslashes($prjStatusListStr), true);

            // save new values
            foreach($prjStatusList as $sid => $isChecked) {
               if ('0' == $isChecked) {
                  unset($prjStatusList[$sid]);
               }
            }
            $strForbidenStatusList = implode(',', array_keys($prjStatusList));
            Config::setValue(Config::id_timetrackingForbidenStatusList, $strForbidenStatusList,
               Config::configType_string, NULL, $projectId, 0, $displayed_teamid, 0, 0, 0);

            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $statusNameListStr = implode(', ', $team->getTimetrackingForbidenStatusList($projectId));
            $data['statusMsg'] = 'SUCCESS';
            $data['projectId'] = $projectId;
            $data['prjStatusNameListStr'] = $statusNameListStr;

         } catch (Exception $e) {
            $logger->error("EXCEPTION saveForbidenStatusList: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $data['statusMsg'] = 'ERROR: '.$e->getMessage();
         }
         // return status & data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else if ('uploadUserGroupsCsvFile' === $action) {

         $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
         $team = TeamCache::getInstance()->getTeam($displayed_teamid);

         $filename = null;
         $statusMsg = 'SUCCESS';
         $userDataArray = array();

         if (isset($_FILES['uploaded_csv'])) {
            try {
               // Get file datas
               $filename = getSourceFile();
               $userGroups = getUserGroupsFromCSV($filename);
               $userDataArray = getUserDataArray($displayed_teamid, $userGroups);

            } catch (Exception $ex) {
               $statusMsg = $ex->getMessage();
            }
         }

         $data = array(
            'statusMsg' => $statusMsg,
            'userGroups_userDataArray' => $userDataArray,
         );

         // return data
         $jsonData = json_encode($data);
         echo $jsonData;
      } else if ('validateNewUserGroups' === $action) {

         try {
            $displayed_teamid = Tools::getSecurePOSTIntValue('displayed_teamid');
            $userGroupsJsonStr = Tools::getSecurePOSTStringValue('userGroups');
            $team = TeamCache::getInstance()->getTeam($displayed_teamid);
            $userGroups = json_decode(stripslashes($userGroupsJsonStr), true);

            $statusMsg = 'SUCCESS';
            $team->setUserGroups($userGroups);

            $data = array(
               'statusMsg' => $statusMsg,
               'userGroups' => $team->getUserGroups()
            );
         } catch (Exception $e) {
            $logger->error("EXCEPTION $action: ".$e->getMessage());
            $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $data['statusMsg'] = 'ERROR: '.$e->getMessage();
         }
         // return data
         $jsonData = json_encode($data);
         echo $jsonData;

      } else {
         $logger->error("Unknown action: $action");
         Tools::sendNotFoundAccess();
      }

   }
}
else {
   Tools::sendUnauthorizedAccess();
}



/**
 *
 * @return string the filename of the uploaded CSV file.
 * @throws Exception
 */
function getSourceFile() {

   global $logger;

    if (isset($_FILES['uploaded_csv'])) {
        $filename = $_FILES['uploaded_csv']['name'];
        $tmpFilename = $_FILES['uploaded_csv']['tmp_name'];

        $err_msg = NULL;

        if ($_FILES['uploaded_csv']['error']) {
            $err_id = $_FILES['uploaded_csv']['error'];
            switch ($err_id) {
                case 1:
                    $err_msg = "UPLOAD_ERR_INI_SIZE ($err_id) on file : " . $filename;
                    //echo"Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !";
                    break;
                case 2:
                    $err_msg = "UPLOAD_ERR_FORM_SIZE ($err_id) on file : " . $filename;
                    //echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
                    break;
                case 3:
                    $err_msg = "UPLOAD_ERR_PARTIAL ($err_id) on file : " . $filename;
                    //echo "L'envoi du fichier a été interrompu pendant le transfert !";
                    break;
                case 4:
                    $err_msg = "UPLOAD_ERR_NO_FILE ($err_id) on file : " . $filename;
                    //echo "Le fichier que vous avez envoyé a une taille nulle !";
                    break;
            }
            $logger->error($err_msg);
        } else {
            // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
            // ce qui signifie qu'il n'y a eu aucune erreur
        }

        $extensions = array('.csv', '.CSV');
        $extension = strrchr($filename, '.');
        if (!in_array($extension, $extensions)) {
            $err_msg = T_('Please upload files with the following extension: ') . implode(', ', $extensions);
            $logger->error($err_msg);
        }
    } else {
        $err_msg = "no file to upload.";
        $logger->error($err_msg);
        $logger->error('$_FILES=' . var_export($_FILES, true));
    }
    if (NULL !== $err_msg) {
        throw new Exception($err_msg);
    }
    return $tmpFilename;
}


   /**
   * @param string $filename
   * @param string $delimiter
   * @param string $enclosure
   * @param string $escape
   * @return mixed[]
   */
 function getUserGroupsFromCSV($filename, $delimiter = ';', $enclosure = '"', $escape = '"') {

    global $logger;

    $file = new SplFileObject($filename);
    $file->setFlags(SplFileObject::READ_CSV);
    $file->setCsvControl($delimiter, $enclosure, $escape);

    $newUserGroups = array();
    $row = 0;
    while (!$file->eof()) {
       while ($data = $file->fgetcsv($delimiter, $enclosure)) {
          $row++;
          if (1 == $row) {
             continue;
          } // skip column names
          // two columns: username ; groupName
          if ('' != $data[0] && '' != $data[1]) {

             $username = $data[0];
             $groupName = $data[1];

             if (!User::exists($username)) {
                $logger->error("User '$username' not found in Mantis DB !");
             } else {
                $userid = User::getUserId($username);
                $newUserGroups[$userid] = $groupName;
             }
          } else {
             $logger->error("Row $row: Missing fields ['$data[0]','$data[1]']");
          }
       }
    }
    //self::$logger->error($newUserGroups);
    return $newUserGroups;
 }

function getUserDataArray($teamid, $userGroups) {

   //global $logger;
   $userDataArray = array();

   foreach ($userGroups as $uid => $groupName) {
      $user = UserCache::getInstance()->getUser($uid);
      $userDataArray[$uid] = array(
        'userId' => $uid,
        'userName' => $user->getName(),
        'userRealname' => $user->getRealname(),
        'groupName' => $groupName,
        'color' => ($user->isTeamMember($teamid)) ? 'blue' : 'darkgrey',
        'message' => '',
      );
   }
   // add team members not found in userGroups
   $team = TeamCache::getInstance()->getTeam($teamid);
   $teamMembers = $team->getActiveMembers();
   foreach ($teamMembers as $uid => $uname) {
      if (!array_key_exists($uid, $userGroups)) {
         $user = UserCache::getInstance()->getUser($uid);
         $userGroups[$uid] = '--undefined--';
         $userDataArray[$uid] = array(
            'userId' => $uid,
            'userName' => $uname,
            'userRealname' => $user->getRealname(),
            'groupName' => '--undefined--',
            'color' => 'red',
            'message' => T_("No group defined for team member"),
          );
      }
   }
   // Sort the multidimensional array
   uasort($userDataArray, "f_customUserGroupSort");
   return $userDataArray;
}

/**
 * Sort blue/red/grey then by name
 */
function f_customUserGroupSort($a,$b) {

   if ($a['color'] == $b['color']) {
      return $a['userName'] > $b['userName'];
   }
   $keyVal = array('blue' => 0, 'red' => 1, 'darkgrey' => 2);
   return $keyVal[$a['color']] > $keyVal[$b['color']];
}
