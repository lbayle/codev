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

include_once "timetrack_cache.class.php";

/**
 * TimeTrackTuple
 */
class TimeTrack {

  private $logger;

  var $id;
  var $userId;
  var $userName;
  var $userRealname;
  var $bugId;
  var $jobId;
  var $date;
  var $duration;

  var $projectId;
  var $categoryId;

  public function TimeTrack($id) {
    $this->id = $id;
    $this->logger = Logger::getLogger(__CLASS__);

    $this->initialize();
  }

  public function initialize() {
    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, ".
      "codev_timetracking_table.jobid, codev_timetracking_table.date, codev_timetracking_table.duration, ".
      "mantis_user_table.username, mantis_user_table.realname ".
      "FROM `codev_timetracking_table`, `mantis_user_table` ".
      "WHERE codev_timetracking_table.id=$this->id ".
      "AND mantis_user_table.id = codev_timetracking_table.userid";
    $result    = mysql_query($query);
    if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
    }
    $row = mysql_fetch_object($result);

    $this->userId   = $row->userid;
    $this->userName = $row->username;
    $this->userRealname = $row->realname;
    $this->bugId    = $row->bugid;
    $this->jobId    = $row->jobid;
    $this->date     = $row->date;
    $this->duration = $row->duration;

    // Get information on this bug
    $query2  = "SELECT project_id, category_id FROM `mantis_bug_table` WHERE id=$this->bugId";
    //$query2  = "SELECT summary, status, date_submitted, project_id FROM `mantis_bug_table` WHERE id=$this->bugId";
    $result2 = mysql_query($query2);
    if (!$result2) {
    	      $this->logger->error("Query FAILED: $query2");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
    }
    $row2 = mysql_fetch_object($result2);

    $this->projectId = $row2->project_id;
    $this->categoryId = $row2->category_id;

    //echo "DEBUG TimeTrack $this->id $this->userId $this->bugId $this->jobId $this->date $this->duration $this->issue_projectId<br/>";


  }

  /**
   *
   * @param unknown_type $userid
   * @param unknown_type $bugid
   * @param unknown_type $job
   * @param unknown_type $timestamp
   * @param unknown_type $duration
   */
  public static function create($userid, $bugid, $job, $timestamp, $duration) {
    $query = "INSERT INTO `codev_timetracking_table`  (`userid`, `bugid`, `jobid`, `date`, `duration`) VALUES ('$userid','$bugid','$job','$timestamp', '$duration');";
    $result = mysql_query($query);
    if (!$result) {
    	$this->logger->error("Query FAILED: $query");
    	$this->logger->error(mysql_error());
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }
    $trackid = mysql_insert_id();
    return $trackid;
  }

  /**
   * update Remaining and delete TimeTrack
   * @param unknown_type $trackid
   */
  public static function delete($trackid) {

    // increase remaining (only if 'remaining' already has a value)
    $query = "SELECT bugid, duration FROM `codev_timetracking_table` WHERE id = $trackid;";
    $result = mysql_query($query);
    if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
    }
    while($row = mysql_fetch_object($result))
    { // REM: only one line in result, while should be optimized
      $bugid = $row->bugid;
      $duration = $row->duration;
    }
    $issue = IssueCache::getInstance()->getIssue($bugid);
    if (NULL != $issue->remaining) {
      $remaining = $issue->remaining + $duration;
      $issue->setRemaining($remaining);
    }

    // delete track
    $query2 = "DELETE FROM `codev_timetracking_table` WHERE id = $trackid;";
    $result = mysql_query($query2);
    if (!$result) {
    	      $this->logger->error("Query FAILED: $query2");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
    }
  }

} // class TimeTrack

?>
