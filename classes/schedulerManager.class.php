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
//      $mList = TeamCache::getInstance()->getTeam($this->team_id)->getActiveMembers();
//      $this->userTaskList = array_keys($mList);
//      $this->setTasks();
        $this->setBouchon();
      $this->schedulerTaskProvider = new SchedulerTaskProvider();
   }
   
   public function execute() {
      $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
      $currentDay = mktime(0, 0, 0);
      $projectionDay = 10;
      $endDate = $currentDay+$projectionDay*24*60*60;
      for($date = $currentDay; $date < $endDate; $date+=24*60*60) {
         foreach ($this->userTaskList as $userId=>$userTasks) {
            $midnightTimestamp = $date;
            $userAvailableTime = $this->getUserAvailableTime($userId, $midnightTimestamp);
            while(0 < $userAvailableTime && !empty($userTasks)){
                  $nextTaskId = $this->schedulerTaskProvider->getNextUserTask($userTasks);
                  //self::$logger->error($nextTaskId);
                  if(NULL != $nextTaskId){
                     $timeUsed = $this->decreaseBacklog($userId, $nextTaskId, $userAvailableTime);
                     if(0 != $timeUsed){
                        $userAvailableTime -= $timeUsed;
                        $endT = $midnightTimestamp + $timeUsed*24*60*60;
                        $ganttActivity = new GanttActivity($nextTaskId, $userId, $midnightTimestamp, $endT);
                        //$midnightTimestamp = $endT;
                        //self::$logger->error($ganttActivity);
                        $ganttActivity->setColor("red");
                        $this->transformGanttActivityToDxhtmlData($ganttActivity);
                     }
                  }
                  else{
                     $userAvailableTime = 0;
                     unset($this->userTaskList[$userId]);
                  }
            }
         }
      }
      //self::$logger->error($this->data);
      return $this->data;
   }
   
   //done
   /**
    * Set todoTaskList value
    */
   private function setTasks(){
      $tasksList = TeamCache::getInstance()->getTeam($this->team_id)->getTeamIssueList();
      Tools::usort($tasksList);
      foreach($tasksList as $task){
         $id = $task->getId();
         $duration = $task->getDuration();
         if(0 < $duration){
            $this->todoTaskIdList[$id] = $duration;
         }
      }
//      $this->todoTaskIdList[4289] = 62.3;
//      $this->todoTaskIdList[9670] = 12;
   }
   
   private function setBouchon(){
      $this->userTaskList = array(169 => array(4289 => 8, 9670 => 3), 74 => array(9670 => 10), 134 => array(4289 => 4));
      $this->todoTaskIdList[4289] = 12;
      $this->todoTaskIdList[9670] = 13;
   }
   
   
   //Todo
   private function decreaseBacklog($userId, $taskid, $userAvailableTime) {
      if($this->userTaskList[$userId][$taskid] >= $userAvailableTime)
      {
         $this->userTaskList[$userId][$taskid] -= $userAvailableTime;
         $timeUsed = $userAvailableTime;
      }
      else{
         $timeUsed = $this->userTaskList[$userId][$taskid];
         //$this->todoTaskIdList[$taskid] = 0;
         unset($this->todoTaskIdList[$taskid]);
         unset($this->userTaskList[$userId][$taskid]);
         if(!empty($this->todoTaskIdList)){
            $this->schedulerTaskProvider->createCandidateTaskList(array_keys($this->todoTaskIdList));
         }
      }
      return $timeUsed;
   }
   
   
   //Bouchon
   private function getUserAvailableTime($userId, $midnightTimestamp){
      $user = UserCache::getInstance()->getUser($userId);
      return $user->getAvailableTime($midnightTimestamp);
   }

   private function transformGanttActivityToDxhtmlData($ganttActivity){
      $date = $ganttActivity->startTimestamp;
      $dateParse = date('Y-m-d H:i:s', $date);
      $endDate = $ganttActivity->endTimestamp;
      $endDateParse = date('Y-m-d H:i:s', $endDate);
      $pushdata = array("text"=>"$ganttActivity->bugid","start_date"=>"$dateParse" ,"end_date"=>"$endDateParse" ,"user_id"=>"$ganttActivity->userid", "color"=>"$ganttActivity->color");
      array_push($this->data, $pushdata);
   }
}

SchedulerManager::staticInit();

?>