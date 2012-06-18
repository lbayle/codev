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
include_once "commandset.class.php";
include_once "command.class.php";

include_once "smarty_tools.php";

include "command_tools.php";
include "commandset_tools.php";

$logger = Logger::getLogger("command_edit");

/**
 *
 * @param Command $cmd
 */
function updateCmdInfo($cmd) {

   // security check
   $cmd->setTeamid(checkNumericValue($_POST['teamid']));

   $formattedValue = mysql_real_escape_string($_POST['cmdName']);
   $cmd->setName($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdReference']);
   $cmd->setReference($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdVersion']);
   $cmd->setVersion($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdReporter']);
   $cmd->setReporter($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdDesc']);
   $cmd->setDesc($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdStartDate']);
   $cmd->setStartDate(date2timestamp($formattedValue));

   $formattedValue = mysql_real_escape_string($_POST['cmdDeadline']);
   $cmd->setDeadline(date2timestamp($formattedValue));


   $cmd->setState(checkNumericValue($_POST['cmdState'], true));
   $cmd->setBudgetDev(checkNumericValue($_POST['cmdBudgetDev'], true));
   $cmd->setCost(checkNumericValue($_POST['cmdCost'], true));
   $cmd->setBudgetMngt(checkNumericValue($_POST['cmdBudgetMngt'], true));
   #$cmd->setBudgetGarantie(checkNumericValue($_POST['cmdBudgetGarantie'], true));
   #$cmd->setAverageDailyRate(checkNumericValue($_POST['cmdAverageDailyRate'], true));


}

/**
 * find CommandSets associated to all the teams i am member of.
 * (observed teams excluded))
 *
 * @param int $userid
 * @return array
 */
function getParentCmdSetCandidates($user) {
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

// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Command (edition)'));

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

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;
         $logger->debug("create new Command for team $teamid<br>");

         $cmdName = mysql_real_escape_string($_POST['cmdName']);

         $cmdid = Command::create($cmdName, $teamid);
         $smartyHelper->assign('commandid', $cmdid);

         $cmd = CommandCache::getInstance()->getCommand($cmdid);

         // set all fields
         updateCmdInfo($cmd);

      }

      // ------ Display Empty Command Form
      // Note: this will be overridden by the 'update' section if the 'createCmd' action has been called.
      $smartyHelper->assign('cmdInfoFormBtText', 'Create');
      $smartyHelper->assign('cmdInfoFormAction', 'createCmd');

      $smartyHelper->assign('cmdStateList', getCmdStateList());
      $smartyHelper->assign('cmdState', Command::$stateNames[0]);

      $smartyHelper->assign('commandsetid', $commandsetid);
      $smartyHelper->assign('commandsets', getCommandSets($teamid, $commandsetid));
   }


   if (0 != $cmdid) {
      // -------- UPDATE CMD -------

      $cmd = CommandCache::getInstance()->getCommand($cmdid);


      // ------ Actions

      if ("addCmdIssue" == $action) {
         $bugid = $_POST['bugid'];
         $logger->debug("add Issue $bugid on Command $cmdid team $teamid<br>");

         $cmd->addIssue($bugid);

      } else if ("removeCmdIssue" == $action) {

         $cmd->removeIssue($_POST['bugid']);

      } else if ("addToCmdSet" == $action) {

         $commandsetid = $_POST['commandsetid'];
         $logger->debug("add Command $cmdid to CommandSet $commandsetid<br>");

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
         $cmdset->addCommand($cmdid, CommandSet::cmdType_general);

      } else if ("removeFromCmdSet" == $action) {

         $commandsetid = $_POST['commandsetid'];
         $logger->debug("remove Command $cmdid from CommandSet $commandsetid<br>");

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
         $cmdset->removeCommand($cmdid);


      } else if ("updateCmdInfo" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;

         updateCmdInfo($cmd);

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

   }



   







}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>
