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

require_once '../path.inc.php';
require_once "mysql_config.inc.php";
require_once "mysql_connect.inc.php";
require_once "internal_config.inc.php";
require_once "constants.php";

require_once ('time_tracking.class.php');
require_once ('team.class.php');

require_once ('jpgraph.php');
require_once ('jpgraph_gantt.php');


class GanttActivity {

	public $bugid;
	private $userid;
   private $startTimestamp;
   private $endTimestamp;
	private $color;

   public $progress;
   public $activityIdx;  // index in jpgraph Data structure

   public function __construct($bugId, $userId, $startT, $endT, $progress=NULL) {
      $this->bugid = $bugId;
      $this->userid = $userId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      if (NULL != $progress) {
      	$this->progress = $progress;
      } else {
      	// (BI+BS - RAF) / (BI+BS)
         $issue = IssueCache::getInstance()->getIssue($this->bugid);
         $this->progress = $issue->getProgress();
      }
	}

   public function setColor($color) {
      $this->color = $color;
   }

   public function getJPGraphData($activityIdx) {

      // save this for later, to compute constrains
      $this->activityIdx = $activityIdx;

   	$user = UserCache::getInstance()->getUser($this->userid);
      $issue = IssueCache::getInstance()->getIssue($this->bugid);

   	$formattedActivityName = substr("$this->bugid - $issue->summary", 0, 50);

   	return array($activityIdx,
   	             ACTYPE_NORMAL,
                   $formattedActivityName,
                   date('Y-m-d', $this->startTimestamp),
                   date('Y-m-d', $this->endTimestamp),
                   $user->getName());
   }

   public function toString() {
   	return "issue $this->bugid  - ".date('Y-m-d', $this->startTimestamp)." - ".date('Y-m-d', $this->endTimestamp)." - ".$this->userid;
   }

      // ----------------------------------------------
   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $activityB
    *
    * @param GanttActivity $activityB the object to compare to
    */
   function compareTo($activityB) {

   	// the oldest activity should be in front of the list
   	if ($this->endTimestamp > $activityB->endTimestamp) {
           #echo "activity.compareTo FALSE  (".date('Y-m-d', $this->endTimestamp)." > ".date('Y-m-d', $activityB->endTimestamp).")<br/>";
   	   return false;
   	}
           #echo "activity.compareTo   (".date('Y-m-d', $this->endTimestamp)." < ".date('Y-m-d', $activityB->endTimestamp).")<br/>";
        return true;
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
      $this->constrainsList   = array();
  }



   /**
    * get tasks resolved in the period
    */
   private function getResolvedIssues() {

   	$tt = new TimeTracking($this->startTimestamp, $this->endTimestamp, $this->teamid);
   	$resolvedIssuesList = $tt->getResolvedIssues();

      $sortedList = qsort($resolvedIssuesList);

   	return $sortedList;
   	#return $resolvedIssuesList;

   }

