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
      case 'getTaskList':
         getTaskList();
         break;
      case 'setTaskUserList':
         $data = setTimePerUserList();
         getAllTaskUserList($data);
         break;
      case 'getAllTaskUserList':
         getAllTaskUserList(null);
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
      $s->init();
      
      $data = $s->execute();
      echo json_encode($data);
   } catch (Exception $e) {
      // TODO handle exception
      $SchedAjaxLogger->error("getProjection: exception raised !!");
   }
}


/**
 * Get task list according to selected project
 */
function getTaskList()
{
   global $SchedAjaxLogger;
   
   $projectId = Tools::getSecurePOSTStringValue('projectId');
   
   if(null != $projectId)
   {
      $project = ProjectCache::getInstance()->getProject($projectId);
      $taskList = $project->getIssues();
   }
   
   // Set task id list
   foreach($taskList as $key => $task)
   {
      $statusThreshold = $task->getBugResolvedStatusThreshold();
      $status = $task->getStatus();

      if($status < $statusThreshold){
         if(0 < $task->getEffortEstim())
         {
            $taskIdList[$key] = $task->getSummary();
         }
      }
   }
   
   $data['scheduler_taskList'] = $taskIdList;
   
   $jsonData = json_encode($data);
   echo $jsonData;
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
      $taskSummary = IssueCache::getInstance()->getIssue($taskId)->getSummary();
      
      // If list of user exist => we add users to task
      if(null != $usersTimeList) 
      {
         foreach($usersTimeList as $userTime) 
         {
            $taskUserList[$userTime['userId']] = $userTime['userTime'];
         }
         $uptadeSuccessful = SchedulerManager::updateTimePerUserListOfTask($taskId, $taskUserList, $_SESSION['userid'], $_SESSION['teamid']);
         
         $data['scheduler_message'] = T_("Users have been affected to task : ") . $taskSummary;
      }
      else // If list of user doesn't exist => we remove users to task
      {
         $uptadeSuccessful = SchedulerManager::removeTimePerUserOfTask($taskId, $_SESSION['userid'], $_SESSION['teamid']);
         
         $data['scheduler_message'] = T_("Users have been remove from task : ") . $taskSummary;
      }
      
      if($uptadeSuccessful) {
         
         $data['scheduler_status'] = "SUCCESS";
         
      } else {
         $data['scheduler_status'] = T_("Invalid modifications");
         $data['scheduler_message'] = T_("Invalid modifications");
      }
   }
   return $data;
}

/**
 * Get time per user per task list [$task => [$userId => $time]]
 * With information of task (label, external reference,...)
 * @global type $SchedAjaxLogger
 * @param array $data : previous computed information to send to view
 */
function getAllTaskUserList($data = false)
{
   global $SchedAjaxLogger;
   //$SchedAjaxLogger->error('---------- getAllTaskUserList ----------');
   
   // Get time per user per task list
   $timePerUserPerTaskLibelleList = null;
   $timePerUserPerTaskList = SchedulerManager::getTimePerUserPerTaskList($_SESSION['userid'], $_SESSION['teamid']);
   
   if(null != $timePerUserPerTaskList)
   {
      // Set time Per User Per Task List with label
      foreach($timePerUserPerTaskList as $taskIdKey => $timePerUserList) {
         $taskInfos = IssueCache::getInstance()->getIssue($taskIdKey);
         $taskSummary = $taskInfos->getSummary();
         $taskExternalReference = $taskInfos->getTcId();
         $projectId = $taskInfos->getProjectId();

         foreach($timePerUserList as $userIdKey => $time) {
            $userName = UserCache::getInstance()->getUser($userIdKey)->getName();
            $timePerUserPerTaskLibelleList[$taskIdKey]['users'][$userName] = $time;
            $timePerUserPerTaskLibelleList[$taskIdKey]['summary'] = $taskSummary;
            $timePerUserPerTaskLibelleList[$taskIdKey]['externalReference'] = $taskExternalReference;
            $timePerUserPerTaskLibelleList[$taskIdKey]['projectId'] = $projectId;
         }
      }
   }
   
   $data['scheduler_timePerUserPerTaskInfosList'] = $timePerUserPerTaskLibelleList;
   
   // Create ajax file path
   $sepChar = DIRECTORY_SEPARATOR;
   $directory = Constants::$codevRootDir.$sepChar. "tpl" .$sepChar. "ajax" .$sepChar. schedulerAffectationSummary .".html";
   
   // Generate html table
   $smartyHelper = new SmartyHelper();
   foreach ($data as $smartyKey => $smartyVariable) {
      $smartyHelper->assign($smartyKey, $smartyVariable);
   }
   $html = $smartyHelper->fetch($directory);
   
   $data["scheduler_summaryTableHTML"] = $html;

   $jsonData = json_encode($data);
   echo $jsonData;
}

/**
 * Get to the view the list of user and their time on a task [$userId => $time]
 * @global type $SchedAjaxLogger
 */
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
               // Add task handler to list
               $tasksUserList[$taskHandlerId] = null;
            }
         }
      } else {
         if (null != $taskHandlerId) {
            // Add task handler to list
            $tasksUserList[$taskHandlerId] = null;
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

function setOptions()
{
   global $SchedAjaxLogger;
   
   $taskProviderId = Tools::getSecurePOSTStringValue('taskProvider');
   $displayableInfo = Tools::getSecurePOSTStringValue('displayableInfo');

   SchedulerManager::setUserOption(SchedulerManager::OPTION_taskProvider, $taskProviderId, $_SESSION['userid'], $_SESSION['teamid']);
   SchedulerManager::setUserOption(SchedulerManager::OPTION_displayedInfo, $displayableInfo, $_SESSION['userid'], $_SESSION['teamid']);
   
   $data['scheduler_status'] = "SUCCESS";
   $data['scheduler_message'] = T_("Options saved successfully");
   
   // return data (just an integer value)
   $jsonData = json_encode($data);
   echo $jsonData;
}

//$_SESSION['tasksUserList']['id_de_tache']['id_du_user'] = temps_du_user

