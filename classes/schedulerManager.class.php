<?php
/*
   This file is part of CoDevTT.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

class SchedulerManager{

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   private $team_id;
   private $todoTaskIdList = array();
   private $userTaskList = array();
   private $schedulerTaskProvider;
   private $data = array();

   public function __construct() {
      $this->team_id = $_SESSION['teamid'];
      $this->data["activity"] = array();
//      $mList = TeamCache::getInstance()->getTeam($this->team_id)->getActiveMembers();
//      $this->userTaskList = array_keys($mList);
      //$this->setTasks();
        //$this->setBouchon();
      
      $this->addHandlerTask();
      $this->schedulerTaskProvider = new SchedulerTaskProvider();
   }
   
   public function execute() {
//      self::$logger->error($this->todoTaskIdList);
//      self::$logger->error($this->userTaskList);
      $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
      $currentDay = mktime(0, 0, 0);
      $projectionDay = 30;
      $endDate = $currentDay+$projectionDay*24*60*60;
      for($date = $currentDay; $date < $endDate; $date+=24*60*60) {
         foreach ($this->userTaskList as $userId=>$userData) {
            $midnightTimestamp = $date;
            $userAvailableTime = $this->getUserAvailableTime($userId, $midnightTimestamp);
            while(0 < $userAvailableTime && array_key_exists($userId, $this->userTaskList)){
                  $nextTaskId = $this->schedulerTaskProvider->getNextUserTask(array_keys($this->userTaskList[$userId]["tasks"]), $this->userTaskList[$userId]["cursor"]);
                  $this->userTaskList[$userId]["cursor"] = $nextTaskId;
//                  self::$logger->error($userId);
//                  self::$logger->error($this->userTaskList[$userId]["cursor"]);
                  if(NULL != $nextTaskId){
                     $timeUsed = $this->decreaseBacklog($userId, $nextTaskId, $userAvailableTime);
                     if(0 != $timeUsed){
                        $userAvailableTime -= $timeUsed;
                        $endT = $midnightTimestamp + $timeUsed*24*60*60;
                        $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                        $midnightTimestamp = $endT;
                        $color = $this->getColor($midnightTimestamp, $nextTaskId);
                        $ganttActivity->setColor($color);
                        array_push($this->data["activity"], $ganttActivity->getDxhtmlData());
                     }
                     else{
                        $userAvailableTime = 0;
                     }
                  }
                  else{
                     $userAvailableTime = 0;
                  }

            }
         }
         if(empty($this->userTaskList))
         {
            break;
         }
      }
      $this->createBacklogData();
      return $this->data;
   }
   
   private function createBacklogData(){
      //$this->data["backlog"] = $this->userTaskList;
      foreach($this->userTaskList as $userid=>$taskList){
         $user = UserCache::getInstance()->getUser($userid);
         $userName = $user->getRealname();
         
         $this->data["backlog"][$userName] = $taskList;
      }
   }
   
   //done
   /**
    * Set todoTaskList value
    */
   public function setTasks($tasksUserList){
      $this->todoTaskIdList = array_replace($this->todoTaskIdList, $tasksUserList);
   }
   
   public function setUserTaskList($jsonUserTaskList){
//      self::$logger->error($jsonUserTaskList);
//      self::$logger->error($this->userTaskList);
      foreach($jsonUserTaskList as $useridkey=>$tasklist){
         foreach($tasklist['tasks'] as $taskId=>$duration){
            $this->userTaskList[$useridkey]['tasks'][$taskId] = $jsonUserTaskList[$useridkey]['tasks'][$taskId];
         }
         $this->userTaskList[$useridkey]['cursor'] = null;
      }
   }
   
   private function getColor($midnightTimestamp, $taskId){
      $task = IssueCache::getInstance()->getIssue($taskId);
      //self::$logger->error($task->getDeadLine());
      $deadline = $task->getDeadLine();
      if(($midnightTimestamp < $deadline) || (null == $deadline)){
         return "green";
      }
      else{
         return "red";
      }
   }   

   private function decreaseBacklog($userId, $taskid, $userAvailableTime) {
      
   if($this->userTaskList[$userId]["tasks"][$taskid] > $userAvailableTime)
      {
         $this->userTaskList[$userId]["tasks"][$taskid] -= $userAvailableTime;
         $this->todoTaskIdList[$taskid] -= $userAvailableTime;
         $timeUsed = $userAvailableTime;
      }
      else{
         //self::$logger->error("unset");
         $timeUsed = $this->userTaskList[$userId]["tasks"][$taskid];
         unset($this->userTaskList[$userId]["tasks"][$taskid]);
         $this->todoTaskIdList[$taskid] -= $userAvailableTime;
         if(empty($this->userTaskList[$userId]["tasks"])){
            unset($this->userTaskList[$userId]);
         }
         if(0 >= $this->todoTaskIdList[$taskid]){
            unset($this->todoTaskIdList[$taskid]);
            $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
         }
      }
     
//      self::$logger->error(UserCache::getInstance()->getUser($userId)->getName());
//      self::$logger->error($this->userTaskList);
//      self::$logger->error($taskid);
//      self::$logger->error($this->todoTaskIdList[$taskid]);
      return $timeUsed;
   }
   
   private function getUserAvailableTime($userId, $midnightTimestamp){
      $user = UserCache::getInstance()->getUser($userId);
      return $user->getAvailableTime($midnightTimestamp);
   }


   public static function updateTasksPerUser($jsonData) {
      Config::setValue($id, $value, $type, $project_id=0, $user_id=0, $team_id=0, $command_id=0, $cset_id=0, $service_id=0);
   }
   
   /**
    * Get [$userId => [$taskId => $time]] of user/team
    * @param type $userId
    * @param type $teamId
    * @return associative array : [$userId => [$taskId => $time]]
    */
   public static function getTimePerTaskPerUserList($userId, $teamId = null)
   {
      $timePerTaskPerUserJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
      $timePerTaskPerUser = json_decode($timePerTaskPerUserJson, true); // [$userId => [$taskId => $time]]
      //self::$logger->error($timePerTaskPerUser);
      return $timePerTaskPerUser;
   }
   
   /**
    * Get [$taskId => [$userId => $time]] of user/team
    * @param type $userId
    * @param type $teamId
    * @return associative array : [$taskId => [$userId => $time]]
    */
   public static function getTimePerUserPerTaskList($userId, $teamId = null)
   {
      $timePerTaskPerUserJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
      $timePerTaskPerUser = json_decode($timePerTaskPerUserJson, true); // [$userId => [$taskId => $time]]
      
      $timePerUserPerTask = null;
      if(null != $timePerTaskPerUser)
      {
         foreach($timePerTaskPerUser as $userIdKey => $timePerTask)
         {
            foreach($timePerTask as $taskIdKey => $time)
            {
               $timePerUserPerTask[$taskIdKey][$userIdKey] = $time;
            }
         }
      }
      
      return $timePerUserPerTask;
   }
   
      /**
    * Get [$taskId => $time] of user/team
    * @param type $userId
    * @param type $teamId
    * @return associative array : [$taskId => $time]
    */
   public static function getTimePerTaskList($userId, $teamId = null)
   {
      $timePerTaskPerUserJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
      $timePerTaskPerUser = json_decode($timePerTaskPerUserJson, true); // [$userId => [$taskId => $time]]
      
      $timePerTask = array();
      if(null != $timePerTaskPerUser)
      {
         foreach($timePerTaskPerUser as $userIdKey => $timePerTaskPerUser)
         {
            foreach($timePerTaskPerUser as $taskIdKey => $time)
            {
//               if(NULL == $timePerTask[$taskIdKey]){
//                  $timePerTask[$taskIdKey] = 0;
//               }
//               else{
//                  $timePerTask[$taskIdKey] += $time;
//               }
               $timePerTask[$taskIdKey] += $time;
            }
            
         }
      }
      
      return $timePerTask;
   }
   
   /**
    * Update time of user of a task
    * Associative array created : [$userId => [$taskId => $time]]
    * @param type $taskId
    * @param type $userTimeList : Associative array : [$userId => $time]
    * @param type $userId : current User id
    * @param type $teamId : curent user team id
    * @return boolean : true if updated, false if not
    */
   public static function updateTimePerUserListOfTask($taskId, $userTimeList, $userId, $teamId)
   {
      
      if(self::isTimePerUserListValid($taskId, $userTimeList))
      {
         // Get old configuration
         $userTaskTimeListJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
         $userTaskTimeList = json_decode($userTaskTimeListJson, true);

         // If options already exist
         if(null != $userTaskTimeList)
         {
            if(null != $userTimeList)
            {
               // For each users formerly affected to the task, remove time concerning the task 
               foreach($userTaskTimeList as $keyUserId => $taskTimeList)
               {
                  unset($userTaskTimeList[$keyUserId][$taskId]); 
               }
            }
         }
         
         foreach($userTaskTimeList as $keyUserId => $taskTimeList)
         {
            if(null == $userTaskTimeList[$keyUserId])
            {
               unset($userTaskTimeList[$keyUserId]);
            }
         }
         
         // For each users newly affected to the task, add time concerning the task
         foreach($userTimeList as $keyUser => $userTime)
         {
            $userTaskTimeList[$keyUser][$taskId] = $userTime;
         }

         $userTaskTimeListJson = json_encode($userTaskTimeList);
         
         Config::setValue(Config::id_schedulerOptions, $userTaskTimeListJson, Config::configType_string, NULL, 0, $userId, $teamId);
         
         return true;
      }
      
      return false;
   }
   
   /**
    * Get [$user => $time] of task
    * @param type $taskId
    * @param type $userId : id of current user
    * @param type $teamId : curent user team id
    * @returns associative array : [$user => $time]
    */
   public static function getTimePerUserListOfTask($taskId, $userId, $teamId)
   {
      // Get config from BD 
      $userTaskTimeListJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
      // Associative array getted : [$userId => [$taskId => $time]]
      $userTaskTimeList = json_decode($userTaskTimeListJson, true);
      
      
      $userTimeList = null;
      if(null != $userTaskTimeList)
      {
         // Foreach user of BD list
         foreach($userTaskTimeList as $keyUserId => $taskTimeList)
         {
            // if task is affected to user
            if(null != $taskTimeList[$taskId])
            {
               $userTimeList[$keyUserId] = $taskTimeList[$taskId];
            }
         }
      }
      
      return $userTimeList;
   }
   
   
   
   /**
    * 
    * @param type $taskId
    * @param type $userTimeList : Associative array : [$userId => $time]
    */
   public static function isTimePerUserListValid($taskId, $userTimeList)
   {
      $totalUsersTime = 0;
      $estimedTime = IssueCache::getInstance()->getIssue($taskId)->getEffortEstim();
      
      foreach ($userTimeList as $userTime)
      {
         $totalUsersTime += $userTime;
      }
      
      if($totalUsersTime == $estimedTime)
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   
   private function addHandlerTask(){
         $team = TeamCache::getInstance()->getTeam($this->team_id);
         $taskList = $team->getTeamIssueList(false, false);
         
         $activeTeam = $team->getActiveMembers();
         
         foreach($taskList as $key => $task)
         {
            $statusThreshold = $task->getBugResolvedStatusThreshold();
            $status = $task->getStatus();
            
            if($status < $statusThreshold){
               $handlerId = $task->getHandlerId();
            
               if(array_key_exists($handlerId, $activeTeam)){
                  $effEstim = $task->getEffortEstim();
                  if(0 < $effEstim)
                  {
                     $taskId = $task->getId();
                     $this->todoTaskIdList[$taskId] = $effEstim;
                     $this->userTaskList[$handlerId]['tasks'][$taskId] = $effEstim;
                     $this->userTaskList[$handlerId]['cursor'] = null;
                  }
               }
            }
         }
         //self::$logger->error($this->userTaskList);
   }
}

SchedulerManager::staticInit();

