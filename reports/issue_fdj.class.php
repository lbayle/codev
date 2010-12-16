<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php

// -- FDJ specificities for IssueTracking

include_once "../constants.php";
include_once "issue.class.php";
include_once "../auth/user.class.php";

class IssueFDJ extends Issue {

  // Computes the lifeCycle of the issue (time spent on each status)
  public function computeDurations () {
    global $status_new;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_deferred;
    global $status_resolved;
    global $status_delivered;
    global $status_closed;
    global $status_feedback_ATOS;
    global $status_feedback_FDJ;

    $this->statusList[$status_new]      = new Status($status_new, $this->getDuration_new());
    $formatedDateList = $this->getDuration_feedback();
    $this->statusList[$status_feedback_ATOS] = new Status($status_feedback_ATOS, $formatedDateList[$status_feedback_ATOS]);
    $this->statusList[$status_feedback_FDJ]  = new Status($status_feedback_FDJ,  $formatedDateList[$status_feedback_FDJ]);
    $this->statusList[$status_ack]      = new Status($status_ack,      $this->getDuration_other($status_ack));
    $this->statusList[$status_analyzed] = new Status($status_analyzed, $this->getDuration_other($status_analyzed));
    $this->statusList[$status_accepted] = new Status($status_accepted, $this->getDuration_other($status_accepted));
    $this->statusList[$status_openned]  = new Status($status_openned,  $this->getDuration_other($status_openned));
    $this->statusList[$status_deferred] = new Status($status_deferred, $this->getDuration_other($status_deferred));
    $this->statusList[$status_resolved] = new Status($status_resolved, $this->getDuration_other($status_resolved));
    $this->statusList[$status_delivered] = new Status($status_delivered, $this->getDuration_other($status_delivered));
    $this->statusList[$status_closed]   = new Status($status_closed,   $this->getDuration_other($status_closed));

    //echo "computeDurations: duration new = ".$this->statusList[$status_new]->duration."<br/>";
  }
   
  // Feedback is special: it must be separated in two groups:
  // -- feedback assigned to 'ATOS'
  // -- feedback assigned to 'FDJ'
  private function getDuration_feedback() {
    
  	 global $status_feedback_ATOS;
    global $status_feedback_FDJ;
  	 global $status_feedback;
    global $FDJ_teamid;
      
    $time_atos = 0;
    $time_fdj = 0;
    $current_date = time();

    //  -- the start_date is transition where new_value = status
    //  -- the end_date   is transition where old_value = status, or current date if no transition found.

    // Find start_date
    $query = "SELECT id, date_modified, old_value, new_value ".
             "FROM `mantis_bug_history_table` ".
             "WHERE bug_id=$this->bugId ".
             "AND field_name = 'status' ".
             "AND (new_value=$status_feedback OR old_value=$status_feedback) ".
             "ORDER BY id ASC";
    $result = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $start_date = $row->date_modified;
      $start_id = $row->id;

      // Next line is end_date. if NULL then end_date = current_date
      if ($row = mysql_fetch_object($result)) {
        $end_date = $row->date_modified;
        $end_id   = $row->id;
        $sql_condition = " AND id <= '$end_id'";
      } else {
        $end_date = $current_date;
        $end_id   = $start_id; // easy way to check if feedback is the current status
        $sql_condition = "";
      }
      $intervale =  $end_date - $start_date;

      //echo "STATUS start_id = $start_id &nbsp;&nbsp;&nbsp;&nbsp; end_id = $end_id <br/>";

      // Determinate to whom it was assigned
      //   -- find the last handler_id change before $end_id
      $query2 = "SELECT id, date_modified, old_value, new_value ".
                "FROM `mantis_bug_history_table` ".
                "WHERE bug_id=$this->bugId ".
                "AND field_name='handler_id' ".
                $sql_condition.
                " ORDER BY id DESC";
      $result2 = mysql_query($query2) or die("Query failed: $query2");

      // the list is in reverse order so the first one is the latest assignment.
      $row2 = mysql_fetch_object($result2);
      $handler_id = $row2->new_value;
      $latest_assignTo_id = $row2->id;
      $latest_assignTo_date = $row2->date_modified;
      $previous_handler_id= $row2->old_value;

      //echo "latest assign id = $latest_assignTo_id &nbsp;&nbsp;&nbsp;&nbsp; date=$latest_assignTo_date &nbsp;&nbsp;&nbsp;&nbsp;  handler_id=$handler_id<br/>";

      // If 'feedback' is NOT the current status
      if ($end_id > $start_id) {
        // REM:
        // the problem is that if the user changes status and assigned_to at the same
        // time, the 'assigned to' action is logged before the 'change status'.
        //   => the latest 'assigned to' action belongs to the future 'change status' action.
               
        // so if the next action is a 'change status' and the date is the same than the 'assigned to'
        // action, THEN we must take the previous 'assigned to' action in the list.
               
        // Get the next action to check if it is a 'change status'
        $query3 = "SELECT id, date_modified, field_name FROM `mantis_bug_history_table` WHERE bug_id=$this->bugId AND id > '$latest_assignTo_id' ORDER BY id ASC";
        $result3 = mysql_query($query3) or die("Query failed: $query3");
        $row3 = mysql_fetch_object($result3);
               
        $next_action_date  = $row3->date_modified;
        $next_action_field = $row3->field_name;

        //echo "next action id = $row3->id &nbsp;&nbsp;&nbsp;&nbsp; date=$next_action_date field=$next_action_field<br/>";
               
        if (($next_action_date == $latest_assignTo_date) && ($next_action_field == "status")) {
          // we want the previous assigned_to (note: the $result2 is order by DESC)
          //echo "we want previous assign<br/>";
          $handler_id = $row2->old_value;

          //$row2 = mysql_fetch_object($result2);
          //echo "previous assign id = $row2->id &nbsp;&nbsp;&nbsp;&nbsp; date=$row2->date_modified &nbsp;&nbsp;&nbsp;&nbsp; handler_id=$handler_id <br/>";
        }
      }
      $user1 = new User($handler_id);
      if ($user1->isTeamDeveloper($FDJ_teamid)) {
        //echo "user $handler_id is FDJ (team $FDJ_teamid)<br/>";
        $time_fdj = $time_fdj + $intervale;
      } else {
        //echo "user $handler_id is ATOS<br/>";
        $time_atos = $time_atos + $intervale;
      }
    }

    $formatedDateList = array();
    $formatedDateList[$status_feedback_ATOS] = $time_atos;
    $formatedDateList[$status_feedback_FDJ]  = $time_fdj;
    
    return $formatedDateList;
    
  }  // getDuration_feedback

}

?>

