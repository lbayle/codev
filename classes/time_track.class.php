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

/**
 * TimeTrackTuple
 */
class TimeTrack extends Model implements Comparable {

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

   private $id;
   private $userId;
   private $bugId;
   private $jobId;
   private $date;
   private $duration;
   private $committer_id;
   private $commit_date;
   private $note;
   private $cost;
   private $currency;

   private $projectId;
   private $categoryId;

   /**
    * @param int $id The time track id
    * @param resource $details The time track details
    */
   public function __construct($id, $details = NULL) {
      $this->id = $id;

      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The issue details
    */
   public function initialize($row = NULL) {
      if($row == NULL) {
         $query = 'SELECT * FROM `codev_timetracking_table` WHERE id = '.$this->id.';';

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }

      $this->userId = $row->userid;
      $this->bugId = $row->bugid;
      $this->jobId = $row->jobid;
      $this->date = $row->date;
      $this->duration = $row->duration;
      $this->committer_id = $row->committer_id;
      $this->commit_date = $row->commit_date;

      // PERF: initialy, it was planned to store the cost directly in the timetrack to avoid heavy computing,
      //       but it turns out that it is not necessary, and it is much easier to handle if UDC/ADC changes.
/*
      $this->cost = (NULL === $row->cost) ? NULL : $row->cost / 1000000; // (6 decimals)
      $this->currency = $row->currency;
*/
      #echo "DEBUG TimeTrack $this->id $this->userId $this->bugId $this->jobId $this->date $this->duration $this->issue_projectId<br/>";
   }

   /**
    * Sort older to newer
    * @param Issue $timetrackA
    * @param Issue $timetrackB
    * @return int -1 if $timetrackB is more recent, 1 if $timetrackB is older, 0 if same day
    */
   public static function compare(Comparable $timetrackA, Comparable $timetrackB) {

      if ($timetrackB->getDate() > $timetrackA->getDate()) {
         return -1;
      }
      if ($timetrackB->getDate() < $timetrackA->getDate()) {
         return 1;
      }

      // same day
      if ($timetrackB->getCommitDate() > $timetrackA->getCommitDate()) {
         return -1;
      }
      if ($timetrackB->getCommitDate() < $timetrackA->getCommitDate()) {
         return 1;
      }

      return 0;
   }

   public function getProjectId() {
      if (NULL == $this->projectId) {
         $issue = IssueCache::getInstance()->getIssue($this->bugId);

         $this->projectId = $issue->getProjectId();
         $this->categoryId = $issue->getCategoryId();
      }
      return $this->projectId;
   }

   public function getCategoryId() {
      if (NULL == $this->categoryId) {
         $issue = IssueCache::getInstance()->getIssue($this->bugId);

         $this->projectId = $issue->getProjectId();
         $this->categoryId = $issue->getCategoryId();
      }
      return $this->categoryId;
   }

   /**
    * @static
    * @param int $userid the user that worked on the task
    * @param int $bugid
    * @param int $job
    * @param int $timestamp
    * @param number $duration
    * @param int $committerid the user who added the timetrack (user or his manager)
    * @return int
    */
   public static function create($userid, $bugid, $job, $timestamp, $duration, $committerid, $teamid) {

      if ((0 == $userid) ||
          (0 == $bugid) ||
          (0 == $job) ||
          (0 == $timestamp) ||
          (0 == $duration)) {
         self::$logger->error("create track : userid = $userid, bugid = $bugid, job = $job, timestamp = $timestamp, duration = $duration");
         return 0;
      }

      // compute timetrack cost
      // PERF: initialy, it was planned to store the cost directly in the timetrack to avoid heavy computing,
      //       but it turns out that it is not necessary, and it is much easier to handle if UDC/ADC changes.
      $cost = NULL;
      $currency = NULL;
/*
      try {
         $team= TeamCache::getInstance()->getTeam($teamid);
         $udr = $team->getUdrValue($userid, $timestamp);
         $cost = round($duration * $udr, 6) *1000000; // (6 decimals)
         $currency = $team->getTeamCurrency();
      } catch (Exception $e) {
         $cost = NULL;
      }
*/
      $commit_date=time();
      $query  = "INSERT INTO `codev_timetracking_table`  (`userid`, `bugid`, `jobid`, `date`, `duration`, `committer_id`, `commit_date`";
      $query .= is_null($cost) ? ") " : ", `cost`, `currency`) ";
      $query .= " VALUES ('$userid','$bugid','$job','$timestamp', '$duration', '$committerid', '$commit_date'";
      $query .= is_null($cost) ? ") " :  ", '$cost', '$currency');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }
   
   
   public function update($date, $duration, $jobid, $note = NULL) {
      $this->date = $date;
      $this->duration = $duration;
      $this->jobId = $jobid;
      $commitDate = time();
      
      $query = 'UPDATE `codev_timetracking_table` SET `date`='.$this->date.', `duration`='.$this->duration.', `jobid`='.$this->jobId.', `commit_date`='.$commitDate.' WHERE id='.$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      
      if (!$result) {
         return false;
      }
      if(NULL != $note){
         $this->removeNote($this->userId);
         self::setNote($this->bugId, $this->id, $note, $this->userId);
      }
      return true;
   }

   /**
    * Remove the current track
    * @param int $userid the one that removed the track
    * @param bool $isRecreditBacklog if true, add timetrack duration to the task backlog
    * @return bool True if the track is removed
    */
   public function remove($userid, $isRecreditBacklog=FALSE) {
      $query = 'DELETE FROM `codev_timetracking_table` WHERE id='.$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      }

      if ($isRecreditBacklog) {
         $this->updateBacklog($this->duration);
      }

      if(!$this->removeNote($userid)){
         self::$logger->error("Delete note for track=$this->id FAILED.");
      }
      return true;
   }

   public function updateBacklog($diff) {
      $issue = IssueCache::getInstance()->getIssue($this->bugId);
      if (!is_null($issue->getBacklog())) {
         $backlog = $issue->getBacklog() + $diff;
         $issue->setBacklog($backlog);
      }
   }

   /**
    *
    * @param int $userid the one that removed the note
    * @return boolean
    */
   public function removeNote($userid) {
      $retCode = false;

      $query = "SELECT `noteid` FROM `codev_timetrack_note_table` WHERE timetrackid=$this->id;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      }

      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         $noteid = SqlWrapper::getInstance()->sql_result($result, 0);
         IssueNote::delete($noteid, $this->bugId, $userid);
      } else {
         self::$logger->warn("No track_note defined for timetrack_id = $this->id");
      }

      $query2 = "DELETE FROM `codev_timetrack_note_table` WHERE timetrackid = $this->id;";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);

