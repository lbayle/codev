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

   const OPTION_timePerTaskPerUser = 'timePerTaskPerUser'; // array [$userId => [$taskId => $time]]
   const OPTION_taskProvider       = 'taskProvider';    // Scheduling method
   const OPTION_isDisplayExtRef    = 'isDisplayExtRef';
   const OPTION_nbDaysForecast     = 'nbDaysForecast';  // nb days to compute
   const OPTION_displayedUsers     = 'displayedUsers';  // array(userid): display subset of team users
   const OPTION_nbDaysToDisplay    = 'nbDaysToDisplay'; // displayed window
   const OPTION_windowStartDate    = 'windowStartDate'; // "today", "monday this week", "first day of this month"
   const OPTION_warnThreshold      = 'warnThreshold';   // warn if ends n days before deadline

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
   private $userOptions;

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

   public function __construct($userid, $teamid) {

      $this->team_id = $teamid;
      $this->user_id = $userid;
      
      $this->schedulerTaskProviderList = array('SchedulerTaskProvider0', 'SchedulerTaskProvider');
   }

   /**
    * TODO : describe actions ...
    *
    */
   private function initExec() {

      $this->data["activity"] = array();

      // Set task provider
      $taskProviderName = $this->getUserOption(self::OPTION_taskProvider);
      $this->setTaskProvider($taskProviderName);

      $this->addHandlerTask();

      // Set setUserTaskList (calculateAutoAffectation)
      $timePerTaskPerUser = $this->getUserOption(self::OPTION_timePerTaskPerUser);
      $timePerUserPerTasks = self::transposeTo_TimePerUserPerTask($timePerTaskPerUser);
      $timePerUserPerTaskList = $this->computeAutoAssignation($timePerUserPerTasks);
      $timePerTaskPerUserList = self::transposeTo_TimePerTaskPerUser($timePerUserPerTaskList);
      $this->setUserTaskList($timePerTaskPerUserList);
      
      // sort todoTaskIdList once for all,
      // this avoids schedulerTaskProvider to do it at each call to createCandidateTaskList()
      $this->sortTodoTaskIdList();

   }

   /**
    * TODO : describe actions ...
    *
    * @return type
    */
   public function execute() {

      /*
       * while processing, cache to concat consecutive activities in one strike
       * (for the same user on the same task)
       * @var array[userid] => GanttActivity
       */
      $userLatestActivity = array();

      $isDisplayExtRef = $this->getUserOption(self::OPTION_isDisplayExtRef);
      $projectionDay   = $this->getUserOption(self::OPTION_nbDaysForecast);

      $this->initExec();

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
                        $prevActivity = $userLatestActivity[$userId];
                        if (NULL == $prevActivity) {
                           // first activity fot this user
                           $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                           $userLatestActivity[$userId] = $ganttActivity;
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
                              $userLatestActivity[$userId] = $ganttActivity;
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
      foreach ($userLatestActivity as $ganttActivity) {
         array_push($this->data["activity"], $ganttActivity->getDxhtmlData($isDisplayExtRef));
      }

      $this->createBacklogData();
      $this->adjustColors();
      return $this->data;
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

   /**
    * TODO : describe actions ...
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
   
   /**
    * Compute time for users with auto-assignation according to task duration
    * 
    * @param type $timePerUserPerTaskList
    * @return $timePerUserPerTaskList
    */
   private function computeAutoAssignation($timePerUserPerTaskList){
      if (null != $timePerUserPerTaskList) {
         foreach($timePerUserPerTaskList as $taskIdKey => $timePerUser) {
            $task = IssueCache::getInstance()->getIssue($taskIdKey);
            $backlog = $task->getDuration();

            $userAuto = array();

            // For each users newly affected to the task, add time concerning the task
            foreach ($timePerUser as $keyUser => $userTime) {
               if(null != $userTime) {
                  $backlog -= $userTime;
               } else {
                  $userAuto[$keyUser][$taskIdKey] = 0;
               }
            }

            if (null != $userAuto) {
               $timePerUserAuto = round($backlog/count($userAuto), 1);
               $diff = $timePerUserAuto*count($userAuto) - $backlog;

               foreach ($userAuto as $keyUser => $userTime) {
                     if($diff <= $timePerUserAuto) {
                        $timePerUserPerTaskList[$taskIdKey][$keyUser] = round($timePerUserAuto - $diff,1);
                        $diff = 0;
                     } else {
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
    * Initialize $userTaskList with the scheduler settings
    *
    * @param type $jsonUserTaskList
    */
   private function setUserTaskList($jsonUserTaskList) {
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
    * depending on deadline, change task activity colors
    */
   private function adjustColors() {

      foreach($this->data["activity"] as $key=>$data) {
        $bugid = $data["bugid"];
        $activityEndDate = strtotime($data["end_date"]);
        $taskEndTimestamp = $this->todoTaskDates[$bugid]['endTimestamp'];
        
        $color = $this->getColor($bugid, $taskEndTimestamp, $activityEndDate);
        $this->data['activity'][$key]['color'] = $color;
      }
   }

   /**
    * get task color depending on it's deadline
    */
   public function getColor($bugid, $taskEndTimestamp, $activityEndDate = NULL) {
      $warnThreshold   = $this->getUserOption(self::OPTION_warnThreshold);
      $deadline = IssueCache::getInstance()->getIssue($bugid)->getDeadLine();

      // task has no deadline => light green
      if (NULL == $deadline) { return 'lightgreen'; }

      $warnline = strtotime('-'.$warnThreshold.' days', $deadline);  // n days before deadline

      if ($taskEndTimestamp > $deadline) {
         // task is late, but:
         if ((NULL != $activityEndDate) && ($activityEndDate < $deadline)) {
            // this activity ends before deadline => light red
            return '#FF816B';
         } else {
            // this activity ends after deadline => red
            return '#FF421F';
         }
      } else {
         // task is on time, but:
         if ($taskEndTimestamp > $warnline) {
            // task ends shortly before the deadline => orange
            return '#FFBA00';
         } else {
            //  task is on time => green
            return '#009900'; // 'green';
         }
      }
   }

   /**
    * TODO : describe actions ...
    *
    */
   private function createBacklogData() {
      //$this->data["backlog"] = $this->userTaskList;
      foreach($this->userTaskList as $userid=>$taskList) {
         $user = UserCache::getInstance()->getUser($userid);
         $userName = $user->getRealname();
         
         $this->data["backlog"][$userName] = $taskList;
      }
   }

 
   /**
    * Set task provider of scheduler manager
    * @param type $taskProviderName
    */
   public function setTaskProvider($taskProviderName = null) {

      if(!in_array($taskProviderName, $this->schedulerTaskProviderList)) {
         $taskProviderName = $this->schedulerTaskProviderList[0];
         self::$logger->error("setTaskProvider($taskProviderName): Unknown taskProvider, using default.");
      }
      
      // Instantiate scheduler provider
      $providerReflection = new ReflectionClass($taskProviderName); 
      $this->schedulerTaskProvider = $providerReflection->newInstance();
   }
   
   
   private function getUserAvailableTime($userId, $midnightTimestamp) {
      $user = UserCache::getInstance()->getUser($userId);
      return $user->getAvailableTime($midnightTimestamp);
   }
   
   /**
    * Get scheduler options of user / team from DB
    * @param type $userId
    * @return type
    */
   private function getUserOptions($userId, $reload = FALSE) {

      if (NULL == $this->userOptions || $reload) {

         // set default values (if new options are added, user may not have them)
         $this->userOptions = array (
            self::OPTION_taskProvider => 'SchedulerTaskProvider0', // $this->schedulerTaskProviderList[0],
            self::OPTION_isDisplayExtRef => FALSE,
            self::OPTION_nbDaysForecast => 90,  // 90 days = 3 month
            self::OPTION_nbDaysToDisplay => 30, // 30 days = 1 month
            self::OPTION_windowStartDate => 'thisWeek',

            self::OPTION_warnThreshold => 5,    // 5 days before deadline
            self::OPTION_displayedUsers => NULL,
            self::OPTION_timePerTaskPerUser => NULL,
         );

         // override default values with user settings
         $userOptionsJson = Config::getValue(Config::id_schedulerOptions, array($userId, 0, $this->team_id, 0, 0, 0), true);
         if(null != $userOptionsJson) {
            $options = json_decode($userOptionsJson, true);
            foreach ($options as $key => $value) {
               $this->userOptions[$key] = $value;
            }
         }
      }
      return $this->userOptions;
   }
   
   /**
    * Get user specific option from DB
    * @param string $optionName
    * @param type $userId
    * @param type $teamId
    * @return type
    */
   public function getUserOption($optionName, $userId = -1) {

      if (-1 == $userId) { $userId = $this->user_id; }
      $userOptions = $this->getUserOptions($userId);
//      self::$logger->error("getUserOption($optionName, $userId) = ". $userOptions[$optionName]);
      return $userOptions[$optionName];
   }

   /**
    * Set a specific option option of the user / team in DB
    * @param string $optionName
    * @param type $value
    * @param type $userId
    * @param type $teamId
    */
   public function setUserOption($optionName, $value, $userId = -1) {

      if (-1 == $userId) { $userId = $this->user_id; }
      $userOptions = $this->getUserOptions($userId);

      if (!array_key_exists($optionName, $userOptions)) {
         // key always exists (at least with default value)
         self::$logger->error("setUserOption($optionName, $value, $userId): unknown optionName !");
         return false;
      }

      $this->userOptions[$optionName] = $value;
      $userOptionsJson = json_encode($this->userOptions);
      Config::setValue(Config::id_schedulerOptions, $userOptionsJson, Config::configType_string, NULL, 0, $userId, $this->team_id);
   }

   /**
    *
    * @return string current TaskProfider class name
    */
   public function getSchedulerTaskProviderList(){
      return $this->schedulerTaskProviderList;
   }

   /**
    *
    * @return array [bugid => ['startTimestamp' = t1, 'endTimestamp' = t2]]
    */
   public function getComputedTaskDates() {
      return $this->todoTaskDates;
   }
   

   /**
    * ========= STATIC ========
    * this is a double check, JS does first round.
    * 
    * TODO: rules must be updated
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
    * ========= STATIC ========
    * Matrix conversion
    * IN  = [$userId => [$taskId => $time]]   (OPTION_timePerTaskPerUser)
    * OUT = [$taskId => [$userId => $time]]
    *
    * @param type $userId
    * @param type $teamId
    * @return array : [$taskId => [$userId => $time]]
    */
   public static function transposeTo_TimePerUserPerTask($timePerTaskPerUser) {

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
    *
    * ========= STATIC ========
    * Matrix conversion
    * IN  = [$taskId => [$userId => $time]]
    * OUT = [$userId => [$taskId => $time]]   (OPTION_timePerTaskPerUser)
    * 
    * @param type $timePerUserPerTaskList
    * @return array : [$userId => [$taskId => $time]]
    */
   public static function transposeTo_TimePerTaskPerUser($timePerUserPerTaskList)
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
