<?php
require('../include/session.inc.php');

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

require('include/super_header.inc.php');

include_once('include/internal_config.inc.php');

require('management/command_tools.php');
require('management/commandset_tools.php');

require_once('smarty_tools.php');
require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("command_edit");

/**
 * @param Command $cmd
 */
function updateCmdInfo(Command $cmd) {
   $cmd->setTeamid(Tools::getSecurePOSTIntValue('teamid'));

   $formattedValue = Tools::getSecurePOSTStringValue('cmdName');
   $cmd->setName($formattedValue);

   $formattedValue = Tools::getSecurePOSTStringValue('cmdReference','');
   $cmd->setReference($formattedValue);

   $formattedValue = Tools::getSecurePOSTStringValue('cmdVersion','');
   $cmd->setVersion($formattedValue);

   $formattedValue = Tools::getSecurePOSTStringValue('cmdReporter','');
   $cmd->setReporter($formattedValue);

   $formattedValue = Tools::getSecurePOSTStringValue('cmdDesc','');
   $cmd->setDesc($formattedValue);

   $formattedValue = Tools::getSecurePOSTStringValue('cmdStartDate','');
   $cmd->setStartDate(Tools::date2timestamp($formattedValue));

   $formattedValue = Tools::getSecurePOSTStringValue('cmdDeadline');
   $cmd->setDeadline(Tools::date2timestamp($formattedValue));

   $cmd->setState(SmartyTools::checkNumericValue($_POST['cmdState'], true));
   $cmd->setBudgetDev(SmartyTools::checkNumericValue($_POST['cmdBudgetDev'], true));
   $cmd->setCost(SmartyTools::checkNumericValue($_POST['cmdCost'], true));
   $cmd->setBudgetMngt(SmartyTools::checkNumericValue($_POST['cmdBudgetMngt'], true));
   #$cmd->setBudgetGarantie(checkNumericValue($_POST['cmdBudgetGarantie'], true));
   #$cmd->setAverageDailyRate(checkNumericValue($_POST['cmdAverageDailyRate'], true));
}

/**
 * find CommandSets associated to all the teams i am member of.
 * (observed teams excluded))
 *
 * @param User $user
 * @return string[]
 */
function getParentCmdSetCandidates(User $user) {
   $parentCmdSets = array();

   $lTeamList = $user->getLeadedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $mTeamList = $user->getDevTeamList();
   $teamList = $mTeamList + $lTeamList + $managedTeamList;

   foreach ($teamList as $teamid => $name) {
      $team = TeamCache::getInstance()->getTeam($teamid);
      $cmdsetList = $team->getCommandSetList();

      foreach ($cmdsetList as $csid => $cmdset) {
         $parentCmdSets[$csid] = $cmdset->getName();
      }
   }

   return $parentCmdSets;
}

/**
 * returns all issues not already assigned to a command
 * and which project_id is defined in the team
 *
 * @param int $teamid
 * @return mixed[]
 */
