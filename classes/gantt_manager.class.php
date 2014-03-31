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

class GanttActivity implements Comparable {

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

   public $bugid;
   public $userid;
   public $startTimestamp;
   public $endTimestamp;
   public $color;

   public $progress;
   public $activityIdx;  // index in jpgraph Data structure

   public function __construct($bugId, $userId, $startT, $endT, $progress=NULL) {
      $this->bugid = $bugId;
      $this->userid = $userId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      if ($startT > $endT) {
         self::$logger->error("bugid=$bugId: Activity startDate $startT (".date('Y-m-d',$startT).") > endDate $endT (".date('Y-m-d',$endT).")");
      }

      if (NULL != $progress) {
         $this->progress = $progress;
      } else {
         // (BI+BS - RAF) / (BI+BS)
         $issue = IssueCache::getInstance()->getIssue($this->bugid);
         $this->progress = $issue->getProgress();
      }

      $this->color = 'lavender';

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Activity created for issue $bugId (".date('Y-m-d',$startT).") -> (".date('Y-m-d',$endT).")");
      }
   }

   public function setColor($color) {
      $this->color = $color;
   }

   public function setActivityIdx($activityIdx) {
      $this->activityIdx = $activityIdx;
   }

   public function getJPGraphBar($issueActivityMapping) {
      $user = UserCache::getInstance()->getUser($this->userid);
      $issue = IssueCache::getInstance()->getIssue($this->bugid);

      if (NULL != $issue->getTcId()) {
         $formatedActivityName = substr($this->bugid." [".$issue->getTcId()."] - ".$issue->getSummary(), 0, 50);
      } else {
         $formatedActivityName = substr($this->bugid." - ".$issue->getSummary(), 0, 50);
      }

      $formatedActivityInfo = $user->getName();
      if ($issue->getCurrentStatus() < $issue->getBugResolvedStatusThreshold()) {
         $formatedActivityInfo .= " (".Constants::$statusNames[$issue->getCurrentStatus()].")";
      }

      $bar = new GanttBar($this->activityIdx,
         utf8_decode($formatedActivityName),
         date('Y-m-d', $this->startTimestamp),
         date('Y-m-d', $this->endTimestamp),
         $formatedActivityInfo,10);

      // --- colors
      $bar->SetPattern(GANTT_SOLID, $this->color);
      $bar->progress->Set($this->progress);
      $bar->progress->SetPattern(GANTT_SOLID,'slateblue');

      // --- add constrains
      $relationships = $issue->getRelationships();
      $relationships = $relationships[''.Constants::$relationship_constrains];
      if (is_array($relationships)) {
         foreach($relationships as $bugid) {
            // Add a constrain from the end of this activity to the start of the activity $bugid
            $bar->SetConstrain($issueActivityMapping[$bugid], CONSTRAIN_ENDSTART);
         }
      }
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("JPGraphBar bugid=$this->bugid prj=".$issue->getProjectId()." activityIdx=$this->activityIdx".
            " progress=$this->progress [".
            date('Y-m-d', $this->startTimestamp)." -> ".
            date('Y-m-d', $this->endTimestamp)."]");
         
         self::$logger->debug("JPGraphBar bugid=$this->bugid GanttBar = ".var_export($bar, TRUE));
      }
      
      return $bar;
   }

   public function toString() {
      return "issue $this->bugid  - ".date('Y-m-d', $this->startTimestamp)." - ".date('Y-m-d', $this->endTimestamp)." - ".$this->userid;
   }

   /**
    * uSort compare method
    *
    *
    * @param Comparable $activityA
    * @param Comparable $activityB
    *
    * @return '1' if $activityB > $activityA, -1 if $activityB is lower, 0 if equals
    */
   public static function compare(Comparable $activityA, Comparable $activityB) {

      // the oldest activity should be in front of the list
      if ($activityA->endTimestamp > $activityB->endTimestamp) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("activity.compareTo FALSE  (".date('Y-m-d', $activityA->endTimestamp)." > ".date('Y-m-d', $activityB->endTimestamp).")");
         }
         return 1;
      } else if ($activityA->endTimestamp < $activityB->endTimestamp) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("activity.compareTo   (".date('Y-m-d', $activityA->endTimestamp)." < ".date('Y-m-d', $activityB->endTimestamp).")");
         }
         return -1;
      }
      return 0;
   }
}

GanttActivity::staticInit();

