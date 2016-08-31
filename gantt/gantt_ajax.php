<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

$ganttAjaxLogger = Logger::getLogger("gantt_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   switch ($action) {
      case 'getGanttTasks':
         try {
            $teamid =  $_SESSION['teamid'];
            $session_userid =  $_SESSION['userid'];
            $session_user = UserCache::getInstance()->getUser($session_userid);
            $isManager = $session_user->isTeamManager($teamid);

            $schedulerManager = new SchedulerManager($session_userid, $teamid);
            $data = $schedulerManager->execute();
            $tasksDates = $schedulerManager->getComputedTaskDates();
            $schedEndTimerstamp = $schedulerManager->getSchedulerEndTimestamp();
            $isExtRef = $schedulerManager->getUserOption(SchedulerManager::OPTION_isDisplayExtRef);

            $timePerTaskPerUser = $schedulerManager->getUserOption(SchedulerManager::OPTION_timePerTaskPerUser);
            $timePerUserPerTaskList = SchedulerManager::transposeTo_TimePerUserPerTask($timePerTaskPerUser);

            // convert $taskDates to $dxhtmlGanttTasks
            $tasksData = array();
            $bugid_to_idx = array();
            $idx = 1;
            foreach($tasksDates as $bugid => $taskDates) {
               // OPTION set startDate to first timestrack (if exists)
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
               $taskTooltip = getTaskTooltip_minimal($issue, $teamid, $session_userid, $isManager, $timePerUserPerTaskList);

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
            //$ganttAjaxLogger->error("nb displayed tasks in gantt: ".count($tasksData));

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
            $ganttAjaxLogger->error($e->getMessage());
            $jsonData = array(
               'statusMsg' => $e->getMessage() // 'ERROR'
            );
         }
         echo json_encode($jsonData);
         break;
      default:
         Tools::sendNotFoundAccess();
         break;
   }
} else {
   Tools::sendUnauthorizedAccess();
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

/**
 *
 */
function getTaskTooltip_minimal($issue, $teamid, $session_userid, $isManager, $timePerUserPerTaskList) {
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
      $finalTooltipAttr[T_('Deadline')] = date(T_("Y-m-d"), $issue->getDeadline());
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

function getTaskTooltip($issue, $teamid, $session_userid, $isManager) {

   $finalTooltipAttr = array();

   $tooltipAttr = $issue->getTooltipItems($teamid, $session_userid, $isManager);
   //unset($tooltipAttr[T_('Project')]);
   //unset($tooltipAttr[T_('Type')]);
   //unset($tooltipAttr[T_('Category')]);
   unset($tooltipAttr[T_('Status')]);
   unset($tooltipAttr[T_('Priority')]);
   unset($tooltipAttr[T_('Severity')]);
   unset($tooltipAttr[T_('External ID')]);
   unset($tooltipAttr[T_('Backlog')]);
   unset($tooltipAttr[T_('Deadline')]);

   // insert in front
   $extRef = $issue->getTcId();

   $finalTooltipAttr[T_('Summary')] = $issue->getSummary();
   $finalTooltipAttr += $tooltipAttr;

   if ($issue->getDeadline() > 0) {
      $finalTooltipAttr[T_('Deadline')] = date(T_("Y-m-d"), $issue->getDeadline());
   }

   // HTML
   $htmlTooltip =
              '<table style="margin:0;border:0;padding:0;background-color:white;">'.
              '<tbody>';
   $driftColor = NULL;
   $driftMgrColor = NULL;
   if (array_key_exists('DriftColor', $finalTooltipAttr)) {
      $driftColor = $finalTooltipAttr['DriftColor'];
      unset($finalTooltipAttr['DriftColor']);
   }
   if (array_key_exists('DriftMgrColor', $finalTooltipAttr)) {
      $driftMgrColor = $finalTooltipAttr['DriftMgrColor'];
      unset($finalTooltipAttr['DriftMgrColor']);
   }
   foreach ($finalTooltipAttr as $key => $value) {
      $htmlTooltip .= '<tr>'.
                  '<td valign="top" style="color:blue;width:35px;">'.$key.'</td>';
      if ($driftColor != NULL && $key == T_('Drift')) {
         $htmlTooltip .= '<td><span style="background-color:#'.$driftColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
      } else if (!is_null($driftMgrColor) && $key == T_('DriftMgr')) {
         $htmlTooltip .= '<td><span style="background-color:#'.$driftMgrColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
      } else {
         $htmlTooltip .= '<td>'.nl2br(htmlspecialchars($value)).'</td>';
      }
      $htmlTooltip .= '</tr>';
   }
   $htmlTooltip .= '</tbody></table>';
   return $htmlTooltip;
}

