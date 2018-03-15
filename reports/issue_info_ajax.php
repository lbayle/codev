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
            $logger->error("getCmdCandidates: NOT_MANAGER user=$userid issue=$bugid cmd=$cmdid");
            $jsonData=json_encode(array('statusMsg' => T_('Sorry, only managers can add tasks to commands')));
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
         Tools::sendBadRequest("Error: getCmdCandidates bad values: user=$userid issue=$bugid cmd=$cmdid");
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
            $logger->error("addToCmd: NOT_MANAGER user=$userid issue=$bugid cmd=$cmdid");
            $jsonData=json_encode(array('statusMsg' => T_('Sorry, only managers can add tasks to commands')));
         }  else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         }  else if ($teamid != $cmd->getTeamid()) {
            Tools::sendBadRequest("Error: addToCmd bad teamid: user=$userid teamid=$teamid cmd=$cmdid");
         }  else {
            $cmd->addIssue($bugid, true);
            $jsonData=json_encode(array('statusMsg' => 'SUCCESS', 'cmdid' => $cmdid, 'cmdName' => $cmd->getName()));
         }
         // return ajax data
         echo $jsonData;
         
      } catch (Exception $e) {
         Tools::sendBadRequest("Error: addToCmd bad values: user=$userid issue=$bugid cmd=$cmdid");
      }
   } else if ('getTimetracking' == $action) {
      $bugid  = Tools::getSecureGETIntValue('bugid');
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];

      try {
         // user,issue must exist
         // user must be team active member (manager, developper)
         // issue must be in team's projects
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $prjList = $team->getProjects();
         $activeMembers = $team->getActiveMembers();

         if (!array_key_exists($userid, $activeMembers)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, you're not an active member of this team")));
         } else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         } else {
            $values = array(
               'statusMsg' => 'SUCCESS',
               'bugStatusNew' => Constants::$status_new,
               'statusNameNew' => Constants::$statusNames[Constants::$status_new],
               'bugResolvedStatusThreshold' => $issue->getBugResolvedStatusThreshold(),
               'issueCurrentStatus' => $issue->getCurrentStatus(),
               'issueEffortEstim' => $issue->getEffortEstim(),
               'issueBacklog' => $issue->getBacklog(),
            );

            if ($user->isTeamManager($teamid)) {
               $values['issueMgrEffortEstim'] = $issue->getMgrEffortEstim();
            }
            $jsonData=json_encode($values);
         }
         // return ajax data
         echo $jsonData;
      } catch (Exception $e) {
         Tools::sendBadRequest("Error: getTimetracking bad values: user=$userid issue=$bugid");
      }

   } else if ('updateTimetracking' == $action) {
      $bugid             = Tools::getSecureGETIntValue('bugid');
      $newEffortEstim    = Tools::getSecureGETNumberValue('fut_issueEffortEstim');

      // newBacklog can be NULL (empty string) if status == 'New'
      $newBacklog        = filter_input(INPUT_GET, 'fut_backlog');
      if ( ('' !== $newBacklog) && (!is_numeric($newBacklog))) {
         self::sendBadRequest('Attempt to set non_numeric value ('.$newBacklog.') for fut_backlog');
         die("<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>");
      }

      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      try {
         // user,issue must exist
         // user must be team active member (manager, developper)
         // issue must be in team's projects
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $prjList = $team->getProjects();
         $activeMembers = $team->getActiveMembers();

         if (!array_key_exists($userid, $activeMembers)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, you're not an active member of this team")));
         } else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         }  else {
            // update values
            if ($user->isTeamManager($teamid)) {
               $newMgrEffortEstim = Tools::getSecureGETNumberValue('fut_issueMgrEffortEstim');
               if ($newMgrEffortEstim != $issue->getMgrEffortEstim()) {
                  $issue->setMgrEffortEstim($newMgrEffortEstim);
               }
            }
            if ($newEffortEstim != $issue->getEffortEstim()) {
               $issue->setEffortEstim($newEffortEstim);
            }
            if ($newBacklog != $issue->getBacklog()) {
               $issue->setBacklog($newBacklog);
            }
            $jsonData=json_encode(array('statusMsg' => 'SUCCESS', 'issueId' => $issue->getId()));
         }
         // return ajax data
         echo $jsonData;
      } catch (Exception $e) {
         $logger->fatal('EXCEPTION: '.$e->getMessage());
         Tools::sendBadRequest("Error: updateTimetracking bad values: user=$userid issue=$bugid");
      }
   } else if ('getTaskInfo' == $action) {
      $bugid  = Tools::getSecureGETIntValue('bugid');
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];

      try {
         // user,issue must exist
         // user must be team active member (manager, developper)
         // issue must be in team's projects
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $prjList = $team->getProjects();
         $activeMembers = $team->getActiveMembers();

         if (!array_key_exists($userid, $activeMembers)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, you're not an active member of this team")));
         } else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         }  else {

            // get data to fill combobox fields
            $versionList = $project->getProjectVersions(FALSE);
            //asort($versionList);
            $targetVersionId = array_search($issue->getTargetVersion(), $versionList);

            $availableHandlerList = $team->getActiveMembers(NULL,NULL,TRUE);
            #asort($availableHandlerList);
            $taskInfo = array(
               'statusMsg' => 'SUCCESS',
               'issueId' => $issue->getId(),
               'extRef' => $issue->getTcId(),
               'currentHandlerId' => $issue->getHandlerId(),
               'availableHandlerList' => $availableHandlerList,
               'codevttType' => $issue->getType(),
               'currentStatus' =>  $issue->getCurrentStatus(),
               'availableStatusList' => $issue->getAvailableStatusList(true),
               'targetVersionId' => $targetVersionId,
               'availableTargetVersion' => $versionList,
            );
            if (NULL != $issue->getDeadLine(TRUE)) {
               $taskInfo['deadline'] = date("Y-m-d", $issue->getDeadLine());
            }
            if (NULL != $issue->getDeliveryDate()) {
               $taskInfo['deliveryDate'] = date("Y-m-d", $issue->getDeliveryDate());
            }
            $jsonData=json_encode($taskInfo);
         }
         echo $jsonData;

      } catch (Exception $e) {
         Tools::sendBadRequest("Error: getTaskInfo bad values: user=$userid issue=$bugid");
      }
   } else if ('updateTaskInfo' == $action) {
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      $bugid  = Tools::getSecureGETIntValue('bugid');
      $newExtRef = Tools::getSecureGETStringValue('futi_extRef', ''); // empty is allowed
      $newHandlerId = Tools::getSecureGETIntValue('futi_cbHandlerId');
      $newStatus = Tools::getSecureGETIntValue('futi_cbStatus');
      $newType = Tools::getSecureGETStringValue('futi_codevttType');
      //$newPriority = Tools::getSecureGETIntValue('futi_priority');
      //$newSeverity = Tools::getSecureGETIntValue('futi_severity');
      $newTargetVersionId = Tools::getSecureGETStringValue('futi_cbTargetVersion');

      // may not be specified
      $formatedDeadline = Tools::getSecureGETStringValue('futi_deadlineDatepicker', 'undefined'); // empty is allowed
      $formatedDeliveryDate = Tools::getSecureGETStringValue('futi_deliveryDatepicker', 'undefined'); // empty is allowed

      try {
         // user,issue must exist
         // user must be team active member (manager, developper)
         // issue must be in team's projects
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $prjList = $team->getProjects();
         $activeMembers = $team->getActiveMembers();

         if (!array_key_exists($userid, $activeMembers)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, you're not an active member of this team")));
         } else if (!array_key_exists($issue->getProjectId(), $prjList)) {
            $jsonData=json_encode(array('statusMsg' => T_("Sorry, this task is not in your team's projects")));
         }  else {

            if (('undefined' === $formatedDeadline) || ('' === $formatedDeadline)) {
               $newDeadline = null; // delete value in DB
            } else {
               $newDeadline = Tools::date2timestamp($formatedDeadline);
            }
            if (('undefined' === $formatedDeliveryDate) || ('' === $formatedDeliveryDate)) {
               $newDeliveryDate = null;  // delete value in DB
            } else {
               $newDeliveryDate = Tools::date2timestamp($formatedDeliveryDate);
            }

            // update values
            if ($newExtRef != $issue->getTcId()) {
               $issue->setExternalRef($newExtRef);
            }
            if ($newHandlerId != $issue->getHandlerId()) {
               $issue->setHandler($newHandlerId);
            }
            if ($newStatus != $issue->getStatus()) {
               $issue->setStatus($newStatus);
               if ($newStatus >= $issue->getBugResolvedStatusThreshold()) {
                  $issue->setBacklog(0);
                  $isUpdateGeneralInfo = 'yes';
               }
            }
            if ($newType != $issue->getType()) {
               $issue->setType($newType);
            }

            // TODO priority & severity

            $newTargetVersionName = (0 == $newTargetVersionId) ? '' : Project::getProjectVersionName($newTargetVersionId);
            if ($newTargetVersionName != $issue->getTargetVersion()) {
               $issue->setTargetVersion($newTargetVersionId);
            }
            if ($newDeadline != $issue->getDeadLine()) {
               $issue->setDeadline($newDeadline);
            }
            if ($newDeliveryDate != $issue->getDeliveryDate()) {
               $issue->setDeliveryDate($newDeliveryDate);
            }

            // send data to update divTaskInfo
            if (0 != $issue->getHandlerId()) {
               $handlerName = UserCache::getInstance()->getUser($issue->getHandlerId())->getName();
            } else {
               $handlerName = '';
            }
            $taskInfo = array(
               'statusMsg' => 'SUCCESS',
               'issueId' => $bugid,
               'issueExtRef' => $issue->getTcId(),
               'handlerName'=> $handlerName,
               'statusName'=> $issue->getCurrentStatusName(),
               'projectName' => $issue->getProjectName(),
               'categoryName' => $issue->getCategoryName(),
               'issueType' => $issue->getType(),
               'priorityName'=> $issue->getPriorityName(),
               'severityName'=> $issue->getSeverityName(),
               'targetVersion'=> $issue->getTargetVersion(),
               'timeDrift' => IssueInfoTools::getTimeDrift($issue),
               'isUpdateGeneralInfo' => $isUpdateGeneralInfo,
            );
            $jsonData=json_encode($taskInfo);
         }
         echo $jsonData;

      } catch (Exception $e) {
         Tools::sendBadRequest("Error: updateTaskInfo bad values: user=$userid issue=$bugid");
      }
   }  else if ('getMantisNotes' == $action) {
      $userid = $_SESSION['userid'];
      $teamid = $_SESSION['teamid'];
      $bugid  = Tools::getSecureGETIntValue('bugid');

      try {
         // user,issue must exist
         $user = UserCache::getInstance()->getUser($userid);
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $team = TeamCache::getInstance()->getTeam($teamid);

         $issueNoteList = $issue->getIssueNoteList();

         $formatedNotes = array();
         foreach ($issueNoteList as $noteId => $issueNote) {
            $reporter = UserCache::getInstance()->getUser($issueNote->getReporterId());
            $noteInfo = array (
                'noteId' => $noteId,
                'reporterName' => $reporter->getRealname(),
                'dateSubmitted' => date('Y-m-d H:i:s', $issueNote->getDateSubmitted()),
                'originTag' => T_($issueNote->getOriginTag()),
                'dateLastModified' => date('Y-m-d H:i:s', $issueNote->getLastModified()),
                'text' => nl2br(htmlspecialchars($issueNote->getText(FALSE, TRUE))), // tags removed, not readBy
            );
            $formatedNotes[$issueNote->getId()] = $noteInfo;
         }

         $dataIssueNotes = array(
            'statusMsg' => 'SUCCESS',
            'taskNotes' => $formatedNotes,
         );
         $jsonData=json_encode($dataIssueNotes);
         echo $jsonData;

      } catch (Exception $e) {
         Tools::sendBadRequest("Error: updateTaskInfo bad values: user=$userid issue=$bugid");
      }

   } else {
      Tools::sendNotFoundAccess();
   }
}
else {
   Tools::sendUnauthorizedAccess();
}