      return ($result2) ? true : false;
   }

   /**
    * @return int
    */
   public function getId() {
      return $this->id;
   }

   /**
    * @return int
    */
   public function getUserId() {
      return $this->userId;
   }

   /**
    * @return int
    */
   public function getIssueId() {
      return $this->bugId;
   }

   /**
    * @return int
    */
   public function getJobId() {
      return $this->jobId;
   }

   /**
    * @return int
    */
   public function getDate() {
      return $this->date;
   }

   public function getDuration() {
      return $this->duration;
   }

   public function getCommitterId() {
      return $this->committer_id;
   }
   public function getCommitDate() {
      return $this->commit_date;
   }

   /**
    * return cost of this timetrack.
    * if $targetCurrency is NULL, then return in teamCurrency
    *
    * if cost == NULL, result depends on $teamid:
    *  - if $teamid == NULL: return NULL (raw value)
    *  - if  $teamid > 0 : compute cost with teamADR (no DB update !)
    *      => if teamADR is undefined, throw exception
    *
    * @param string $targetCurrency target currency (default: team currency)
    * @param type $teamid if not NULL & cost is NULL,  return teamADR
    * @return float cost if cost is null, return null
    * @throws Exception
    */
   public function getCost($targetCurrency=NULL, $teamid=NULL) {

      if (NULL === $this->cost) { // Note: with type comparison, as cost can be 0 (yes, why not)

         if (NULL == $teamid) { return FALSE; } // raw value

         // search for UDC
         $team = TeamCache::getInstance()->getTeam($teamid);
         $udr = $team->getUdcValue($this->userId, $this->date, $team->getTeamCurrency());

         // compute with UDC, but no update in DB !
         $this->cost = $this->duration * $udr;
         $this->currency = $team->getTeamCurrency();
      }

      // convert to target currency
      if (NULL === $targetCurrency) {
         $team = TeamCache::getInstance()->getTeam($teamid);
         $targetCurrency = $team->getTeamCurrency();
      }
      if ($targetCurrency !== $this->currency) {
         $newCost = Currencies::getInstance()->convertValue($this->cost, $this->currency, $targetCurrency);
         return $newCost;
      } else {
         return $this->cost;
      }
   }

   public function getNote() {

      if (NULL == $this->note) {

         $query = "SELECT note FROM `mantis_bugnote_text_table` ".
                 "WHERE id=(SELECT bugnote_text_id FROM `mantis_bugnote_table` ".
                            "WHERE bugnote_text_id=(SELECT noteid FROM `codev_timetrack_note_table` ".
                                                                  "WHERE timetrackid=$this->id))";
         $result = SqlWrapper::getInstance()->sql_query($query);

         if(SqlWrapper::getInstance()->sql_num_rows($result) == 0) {
            $query3 = 'DELETE FROM `codev_timetrack_note_table` WHERE timetrackid='.$this->id.';';
            $result3 = SqlWrapper::getInstance()->sql_query($query3);
            $this->note = "";

         } else {
            $row = SqlWrapper::getInstance()->sql_fetch_object($result);
            $pattern = '/^'.IssueNote::tag_begin.IssueNote::tagid_timetrackNote.'.*'.IssueNote::tag_end.'\n/';
            $this->note = trim(preg_replace($pattern, '', $row->note));
         }
      }
      return $this->note;

   }

      public static function setNote($bug_id, $track_id, $text, $reporter_id) {
      $timetrack = TimeTrackCache::getInstance()->getTimeTrack($track_id);
      self::$logger->debug("Task $bug_id setTimesheetNote:[$text]");

      // add TAG in front (if not found)
      if (FALSE === strpos($text, IssueNote::tagid_timetrackNote)) {
         $date = Tools::formatDate("%Y-%m-%d", $timetrack->getDate());

         $tag = IssueNote::tag_begin . IssueNote::tagid_timetrackNote . ' ' . $date . ' ' . IssueNote::tag_doNotRemove . IssueNote::tag_end;
         $text = $tag . "\n" . $text;
      }

      $issueNote = IssueNote::getTimesheetNote($bug_id);
      if (is_null($issueNote)) {
         $bugnote_id = IssueNote::create($bug_id, $reporter_id, $text, IssueNote::type_timetrackNote, TRUE, $timetrack->getDate());
      } else {
         # notify users that the note has changed
         $text = IssueNote::removeAllReadByTags($text);

         $issueNote->setText($text, $reporter_id);
         $bugnote_id = $issueNote->getId();
      }

      $query = "INSERT INTO `codev_timetrack_note_table` (timetrackid, noteid) VALUES ($track_id, $bugnote_id)";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

}
TimeTrack::staticInit();


