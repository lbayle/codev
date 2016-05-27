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
class TimeTrack extends Model {

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

      #echo "DEBUG TimeTrack $this->id $this->userId $this->bugId $this->jobId $this->date $this->duration $this->issue_projectId<br/>";
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
    * @param int $committer_id the user who added the timetrack (user or his manager)
    * @return int
    */
   public static function create($userid, $bugid, $job, $timestamp, $duration, $committer_id) {

      if ((0 == $userid) ||
          (0 == $bugid) ||
          (0 == $job) ||
          (0 == $timestamp) ||
          (0 == $duration)) {
         self::$logger->error("create track : userid = $userid, bugid = $bugid, job = $job, timestamp = $timestamp, duration = $duration");
         return 0;
          }
      $commit_date=time();
      $query = "INSERT INTO `codev_timetracking_table`  (`userid`, `bugid`, `jobid`, `date`, `duration`, `committer_id`, `commit_date`) VALUES ('$userid','$bugid','$job','$timestamp', '$duration', '$committer_id', '$commit_date');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * Remove the current track
    * @return bool True if the track is removed
    */
   public function remove($userid) {
      $query = 'DELETE FROM `codev_timetracking_table` WHERE id='.$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      }

      if(!UniteOeuvre::remove($this->id)){
         self::$logger->error("Delete UO for track=$this->id FAILED.");
      }
      if(!$this->removeNote($userid)){
         self::$logger->error("Delete note for track=$this->id FAILED.");
      }
      return true;
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
         self::$logger->error("No track_note defined for timetrack_id = $this->id");
      }

      $query2 = "DELETE FROM `codev_timetrack_note_table` WHERE timetrackid = $this->id;";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);

      return ($result2) ? true : false;
   }
   

   /**
    * update Backlog and delete TimeTrack
    * @param int $trackid
    * @param int $userid the one that deletes the timetrack
    */
   public static function delete($trackid, $userid) {
      // increase backlog (only if "backlog' already has a value)
      $timetrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
      $bugid = $timetrack->bugId;
      $duration = $timetrack->duration;
      $issue = IssueCache::getInstance()->getIssue($bugid);
      if (!is_null($issue->getBacklog())) {
         $backlog = $issue->getBacklog() + $duration;
         $issue->setBacklog($backlog);
      }

      // delete track
      if (!$timetrack->remove($userid)) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
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
    * add/update the TimetheetNote of an Issue
    * @param type $bug_id
    * @param type $text
    */
   public static function setNote($bug_id, $track_id, $text, $reporter_id) {

      self::$logger->debug("Task $bug_id setTimesheetNote:[$text]");

      // add TAG in front (if not found)
      if (FALSE === strpos($text, self::tagid_timetrackNote)) {
         $tag = self::tag_begin . self::tagid_timetrackNote . ' ' . $track_id . self::tag_doNotRemove . self::tag_end;
         $text = $tag . "\n" . $text;
      }

      $issueNote = self::getTimesheetNote($bug_id);
      if (is_null($issueNote)) {
         $bugnote_id = self::create($bug_id, $reporter_id, $text, self::type_timetrackNote, TRUE);
      } else {
         # notify users that the note has changed
         $text = self::removeAllReadByTags($text);

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
         }
         else
         {
            $row = SqlWrapper::getInstance()->sql_fetch_object($result);
            $this->note = str_replace(array("\r", "\n"), '', $row->note);
            $this->note = strip_tags($this->note);
         }
     }
     return $this->note;
   }
}
TimeTrack::staticInit();


