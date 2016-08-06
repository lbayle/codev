<?php
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

class SchedulerManager {

   const OPTION_timePerTaskPerUser = 'timePerTaskPerUser'; // array
   const OPTION_taskProvider       = 'taskProvider';    // Scheduling method
   const OPTION_isDisplayExtRef    = 'isDisplayExtRef';
   const OPTION_nbDaysForecast     = 'nbDaysForecast';  // nb days to compute
   const OPTION_displayedUsers     = 'displayedUsers';  // array(userid): display subset of team users
   const OPTION_nbDaysToDisplay    = 'nbDaysToDisplay'; // displayed window

   private static $logger;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   private $team_id;
   private $user_id;

   /**
    * Tasks to be planified
    * @var array[bugid] => duration
    */
   private $todoTaskIdList = array();
   
   /**
    * Scheduler settings allow to define the max time that
    * a user is allowed to spent on a task (time quota).
    * Quota can be NULL if user has no quota for this task ('auto' mode)
    *
    * @var array[userid] = array(bugid => time quota)
    */
   private $userTaskList = array();

   /**
    * while processing, keep track of the latest planified task for each user.
    * @var array[userid] => bugid
    */
   private $userCursorList = array();

   /**
    * while processing, cache to concat consecutive activities in one strike
    * (for the same user on the same task)
    * @var array[userid] => GanttActivity
    */
   private $userLatestActivity = array();

   /**
    * Part of the engine responsible for choosing the next task to planify
    * @var SchedulerTaskProviderAbstract
    */
   private $schedulerTaskProvider;
   
   /**
    * List of scheduler task provider usable in scheduler manager
    * @var array of SchedulerTaskProviderAbstract
    */
   private $schedulerTaskProviderList;
   
   /**
    * keep task start & end dates for Gantt & activity colors
    * bugid => array{ 'startTimestamp' = timestamp,
    *                 'endTimestamp'   = timestamp}
    *
    * @var array of array
    */
   private $todoTaskDates = array();

   private $data = array();

   public function __construct() {
      self::$logger->error("constructeur");

      $this->team_id = $_SESSION['teamid'];
      $this->user_id = $_SESSION['userid'];
      $this->data["activity"] = array();
      
      $this->addHandlerTask();
      $this->schedulerTaskProviderList = array('SchedulerTaskProvider0', 'SchedulerTaskProvider');
   }
   
   public function init()
   {
      self::$logger->error("init");

      // Set timePerTaskPerUserList of scheduler manager
      $timePerUserPerTaskList = self::getTimePerUserPerTaskList($this->user_id, $this->team_id);
      $timePerTaskPerUserList = null;
      
      $timePerUserPerTaskList = self::calculateAutoAffectation($timePerUserPerTaskList);
      $timePerTaskPerUserList = self::transformToTimePerTaskPerUserList($timePerUserPerTaskList);
      
      $this->setUserTaskList($timePerTaskPerUserList);

      
      // Set task provider of scheduler manager
      $taskProviderName = self::getUserOption(self::OPTION_taskProvider, $this->user_id, $this->team_id);
      $this->setTaskProvider($taskProviderName);
      
   }
   
