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

include "commandset_tools.php";

include_once "smarty_tools.php";

$logger = Logger::getLogger("commandset_edit");


/**
 *
 * @param CommandSet $cmdset
 */
function updateCommandSetInfo($cmdset) {
   echo "AAAA";

   // security check
   $cmdset->setTeamid(checkNumericValue($_POST['teamid']));

   $formattedValue = mysql_real_escape_string($_POST['commandsetName']);
   $cmdset->setName($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['commandsetDesc']);
   $cmdset->setDesc($formattedValue);

   $formattedValue = mysql_real_escape_string($_POST['commandsetDate']);
   $cmdset->setDate(date2timestamp($formattedValue));

   $cmdset->setCost(checkNumericValue($_POST['commandsetCost'], true));

   $cmdset->setBudgetDays(checkNumericValue($_POST['commandsetBudget'], true));

   echo "ZZZZ";
}

// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('CommandSet (edit)'));

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


   // use the commandsetid set in the form, if not defined (first page call) use session commandsetid
   $commandsetid = 0;
   if(isset($_POST['commandsetid'])) {
      $commandsetid = $_POST['commandsetid'];
   } else if(isset($_GET['commandsetid'])) {
      $commandsetid = $_GET['commandsetid'];
   } else if(isset($_SESSION['commandsetid'])) {
      $commandsetid = $_SESSION['commandsetid'];
   }
   $_SESSION['commandsetid'] = $commandsetid;


   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------

   $smartyHelper->assign('commandsetid', $commandsetid);
   $smartyHelper->assign('commandsets', getCommandSets($teamid, $commandsetid));


   if (0 == $commandsetid) {

      // -------- CREATE CMDSET -------

      // ------ Actions
      if ("createCmdset" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;
         $logger->debug("create new CommandSet for team $teamid<br>");

         $cmdsetName = mysql_real_escape_string($_POST['commandsetName']);

         $commandsetid = CommandSet::create($cmdsetName, $teamid);
         $smartyHelper->assign('commansetdid', $commandsetid);

         #$cmdset = CommandSetCache::getInstance()->getCommandSet($commandsetid);
         $cmdset = new CommandtSet($commandsetid);

         // set all fields
         updateCommandSetInfo($cmdset);

      }

      // ------ Display Empty Command Form
      // Note: this will be overridden by the 'update' section if the 'createCommandset' action has been called.
      $smartyHelper->assign('cmdsetInfoFormBtText', 'Create');
      $smartyHelper->assign('cmdsetInfoFormAction', 'createCmdset');
   }


   if (0 != $commandsetid) {
      // -------- UPDATE CMDSET -------

      #$cmdset = CommandSetCache::getInstance()->getCommandSet($commansetdid);
      $cmdset = new CommandtSet($commandsetid);

      // ------ Actions

      if ("addCommand" == $action) {

         $_SESSION['cmdid'] = 0;
         header('Location:command_edit.php?cmdid=0');

      } else if ("updateCmdsetInfo" == $action) {

         $teamid = checkNumericValue($_POST['teamid']);
         $_SESSION['teamid'] = $teamid;

         updateCommandSetInfo($cmdset);

      } else if ("removeCmd" == $action) {

         $cmdid = checkNumericValue($_POST['cmdid']);
         $cmdset->removeCommand($cmdid);
      }

      // ------ Display CommandSet

      $smartyHelper->assign('commandsetid', $commandsetid);
      $smartyHelper->assign('cmdsetInfoFormBtText', 'Save');
      $smartyHelper->assign('cmdsetInfoFormAction', 'updateCmdsetInfo');
      $smartyHelper->assign('isAddCmdForm', true);


      displayCommandSet($smartyHelper, $cmdset);

   }
   
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>
