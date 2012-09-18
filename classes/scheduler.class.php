<?php
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

require_once('classes/constants.class.php');

include_once('classes/user_cache.class.php');

require_once('lib/log4php/Logger.php');

class ScheduledTask {

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

   private $bugId;
   private $duration; // in days
   private $deadLine;
   private $priorityName;
   private $severityName;
   private $statusName;
   private $handlerName;
   private $projectName;

   private $isOnTime; // determinates the color
   private $summary;
   private $nbDaysToDeadLine;

   private $isMonitored; // determinates the color

   private $taskTitle;

   /**
    * @param int $bugId
    * @param int $deadLine
    * @param number $duration
    */
   public function __construct($bugId, $deadLine, $duration) {
      $this->bugId = $bugId;
      $this->deadLine = $deadLine;
      $this->duration = $duration;
      $this->isMonitored = false;
   }

   public function getPixSize($dayPixSize) {
      return round($this->duration * $dayPixSize);
   }

   /**
    * @return string
    */
   public function getDescription() {
      if (NULL == $this->taskTitle) {
         $this->taskTitle= "";
         $this->taskTitle .= $this->bugId;
         $this->taskTitle .= " ($this->duration ".T_("days");
         $this->taskTitle .= ", $this->priorityName";
         $this->taskTitle .= ", $this->statusName";
         if (NULL != $this->deadLine) {
            $this->taskTitle .= ", ".date("d/m/Y", $this->deadLine);
         }
         if ($this->isMonitored) {
            $this->taskTitle .= ", ".T_("monitored")."-$this->handlerName";
         }
         $this->taskTitle .= ")       $this->summary";

      }

      return $this->taskTitle;
   }

   /**
    * returns the tasks with the isOnTime attribute to definie color.
    * @static
    * @param User $user
    * @param int $today   (a day AT MIDNIGHT)
    * @param bool $addMonitored
    * @return ScheduledTask[] array of ScheduledTask
    */
   public static function scheduleUser(User $user, $today, $addMonitored = false) {
      $scheduledTaskList = array();

      // get Ordered List of Issues to schedule
      $issueList = $user->getAssignedIssues();
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("scheduleUser ".$user->getId()." : nb assigned issues = ". count($issueList));
      }

      // foreach task
      $sumDurations = 0;
      foreach ($issueList as $issue) {

         // determinate issue duration (Backlog, EffortEstim, MgrEffortEstim)
         $issueDuration = $issue->getDuration();

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue ".$issue->getId()."  Duration = $issueDuration deadLine=".date("Y-m-d", $issue->getDeadLine()));
         }

         $currentST = new ScheduledTask($issue->getId(), $issue->getDeadLine(), $issueDuration);

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue ".$issue->getId()."   -- user->getAvailableWorkload(".$today.", ".$issue->getDeadLine().")");
            self::$logger->debug("issue ".$issue->getId()." nbDaysToDeadLine=".$user->getAvailableWorkload($today, $issue->getDeadLine()));
         }
         $currentST->nbDaysToDeadLine = $user->getAvailableWorkload($today, $issue->getDeadLine());
         $currentST->projectName = $issue->getProjectName();
         $currentST->summary = $issue->getSummary();
         $currentST->priorityName = $issue->getPriorityName();
         $currentST->severityName = $issue->getSeverityName();
         $currentST->statusName = Constants::$statusNames[$issue->getCurrentStatus()];

         $handler = UserCache::getInstance()->getUser($issue->getHandlerId());
         $currentST->handlerName = $handler->getName();

         // check if onTime
         if (NULL == $issue->getDeadLine()) {
            $currentST->isOnTime = true;
         } else {
            $currentST->isOnTime = (($sumDurations + $issueDuration) <= $currentST->nbDaysToDeadLine) ? true : false;
         }

         // add to list
         if (0 != $issueDuration) {
            $scheduledTaskList["$sumDurations"] = $currentST;
            $sumDurations += $issueDuration;
         }
      }

      if ($addMonitored) {
         $monitoredList = $user->getMonitoredIssues();

         foreach ($monitoredList as $issue) {
            if (in_array($issue, $issueList)) {
               continue;
            }

            // determinate issue duration (Backlog, EffortEstim, MgrEffortEstim)
            $issueDuration = $issue->getDuration();

            #echo "DEBUG Monitored issue $issue->bugId  Duration = $issueDuration<br/>";

            $currentST = new ScheduledTask($issue->getId(), $issue->getDeadLine(), $issueDuration);

            $currentST->nbDaysToDeadLine = $user->getAvailableWorkload($today, $issue->getDeadLine());
            $currentST->projectName = $issue->getProjectName();
            $currentST->summary = $issue->getSummary();
            $currentST->priorityName = $issue->getPriorityName();
            $currentST->severityName = $issue->getSeverityName();
            $currentST->statusName = Constants::$statusNames[$issue->getCurrentStatus()];
            $currentST->isMonitored = true;

            $handler = UserCache::getInstance()->getUser($issue->getHandlerId());
            $currentST->handlerName = $handler->getName();

            // check if onTime
            if (NULL == $issue->getDeadLine()) {
               $currentST->isOnTime = true;
            } else {
               $currentST->isOnTime = (($sumDurations + $issueDuration) <= $currentST->nbDaysToDeadLine) ? true : false;
            }

            // add to list
            if (0 != $issueDuration) {
               $scheduledTaskList["$sumDurations"] = $currentST;
               $sumDurations += $issueDuration;
            }
         }
      }

      return $scheduledTaskList;
   }

   /**
    * @return int
    */
   public function getNbDaysToDeadLine() {
      return $this->nbDaysToDeadLine;
   }

   /**
    * @return bool
    */
   public function isMonitored() {
      return $this->isMonitored;
   }

   /**
    * @return int
    */
   public function getIssueId() {
      return $this->bugId;
   }

   /**
    * @return number
    */
   public function getDuration() {
      return $this->duration;
   }

   /**
    * @return int
    */
   public function getDeadline() {
      return $this->deadLine;
   }

   /**
    * @return string
    */
   public function getPriorityName() {
      return $this->priorityName;
   }

   /**
    * @return string
    */
   public function getHandlerName() {
      return $this->handlerName;
   }

   /**
    * @return bool
    */
   public function isOnTime() {
      return $this->isOnTime;
   }

   /**
    * @return string
    */
   public function getSummary() {
      return $this->summary;
   }

   /**
    * @return string
    */
   public function getSeverityName() {
      return $this->severityName;
   }

   /**
    * @return string
    */
   public function getStatusName() {
      return $this->statusName;
   }

   /**
    * @return string
    */
   public function getProjectName() {
      return $this->projectName;
   }

}

ScheduledTask::staticInit();

?>
