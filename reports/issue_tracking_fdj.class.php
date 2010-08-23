<?php

// -- FDJ specificities for IssueTracking

include_once "issue.class.php";
include_once "issue_fdj.class.php";
include_once "issue_tracking.class.php";

class IssueTrackingFDJ extends IssueTracking {
        
  public function initialize() {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_deferred;
    global $status_resolved;
    global $status_closed;
    global $status_feedback_ATOS;
    global $status_feedback_FDJ;
      
    $this->displayedStatusList = array($status_new, 
                                       $status_ack, 
                                       $status_feedback_ATOS, 
                                       $status_feedback_FDJ, 
                                       $status_analyzed,
                                       $status_accepted,
                                       $status_openned,
                                       $status_deferred,
                                       $status_resolved,
                                       $status_closed);
      
    $query = "SELECT id FROM `mantis_bug_table` ORDER BY id DESC";
    $result = mysql_query($query) or die("Query failed: $query");

    while($row = mysql_fetch_object($result))
    {
      $issue = new IssueFDJ ($row->id);
      $issue->computeDurations();
      $this->issueList[$row->id] = $issue; 
    }
  }
}

?>

