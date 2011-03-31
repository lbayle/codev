<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php

include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";



class ScheduledTask {
   var $bugId;
   var $duration;	     // in days
   var $deadLine;

   var $isOnTime;  // determinates the color
   var $info;
   
   
   function ScheduledTask($bugId, $deadLine, $duration) {
      $this->bugId = $bugId;
      $this->deadLine = $deadLine;
      $this->duration = $duration;
      
   }
   
   public function getPixSize($dayPixSize) {
   	return number_format(($this->duration * $dayPixSize), 0);
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
	public function scheduleUser($user, $today) {
		
		global $ETA_balance;
		
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
			
         // check if onTime
			if (NULL == $issue->deadLine) {
				$currentST->isOnTime = true;
			} else {
            $nbDaysToDeadLine = $user->getProductionDaysForecast($today, $issue->deadLine);
            $currentST->isOnTime = (($sumDurations + $issueDuration) < $nbDaysToDeadLine) ? true : false;			
			}
			
         // add to list
         $scheduledTaskList[$sumDurations] = $currentST;
         
         $sumDurations += $issueDuration;
         
		} // foreach task
		
		return $scheduledTaskList;
	}
	
}



?>