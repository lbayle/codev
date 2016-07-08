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

   /**
    * Tasks to be planified
    * @var array[bugid] => duration
    */
   private $todoTaskIdList = array();


   private $userTaskList = array();
   private $userCursorList = array();

   private $userLatestActivity = array();

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

      // sort todoTaskIdList once for all,
      // this avoids schedulerTaskProvider to do it at each createCandidateTaskList() call
      $this->sortTodoTaskIdList();

      $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
      $currentDay = mktime(0, 0, 0);
      $projectionDay = 120;
      $endDate = strtotime("+$projectionDay day",$currentDay);
      
      for($date = $currentDay; $date < $endDate; $date=strtotime("+1 day",$date)) {

         $users = array_keys($this->userTaskList);
         foreach ($users as $userId) {
            $midnightTimestamp = $date;
            $userAvailableTime = $this->getUserAvailableTime($userId, $midnightTimestamp);

            while(0 < $userAvailableTime && array_key_exists($userId, $this->userTaskList)){
                  $nextTaskId = $this->schedulerTaskProvider->getNextUserTask(array_keys($this->userTaskList[$userId]), $this->userCursorList[$userId]);
                  $this->userCursorList[$userId] = $nextTaskId;
                  if(NULL != $nextTaskId) {
                     $timeUsed = $this->decreaseBacklog($userId, $nextTaskId, $userAvailableTime);
                     if(0 != $timeUsed) {
                        $userAvailableTime -= $timeUsed;
                        $endT = $midnightTimestamp + $timeUsed*86400; // 24*60*60 (day -> ms);
                        $color = $this->getColor($endT, $nextTaskId);

                        // update latest activity or create a new one if different task
                        $prevActivity = $this->userLatestActivity[$userId];
                        if (NULL == $prevActivity) {
                           // first activity fot this user
                           $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                           $ganttActivity->setColor($color);
                           $this->userLatestActivity[$userId] = $ganttActivity;
                        } else {
                           // if same issue, just extend $prevActivity endTimestamp
                           if (($prevActivity->bugid == $nextTaskId) &&
                               ($prevActivity->endTimestamp == $midnightTimestamp)) {
                              $prevActivity->endTimestamp = $endT;
                              $prevActivity->setColor($color);
                           } else {
                              // store previous activity
                              array_push($this->data["activity"], $prevActivity->getDxhtmlData());

                              // create a new one
                              $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                              $ganttActivity->setColor($color);
                              $this->userLatestActivity[$userId] = $ganttActivity;
                           }
                        }

                        // next activity will start at the end of the previous one.
                        $midnightTimestamp = $endT;
                     } else {
                        // $timeUsed == 0
                        $userAvailableTime = 0;
                     }
                  } else {
                     // $nextTaskId == NULL
                     $userAvailableTime = 0;
                  }
            } // while $userAvailableTime
         } // foreach userid

         if(empty($this->userTaskList)) {
            // all tasks of each user have been planified, no need to continue calendar
            break;
         }
      } // day

      // store latest activities, still in cache
      foreach ($this->userLatestActivity as $ganttActivity) {
         array_push($this->data["activity"], $ganttActivity->getDxhtmlData());
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

   /**
    * some schedulerTaskProvider use their own sort criteria,
    * but others may use the initial order.
    * This method ensures that the nitial todoList is ordered with
    * the standard Issue ordering algorithm -> see Issue::compare().
    */
   private function sortTodoTaskIdList() {
      $issueList = array();

      foreach (array_keys($this->todoTaskIdList) as $bugid) {
         $issueList[$bugid] = IssueCache::getInstance()->getIssue($bugid);
      }
      // use standard Issue compare method
      Tools::usort($issueList);

      $newTodoList = array();
      foreach ($issueList as $issue) {
         $bugid = $issue->getId();
         $newTodoList[$bugid] = $this->todoTaskIdList[$bugid];
      }
      unset($this->todoTaskIdList);
      $this->todoTaskIdList = $newTodoList;
   }

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
         foreach($tasklist as $taskId=>$duration){
            $this->userTaskList[$useridkey][$taskId] = $jsonUserTaskList[$useridkey][$taskId];
         }
         $this->userCursorList[$useridkey] = null;
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
      
      if($this->userTaskList[$userId][$taskid] > $userAvailableTime)
      {
         $this->userTaskList[$userId][$taskid] -= $userAvailableTime;
         $this->todoTaskIdList[$taskid] -= $userAvailableTime;
         $timeUsed = $userAvailableTime;
      }
      else{
         //self::$logger->error("unset");
         $timeUsed = $this->userTaskList[$userId][$taskid];
         unset($this->userTaskList[$userId][$taskid]);
         $this->todoTaskIdList[$taskid] -= $userAvailableTime;
         if(empty($this->userTaskList[$userId])){
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

   /**
    *
    */
   private function addHandlerTask(){
         $team = TeamCache::getInstance()->getTeam($this->team_id);
         $issueList = $team->getCurrentIssueList(false, true, false);

         foreach($issueList as $bugid => $issue)
         {
            $handlerId = $issue->getHandlerId();

            // duration is the Backlog of the task, or if not set, the MAX(EffortEstim, mgrEffortEstim)
            $duration = $issue->getDuration();
            if(0 < $duration)
            {
               $this->todoTaskIdList[$bugid] = $duration;
               $this->userTaskList[$handlerId][$bugid] = $duration; // $issue->getEffortEstim();
               $this->userCursorList[$handlerId] = null;
            }
         }
         self::$logger->error($this->todoTaskIdList);
         self::$logger->error($this->userTaskList);
   }
}

SchedulerManager::staticInit();

