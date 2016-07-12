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

$SchedAjaxLogger = Logger::getLogger("scheduler_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');
   switch ($action) {
      case 'getTeam':
         getTeam();
          break;
      case 'getOldTimetrack':
         getOldTimetrack();
         break;
      case 'getProjection':
         getProjection();
         break;
      case 'setTaskUserList':
         setTimePerUserList();
         break;
      case 'getTaskUserList':
         getTaskUserList();
         break;
      case 'setOptions':
         setOptions();
         break;
      default:
          Tools::sendNotFoundAccess();
          break;
   }
} else {
   Tools::sendUnauthorizedAccess();
}

function getTeam() {
   global $SchedAjaxLogger;
   
   $data = array();
   $team_id = $_SESSION['teamid'];
   $mList = TeamCache::getInstance()->getTeam($team_id)->getActiveMembers();
   foreach ($mList as $key => $m) {
      $pushdata = array("key"=>"$key", "label"=>"$m");
      array_push($data, $pushdata);
   }
   echo json_encode($data);
}

function getOldTimetrack() {
   global $SchedAjaxLogger;

   try {
      $timeTracks = array();
      $allTimetracks = array();
      $team_id = $_SESSION['teamid'];
      $team = TeamCache::getInstance()->getTeam($team_id);

      $mList = $team->getActiveMembers();
      foreach($mList as $userId => $m) {

         $user = UserCache::getInstance()->getUser($userId);
         $prevTimetrack = NULL;

         //$startOfCycle = $user->getArrivalDate($team_id);
         $startOfCycle = strtotime("-1 month", mktime(0, 0, 0)); // TODO remove this hardcoded value !
         $endOfCycle = strtotime("+3 month", mktime(0, 0, 0)); // TODO remove this hardcoded value !

         $timeTracks = $user->getTimeTracks($startOfCycle, $endOfCycle);
         
         if(null != $timeTracks)
         {
            // sort older to newer
            Tools::usort($timeTracks);

            foreach($timeTracks as $timetrack) {

               $issue_id = $timetrack->getIssueId();
               $startTimestamp = $timetrack->getDate();
               $endTimestamp = $startTimestamp + $timetrack->getDuration()* 86400; // 24*60*60;

               if (NULL == $prevTimetrack) {
                  // first timetrack fot this user
                  $prevTimetrack = array(
                      'startTimestamp' => $startTimestamp,
                      'endTimestamp'   => $endTimestamp,
                      'endMidnightTimestamp' => $endTimestamp,
                      'bugid'          => $issue_id,
                      );
               } else {
                  // if same issue, just extend $prevTimetrack endTimestamp
                  if (($prevTimetrack['bugid'] == $issue_id) &&
                      ($prevTimetrack['endMidnightTimestamp'] == $startTimestamp)) {
                     $endTimestamp += ($prevTimetrack['endTimestamp'] - $prevTimetrack['endMidnightTimestamp']);
                     $prevTimetrack['endTimestamp'] = $endTimestamp;
                     $prevTimetrack['endMidnightTimestamp'] = mktime(0, 0, 0, date('m', $endTimestamp), date('d', $endTimestamp), date('Y', $endTimestamp));
                  } else {
                     // store previous timetrack
                     $dxhtmlData = formatActivity($prevTimetrack, $team, $userId);
                     array_push($allTimetracks, $dxhtmlData);

                     // if same day than prevTimetrack, append to it
                     if ($prevTimetrack['endMidnightTimestamp'] == $startTimestamp) {
                        $startTimestamp = $prevTimetrack['endTimestamp'];
                        $endTimestamp += ($prevTimetrack['endTimestamp'] - $prevTimetrack['endMidnightTimestamp']);
                     }

                     // create a new one
                     $prevTimetrack = array(
                         'startTimestamp' => $startTimestamp,
                         'endTimestamp'   => $endTimestamp,
                         'endMidnightTimestamp' => mktime(0, 0, 0, date('m', $endTimestamp), date('d', $endTimestamp), date('Y', $endTimestamp)),
                         'bugid'          => $issue_id,
                        );
                  }
               }
            }
            // store latest timetrack strike still in cache
            if(null != $prevTimetrack){
               $dxhtmlData = formatActivity($prevTimetrack, $team, $userId);
            }
            array_push($allTimetracks, $dxhtmlData);
         }
      }
   } catch (Exception $e) {
      // TODO handle exception
      $SchedAjaxLogger->error("getOldTimetrack: exception raised !!");
   }

   echo json_encode($allTimetracks);
}

/**
 * get timetrack color & text depending on issue type
 * @param Issue $issue
 * @param Team $team
 * @return string color
 */