   /**
    * get sorted list of current issues
    */
   private function getCurrentIssues() {

   	$teamIssueList = array();

      $members = Team::getMemberList($this->teamid);
      #$projects = Team::getProjectList($this->teamid);

      // --- get all issues
      foreach($members as $uid => $uname) {
         $user = UserCache::getInstance()->getUser($uid);

         if ($user->isTeamDeveloper($this->teamid)) {
      	   $issueList = $user->getAssignedIssues();
      	   $teamIssueList = array_merge($teamIssueList, $issueList);
         }
      }

      // quickSort the list
      $sortedList = qsort($teamIssueList);

      return $sortedList;
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

      	if (NULL == $this->userActivityList[$issue->handlerId]) {
      	   $this->userActivityList[$issue->handlerId] = array();
      	}
      	$this->userActivityList[$issue->handlerId][] = $activity;

    	   #echo "DEBUG add to userActivityList[".$issue->handlerId."]: ".$activity->toString()."  (resolved)<br/>\n";
      }

   }



   /**
    *  STATUS   | BEGIN                | END
    *  open     | firstAckDate         | previousIssueEndDate + getRemaining()
    *  analyzed | firstAckDate         | previousIssueEndDate + getRemaining()
    *  ack      | firstAckDate         | previousIssueEndDate + getRemaining()
    *  feedback | previousIssueEndDate | previousIssueEndDate + getRemaining()
    *  new      | previousIssueEndDate | previousIssueEndDate + getRemaining()

    */
   private function dispatchCurrentIssues($issueList) {
   	global $status_acknowledged;
   	global $status_new;
   	global $status_feedback;

      $bug_resolved_status_threshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);


      $userDispatchInfo = array(); // $userDispatchInfo[$issue->handlerId] = array(endTimestamp, $availTimeOnEndTimestamp)
      $today = date2timestamp(date("Y-m-d", time()));

      $availTimeOnBeginTimestamp = 0;

      foreach ($issueList as $issue) {

         // --- init user history
			if (NULL == $userDispatchInfo[$issue->handlerId]) {
				$user = UserCache::getInstance()->getUser($issue->handlerId);
			   $userDispatchInfo[$issue->handlerId] = array($today, $user->getAvailableTime($today));
			}

			//the dateOfInsertion is the arrivalDate of the user's latest added Activity (or 'today' if none)
			// but if the availableTime on dateOfInsertion is 0, then search the next 'free' day
			while ( 0 == $userDispatchInfo[$issue->handlerId][1]) {
				$dateOfInsertion = $userDispatchInfo[$issue->handlerId][0];
				#echo "DEBUG no availableTime on dateOfInsertion ".date("Y-m-d", $dateOfInsertion)."<br/>";
				$dateOfInsertion = strtotime("+1 day",$dateOfInsertion);
            $userDispatchInfo[$issue->handlerId] = array($dateOfInsertion, $user->getAvailableTime($dateOfInsertion));
			}

			#echo "DEBUG issue $issue->bugId : avail 1st Day (".date("Y-m-d", $userDispatchInfo[$issue->handlerId][0]).")= ".$userDispatchInfo[$issue->handlerId][1]."<br/>";

			// --- find startDate
			if ($issue->currentStatus > $status_feedback) {
      	   $startDate = $issue->getFirstStatusOccurrence($status_acknowledged);
      	   if (NULL == $startDate) {
      	   	$startDate = $issue->getFirstStatusOccurrence($issue->currentStatus); // TODO: wrong ! check all status
      	   }
      	   if (NULL == $startDate) { $startDate = $issue->dateSubmission; }

      	   // we got the start day (in the past) which is different from the endDate of previous activity.

			} else {
				// if status is new/feedback, we want the startDate to be the same as the endDate of previous activity.
				$startDate = $userDispatchInfo[$issue->handlerId][0];
			}



         $tmpDate=$userDispatchInfo[$issue->handlerId][0]; // DEBUG


			// --- compute endDate
			// the arrivalDate depends on the dateOfInsertion and the available time on that day
			$userDispatchInfo[$issue->handlerId] = $issue->computeEstimatedDateOfArrival($userDispatchInfo[$issue->handlerId][0],
			                                                                             $userDispatchInfo[$issue->handlerId][1]);
			$endDate = $userDispatchInfo[$issue->handlerId][0];


			#echo "DEBUG issue $issue->bugId : user $issue->handlerId status $issue->currentStatus startDate ".date("Y-m-d", $startDate)." tmpDate=".date("Y-m-d", $tmpDate)." endDate ".date("Y-m-d", $endDate)." RAF=".$issue->getRemaining()."<br/>";
			#echo "DEBUG issue $issue->bugId : left last Day = ".$userDispatchInfo[$issue->handlerId][1]."<br/>";

			// userActivityList
      	$activity = new GanttActivity($issue->bugId, $issue->handlerId, $startDate, $endDate);

      	//$activity->setColor($gantt_task_grey);

      	if (NULL == $this->userActivityList[$issue->handlerId]) {
      	   $this->userActivityList[$issue->handlerId] = array();
      	}
      	$this->userActivityList[$issue->handlerId][] = $activity;

    	   #echo "DEBUG add to userActivityList[".$issue->handlerId."]: ".$activity->toString()."  (resolved)<br/>\n";
      }

      return $this->userActivityList;
   }

   public function getTeamActivities() {

      $resolvedIssuesList = $this->getResolvedIssues();

      //echo "DEBUG getGanttGraph : dispatchResolvedIssues nbIssues=".count($resolvedIssuesList)."<br/>\n";
      $this->dispatchResolvedIssues($resolvedIssuesList);

      $currentIssuesList = $this->getCurrentIssues();
      $this->dispatchCurrentIssues($currentIssuesList);


      //echo "DEBUG getGanttGraph : display nbUsers=".count($this->userActivityList)."<br/>\n";
      $mergedActivities = array();
      foreach($this->userActivityList as $userid => $activityList) {
         $user = UserCache::getInstance()->getUser($userid);
         #echo "==== ".$user->getName()." activities: <br/>";
         $mergedActivities = array_merge($mergedActivities, $activityList);

      }

      $sortedList = qsort($mergedActivities);

      return $sortedList;
   }

   private function getConstrains($teamActivities, $issueActivityMapping) {

      $constrains = array();

      foreach($teamActivities as $a) {
         $issue = IssueCache::getInstance()->getIssue($a->bugid);
         $relationships = $issue->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
         foreach($relationships as $r) {
             #echo "DEBUG Activity $a->activityIdx constrains ".$issueActivityMapping[$r]."<br/>";

             $constrains[] = array($a->activityIdx, $issueActivityMapping[$r], CONSTRAIN_ENDSTART);
         }
      }
      return $constrains;
   }


   /**
    *
    */
   public function getGanttGraph() {


      $teamActivities = $this->getTeamActivities();

      // ----
      $issueActivityMapping = array();
      $progress = array();
      $data = array();

      $activityIdx = 0;
      foreach($teamActivities as $a) {
         $data[] = $a->getJPGraphData($activityIdx);
         $progress[] = array($activityIdx, $a->progress);

         // mapping to ease constrains building
         $issueActivityMapping[$a->bugid] = $activityIdx;

         ++$activityIdx;
      }

      // ---
      $constrains = $this->getConstrains($teamActivities, $issueActivityMapping);
      // ----
   	$graph = new GanttGraph();

      $team = new Team($this->teamid);
      $graph->title->Set("Team '".$team->name."'");

      // Setup scale
      $graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);
      $graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAYWNBR);

      // Add the specified activities
      $graph->CreateSimple($data,$constrains,$progress);

   	return $graph;
   }

}
?>