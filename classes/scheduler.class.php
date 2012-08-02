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

include_once('classes/issue.class.php');
include_once('classes/user.class.php');
include_once('classes/team.class.php');

require_once('lib/log4php/Logger.php');

class ScheduledTask {
   var $bugId;
   var $duration;	     // in days
   var $deadLine;
   var $priorityName;
   var $severityName;
   var $statusName;
   var $handlerName;
   var $projectName;

   var $isOnTime;  // determinates the color
   var $summary;
   var $nbDaysToDeadLine;

   var $isMonitored;  // determinates the color

   private $taskTitle;

   public function __construct($bugId, $deadLine, $duration) {
      $this->bugId = $bugId;
      $this->deadLine = $deadLine;
      $this->duration = $duration;
      $this->isMonitored = false;

   }

   public function getPixSize($dayPixSize) {
   	return round($this->duration * $dayPixSize);
   }

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

}


class Scheduler {

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

	public function Scheduler () {
	}


   /**
    * returns the tasks with the isOnTime attribute to definie color.
    *
    * @param User $user
    * @param timestamp $today   (a day AT MIDNIGHT)
    * @return array of ScheduledTask
    */
	public function scheduleUser($user, $today, $addMonitored = false) {

      global $statusNames;

		$scheduledTaskList = array();

		// get Ordered List of Issues to schedule
		$issueList = $user->getAssignedIssues();
        self::$logger->debug("scheduleUser $user->id : nb assigned issues = ". count($issueList));

		// foreach task
      $sumDurations = 0;
		foreach ($issueList as $issue) {

			// determinate issue duration (Backlog, EffortEstim, MgrEffortEstim)
			$issueDuration = $issue->getDuration();

			self::$logger->debug("issue $issue->bugId  Duration = $issueDuration deadLine=".date("Y-m-d", $issue->getDeadLine()));

			$currentST = new ScheduledTask($issue->bugId, $issue->getDeadLine(), $issueDuration);

			self::$logger->debug("issue $issue->bugId   -- user->getAvailableWorkload(".$today.", ".$issue->getDeadLine().")");
			self::$logger->debug("issue $issue->bugId nbDaysToDeadLine=".$user->getAvailableWorkload($today, $issue->getDeadLine()));
			$currentST->nbDaysToDeadLine = $user->getAvailableWorkload($today, $issue->getDeadLine());
			$currentST->projectName      = $issue->getProjectName();
			$currentST->summary          = $issue->summary;
         $currentST->priorityName     = $issue->getPriorityName();
         $currentST->severityName     = $issue->getSeverityName();
         $currentST->statusName       = $statusNames[$issue->currentStatus];

         $handler = UserCache::getInstance()->getUser($issue->handlerId);
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

		} // foreach task

		// ------------
		if ($addMonitored) {
			$monitoredList = $user->getMonitoredIssues();

         foreach ($monitoredList as $issue) {

            if (in_array($issue, $issueList)) {
            	continue;
            }


            // determinate issue duration (Backlog, EffortEstim, MgrEffortEstim)
			$issueDuration = $issue->getDuration();

            #echo "DEBUG Monitored issue $issue->bugId  Duration = $issueDuration<br/>";

            $currentST = new ScheduledTask($issue->bugId, $issue->getDeadLine(), $issueDuration);

            $currentST->nbDaysToDeadLine = $user->getAvailableWorkload($today, $issue->getDeadLine());
            $currentST->projectName      = $issue->getProjectName();
            $currentST->summary          = $issue->summary;
            $currentST->priorityName     = $issue->getPriorityName();
            $currentST->severityName     = $issue->getSeverityName();
            $currentST->statusName       = $statusNames[$issue->currentStatus];
            $currentST->isMonitored      = true;

            $handler = UserCache::getInstance()->getUser($issue->handlerId);
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
         } // foreach task
		} // addMonitored

		return $scheduledTaskList;
	}

}

Scheduler::staticInit();

?>