function formatActivity(array $activity, Team $team, $userId) {

   // could be static
   $teamProjects = array_keys($team->getProjects());

   $bugid = $activity['bugid'];

   if (!Issue::exists($bugid)) {
      $color = 'orange';
      $text = "Err_$bugid";
      $desc = "Task $bugid does not exist in Mantis DB !";
   } else {
      $issue = IssueCache::getInstance()->getIssue($activity['bugid']);
      $projectid = $issue->getProjectId();
      $prj = ProjectCache::getInstance()->getProject($projectid);

      if ($prj->isExternalTasksProject()) {
         $color = 'lightgrey';
         $text = $issue->getSummary();
         $desc = $issue->getSummary();
      } else {
         if (in_array($projectid, $teamProjects)) {
            if ($team->isSideTasksProject($projectid)) {
               $text = $issue->getSummary();
               $desc = $issue->getSummary();
               $color =  '#81BEF7';
            } else {
               $text = $bugid;
               $desc = "[$bugid] ".$issue->getSummary();
               $color =  "#8181F7";
            }
         } else {
            // user worked for another team
            $text = $bugid;
            $desc = "[$bugid] ".$issue->getSummary();
            $color =  '#A4A4A4';
         }
      }
   }

   $dxhtmlData = array(
       'start_date' => date('Y-m-d H:i:s', $activity['startTimestamp']),
       'end_date'   => date('Y-m-d H:i:s', $activity['endTimestamp']),
       'bugid'      => $bugid,
       "user_id"    => $userId,
       "text"       => $text,
       "color"      => $color,
       "desc"       => $desc);

   return $dxhtmlData;
}

function getProjection() {
   global $SchedAjaxLogger;

   try {
      $s = new SchedulerManager();
      $team_id = $_SESSION['teamid'];
      $user_id = $_SESSION['userid'];

      // Set timePerTaskPerUserList of scheduler manager
      $timePerTaskPerUserList = SchedulerManager::getTimePerTaskPerUserList($user_id, $team_id);
      $schedulerTimePerTaskPerUserList = transformToSchedulerModel($timePerTaskPerUserList);
      $s->setUserTaskList($schedulerTimePerTaskPerUserList);
      
      // Set task provider of scheduler manager
      $taskProviderId = SchedulerManager::getUserOption("taskProvider", $user_id, $team_id);
      $s->setTaskProvider($taskProviderId);
      
      $data = $s->execute();
      echo json_encode($data);
   } catch (Exception $e) {
      // TODO handle exception
      $SchedAjaxLogger->error("getProjection: exception raised !!");
   }
}

//function setTimeDefaultHandler(){
//   global $SchedAjaxLogger;
//   foreach()
//   $uptadeSuccessful = SchedulerManager::updateTimePerUserListOfTask($taskId, $taskUserList, $_SESSION['userid'], $_SESSION['teamid']);
//   if(!$uptadeSuccessful){
//      $SchedAjaxLogger->error("updateTimePerUserListOfTask: exception raised !!");
//   }
//}

function setTimePerUserList() {
   global $SchedAjaxLogger;
   //$SchedAjaxLogger->error('---------- setTimePerUserList ----------');
   
   $taskId = Tools::getSecurePOSTStringValue('taskId');
   //$SchedAjaxLogger->error($taskId);
   $usersTimeList = Tools::getSecurePOSTStringValue('taskUserList');
   $usersTimeList = json_decode(stripslashes($usersTimeList), true);
   
   
   if(null != $taskId) {
      if(null != $usersTimeList) {
         foreach($usersTimeList as $userTime) {
            $taskUserList[$userTime['userId']] = $userTime['userTime'];
         }
         $uptadeSuccessful = SchedulerManager::updateTimePerUserListOfTask($taskId, $taskUserList, $_SESSION['userid'], $_SESSION['teamid']);
         
      }
      else
      {
         $SchedAjaxLogger->error('---------- task ----------');
         $SchedAjaxLogger->error($taskId);
         $uptadeSuccessful = SchedulerManager::removeTimePerUserOfTask($taskId, $_SESSION['userid'], $_SESSION['teamid']);
      }
      
      if($uptadeSuccessful) {
         $data['scheduler_status'] = "SUCCESS";
      } else {
         $data['scheduler_status'] = T_("Invalid modifications");
      }
   }
   
   // Get time per user per task list
   $timePerUserPerTaskLibelleList = null;
   $timePerUserPerTaskList = SchedulerManager::getTimePerUserPerTaskList($_SESSION['userid'], $_SESSION['teamid']);

   // Set time Per User Per Task List with label
   foreach($timePerUserPerTaskList as $taskIdKey => $timePerUserList) {
      $taskSummary = IssueCache::getInstance()->getIssue($taskIdKey)->getSummary();
      $taskExternalReference = IssueCache::getInstance()->getIssue($taskIdKey)->getTcId();

      foreach($timePerUserList as $userIdKey => $time) {
         $userName = UserCache::getInstance()->getUser($userIdKey)->getName();
         $timePerUserPerTaskLibelleList[$taskIdKey]['users'][$userName] = $time;
         $timePerUserPerTaskLibelleList[$taskIdKey]['taskName'] = $taskSummary;
         $timePerUserPerTaskLibelleList[$taskIdKey]['externalReference'] = $taskExternalReference;
      }
   }
   $SchedAjaxLogger->error($taskExternalReference);
   $data['scheduler_timePerUserPerTaskLibelleList'] = $timePerUserPerTaskLibelleList;
   // return data (just an integer value)
   $jsonData = json_encode($data);
   echo $jsonData;
}


