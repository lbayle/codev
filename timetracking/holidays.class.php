<?php

// -- Holidays preview --

//include_once "time_track.class.php";
include_once "../reports/issue.class.php";

class Holidays {
  var $userid;
  var $year;
   
  public function Holidays($userid, $year) {
    $this->userid = $userid;
    $this->year = $year;
  }

  public function getDaysOfInMonth($month) {
    $daysOf = array();  // day => duration
        
    $startTimestamp = mktime(0, 0, 0, $month, 1, $this->year);
    $endTimestamp   = mktime(23, 59, 59, $month, 31, $this->year);
        
    return $this->getDaysOfInPeriod($startTimestamp, $endTimestamp);
  }
   
  public function getDaysOfInPeriod($startTimestamp, $endTimestamp) {
    $daysOf = array();  // day => duration
      
    $query     = "SELECT bugid, date, duration FROM `codev_timetracking_table` ".
      "WHERE date >= $startTimestamp AND date < $endTimestamp AND userid = $this->userid";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result)) {
         
      $issue = new Issue ($row->bugid);
      if ($issue->isVacation()) {
        $daysOf[date("j", $row->date)] += $row->duration;
        //echo "DEBUG user $this->userid daysOf[".date("j", $row->date)."] = ".$daysOf[date("j", $row->date)]." (+$row->duration)<br/>";
      }
    }
    return $daysOf;
  }
   
} // class

?>