<?php /*
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
*/ ?>
<?php

class GanttActivity {

	private $bugid;
   private $startTimestamp;
   private $endTimestamp;

	private $color;

	public function __construct($bugId, $startT, $endT) {
      $this->bugid = $bugId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      //echo "GanttActivity ".$this->toString()."<br/>\n";
	}

   public function setColor($color) {
      $this->color = $color;
   }

   public function toString() {
   	return "issue $this->bugid  - ".date('Y-m-d', $this->startTimestamp)." - ".date('Y-m-d', $this->endTimestamp);
   }
}



/**

1) recupere la liste des taches finies (status >= bug_resolved_status_threshold)
1.1) convertis en GanttActivity. (aucun calcul, les dates debut/fin sont connues) et dispatch dans des userActivityList

2) recupere toutes les taches en cours de l'equipe (status < bug_resolved_status_threshold)

3) trie taches en cours

4) convertis en GanttActivity et dispatch dans les userActivityList en calculant les dates.

5) cree le grath

 */
class GanttManager {

  private $teamid;
  private $startTimestamp;
  private $endTimestamp;

  //
  private $userActivityList; // $userActivityList[user][activity]

   /**
    * @param $teamId
    * @param $startT  start timestamp. if NULL, then now
    * @param $endT    end timestamp. if NULL, shedule all remaining tasks
    */
   public function __construct($teamId, $startT=NULL, $endT=NULL) {

      echo "GanttManager($teamId, ".date('Y-m-d', $startT).", ".date('Y-m-d', $endT).")<br/>";
      $this->teamid = $teamId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      $this->userActivityList = array();   // $userActivityList[user][activity]
  }



   /**
    * get tasks resolved in the period
    */
   private function getResolvedIssues() {

   	$tt = new TimeTracking($this->startTimestamp, $this->endTimestamp, $this->teamid);
   	$resolvedIssuesList = $tt->getResolvedIssues();
   	return $resolvedIssuesList;

   }

   /**
    * create a GanttActivity for each issue and dispatch it in $userActivityList[user]
    */
   private function dispatchResolvedIssues($resolvedIssuesList) {
   	global $status_acknowledged;
   	global $status_new;
   	global $status_closed;
   	global $gantt_task_grey;

      $bug_resolved_status_threshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);


      foreach ($resolvedIssuesList as $issue) {

      	$startDate = $issue->getFirstStatusOccurrence($status_acknowledged);
      	if (NULL == $startDate) { $startDate = $issue->dateSubmission; }

      	$endDate = $issue->getLatestStatusOccurrence($bug_resolved_status_threshold);
      	if (NULL == $endDate) {
            // TODO: no, $status_closed is not the only one ! check for all status > $bug_resolved_status_threshold
      		$endDate = $issue->getLatestStatusOccurrence($status_closed);
      	}

      	$activity = new GanttActivity($issue->bugId, $startDate, $endDate);

      	//$activity->setColor($gantt_task_grey);

      	if (NULL == $this->userActivityList[$issue->handlerId]) {
      	   $this->userActivityList[$issue->handlerId] = array();
      	}
      	$this->userActivityList[$issue->handlerId][] = $activity;
    	   //echo "DEBUG add to userActivityList[".$issue->handlerId."]: ".$activity->toString()."  (resolved)<br/>\n";

      }
   }

   /**
    * get sorted list of current issues
    */
   private function getCurrentIssues() {

   }

   /**
    *
    */
   private function dispatchCurrentIssues() {

   }

   public function getGanttGraph() {

   	//echo "DEBUG getGanttGraph : getResolvedIssues<br/>\n";
      $resolvedIssuesList = $this->getResolvedIssues();
   	//echo "DEBUG getGanttGraph : dispatchResolvedIssues nbIssues=".count($resolvedIssuesList)."<br/>\n";
      $this->dispatchResolvedIssues($resolvedIssuesList);

   	//echo "DEBUG getGanttGraph : display nbUsers=".count($this->userActivityList)."<br/>\n";
      foreach($this->userActivityList as $userid => $activityList) {
      	$user = UserCache::getInstance()->getUser($userid);
      	echo "==== ".$user->getName()." activities: <br/>";
      	foreach($activityList as $a) {
      		echo $a->toString()."<br/>";
      	}
      }

   }

}
?>