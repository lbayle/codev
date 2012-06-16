<?php
include_once('../include/session.inc.php');

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

require('super_header.inc.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "command.class.php";
include_once('consistency_check2.class.php');

#include_once "time_tracking.class.php";


include_once "smarty_tools.php";

include "command_tools.php";

$logger = Logger::getLogger("command_info");


function getCommands($teamid, $selectedCmdId) {

   $commands = array();
   if (0 != $teamid) {

      $team = TeamCache::getInstance()->getTeam($teamid);
      $cmdList = $team->getCommands();

      foreach ($cmdList as $id => $cmd) {
         $commands[] = array(
            'id' => $id,
            'name' => $cmd->getName(),
            'selected' => ($id == $selectedCmdId)
         );
      }
   }
   return $commands;
}

/**
 * Get consistency errors
 * @param Command $cmd
 */
function getConsistencyErrors($cmd) {
   global $statusNames;

   $consistencyErrors = array(); // if null, array_merge fails !

   $issueSel = $cmd->getIssueSelection();
   $issueList = $issueSel->getIssueList();
    $ccheck = new ConsistencyCheck2($issueList);

    $cerrList = $ccheck->check();

    if (count($cerrList) > 0) {
        $i = 0;
        foreach ($cerrList as $cerr) {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $consistencyErrors[] = array(
                'issueURL' => issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
                'issueStatus' => $statusNames[$cerr->status],
                'user' => $user->getName(),
                'desc' => $cerr->desc);
        }
        $i++;
    }

    return $consistencyErrors;
}





// =========== MAIN ==========

require('display.inc.php');


$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Command'));

if (isset($_SESSION['userid'])) {


   $userid = $_SESSION['userid'];
   $session_user = UserCache::getInstance()->getUser($userid);

   $teamid = 0;
   if(isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else if(isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   }
   $_SESSION['teamid'] = $teamid;

   // use the cmdid set in the form, if not defined (first page call) use session cmdid
   $cmdid = 0;
   if(isset($_POST['cmdid'])) {
      $cmdid = $_POST['cmdid'];
   } else if(isset($_SESSION['cmdid'])) {
      $cmdid = $_SESSION['cmdid'];
   }
   $_SESSION['cmdid'] = $cmdid;


   // set TeamList (including observed teams)
   $teamList = $session_user->getTeamList();
   $smartyHelper->assign('teamid', $teamid);
   $smartyHelper->assign('teams', getTeams($teamList, $teamid));

   $smartyHelper->assign('commandid', $cmdid);
   $smartyHelper->assign('commands', getCommands($teamid, $cmdid));

   $action = isset($_POST['action']) ? $_POST['action'] : '';


   // ------ Display Command

   if (0 != $cmdid) {

      $cmd = CommandCache::getInstance()->getCommand($cmdid);

      displayCommand($smartyHelper, $cmd);


      // ConsistencyCheck
      $consistencyErrors = getConsistencyErrors($cmd);

      if(count($consistencyErrors) > 0) {
         
         $smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
         $smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors affecting the Command"));
         $smartyHelper->assign('ccheckErrList', $consistencyErrors);
         
      }

   } else {

      if ('displayCommand' == $action) {

         header('Location:command_edit.php?cmdid=0');
      }
   }



}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);


?>
