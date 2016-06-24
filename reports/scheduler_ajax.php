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

$SchedAjaxLogger = Logger::getLogger("scheduler_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');

      //$smartyHelper = new SmartyHelper();
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
         setTaskUserList();
         break;
      case 'getTaskUserList':
         getTaskUserList();
         break;
      default:
          Tools::sendNotFoundAccess();
          break;
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

function getTeam(){
   global $SchedAjaxLogger;
   
   $data = array();
   $team_id = $_SESSION['teamid'];
   $mList = TeamCache::getInstance()->getTeam($team_id)->getActiveMembers();
   foreach($mList as $key=>$m){
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
         $endOfCycle = strtotime("+3 month", mktime(0, 0, 0));
         $timeTracks = $user->getTimeTracks($user->getArrivalDate($team_id), $endOfCycle);

         foreach($timeTracks as $timetrack_id=>$timetrack){

            // TODO: check if timetrack is on an issue of the team's projects. if not, display other color
            
            $issue_id = $timetrack->getIssueId();
            if (Issue::exists($issue_id)) {
               $issue = IssueCache::getInstance()->getIssue($issue_id);
               $issue_name = $issue->getSummary();
            } else {
               // issue does not exist in Mantis DB
               $issue_name = "Err_$issue_id";
            }
            $midnightTimestamp = $timetrack->getDate();
            $dateParse = date('Y-m-d H:i:s', $midnightTimestamp);
            $endTimestamp = $midnightTimestamp + $timetrack->getDuration()* 86400; // 24*60*60;
            $endDateParse = date('Y-m-d H:i:s', $endTimestamp);
            $pushdata = array("text"=>"$issue_name","start_date"=>"$dateParse" ,"end_date"=>"$endDateParse" ,"user_id"=>$userId);
            //$pushdata = array("text"=>"$timetrack_id", "user_id"=>$key);
            array_push($allTimetracks, $pushdata);
         }
      }
   } catch (Exception $e) {
      // TODO handle exception
      $SchedAjaxLogger->error("getOldTimetrack: exception raised !!");
   }

   echo json_encode($allTimetracks);
}

function getProjection(){
   $logger = Logger::getLogger("scheduler_ajax");

   try {
      $s = new SchedulerManager();
      $data = $s->execute();
      echo json_encode($data);
      
   } catch (Exception $e) {
      // TODO handle exception
      $logger->error("getProjection: exception raised !!");
   }
}

function setTaskUserList()
{
   $logger = Logger::getLogger("scheduler_ajax");
   $logger->error('---------- setTaskUserList ----------');
   
   $taskId = Tools::getSecurePOSTStringValue('taskId');
   $logger->error($taskId);
   $usersTimeList = Tools::getSecurePOSTStringValue('taskUserList');
   $usersTimeList = json_decode(stripslashes($usersTimeList), true);
   
   if(null != $taskId)
   {
      if(null != $usersTimeList)
      {
         foreach($usersTimeList as $userTime)
         {
            $taskUserList[$userTime['userId']] = $userTime['userTime'];
         }
         $_SESSION['tasksUserList'][$taskId] = $taskUserList;
         $logger->error($_SESSION['tasksUserList']);
      }
   }
   
   $data['scheduler_status'] = "SUCCESS";

   // return data (just an integer value)
   $jsonData = json_encode($data);
   echo $jsonData;
}

function getTaskUserList()
{
   $logger = Logger::getLogger("scheduler_ajax");
   $logger->error('---------- getTaskUserList ----------');
   
   $taskId = Tools::getSecurePOSTStringValue('taskId');
   $logger->error($taskId);
   
   // Get team members
   $team = TeamCache::getInstance()->getTeam($_SESSION['teamid']);
   $unselectedUserList = $team->getActiveMembers();
   $selectedUserList = $unselectedUserList;

   $tasksUserList = null;
   if(null != $taskId)
   {
      if(null != $_SESSION['tasksUserList'])
      {
         if(null != $_SESSION['tasksUserList'][$taskId])
         {
            // Get task user list
            $tasksUserList = $_SESSION['tasksUserList'][$taskId];

            // Set unselected user list : For each user of the task
            foreach($tasksUserList as $key => $user)
            {
               // Remove user of unselected user list
               unset($unselectedUserList[$key]);
            }
         }
      }
   }

   // Set selected user list : For each unselected user
   foreach($unselectedUserList as $key => $user)
   {
      // remove user of selected user list
      unset($selectedUserList[$key]);
   }

   asort($unselectedUserList);
   asort($selectedUserList);
   
   $taskEffortEstim = IssueCache::getInstance()->getIssue($taskId)->getEffortEstim();

   $data['scheduler_unselectedUserList'] = $unselectedUserList;
   $data['scheduler_selectedUserList'] = $selectedUserList;
   $data['scheduler_taskUserList'] = $tasksUserList;
   $data['scheduler_taskEffortEstim'] = $taskEffortEstim;
   
   // return data (just an integer value)
   $jsonData = json_encode($data);
   echo $jsonData;
}


