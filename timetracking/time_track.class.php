<?php

// -- TimeTrackTuple --

class TimeTrack {
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

    $this->initialize();
  }

  public function initialize() {
    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, ".
      "codev_timetracking_table.jobid, codev_timetracking_table.date, codev_timetracking_table.duration, ".
      "mantis_user_table.username, mantis_user_table.realname ".
      "FROM `codev_timetracking_table`, `mantis_user_table` ".
      "WHERE codev_timetracking_table.id=$this->id ".
      "AND mantis_user_table.id = codev_timetracking_table.userid";
    $result    = mysql_query($query) or die("Query failed: $query");
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
    $result2 = mysql_query($query2) or die("Query failed: $query2");
    $row2 = mysql_fetch_object($result2);

    $this->projectId = $row2->project_id;
    $this->categoryId = $row2->category_id;
                
    //echo "DEBUG TimeTrack $this->id $this->userId $this->bugId $this->jobId $this->date $this->duration $this->issue_projectId<br/>";
  }
        
  public function isVacation() {
    global $vacationCategory;
    global $vacationProject;
                
    if (($this->projectId == $vacationProject) && ($this->categoryId == $vacationCategory)) {
      return true;
    }
    return false;
  }

} // class TimeTrack

?>