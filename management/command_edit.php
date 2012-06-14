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

   $cmd->setCommandSet(checkNumericValue($_POST['commandsetid']));

   $formattedValue = mysql_real_escape_string($_POST['cmdName']);
   $cmd->setName($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdDesc']);
   $cmd->setDesc($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['cmdStartDate']);
   $cmd->setStartDate(date2timestamp($formattedValue));

   $formattedValue = mysql_real_escape_string($_POST['cmdDeadline']);
   $cmd->setDeadline(date2timestamp($formattedValue));


   $cmd->setState(checkNumericValue($_POST['cmdState'], true));
   $cmd->setBudgetDev(checkNumericValue($_POST['cmdBudgetDev'], true));
   $cmd->setBudgetMngt(checkNumericValue($_POST['cmdBudgetMngt'], true));
   $cmd->setBudgetGarantie(checkNumericValue($_POST['cmdBudgetGarantie'], true));
   $cmd->setAverageDailyRate(checkNumericValue($_POST['cmdAverageDailyRate'], true));


}

// your functions here
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
   $commandid = 0;
   if(isset($_POST['cmdid'])) {
      $commandid = $_POST['cmdid'];
   } else if(isset($_GET['cmdid'])) {
      $commandid = $_GET['cmdid'];
   } else if(isset($_SESSION['cmdid'])) {
      $commandid = $_SESSION['cmdid'];
   }
   $_SESSION['cmdid'] = $commandid;

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

   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));


   if (0 == $commandid) {

      // -------- CREATE ENG -------

      // ------ Actions
      if ("createCmd" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;
         $logger->debug("create new Command for team $teamid<br>");

         $cmdName = mysql_real_escape_string($_POST['cmdName']);

         $commandid = Command::create($cmdName, $teamid);
         $smartyHelper->assign('commandid', $commandid);

         $cmd = CommandCache::getInstance()->getCommand($commandid);

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
      $smartyHelper->assign('commandsets', getServices($teamid, $commandsetid));
   }


   if (0 != $commandid) {
      // -------- UPDATE ENG -------

      $cmd = CommandCache::getInstance()->getCommand($commandid);


      // ------ Actions

      if ("addCmdIssue" == $action) {
         $bugid = $_POST['bugid'];
         $logger->debug("add Issue $bugid on Command $commandid team $teamid<br>");

         $cmd->addIssue($bugid);

      } else if ("updateCmdInfo" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;

         updateCmdInfo($cmd);

      } else if ("removeCmdIssue" == $action) {

         $cmd->removeIssue($_POST['bugid']);
      }


      // --- set Service according to the displayed command
     $commandsetid = $cmd->getCommandSet();


   if ((NULL == $commandsetid) || (0 == $commandsetid)) {
         unset($_SESSION['commandsetid']);
         #$smartyHelper->assign('commandsetid', 0);
         $smartyHelper->assign('commandsets', getServices($teamid, 0));
      } else {
         $_SESSION['commandsetid'] = $commandsetid;
         $smartyHelper->assign('commandsetid', $commandsetid);
         $smartyHelper->assign('commandsets', getServices($teamid, $commandsetid));

         $commandset = new CommandtSet($commandsetid); // TODO use cache
         $commandsetName = $commandset->getName();
         $smartyHelper->assign('commandsetName', $commandsetName);
      }
 

      // ------ Display Command
      $smartyHelper->assign('commandid', $commandid);
      $smartyHelper->assign('cmdInfoFormBtText', 'Save');
      $smartyHelper->assign('cmdInfoFormAction', 'updateCmdInfo');
      $smartyHelper->assign('isAddCmdForm', true);

      displayCommand($smartyHelper, $cmd);

   }



   







}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>