   public function execute() {
      
      $isDisplayExtRef = self::getUserOption(self::OPTION_isDisplayExtRef, $this->user_id, $this->team_id);
      $projectionDay   = self::getUserOption(self::OPTION_nbDaysForecast, $this->user_id, $this->team_id);

      // sort todoTaskIdList once for all,
      // this avoids schedulerTaskProvider to do it at each call to createCandidateTaskList()
      $this->sortTodoTaskIdList();
      
      $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
      $currentDay = mktime(0, 0, 0);
      $endDate = strtotime("+$projectionDay day",$currentDay);
      
      for ($date = $currentDay; $date < $endDate; $date=strtotime("+1 day",$date)) {

         $users = array_keys($this->userTaskList);
         foreach ($users as $userId) {
            $midnightTimestamp = $date;
            $userAvailableTime = $this->getUserAvailableTime($userId, $midnightTimestamp);

            while(0 < $userAvailableTime && array_key_exists($userId, $this->userTaskList)) {
                  $nextTaskId = $this->schedulerTaskProvider->getNextUserTask(array_keys($this->userTaskList[$userId]), $this->userCursorList[$userId]);
                  $this->userCursorList[$userId] = $nextTaskId;
                  if (NULL != $nextTaskId) {
                     $timeUsed = $this->decreaseBacklog($userId, $nextTaskId, $userAvailableTime);
                     if (0 != $timeUsed) {
                        $userAvailableTime -= $timeUsed;
                        $endT = $midnightTimestamp + $timeUsed*86400; // 24*60*60 (day -> ms);

                        // store/update task dates
                        if (!array_key_exists($nextTaskId, $this->todoTaskDates)) {
                           $this->todoTaskDates[$nextTaskId] = array ('startTimestamp' => $midnightTimestamp,
                                                                      'endTimestamp'   => $endT);
                        } else {
                           $this->todoTaskDates[$nextTaskId]['endTimestamp'] = $endT;
                        }

                        // update latest activity or create a new one if different task
                        $prevActivity = $this->userLatestActivity[$userId];
                        if (NULL == $prevActivity) {
                           // first activity fot this user
                           $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                           $this->userLatestActivity[$userId] = $ganttActivity;
                        } else {
                           // if same issue, just extend $prevActivity endTimestamp
                           if (($prevActivity->bugid == $nextTaskId) &&
                               ($prevActivity->endTimestamp == $midnightTimestamp)) {
                              $prevActivity->endTimestamp = $endT;
                           } else {
                              // store previous activity
                              array_push($this->data["activity"], $prevActivity->getDxhtmlData($isDisplayExtRef));

                              // create a new one
                              $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
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

         if (empty($this->userTaskList)) {
            // all tasks of each user have been planified, no need to continue calendar
            break;
         }
      } // day

      // store latest activities, still in cache
      foreach ($this->userLatestActivity as $ganttActivity) {
         array_push($this->data["activity"], $ganttActivity->getDxhtmlData($isDisplayExtRef));
      }
      
      $this->createBacklogData();
      $this->adjustColor();
      return $this->data;
   }
   
  
   private function adjustColor(){

      $warnThreshold = 5 ; // TODO: set as option (5 days before deadline)

      foreach($this->data["activity"] as $key=>$data){
        
        $bugid = $data["bugid"];
        $deadline = IssueCache::getInstance()->getIssue($bugid)->getDeadLine();

        if (NULL == $deadline) {
           // task has no deadline => light green
           $this->data["activity"][$key]["color"] = 'lightgreen';
           continue;
        }

        $warnline = strtotime('-'.$warnThreshold.' days', $deadline);  // n days before deadline
        $activityEndDate = strtotime($data["end_date"]);
        $taskEndTimestamp = $this->todoTaskDates[$bugid]['endTimestamp'];

        if ($taskEndTimestamp > $deadline) {
           // task is late, but:
           if ($activityEndDate < $deadline) {
              // this activity ends before deadline => light red
              $this->data['activity'][$key]['color'] = '#FF816B';
           } else {
              // this activity ends after deadline => red
              $this->data['activity'][$key]['color'] = '#FF421F';
           }
        } else {
           // task is on time, but:
           if ($taskEndTimestamp > $warnline) {
              // task ends shortly before the deadline => orange
              $this->data['activity'][$key]["color"] = '#FFBA00';
           } else {
              //  task is on time => green
              $this->data['activity'][$key]["color"] =  '#009900'; // 'green';
           }
        }
      }
   }

   private function createBacklogData() {
      //$this->data["backlog"] = $this->userTaskList;
      foreach($this->userTaskList as $userid=>$taskList) {
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
    * Initialize $userTaskList with the scheduler settings
    *
    * @param type $jsonUserTaskList
    */
   public function setUserTaskList($jsonUserTaskList) {
      if(null != $jsonUserTaskList){
         foreach($jsonUserTaskList as $useridkey=>$tasklist) {
            foreach($tasklist as $taskId=>$duration) {
               $this->userTaskList[$useridkey][$taskId] = $jsonUserTaskList[$useridkey][$taskId];
            }
            $this->userCursorList[$useridkey] = null;
         }
      }
      
   }
   
   /**
    * Set task provider of scheduler manager
    * @param type $taskProviderName
    */
   public function setTaskProvider($taskProviderName = null){
      if(!in_array($taskProviderName, $this->schedulerTaskProviderList))
      {
         $taskProviderName = $this->schedulerTaskProviderList[0];
         self::$logger->error("setTaskProvider($taskProviderName): Unknown taskProvider, using default.");
      }
      
      // Instantiate scheduler provider
      $providerReflection = new ReflectionClass($taskProviderName); 
      $this->schedulerTaskProvider = $providerReflection->newInstance();
   }
   
   /**
    * TODO : describe actions ...
    *
    * @param int $userId
    * @param int $taskid
    * @param int $userAvailableTime
    * @return int Effective time spent on the task for this activity
    */
   private function decreaseBacklog($userId, $taskid, $userAvailableTime) {
      if($this->userTaskList[$userId][$taskid] > $userAvailableTime) {
         $this->userTaskList[$userId][$taskid] -= $userAvailableTime;
         $this->todoTaskIdList[$taskid] -= $userAvailableTime;
         $timeUsed = $userAvailableTime;
      } else {
         $timeUsed = $this->userTaskList[$userId][$taskid];
         unset($this->userTaskList[$userId][$taskid]);
         $this->todoTaskIdList[$taskid] -= $timeUsed;

         if (empty($this->userTaskList[$userId])) {
            unset($this->userTaskList[$userId]);
         }
         if(0 >= round($this->todoTaskIdList[$taskid],2)){
            unset($this->todoTaskIdList[$taskid]);
            $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
         }
      }
      return $timeUsed;
   }
   
   private function getUserAvailableTime($userId, $midnightTimestamp) {
      $user = UserCache::getInstance()->getUser($userId);
      return $user->getAvailableTime($midnightTimestamp);
   }
   
   /**
    * Get scheduler options of user / team from DB
    * @param type $userId
    * @param type $teamId
    * @return type
    */
   public static function getUserOptions($userId, $teamId = null)
   {
      // set default values (if new options are added, user may not have them)
      $userOptions = array (
         self::OPTION_taskProvider => 'SchedulerTaskProvider0', // $this->schedulerTaskProviderList[0],
         self::OPTION_isDisplayExtRef => FALSE,
         self::OPTION_nbDaysForecast => 90,  // 90 days = 3 month
         self::OPTION_nbDaysToDisplay => 30, // 30 days = 1 month
         self::OPTION_displayedUsers => NULL,
         self::OPTION_timePerTaskPerUser => NULL,
      );

      // override default values with user settings
      $userOptionsJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $teamId, 0, 0, 0), true);
      if(null != $userOptionsJson) {
         $options = json_decode($userOptionsJson, true);
         foreach ($options as $key => $value) {
            $userOptions[$key] = $value;
         }
      }
      return $userOptions;
   }
   
   /**
    * Get user specific option from DB
    * @param string $optionName
    * @param type $userId
    * @param type $teamId
    * @return type
    */
   public static function getUserOption($optionName, $userId, $teamId = null)
   {
      $userOptions = self::getUserOptions($userId, $teamId);
//      self::$logger->error("getUserOption($optionName, $userId, $teamId) = ". $userOptions[$optionName]);
      return $userOptions[$optionName];
   }

   /**
    * Get [$userId => [$taskId => $time]] of user/team from DB
    * @param type $userId
    * @param type $teamId
    * @return associative array : [$userId => [$taskId => $time]]
    */
   public static function getTimePerTaskPerUserList($userId, $teamId = null) {
      return self::getUserOption(self::OPTION_timePerTaskPerUser, $userId, $teamId);
   }
   
   /**
    * Get [$taskId => [$userId => $time]] of user/team from DB
    * @param type $userId
    * @param type $teamId
    * @return associative array : [$taskId => [$userId => $time]]
    */
   public static function getTimePerUserPerTaskList($userId, $teamId = null) {
      $timePerTaskPerUser = self::getTimePerTaskPerUserList($userId, $teamId);
      
      $timePerUserPerTask = null;
      if (null != $timePerTaskPerUser) {
         foreach ($timePerTaskPerUser as $userIdKey => $timePerTask) {
            foreach ($timePerTask as $taskIdKey => $time) {
               $timePerUserPerTask[$taskIdKey][$userIdKey] = $time;
            }
         }
      }
      return $timePerUserPerTask;
   }
   
   /**
    * Get [$user => $time] of task from DB
    * @param type $taskId
    * @param type $userId : id of current user
    * @param type $teamId : curent user team id
    * @returns associative array : [$user => $time]
    */
   public static function getTimePerUserListOfTask($taskId, $userId, $teamId) {
      // Get config from BD 
      $timePerUserPerTaskList = self::getTimePerUserPerTaskList($userId, $teamId);

      $userTimeList = null;
      if(null != $timePerUserPerTaskList)
      {
         if(null != $timePerUserPerTaskList[$taskId]) 
         {
            // Foreach user affected to the task
            foreach($timePerUserPerTaskList[$taskId] as $userIdKey => $time) {
               $userTimeList[$userIdKey] = $time;
            }
         }
      }
      
      return $userTimeList;
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
   public static function updateTimePerUserListOfTask($taskId, $userTimeList, $userId, $teamId) {
      if (self::isTimePerUserListValid($taskId, $userTimeList)) {
         // Get old configuration
         $userTaskTimeList = self::getTimePerTaskPerUserList($userId, $teamId);
         // If options already exist
         if (null != $userTaskTimeList) {
            // For each users formerly affected to the task, remove time concerning the task 
            foreach ($userTaskTimeList as $keyUserId => $taskTimeList) {
                  unset($userTaskTimeList[$keyUserId][$taskId]);
            }
         }
         
         // For each users newly affected to the task, add time concerning the task
         foreach ($userTimeList as $keyUser => $userTime) {
            $userTaskTimeList[$keyUser][$taskId] = $userTime;
         }
         self::setTimePerTaskPerUserList($userTaskTimeList, $userId, $teamId);

         return true;
      }
      
      return false;
   }
   
   /**
    * Remove all users time of specified task
    * @param type $taskId
    * @param type $userId : id of current user
    * @param type $teamId : curent user team id
    */
   public static function removeTimePerUserOfTask($taskId, $userId, $teamId)
   {
      $timePerUserPerTask = self::getTimePerUserPerTaskList($userId, $teamId);
      
      if(null != $timePerUserPerTask)
      {
         if(null != $taskId)
         {
            
            // Remove the task
            unset($timePerUserPerTask[$taskId]);
            
            self::setTimePerUserPerTaskList($timePerUserPerTask, $userId, $teamId);
            
            return true;
         }
      }
      return false;
   }
   
   
   /**
    * Set a specific option option of the user / team in DB
    * @param string $optionName
    * @param type $value
    * @param type $userId
    * @param type $teamId
    */
   public static function setUserOption($optionName, $value, $userId, $teamId)
   {
      $userOptions = self::getUserOptions($userId, $teamId);
      
      if(null == $userOptions[$optionName]) {
         // key always exists (at least with default value)
         self::$logger->error("setUserOption($optionName, $value, $userId, $teamId): unknown optionName !");
         return false;
      } 
      
      //self::$logger->error("setUserOption($optionName, $value, $userId, $teamId)"); // DEBUG
      $userOptions[$optionName] = $value;
      $userOptionsJson = json_encode($userOptions);
      Config::setValue(Config::id_schedulerOptions, $userOptionsJson, Config::configType_string, NULL, 0, $userId, $teamId);
   }
   
   /**
    * Set time of users of tasks in DB
    * @param type $timePerTaskPerUser : [$userId => [$taskId => $time]]
    * @param type $userId : id of current user
    * @param type $teamId : curent user team id
    */
   public static function setTimePerTaskPerUserList($timePerTaskPerUser, $userId, $teamId)
   {
      self::setUserOption(self::OPTION_timePerTaskPerUser, $timePerTaskPerUser, $userId, $teamId);
   }
   
   /**
    * Set time of users of tasks in DB
    * @param type $timePerUserPerTask : [$taskId => [$userId => $time]]
    * @param type $userId : id of current user
    * @param type $teamId : curent user team id
    */
   public static function setTimePerUserPerTaskList($timePerUserPerTask, $userId, $teamId)
   {
      $timePerTaskPerUser = null;
      if(null !=$timePerUserPerTask)
      {
         foreach ($timePerUserPerTask as $taskId => $timePerUser)
         {
            foreach($timePerUser as $userIdKey => $time)
            {
               $timePerTaskPerUser[$userIdKey][$taskId] = $time;
            }
         }
         self::setTimePerTaskPerUserList($timePerTaskPerUser, $userId, $teamId);
      }
      
   }
   
   
   /**
    * 
    * @param type $taskId
    * @param type $userTimeList : Associative array : [$userId => $time]
    */
   public static function isTimePerUserListValid($taskId, $userTimeList) {
      $totalUsersTime = 0;
      $estimedTime = IssueCache::getInstance()->getIssue($taskId)->getEffortEstim();
      $atLeastOneAutoAffectedUser = false; // at least one user has auto affected time
      
      foreach ($userTimeList as $userTime) {
         // If a user has auto affected time
         if(null == $userTime) {
            $atLeastOneAutoAffectedUser = true;
            break;
         }
         $totalUsersTime += $userTime;
      }
      
      if(($totalUsersTime == $estimedTime) || $atLeastOneAutoAffectedUser) {
         return true;
      } else {
         return false;
      }
   }

   /**
    *
    */
   private function addHandlerTask() {
      $team = TeamCache::getInstance()->getTeam($this->team_id);
      $issueList = $team->getCurrentIssueList(false, true, false);
      if(null != $issueList){
         foreach($issueList as $bugid => $issue) {
            $handlerId = $issue->getHandlerId();

            // duration is the Backlog of the task, or if not set, the MAX(EffortEstim, mgrEffortEstim)
            $duration = $issue->getDuration();
            if(0 < $duration) {
               $this->todoTaskIdList[$bugid] = $duration;
               $this->userTaskList[$handlerId][$bugid] = $duration;
               $this->userCursorList[$handlerId] = null;
            }
         }
      }
   }
   
   public function getSchedulerTaskProviderList(){
      return $this->schedulerTaskProviderList;
   }
   
   public function getDisplayableTaskInfoList(){
      return $this->schedulerDisplayableInfoList;
   }
   
   /**
    * Calculate time for users who have automatic affectation according to total task time
    * @param type $timePerUserPerTaskList
    * @return $timePerUserPerTaskList
    */
   public static function calculateAutoAffectation($timePerUserPerTaskList){
      if(null != $timePerUserPerTaskList){
         foreach($timePerUserPerTaskList as $taskIdKey => $timePerUser)
         {
            $task = IssueCache::getInstance()->getIssue($taskIdKey);
            $backlog = $task->getDuration();

            $userAuto = array();

            // For each users newly affected to the task, add time concerning the task
            foreach ($timePerUser as $keyUser => $userTime) {
               if(null != $userTime){
                  $backlog -= $userTime;
               }
               else{
                  $userAuto[$keyUser][$taskIdKey] = 0;
               }
            }

            if(null != $userAuto){
               $timePerUserAuto = round($backlog/count($userAuto), 1);
               $diff = $timePerUserAuto*count($userAuto) - $backlog;

               foreach($userAuto as $keyUser => $userTime){
                     if($diff <= $timePerUserAuto) {
                        $timePerUserPerTaskList[$taskIdKey][$keyUser] = round($timePerUserAuto - $diff,1);
                        $diff = 0;
                     }
                     else{
                        $timePerUserPerTaskList[$taskIdKey][$keyUser] = 0;
                        $diff -= $timePerUserAuto;
                     }
               }
            }
         }
      }
      
      return $timePerUserPerTaskList;
   }
   
   /**
    * Transform [$taskId => [$userId => $time]] in [$userId => [$taskId => $time]]
    * @param type $timePerUserPerTaskList
    * @return array : [$userId => [$taskId => $time]]
    */
   public static function transformToTimePerTaskPerUserList($timePerUserPerTaskList)
   {
      $timePerTaskPerUserList = null;
      if (null != $timePerUserPerTaskList) {
         foreach ($timePerUserPerTaskList as $taskIdKey => $timePerUser) {
            foreach ($timePerUser as $userIdKey => $time) {
               $timePerTaskPerUserList[$userIdKey][$taskIdKey] = $time;
            }
         }
      }
      return $timePerTaskPerUserList;
   }
}
SchedulerManager::staticInit();
