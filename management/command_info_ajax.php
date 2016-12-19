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

$ajaxLogger = Logger::getLogger("commandInfo_ajax");

if(Tools::isConnectedUser() && 
   (filter_input(INPUT_POST, 'action') || filter_input(INPUT_GET, 'action'))) {

   // INPUT_GET  for action updateDetailedCharges
   // INPUT_GET  for action updateTaskData
   // INPUT_POST for action getGanttTasks
   $action = filter_input(INPUT_POST, 'action');
   if (empty($action)) {
      $action = filter_input(INPUT_GET, 'action');
   }

   if(!empty($action)) {
      $logger = Logger::getLogger("command_info_ajax");
      $smartyHelper = new SmartyHelper();

      if ('updateDetailedCharges' === $action) {
         // GET

         $cmdid = Tools::getSecureGETIntValue('selectFiltersSrcId');
         $selectedFilters = Tools::getSecureGETStringValue('selectedFilters', '');


         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $session_user->setCommandFilters($selectedFilters, $cmdid);

         $cmd = CommandCache::getInstance()->getCommand($cmdid);
         $isManager = $session_user->isTeamManager($cmd->getTeamid());
         $isObserver = $session_user->isTeamObserver($cmd->getTeamid());

         // DetailedChargesIndicator
         $data = CommandTools::getDetailedCharges($cmd, ($isManager || $isObserver), $selectedFilters);
         foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $smartyHelper->display(DetailedChargesIndicator::getSmartySubFilename());

      } else if ('updateTaskData' === $action) {
         $userid = $_SESSION['userid'];
         $teamid = $_SESSION['teamid'];
         try {
            $user = UserCache::getInstance()->getUser($userid);
            $team = TeamCache::getInstance()->getTeam($teamid);

            $bugId = Tools::getSecureGETNumberValue('bugId');
            $updatedValue = Tools::getSecureGETNumberValue('updatedValue');
            $issue = IssueCache::getInstance()->getIssue($bugId);

            $fieldName = Tools::getSecureGETStringValue('fieldName');
            switch ($fieldName) {
               case 'EffortEstim':
                  $issue->setEffortEstim($updatedValue);

                  echo json_encode(array(
                      'statusMsg' => 'SUCCESS',
                      'taskUpdate' => array(
                          'drift' => $drift = $issue->getDrift(),
                          'driftColor' => $issue->getDriftColor($drift)
                      ),
                  ));
                  break;
               case 'MgrEffortEstim':
                  // lock php, try lock
                  $issue->setMgrEffortEstim($updatedValue);

                  $commandId = Tools::getSecureGETIntValue('commandId');
                  $command = CommandCache::getInstance()->getCommand($commandId);

                  echo json_encode(array(
                      'statusMsg' => 'SUCCESS',
                      'taskUpdate' => array(
                          'driftMgr' => $drift = $issue->getDriftMgr(),
                          'driftColor' => $issue->getDriftColor($drift)
                      ),
                      'budgetUpdate' => CommandTools::getBudgetIndicatorValues($command),
                  ));
                  break;
               default:
                  $ajaxLogger->error("Attempt to change unknown field '$fieldName'");
                  echo json_encode(array(
                     'statusMsg' => "Attempt to change unknown field '$fieldName'"
                   ));
            }
         } catch (Exception $e) {
            Tools::sendBadRequest("Error: updateTaskInfo bad values: user=$userid issue=$bugId");
         }
      } else if ('getGanttTasks' === $action) {
         // POST
         // TODO: same code exists in gantt_ajax.php ...
         try {
            $cmdid = Tools::getSecurePOSTIntValue('commandId');
            $cmd = CommandCache::getInstance()->getCommand($cmdid);
            $cmdIssueList = $cmd->getIssueSelection()->getIssueList();
            $cmdBugidList = array_keys($cmdIssueList);

            //$teamid =  $cmd->getTeamid();
            $teamid =  $_SESSION['teamid'];

            $session_userid =  $_SESSION['userid'];
            $session_user = UserCache::getInstance()->getUser($session_userid);
            $isManager = $session_user->isTeamManager($teamid);
            $isObserver = $session_user->isTeamObserver($teamid);

            // - the scheduler needs to compute the complete Gantt of the team (not only cmd tasks)
            // - resolved tasks will not be scheduled
            $schedulerManager = new SchedulerManager($session_userid, $teamid);
            $schedulerManager->execute();
            $tasksDates = $schedulerManager->getComputedTaskDates();
            $schedEndTimerstamp = $schedulerManager->getSchedulerEndTimestamp();
            $isExtRef = $schedulerManager->getUserOption(SchedulerManager::OPTION_isDisplayExtRef);

            $timePerTaskPerUser = $schedulerManager->getUserOption(SchedulerManager::OPTION_timePerTaskPerUser);
            $timePerUserPerTaskList = SchedulerManager::transposeTo_TimePerUserPerTask($timePerTaskPerUser);

            $logger->error("nb displayed tasks in cmd: ".count($cmdBugidList));
            //$logger->error($cmdBugidList);

            $tasksData = array();
            $bugid_to_idx = array();
            $idx = 1;

            // --- add resolved tasks of the command

            // order resolved tasks by startDate (first timetrack)
            $orderedResolvedIssueList = array();
            foreach ($cmdIssueList as $issue) {
               if ($issue->isResolved()) {
               $tt = $issue->getFirstTimetrack();
               if (NULL != $tt) {
                  // issues with no timetrack will not be displayed
                  $key = $tt->getDate().$issue->getId();
                  $orderedResolvedIssueList[$key]= $issue;
               } else {
                  $logger->error("no timetrack for ".$issue->getId());
               }
               }
            }
            ksort($orderedResolvedIssueList);

            foreach ($orderedResolvedIssueList as $issue) {
               $bugid = $issue->getId();
               if ($isExtRef) {
                  $extRef = $issue->getTcId();
                  if (empty($extRef)) { $extRef = 'm'.$bugid; }
                  $gridText =$extRef .' (m'.$issue->getId().')';
                  $barText = $extRef;
               } else {
                  $gridText = $bugid; // .' | '.$issue->getSummary();
                  $barText = $bugid;
               }
               $duration_tmp = round(($issue->getLatestTimetrack()->getDate() - $issue->getFirstTimetrack()->getDate()) / 86400, 2); // 24*60*60 (ms -> day);
               $duration = ($duration_tmp < 0) ? 1 : round($duration_tmp); // fix dxhtml bug ?

               $taskTooltip = getTaskTooltip_minimal($issue, NULL);
               $handlerName = UserCache::getInstance()->getUser($issue->getHandlerId())->getName();

               $data = array(
                   'id' => $idx,
                   'text' => $gridText,
                   'start_date' => date('d-m-Y H:i', $issue->getFirstTimetrack()->getDate()), // core
                   'duration' => $duration,
                   'progress' => $issue->getProgress(),
                   'color' =>  '#81BEF7', // blue
                   'open' => true,
                   #'textColor' => 'black',
                   #'parent' => 1,
                   #'readonly' => true
                   // custom:
                   'duration_real' => 0,
                   'end_date_real' => date('Y-m-d', $issue->getLatestTimetrack()->getDate()),
                   'barText' => $barText,
                   'tooltipHtml' => $taskTooltip,
                   'assignedTo' => $handlerName,
                   'summary' => $issue->getSummary(),
               );
               //$logger->error($data);

               $tasksData[] = $data;
               $bugid_to_idx[$bugid] = $idx;
               ++$idx;
            }

            // add scheduled tasks
            foreach($tasksDates as $bugid => $taskDates) {

               if (!in_array($bugid, $cmdBugidList)) {
                  // do not display tasks not in command
                  //$logger->error("skipp: ".$bugid);
                  continue;
               }
               //$logger->error("add to gantt: ".$bugid);

               $issue = IssueCache::getInstance()->getIssue($bugid);

               $duration_real = round($issue->getDuration(), 2);
               $duration_tmp = round(($taskDates['endTimestamp'] - $taskDates['startTimestamp']) / 86400, 2); // 24*60*60 (ms -> day);
               $duration = ($duration_tmp < 0) ? 1 : round($duration_tmp); // fix dxhtml bug ?

               if ($isExtRef) {
                  $extRef = $issue->getTcId();
                  if (empty($extRef)) { $extRef = 'm'.$bugid; }
                  $griddText =$extRef .' (m'.$issue->getId().')';
                  $barText = $extRef;
               } else {
                  $griddText = $bugid; // .' | '.$issue->getSummary();
                  $barText = $bugid;
               }

               $color = $schedulerManager->getColor($bugid, $taskDates['endTimestamp']);
               $taskTooltip = getTaskTooltip_minimal($issue, $timePerUserPerTaskList);

               $data = array(
                   'id' => $idx,
                   'text' => $griddText,
                   'start_date' => date('d-m-Y H:i', $taskDates['startTimestamp']), // core
                   'duration' => $duration,
                   'progress' => $issue->getProgress() ,
                   'color' =>  $color,
                   'open' => true,
                   #'textColor' => 'black',
                   #'parent' => 1,
                   #'readonly' => true
                   // custom:
                   'duration_real' => $duration_real,
                   'end_date_real' => date('Y-m-d', $taskDates['endTimestamp']),
                   'barText' => $barText,
                   'tooltipHtml' => $taskTooltip,
                   'assignedTo' => implode(', ', getAssignedUsers($bugid, $timePerUserPerTaskList)),
                   'summary' => $issue->getSummary(),
               );
               if ('lightgreen' == $color) { $data['textColor'] = 'black'; }

               $tasksData[] = $data;
               $bugid_to_idx[$bugid] = $idx;
               ++$idx;
            }

            // set tasks dependencies
            $tasksLinks = array();
            $j = 0;
            foreach ($bugid_to_idx as $bugid => $idx) {
               $issue = IssueCache::getInstance()->getIssue($bugid);

               $relationships = $issue->getRelationships();
               $constrainsList = $relationships[Constants::$relationship_constrains];
               if (NULL !== $constrainsList) {
                  foreach ($constrainsList as $constrainedBugid) {
                     if (array_key_exists($constrainedBugid, $bugid_to_idx)) {
                        $tasksLinks[] = array(
                            'id' => $j,
                            'source' => $idx,
                            'target' => $bugid_to_idx[$constrainedBugid],
                            'type' => '0', // 0:finish_to_start
                        );
                        ++$j;
                     } else {
                        //$ganttAjaxLogger->warn("gantt link $bugid -> $constrainedBugid : $constrainedBugid not in gantt chart");
                     }
                  }
               }
            }

            $dxhtmlGanttTasks = array(
                'data' => $tasksData,
                'links' => $tasksLinks,
            );

            $jsonData = array(
               'statusMsg' => 'SUCCESS',
               'ganttTasks' => $dxhtmlGanttTasks,
               'ganttEndDate' => date('Y-m-d', $schedEndTimerstamp),
            );
         } catch (Exception $e) {
            //$statusMsg = $e->getMessage();
            $logger->error($e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            $jsonData = array(
               'statusMsg' => $e->getMessage() // 'ERROR'
            );
         }
         echo json_encode($jsonData);

      } else {
         // unknown action
         Tools::sendNotFoundAccess();
      }
   }
} else {
   Tools::sendUnauthorizedAccess();
}

/**
 *
 */
function getTaskTooltip_minimal($issue, $timePerUserPerTaskList) {
   $finalTooltipAttr = array();

   $extRef = $issue->getTcId();
   if (!empty($extRef)) {
      $finalTooltipAttr[T_('Task')] = $extRef.' (m'.$issue->getId().')';
   } else {
      $finalTooltipAttr[T_('Task')] = $issue->getId();
   }
   $finalTooltipAttr[T_('Summary')] = $issue->getSummary();
   $finalTooltipAttr[T_('Assigned to')] = implode(', ', getAssignedUsers($issue->getId(), $timePerUserPerTaskList, FALSE));
   $finalTooltipAttr[T_('Progress')] = round(($issue->getProgress() * 100)).'%';
   if ($issue->getDeadline() > 0) {
      $finalTooltipAttr[T_('Deadline')] = date('Y-m-d', $issue->getDeadline());
   }

   $htmlTooltip =
              '<table style="margin:0;border:0;padding:0;background-color:white;"><tbody>';
   foreach ($finalTooltipAttr as $key => $value) {
      $htmlTooltip .= '<tr>'.
         '<td valign="top" style="color:blue;width:35px;">'.$key.'</td>'.
         '<td>'.nl2br(htmlspecialchars($value)).'</td>'.
         '</tr>';
   }
   $htmlTooltip .= '</tbody></table>';
   return $htmlTooltip;
}

/**
 *
 */
function getAssignedUsers($bugid, $timePerUserPerTaskList, $isShortDesc = TRUE) {
   $assignedUsers = array();
   if ((null != $timePerUserPerTaskList) && (null != $timePerUserPerTaskList[$bugid])) {
      $timePerUserList = $timePerUserPerTaskList[$bugid];

      foreach($timePerUserList as $userid => $time) {
         $userName = UserCache::getInstance()->getUser($userid)->getName();

         if (!$isShortDesc) {
            $timeStr = (NULL == $time) ? T_('auto') : $time;
            $userName .= "($timeStr)";
         }
         $assignedUsers[$userid] = $userName;
      }
   } else {
      $issue = IssueCache::getInstance()->getIssue($bugid);
      $handlerid = $issue->getHandlerId();
      if ($handlerid) {
         $handler = UserCache::getInstance()->getUser($handlerid);
         $assignedUsers[$userid] = $handler->getName();
      }
   }
   return $assignedUsers;
}

