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

require_once ('jpgraph.php');
require_once ('jpgraph_gantt.php');


class GanttActivity {

	private $bugid;
	private $userid;
   private $startTimestamp;
   private $endTimestamp;

	private $color;

	public function __construct($bugId, $userId, $startT, $endT) {
      $this->bugid = $bugId;
      $this->userid = $userId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      //echo "GanttActivity ".$this->toString()."<br/>\n";
	}

   public function setColor($color) {
      $this->color = $color;
   }

   public function getJPGraphData() {
   	$user = UserCache::getInstance()->getUser($this->userid);

   	return array(0,
   	             ACTYPE_NORMAL,
                   "   ".$this->bugid,
                   date('Y-m-d', $this->startTimestamp),
                   date('Y-m-d', $this->endTimestamp),
                   $user->getName());
   }

   public function toString() {
   	return "issue $this->bugid  - ".date('Y-m-d', $this->startTimestamp)." - ".date('Y-m-d', $this->endTimestamp)." - ".$this->userid;
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

      //echo "GanttManager($teamId, ".date('Y-m-d', $startT).", ".date('Y-m-d', $endT).")<br/>";
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

      	$activity = new GanttActivity($issue->bugId, $issue->handlerId, $startDate, $endDate);

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

   public function getTeamActivities() {

      $resolvedIssuesList = $this->getResolvedIssues();

   	//echo "DEBUG getGanttGraph : dispatchResolvedIssues nbIssues=".count($resolvedIssuesList)."<br/>\n";
      $this->dispatchResolvedIssues($resolvedIssuesList);
/*
   	//echo "DEBUG getGanttGraph : display nbUsers=".count($this->userActivityList)."<br/>\n";
      foreach($this->userActivityList as $userid => $activityList) {
      	$user = UserCache::getInstance()->getUser($userid);
      	echo "==== ".$user->getName()." activities: <br/>";
      	foreach($activityList as $a) {
      		echo $a->toString()."<br/>";
      	}
      }
*/
      return $this->userActivityList;
   }

   public function getGanttGraph() {

   	$this->getTeamActivities();

      $data = array();

      foreach($this->userActivityList as $userid => $activityList) {
      	//$data[] = ACTYPE_GROUP
      	foreach($activityList as $a) {
      		#echo $a->toString()."<br/>";
            $data[] = $a->getJPGraphData();
      	}
      }

      // ----
      $constrains = array();
      $progress = array();


/* TEST
$data = array(
  array(0,ACTYPE_GROUP,    "Phase 1",        "2001-10-26","2001-11-23",''),
  array(1,ACTYPE_NORMAL,   "  Label 2",      "2001-10-26","2001-11-16",''),
  array(2,ACTYPE_NORMAL,   "  Label 3",      "2001-11-20","2001-11-22",''),
  array(3,ACTYPE_MILESTONE,"  Phase 1 Done", "2001-11-23",'M2') );

$constrains = array();
$progress = array(array(1,0.4));
*/
      // ----
   	$graph = new GanttGraph();

      $graph->title->Set("Team XXX");

      // Setup scale
      $graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);
      $graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAYWNBR);

      // Add the specified activities
      $graph->CreateSimple($data,$constrains,$progress);



   	return $graph;
   }

}
?>