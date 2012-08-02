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

// TODO Remove this import
include_once('classes/timetrack_cache.class.php');

include_once('classes/issue_cache.class.php');
include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

/**
 * TimeTrackTuple
 */
class TimeTrack {

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

  var $id;
  var $userId;
  var $bugId;
  var $jobId;
  var $date;
  var $duration;

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
        $query = "SELECT * FROM `codev_timetracking_table` ".
                 "WHERE codev_timetracking_table.id=$this->id ";

        $result = SqlWrapper::getInstance()->sql_query($query);
        if (!$result) {
           echo "<span style='color:red'>ERROR: Query FAILED</span>";
           exit;
        }
        $row = SqlWrapper::getInstance()->sql_fetch_object($result);
     }

    $this->userId   = $row->userid;
    $this->bugId    = $row->bugid;
    $this->jobId    = $row->jobid;
    $this->date     = $row->date;
    $this->duration = $row->duration;
    
    #echo "DEBUG TimeTrack $this->id $this->userId $this->bugId $this->jobId $this->date $this->duration $this->issue_projectId<br/>";
  }

  public function getProjectId() {
     if (NULL == $this->projectId) {
         $issue = IssueCache::getInstance()->getIssue($this->bugId);

         $this->projectId = $issue->projectId;
         $this->categoryId = $issue->categoryId;
     }
     return $this->projectId;
  }

  public function getCategoryId() {
     if (NULL == $this->categoryId) {
         $issue = IssueCache::getInstance()->getIssue($this->bugId);

         $this->projectId = $issue->projectId;
         $this->categoryId = $issue->categoryId;
     }
     return $this->categoryId;
  }

  /**
   * @param int $userid
   * @param int $bugid
   * @param int $job
   * @param unknown_type $timestamp
   * @param unknown_type $duration
   */
  public static function create($userid, $bugid, $job, $timestamp, $duration) {
    $query = "INSERT INTO `codev_timetracking_table`  (`userid`, `bugid`, `jobid`, `date`, `duration`) VALUES ('$userid','$bugid','$job','$timestamp', '$duration');";
    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }
    $trackid = SqlWrapper::getInstance()->sql_insert_id();
    return $trackid;
  }

   /**
    * Remove the current track
    * @return bool True if the track is removed
    */
   public function remove() {
      $query = 'DELETE FROM `codev_timetracking_table` WHERE id='.$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      } else {
         self::$logger->debug("Track $this->id deleted: userid=$this->userId bugid=$this->bugId job=$this->jobId duration=$this->duration timestamp=$this->date");
         return true;
      }
   }

  /**
   * update Backlog and delete TimeTrack
   * @param int $trackid
   */
  public static function delete($trackid) {
     // increase backlog (only if 'backlog' already has a value)
     $timetrack = TimeTrackCache::getInstance()->getTimeTrack($trackid);
     $bugid = $timetrack->bugId;
     $duration = $timetrack->duration;
     $issue = IssueCache::getInstance()->getIssue($bugid);
     if (NULL != $issue->backlog) {
        $backlog = $issue->backlog + $duration;
        $issue->setBacklog($backlog);
     }

     // delete track
     if (!$timetrack->remove()) {
        echo "<span style='color:red'>ERROR: Query FAILED</span>";
        exit;
     }
  }

}

TimeTrack::staticInit();

?>