function getChildIssuesCandidates($teamid) {
   $issueArray = array();

   // team projects except externalTasksProject & NoStats projects
   $projects = TeamCache::getInstance()->getTeam($teamid)->getProjects();
   $extProjId = InternalConfig::$externalTasksProject;
   unset($projects[$extProjId]);

   $formattedProjectList = implode (', ', array_keys($projects));

   $query  = "SELECT * FROM `mantis_bug_table` ".
             "WHERE project_id IN ($formattedProjectList) ".
             "AND 0 = is_issue_in_team_commands(id, $teamid) ".
             "ORDER BY id DESC";

   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $issue = IssueCache::getInstance()->getIssue($row->id, $row);
      $issueArray[$row->id] = array(
         //"mantisLink" => mantisIssueURL($issue->bugId, NULL, true),
         "bugid" => Tools::issueInfoURL(sprintf("%07d\n",   $issue->bugId)),
         //"bugid" => $issue->bugId,
         "extRef" => $issue->getTC(),
         "project" => $issue->getProjectName(),
         "target" => $issue->getTargetVersion(),
         "status" => $issue->getCurrentStatusName(),
         "summary" => $issue->summary
      );
   }

   return $issueArray;
}

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Command (edition)'));
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
   $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

   // use the cmdid set in the form, if not defined (first page call) use session cmdid
   $cmdid = 0;
   if(isset($_POST['cmdid'])) {
      $cmdid = $_POST['cmdid'];
   } else if(isset($_GET['cmdid'])) {
      $cmdid = $_GET['cmdid'];
   } else if(isset($_SESSION['cmdid'])) {
      $cmdid = $_SESSION['cmdid'];
   }
   $_SESSION['cmdid'] = $cmdid;

   // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
   // Note: It is used for createEnv but will be overridden by the displayed command's commandsetid.
   $commandsetid = 0;
   if(isset($_POST['commandsetid'])) {
      $commandsetid = $_POST['commandsetid'];
   } else if(isset($_SESSION['commandsetid'])) {
      $commandsetid = $_SESSION['commandsetid'];
   }
   $_SESSION['commandsetid'] = $commandsetid;

   $action = isset($_POST['action']) ? $_POST['action'] : '';

   if (0 == $cmdid) {
      // -------- CREATE CMD -------

      // ------ Actions
      if ("createCmd" == $action) {
         $teamid = Tools::getSecurePOSTIntValue('teamid');
         $_SESSION['teamid'] = $teamid;
         $logger->debug("create new Command for team $teamid<br>");

         $cmdName = Tools::getSecurePOSTStringValue('cmdName');

         try {
            $cmdid = Command::create($cmdName, $teamid);
            $smartyHelper->assign('commandid', $cmdid);

            $cmd = CommandCache::getInstance()->getCommand($cmdid);

            // set all fields
            updateCmdInfo($cmd);
         } catch(Exception $e) {
            // Smartify
            echo "Can't create the command because the command name is already used";
         }
      }

      // ------ Display Empty Command Form
      // Note: this will be overridden by the 'update' section if the 'createCmd' action has been called.
      $smartyHelper->assign('cmdInfoFormBtText', 'Create');
      $smartyHelper->assign('cmdInfoFormAction', 'createCmd');

      $smartyHelper->assign('cmdStateList', getServiceContractStateList());
      $smartyHelper->assign('cmdState', Command::$stateNames[0]);

      $smartyHelper->assign('commandsetid', $commandsetid);
      $smartyHelper->assign('commandsets', getCommandSets($teamid, $commandsetid));

      $smartyHelper->assign('cmdName', "New command");
   }

   if (0 != $cmdid) {
      // -------- UPDATE CMD -------

      $cmd = CommandCache::getInstance()->getCommand($cmdid);

      // ------ Actions
      if ("addCmdIssue" == $action) {
         $bugid = Tools::getSecurePOSTIntValue('bugid');
         $logger->debug("add Issue $bugid on Command $cmdid team $teamid");

         $cmd->addIssue($bugid, true); // DBonly
      } else if ("addCmdIssueList" == $action) {
         $bugid_list = $_POST['bugid_list'];

         $logger->debug("add Issues ($bugid_list) on Command $cmdid team $teamid");

         $bugids = explode(',', $bugid_list);

         //$cmd->addIssueList($bugids, true); // DBonly
         foreach ($bugids as $id) {
            if (is_numeric(trim($id))) {
               $cmd->addIssue(intval($id), true); // DBonly
            } else {
               $logger->error('Attempt to set non_numeric value ('.$id.')');
               die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
            }
         }
      } else if ("removeCmdIssue" == $action) {
         $cmd->removeIssue($_POST['bugid']);
      } else if ("addToCmdSet" == $action) {
         $commandsetid = $_POST['commandsetid'];
         $logger->debug("add Command $cmdid to CommandSet $commandsetid");

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
         $cmdset->addCommand($cmdid, Command::type_general);

      } else if ("removeFromCmdSet" == $action) {
         $commandsetid = $_POST['commandsetid'];
         $logger->debug("remove Command $cmdid from CommandSet $commandsetid");

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
         $cmdset->removeCommand($cmdid);
      } else if ("updateCmdInfo" == $action) {
         $teamid = SmartyTools::checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;

         updateCmdInfo($cmd);
      } else if ("deleteCommand" == $action) {
         $logger->debug("delete Command $cmdid");
         Command::delete($cmdid);
         unset($_SESSION['cmdid']);
         header('Location:command_info.php');
      }

      // ------ Display Command
      $smartyHelper->assign('commandid', $cmdid);
      $smartyHelper->assign('cmdInfoFormBtText', 'Save');
      $smartyHelper->assign('cmdInfoFormAction', 'updateCmdInfo');
      $smartyHelper->assign('isAddIssueForm', true);

      $parentCmdSets = getParentCmdSetCandidates($session_user);
      $smartyHelper->assign('parentCmdSetCandidates', $parentCmdSets);
      $smartyHelper->assign('isAddCmdSetForm', true);

      displayCommand($smartyHelper, $cmd);

      // multiple selection dialogBox
      $availableIssueList = getChildIssuesCandidates($teamid);
      $smartyHelper->assign('availableIssueList', $availableIssueList);
      $smartyHelper->assign('sendSelectIssuesActionName', "addCmdIssueList");
      $smartyHelper->assign('selectIssuesBoxTitle', T_('Add tasks to Command').' \''.$cmd->getName().'\'');
      $smartyHelper->assign('openDialogLabel', T_("Add multiple tasks"));
      $smartyHelper->assign('selectIssuesDoneBtText', T_("Add selection"));
      $smartyHelper->assign('selectIssuesBoxDesc', T_("Note: Tasks already assigned to a Command are not displayed."));
      $smartyHelper->assign('selectIssuesConfirmMsg', T_("Add the selected issues to the Command ?"));
   }
   
}

$smartyHelper->displayTemplate($mantisURL);

?>
