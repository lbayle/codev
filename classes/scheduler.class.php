<?php /*
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
*/ ?>

<?php

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";



class ScheduledTask {
   var $bugId;
   var $duration;	     // in days
   var $deadLine;
   var $priorityName;
   var $statusName;
   var $handlerName;
   
   var $isOnTime;  // determinates the color
   var $summary;
   var $nbDaysToDeadLine;

   var $isMonitored;  // determinates the color
   
   
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
   	
   	$taskTitle= "";
      $taskTitle .= $this->bugId;
   	$taskTitle .= " ($this->duration ".T_("days");
      $taskTitle .= ", $this->priorityName";
      $taskTitle .= ", $this->statusName";
      if (NULL != $this->deadLine) {
         $taskTitle .= ", ".date("d/m/Y", $this->deadLine);
      }
      if ($this->isMonitored) {
         $taskTitle .= ", ".T_("monitored")."-$this->handlerName";
      }
      $taskTitle .= ")       $this->summary";
   	
   	return $taskTitle;
   }
   
}


class Scheduler {

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
		
      global $ETA_balance;
      global $statusNames;
		
		$scheduledTaskList = array();

		// get Ordered List of Issues to schedule
		$issueList = $user->getAssignedIssues();
		
		// foreach task
      $sumDurations = 0;
		foreach ($issueList as $issue) {
			
			// determinate issue duration (Remaining, BI, ETA)
			if       (NULL != $issue->remaining)   { $issueDuration = $issue->remaining; } 
			elseif   (NULL != $issue->effortEstim) { $issueDuration = $issue->effortEstim; } 
			else                                   { $issueDuration = $ETA_balance[$issue->eta]; }
			
			#echo "DEBUG issue $issue->bugId  Duration = $issueDuration<br/>";
			
			$currentST = new ScheduledTask($issue->bugId, $issue->deadLine, $issueDuration); 

			$currentST->nbDaysToDeadLine = $user->getProductionDaysForecast($today, $issue->deadLine);
			$currentST->summary          = $issue->summary;
         $currentST->priorityName     = $issue->getPriorityName();
         $currentST->statusName       = $statusNames[$issue->currentStatus];
         
         $handler = UserCache::getInstance()->getUser($issue->handlerId);
         $currentST->handlerName = $handler->getName();
         
         // check if onTime
			if (NULL == $issue->deadLine) {
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
         	
         	
            // determinate issue duration (Remaining, BI, ETA)
            if       (NULL != $issue->remaining)   { $issueDuration = $issue->remaining; } 
            elseif   (NULL != $issue->effortEstim) { $issueDuration = $issue->effortEstim; } 
            else                                   { $issueDuration = $ETA_balance[$issue->eta]; }
         
            #echo "DEBUG Monitored issue $issue->bugId  Duration = $issueDuration<br/>";
         
            $currentST = new ScheduledTask($issue->bugId, $issue->deadLine, $issueDuration); 

            $currentST->nbDaysToDeadLine = $user->getProductionDaysForecast($today, $issue->deadLine);
            $currentST->summary          = $issue->summary;
            $currentST->priorityName     = $issue->getPriorityName();
            $currentST->statusName       = $statusNames[$issue->currentStatus];
            $currentST->isMonitored      = true;
            
            $handler = UserCache::getInstance()->getUser($issue->handlerId);
            $currentST->handlerName = $handler->getName();
            
            // check if onTime
            if (NULL == $issue->deadLine) {
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



?>