/**

1) recupere la liste des taches finies (status >= bug_resolved_status_threshold)
1.1) convertis en GanttActivity. (aucun calcul, les dates debut/fin sont connues) et dispatch dans des userActivityList

2) recupere toutes les taches en cours de l'equipe (status < bug_resolved_status_threshold)

3) trie taches en cours

4) convertis en GanttActivity et dispatch dans les userActivityList en calculant les dates.

5) cree le grath

 */
class GanttManager {

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

   private $teamid;
   private $startTimestamp;
   private $endTimestamp;

   /**
    * @param int $teamId
    * @param int $startT  start timestamp. if NULL, then now
    * @param int $endT    end timestamp. if NULL, shedule all backlog tasks
    */
   public function __construct($teamId, $startT=NULL, $endT=NULL) {
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("GanttManager($teamId, ".date('Y-m-d', $startT).", ".date('Y-m-d', $endT).")");
      }

      $this->teamid = $teamId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

      $this->activitiesByUser = array();   // $activitiesByUser[user][activity]
      $this->constrainsList   = array();
   }

   /**
    * get tasks resolved in the period
    * @return Issue[]
    */
   private function getResolvedIssues() {
      $tt = new TimeTracking($this->startTimestamp, $this->endTimestamp, $this->teamid);
      $resolvedIssuesList = $tt->getResolvedIssues();
      Tools::usort($resolvedIssuesList);
      return $resolvedIssuesList;
   }

   /**
    * get sorted list of current issues
    * @return Issue[]
    */
   private function getCurrentIssues() {
      $teamIssueList = array();

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $users = $team->getUsers();
      #$projects = $team->getProjects();

      // get all issues
      foreach($users as $user) {

         // do not include users that have left the team
         if ((NULL != $user->getDepartureDate($this->teamid)) &&
             ($user->getDepartureDate($this->teamid) < $this->startTimestamp)) {

               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getCurrentIssues(): skip user ".$user->getId().", he left the team on ".date('Y-m-d', $user->getDepartureDate($this->teamid)).")");
               }
               continue;
            }

         // do not take observer's tasks
         if (($user->isTeamDeveloper($this->teamid)) ||
            ($user->isTeamManager($this->teamid))) {

            $issueList = $user->getAssignedIssues();
            $teamIssueList = array_merge($teamIssueList, $issueList);
         }
      }

      // quickSort the list
      Tools::usort($teamIssueList);
      return $teamIssueList;
   }

   /**
    * create a GanttActivity for each issue and dispatch it in $activitiesByUser[user]
    * @param Issue[] $resolvedIssuesList
    */
   private function dispatchResolvedIssues(array $resolvedIssuesList) {
      foreach ($resolvedIssuesList as $issue) {
         #$startDate = $issue->getFirstStatusOccurrence(Constants::$status_acknowledged);
         $firstTrack = $issue->getFirstTimetrack();
         if (is_null($firstTrack) || is_null($firstTrack->getDate())) {
            $startDate = $issue->getDateSubmission();
         } else {
            $startDate = $firstTrack->getDate();
         }

         $endDate = $issue->getLatestStatusOccurrence($issue->getBugResolvedStatusThreshold());
         if (NULL == $endDate) {
            // TODO: no, $status_closed is not the only one ! check for all status > $bug_resolved_status_threshold
            $endDate = $issue->getLatestStatusOccurrence(Constants::$status_closed);
         }

         $activity = new GanttActivity($issue->getId(), $issue->getHandlerId(), $startDate, $endDate);

         if (!array_key_exists($issue->getHandlerId(), $this->activitiesByUser)) {
            $this->activitiesByUser[$issue->getHandlerId()] = array();
         }
         $this->activitiesByUser[$issue->getHandlerId()][] = $activity;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("add to activitiesByUser[".$issue->getHandlerId()."]: ".$activity->toString()."  (resolved)\n");
         }
      }
   }

   /**
    * The backlogStartDate (RSD) is NOT the startDate of the issue.
    *
    * The StartDate (except for status=new) is in the past, it's the
    * the date where the user started investigating on the issue.
    *
    * the RSD is a temporary date, used to determinate the endDate,
    * depending on the backlog days to resolve the issue.
    *
    * Note: If status='new' then, StartDate == RSD.
    *
    * There are a fiew things to consider to compute the RSD:
    * - arrivalDate of user's previous activity : nominal case
    * - constrains can postpone the RSD if constrain date > prev arrivalDate
    * - time left in prev arrivalDate (if 0, try next day)
    *
    * An optimization is mandatory because of constrains:
    * if the RSD has been postponed because user1 is waiting for
    * user2 to resolve the constraining activity. then, user1 should
    * work on an other activity instead of hanging around.
    *
    * So the question is : which activity should user1 work on ?
    * - the next assigned activity (highest priority) ?
    * - use a best-fit or worst-fit algorithm ?
    *
    * @param Issue $issue
    * @param array $userDispatchInfo
    * @return array[]
    */
   private function findBacklogStartDate(Issue $issue, array $userDispatchInfo) {
      $user = UserCache::getInstance()->getUser($issue->getHandlerId());

      $rsd = $userDispatchInfo[0]; // arrivalDate of the user's latest added Activity

      // check relationships
      // Note: if issue is constrained, then the constrained issue should already
      //       have an Activity. the contrary would mean that there is a bug in our
      //       sort algorithm...
      $relationships = $issue->getRelationships();
      $relationships = $relationships[''.Constants::$relationship_constrained_by];

      // find Activity
      foreach ($this->activitiesByUser as $userActivityList) {
         foreach ($userActivityList as $a) {
            if (is_array($relationships) && in_array($a->bugid, $relationships)) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("issue ".$issue->getId()." (".date("Y-m-d", $rsd).") is constrained by $a->bugid (".date("Y-m-d", $a->endTimestamp).")");
               }
               if ($a->endTimestamp > $rsd) {
                  if(self::$logger->isDebugEnabled()) {
                     self::$logger->debug("issue ".$issue->getId()." postponed for $a->bugid");
                  }
                  $rsd = $a->endTimestamp;
                  $userDispatchInfo = array($rsd, $user->getAvailableTime($rsd));
               }
            }
         }
      }

      //the RSD is the arrivalDate of the user's latest added Activity
      // but if the availableTime on BacklogStartDate is 0, then search for the next 'free' day
      while ( 0 == $userDispatchInfo[1]) {
         $rsd = $userDispatchInfo[0];
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("no availableTime on BacklogStartDate ".date("Y-m-d", $rsd));
         }
         $rsd = strtotime("+1 day",$rsd);
         $userDispatchInfo = array($rsd, $user->getAvailableTime($rsd));
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("issue ".$issue->getId()." : avail 1st Day (".date("Y-m-d", $userDispatchInfo[0]).")= ".$userDispatchInfo[1]);
      }
      return $userDispatchInfo;
   }

   /**
    * The startDate is the date where the user started investigating on the issue.
    *
    * This date depends on the currentStatus.
    *
    * Generaly, investigation starts when the user sets the status to
    * 'acknowledge' for the first time. But depending on the workflow
    * configuration, the 'ack' status may be skipped or not exist.
    * so we'll search for the first changeStatus to a status which is:
    * new > ourStatus > bug_resolved_status_threshold
    *
    * If status is new then the user did not start investigations.
    *
    * If the issue has been created with a status > $new, then
    * the startDate shall be the date of submission.
    * (because work has already started).
    *
    * Now, what if currentStatus == 'Feedback' ?
    * If status is feedback, then at least a minimum of investigation
    * has been done to decide setting status to feedback, so i'd say
    * that there is no special case for feedback.
    *
    *  STATUS   | START_DATE
    *  new      | BacklogStartDate
    *  feedback | firstDate of changeStatus to status > New
    *  ack      | firstDate of changeStatus to status > New
    *  analyzed | firstDate of changeStatus to status > New
    *  open     | firstDate of changeStatus to status > New
    *
    * @param Issue $issue
    * @param int $backlogStartDate
    * @return int
    */
   private function findStartDateFromStatus(Issue $issue, $backlogStartDate) {
      if (Constants::$status_new == $issue->getCurrentStatus()) {
         // if status is new, we want the startDate to be the same as the endDate of previous activity.
         $startDate = $backlogStartDate;
      } else {
         $query = "SELECT date_modified FROM `mantis_bug_history_table` ".
                  "WHERE bug_id=".$issue->getId()." ".
                  "AND field_name = 'status' ".
                  "AND old_value=".Constants::$status_new." ORDER BY id DESC";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $startDate  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : NULL;

         // this happens, if the issue has been created with a status != 'new'
         if (NULL == $startDate) { $startDate = $issue->getDateSubmission(); }
      }

      return $startDate;
   }

   /**
    * use min(first timetrack, first status change) as startDate
    *
    * @param Issue $issue
    * @param type $backlogStartDate
    * @return type
    */
   private function findStartDate(Issue $issue, $backlogStartDate) {

      // sometimes people add a timetrack but forget to update the status,
      // in this case the date of the first timetrack will be used
      $tt = $issue->getFirstTimetrack();
      $ttDate = NULL;
      if (NULL != $tt) {
         $ttDate = $tt->getDate();
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("findStartDate() issue=".$issue->getId().": first timetrack on ".date("Y-m-d", $tt->getDate()));
         }
      }
      // use status to find startDate
      $statusDate = $this->findStartDateFromStatus($issue, $backlogStartDate);
      
      // startDate is the older one
      if (NULL == $ttDate) {
         // statusDate is never NULL
         $startDate = $statusDate;
      } else {
         $startDate = min($ttDate, $statusDate);
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("findStartDate() issue=".$issue->getId().": startDate=".date("Y-m-d", $startDate).' : min('.date("Y-m-d", $ttDate).','.date("Y-m-d", $statusDate).')');
         }
      }
      return $startDate;
   }


   /**
    *  STATUS   | BEGIN                | END
    *  open     | firstAckDate         | previousIssueEndDate + getBacklog()
    *  analyzed | firstAckDate         | previousIssueEndDate + getBacklog()
    *  ack      | firstAckDate         | previousIssueEndDate + getBacklog()
    *  feedback | previousIssueEndDate | previousIssueEndDate + getBacklog()
    *  new      | previousIssueEndDate | previousIssueEndDate + getBacklog()
    *
    * @param Issue[] $issueList
    * @return array[]
    */
   private function dispatchCurrentIssues(array $issueList) {
      $teamDispatchInfo = array(); // $teamDispatchInfo[userid] = array(endTimestamp, $availTimeOnEndTimestamp)
      $today = Tools::date2timestamp(date("Y-m-d", time()));

      foreach ($issueList as $issue) {
         $user = UserCache::getInstance()->getUser($issue->getHandlerId());

         // init user history
         if (!array_key_exists($issue->getHandlerId(),$teamDispatchInfo)) {
            // let's assume 'today' being the endTimestamp of previous activity
            $teamDispatchInfo[$issue->getHandlerId()] = array($today, $user->getAvailableTime($today));
         }

         // find backlogStartDate
         $teamDispatchInfo[$issue->getHandlerId()] = $this->findBacklogStartDate($issue, $teamDispatchInfo[$issue->getHandlerId()]);
         $backlogStartDate = $teamDispatchInfo[$issue->getHandlerId()][0];

         // find startDate
         $startDate = $this->findStartDate($issue, $backlogStartDate);

         // compute endDate
         // the arrivalDate depends on the dateOfInsertion and the available time on that day
         $teamDispatchInfo[$issue->getHandlerId()] = $issue->computeEstimatedDateOfArrival($teamDispatchInfo[$issue->getHandlerId()][0],
            $teamDispatchInfo[$issue->getHandlerId()][1]);
         $endDate = $teamDispatchInfo[$issue->getHandlerId()][0];

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue ".$issue->getId()." : user ".$issue->getHandlerId()." status ".$issue->getCurrentStatus()." startDate ".date("Y-m-d", $startDate)." tmpDate=".date("Y-m-d", $backlogStartDate)." endDate ".date("Y-m-d", $endDate)." RAF=".$issue->getDuration());
            self::$logger->debug("issue ".$issue->getId()." : left last Day = ".$teamDispatchInfo[$issue->getHandlerId()][1]);
         }

         // activitiesByUser
         $activity = new GanttActivity($issue->getId(), $issue->getHandlerId(), $startDate, $endDate);

         if (!array_key_exists($issue->getHandlerId(),$this->activitiesByUser)) {
            $this->activitiesByUser[$issue->getHandlerId()] = array();
         }
         $this->activitiesByUser[$issue->getHandlerId()][] = $activity;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("add to activitiesByUser[".$issue->getHandlerId()."]: ".$activity->toString()."  (resolved)");
         }
      }

      return $this->activitiesByUser;
   }

   /**
    * @return GanttActivity[]
    */
   public function getTeamActivities() {
      $resolvedIssuesList = $this->getResolvedIssues();

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("dispatchResolvedIssues nbIssues=".count($resolvedIssuesList));
      }
      $this->dispatchResolvedIssues($resolvedIssuesList);

      $currentIssuesList = $this->getCurrentIssues();
      $this->dispatchCurrentIssues($currentIssuesList);

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("display nbUsers=".count($this->activitiesByUser));
      }
      $mergedActivities = array();
      foreach($this->activitiesByUser as $userid => $activityList) {
         #$user = UserCache::getInstance()->getUser($userid);
         #echo "==== ".$user->getName()." activities: <br/>";
         $mergedActivities = array_merge($mergedActivities, $activityList);

      }

      Tools::usort($mergedActivities);
      return $mergedActivities;
   }

}

GanttManager::staticInit();

?>
