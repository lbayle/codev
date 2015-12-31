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

if(Tools::isConnectedUser() && filter_input(INPUT_GET, 'action')) {

   $action = Tools::getSecureGETStringValue('action', 'none');
   $logger = Logger::getLogger("IssueInfo_ajax");

   $smartyHelper = new SmartyHelper();
   if('getGeneralInfo' == $action) {
      $issue = IssueCache::getInstance()->getIssue(Tools::getSecureGETIntValue('bugid'));
      $user = UserCache::getInstance()->getUser($_SESSION['userid']);
      $managedTeamList = $user->getManagedTeamList();
      $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList, true, false) : array();
      $oTeamList = $user->getObservedTeamList();
      $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList, true, false) : array();
      $teamid = $_SESSION['teamid'];

      $smartyHelper->assign('isManager', $user->isTeamManager($teamid));
      $smartyHelper->assign('isObserver', $user->isTeamObserver($teamid));

      $isManagerView = (array_key_exists($issue->getProjectId(), $managedProjList)) ? true : false;
      $isObserverView = (array_key_exists($issue->getProjectId(), $observedProjList)) ? true : false;

      $smartyHelper->assign('issueGeneralInfo', IssueInfoTools::getIssueGeneralInfo($issue, ($isManagerView || $isObserverView)));
      $smartyHelper->display('ajax/issueGeneralInfo');

   } else if ('removeFromCmd' == $action) {
      $cmdid = Tools::getSecureGETIntValue('cmdid');
      $bugid = Tools::getSecureGETIntValue('bugid');
      $userid = $_SESSION['userid'];
      try {
         // cmd,user,issue must exist
         // user must be manager in the cmd's team
         $user = UserCache::getInstance()->getUser($userid);
         $cmd = CommandCache::getInstance()->getCommand($cmdid);
         $cmdTeamid = $cmd->getTeamid();
         
         if ($user->isTeamManager($cmdTeamid)) {
            $cmd->removeIssue($bugid);
            $jsonData=json_encode(array('statusMsg' => 'SUCCESS', 'cmdid' => $cmdid));
         } else {
            $logger->error("removeFromCmd: NOT_MANAGER user=$userid issue=$bugid cmd=$cmdid");
            $jsonData=json_encode(array('statusMsg' => T_('Sorry, only managers can remove tasks from commands')));
         }
         // return ajax data
         echo $jsonData;
         
      } catch (Exception $e) {
         Tools::sendBadRequest("Error: removeFromCmd bad values: user=$userid issue=$bugid cmd=$cmdid");
      }
   } else if ('getCmdCandidates' === $action) {
      $bugid = Tools::getSecureGETIntValue('bugid');
      $teamid = $_SESSION['teamid'];
      $userid = $_SESSION['userid'];

      try {
         // user must be manager
         // issue must be in team's projects
         
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $prjList = $team->getProjects();
         $issueCmds = $issue->getCommandList();
         
         if (!$user->isTeamManager($teamid)) {
            $logger->error("removeFromCmd: NOT_MANAGER user=$userid issue=$bugid cmd=$cmdid");
            $jsonData=json_encode(array('statusMsg' => T_('Sorry, only managers can add tasks from commands')));
         } else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         } else {
            $cmds = $team->getCommands();
            $cmdList = array();
            /* @var $cmd Command */
            foreach ($cmds as $cmd) {
               // Note: closed Cmds not displayed is enough, add a filter would be too much...
               if ((!array_key_exists($cmd->getId(), $issueCmds)) &&
                    $cmd->getState() < Command::state_closed ) {
                  $cmdList[$cmd->getId()] = $cmd->getName();
               }
            }
            $jsonData=json_encode(array('statusMsg' => 'SUCCESS', 'cmdCandidates' => $cmdList));
         }
         // return ajax data
         echo $jsonData;
         
      } catch (Exception $e) {
         Tools::sendBadRequest("Error: removeFromCmd bad values: user=$userid issue=$bugid cmd=$cmdid");
      }
      
   } else if ('addToCmd' === $action) {
      $cmdid = Tools::getSecureGETIntValue('cmdid');
      $bugid = Tools::getSecureGETIntValue('bugid');
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      try {
         // cmd,user,issue must exist
         // user must be manager
         // cmd must be in team's cmds
         // issue must be in team's projects

         $user = UserCache::getInstance()->getUser($userid);
         $cmd = CommandCache::getInstance()->getCommand($cmdid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $prjList = $team->getProjects();
         
         if (!$user->isTeamManager($teamid)) {
            $logger->error("removeFromCmd: NOT_MANAGER user=$userid issue=$bugid cmd=$cmdid");
            $jsonData=json_encode(array('statusMsg' => T_('Sorry, only managers can add tasks from commands')));
         }  else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         }  else if ($teamid != $cmd->getTeamid()) {
            Tools::sendBadRequest("Error: removeFromCmd bad cmdid: user=$userid teamid=$teamid cmd=$cmdid");
         }  else {
            $cmd->addIssue($bugid, true);
            $jsonData=json_encode(array('statusMsg' => 'SUCCESS', 'cmdid' => $cmdid, 'cmdName' => $cmd->getName()));
         }
         // return ajax data
         echo $jsonData;
         
      } catch (Exception $e) {
         Tools::sendBadRequest("Error: removeFromCmd bad values: user=$userid issue=$bugid cmd=$cmdid");
      }
   } else {
      Tools::sendNotFoundAccess();
   }
}
else {
   Tools::sendUnauthorizedAccess();
}
