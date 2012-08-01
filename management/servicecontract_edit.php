<?php

include_once('../include/session.inc.php');

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

require('../path.inc.php');

require('super_header.inc.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "servicecontract.class.php";

require_once "servicecontract_tools.php";

include_once "smarty_tools.php";

$logger = Logger::getLogger("servicecontract_edit");


/**
 * Action on 'Save' button
 *
 * @param ServiceContract $contract
 */
function updateServiceContractInfo($contract) {

   // security check
   $contract->setTeamid(checkNumericValue($_POST['teamid']));

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractName']);
   $contract->setName($formattedValue);

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractReference']);
   $contract->setReference($formattedValue);

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractVersion']);
   $contract->setVersion($formattedValue);

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractReporter']);
   $contract->setReporter($formattedValue);

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractDesc']);
   $contract->setDesc($formattedValue);

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractStartDate']);
   $contract->setStartDate(date2timestamp($formattedValue));

   $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($_POST['servicecontractEndDate']);
   $contract->setEndDate(date2timestamp($formattedValue));

   $contract->setState(checkNumericValue($_POST['servicecontractState'], true));

}

/**
 * list the Commands that can be added to this ServiceContract.
 *
 * This depends on user's teams
 *
 *
 */
function getCmdSetCandidates($user) {
   $cmdsetCandidates = array();

   $lTeamList = $user->getLeadedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $mTeamList = $user->getDevTeamList();
   $teamList = $mTeamList + $lTeamList + $managedTeamList;

   foreach ($teamList as $teamid => $name) {

      $team = TeamCache::getInstance()->getTeam($teamid);
      $commandsetList = $team->getCommandSetList();

      foreach ($commandsetList as $cid => $cmdset) {

         // TODO remove CmdSets already in this contract.

         $cmdsetCandidates[$cid] = $cmdset->getName();
      }
   }
   asort($cmdsetCandidates);
   
   return $cmdsetCandidates;
}

/**
 * list the Sidetasks Projects that can be added to this ServiceContract.
 *
 * This depends on ServiceContract's team
 * 
 */
function getProjectCandidates($servicecontractid) {

   $candidates = array();

   $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);
   $team = TeamCache::getInstance()->getTeam($contract->getTeamid());

   $projList = $team->getProjects();

   foreach ($projList as $projectid => $name) {
      if ($team->isSideTasksProject($projectid)) {
         $candidates[$projectid] = $name;
      }
   }
   return $candidates;
}



// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('ServiceContract (edit)'));
$smartyHelper->assign('activeGlobalMenuItem', 'Management');

if (isset($_SESSION['userid'])) {

   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if (isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if (isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }
   $_SESSION['teamid'] = $teamid;

   // TODO check if $teamid is set and != 0

   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));


   // use the servicecontractid set in the form, if not defined (first page call) use session servicecontractid
   $servicecontractid = 0;
   if(isset($_POST['servicecontractid'])) {
      $servicecontractid = $_POST['servicecontractid'];
   } else if(isset($_GET['servicecontractid'])) {
      $servicecontractid = $_GET['servicecontractid'];
   } else if(isset($_SESSION['servicecontractid'])) {
      $servicecontractid = $_SESSION['servicecontractid'];
   }
   $_SESSION['servicecontractid'] = $servicecontractid;


   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------

   $smartyHelper->assign('servicecontractid', $servicecontractid);
   $smartyHelper->assign('contracts', getServiceContracts($teamid, $servicecontractid));


   if (0 == $servicecontractid) {

      // -------- CREATE CMDSET -------

      // ------ Actions
      if ("createContract" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;
         $logger->debug("create new ServiceContract for team $teamid<br>");

         $contractName = SqlWrapper::getInstance()->sql_real_escape_string($_POST['contractName']);

         $servicecontractid = ServiceContract::create($contractName, $teamid);
         $smartyHelper->assign('servicecontractid', $servicecontractid);

         $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

         // set all fields
         updateServiceContractInfo($contract);

      }

      // ------ Display Empty Command Form
      // Note: this will be overridden by the 'update' section if the 'createCommandset' action has been called.
      $smartyHelper->assign('contractInfoFormBtText', T_('Create'));
      $smartyHelper->assign('contractInfoFormAction', 'createContract');
   }


   if (0 != $servicecontractid) {
      // -------- UPDATE CMDSET -------

      $contract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

      // ------ Actions

      if ("addCommandSet" == $action) {

         # TODO
         $commandsetid = checkNumericValue($_POST['commandsetid']);

         if (0 == $commandsetid) {
            #$_SESSION['commandsetid'] = 0;
            header('Location:command_edit.php?commandsetid=0');
         } else {
            $contract->addCommandSet($commandsetid, CommandSet::type_general);
         }

      } else if ("removeCmdSet" == $action) {

         $commandsetid = checkNumericValue($_POST['commandsetid']);
         $contract->removeCommandSet($commandsetid);
         
      } else if ("updateContractInfo" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;

         updateServiceContractInfo($contract);
      } else if ("addProject" == $action) {

         # TODO
         $projectid = checkNumericValue($_POST['projectid']);

         if (0 != $projectid) {
            $contract->addSidetaskProject($projectid, Project::type_sideTaskProject);
         }

      } else if ("removeProject" == $action) {

         $projectid = checkNumericValue($_POST['projectid']);
         $contract->removeSidetaskProject($projectid);

      } else if ("deleteContract" == $action) {

         $logger->debug("delete ServiceContract servicecontractid (".$contract->getName().")");
         ServiceContract::delete($servicecontractid);
         unset($_SESSION['servicecontractid']);
         header('Location:servicecontract_info.php');
      }

      // ------ Display ServiceContract

      $smartyHelper->assign('servicecontractid', $servicecontractid);
      $smartyHelper->assign('contractInfoFormBtText', T_('Save'));
      $smartyHelper->assign('contractInfoFormAction', 'updateContractInfo');

      $commandsetCandidates = getCmdSetCandidates($session_user);
      $smartyHelper->assign('commandsetCandidates', $commandsetCandidates);
      $smartyHelper->assign('isAddCmdSetForm', true);

      $projectCandidates = getProjectCandidates($servicecontractid);
      $smartyHelper->assign('projectCandidates', $projectCandidates);
      $smartyHelper->assign('isAddProjectForm', true);

      displayServiceContract($smartyHelper, $contract);

   }
   
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>