function getTaskUserList() {
   global $SchedAjaxLogger;
   //$SchedAjaxLogger->error('---------- getTaskUserList ----------');
   
   $taskEffortEstim = 0;
   
   $taskId = Tools::getSecurePOSTStringValue('taskId');
   
   // Get team members
   $team = TeamCache::getInstance()->getTeam($_SESSION['teamid']);
   $unselectedUserList = $team->getActiveMembers();
   $selectedUserList = $unselectedUserList;

   $tasksUserList = null;
   if(null != $taskId) {
      $taskHandlerId = IssueCache::getInstance()->getIssue($taskId)->getHandlerId();
      $taskEffortEstim = IssueCache::getInstance()->getIssue($taskId)->getEffortEstim();
      
      // Get task user list
      $tasksUserList = SchedulerManager::getTimePerUserListOfTask($taskId, $_SESSION['userid'], $_SESSION['teamid']);
      
      // If task user list exist in BD
      if(null != $tasksUserList) {
         if(null != $taskHandlerId) {
            // If task handler doesnt't belong to task user list
            if(!array_key_exists($taskHandlerId, $tasksUserList)) {
               // Calculate total users affected time
               $totalUsersTime = 0;
               foreach($tasksUserList as $time) {
                  $totalUsersTime += $time;
               }
               // Add task handler to list and affect to him the rest of time
               $tasksUserList[$taskHandlerId] = $taskEffortEstim - $totalUsersTime;
            }
         }
      } else {
         if (null != $taskHandlerId) {
            // Add task handler to list
            $tasksUserList[$taskHandlerId] = $taskEffortEstim;
         }
      }
            
      // Set unselected user list : For each user of the task
      foreach($tasksUserList as $key => $user) {
         // Remove user of unselected user list
         unset($unselectedUserList[$key]);
      }      
   }

   // Set selected user list : For each unselected user
   foreach($unselectedUserList as $key => $user) {
      // remove user of selected user list
      unset($selectedUserList[$key]);
   }

   asort($unselectedUserList);
   asort($selectedUserList);
   
   $data['scheduler_unselectedUserList'] = $unselectedUserList;
   $data['scheduler_selectedUserList'] = $selectedUserList;
   $data['scheduler_taskUserList'] = $tasksUserList;
   $data['scheduler_taskEffortEstim'] = $taskEffortEstim;
   $data['scheduler_taskHandlerId'] = $taskHandlerId;
   
   
   // return data (just an integer value)
   $jsonData = json_encode($data);
   echo $jsonData;
}

function removeTimePerUserList() {
   global $SchedAjaxLogger;
   
   $taskId = Tools::getSecurePOSTStringValue('taskId');
   
   if(null != $taskId) {
      
      SchedulerManager::updateTimePerUserListOfTask($taskId, null, $_SESSION['userid'], $_SESSION['teamid']);
   }
}

function transformToSchedulerModel($timePerTaskPerUserList) {
   $schedulerTimePerTaskPerUserList = array();
   if (NULL != $timePerTaskPerUserList) {
      foreach ($timePerTaskPerUserList as $userIdKey=>$taskList) {
            $schedulerTimePerTaskPerUserList[$userIdKey] = $taskList;
      }
   }
   return $schedulerTimePerTaskPerUserList;
}

function setOptions()
{
   global $SchedAjaxLogger;
   
   $taskProviderId = Tools::getSecurePOSTStringValue('taskProvider');
   
   SchedulerManager::setUserOption("taskProvider", $taskProviderId, $_SESSION['userid'], $_SESSION['teamid']);
}

//$_SESSION['tasksUserList']['id_de_tache']['id_du_user'] = temps_du_user

