<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php

// -- COMPUTE DURATIONS --
// Status & Issue classes

class Status {
   var $statusId; // new=10, ack=30, ...
   var $duration; // in sec since 1970 (unix timestamp)

   function Status($s, $d) {
      $this->statusId = $s;
      $this->duration = $d;
   }
}

class Issue {
   var $bugId;      // mantis id
   var $tcId;       // TelelogicChange id
   var $projectId;  // Capu, peterpan, etc.
   var $categoryId;
   var $eta;
   var $summary;
   var $difficulty;
   var $dateSubmission;
   var $remaining;
   var $elapsed;
   var $EffortEstim;
   var $currentStatus;
   var $release;

   var $statusList; // array of statusInfo elements

   // ----------------------------------------------
   public function Issue ($id) {
      $this->bugId = $id;
      $this->initialize();
   }

   // ----------------------------------------------
   public function initialize() {
      // Get issue info
      $query = "SELECT id, summary, status, date_submitted, project_id, category_id, eta ".
      "FROM `mantis_bug_table` ".
      "WHERE id = $this->bugId";
      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);

      $this->summary         = $row->summary;
      $this->currentStatus   = $row->status;
      $this->dateSubmission  = $row->date_submitted;
      $this->projectId       = $row->project_id;
      $this->categoryId      = $row->category_id;
      $this->eta             = $row->eta;

      // Get custom fields
      $query2 = "SELECT field_id, value FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId";
      $result2 = mysql_query($query2) or die("Query failed: $query2");
      while($row = mysql_fetch_object($result2))
      {
         switch ($row->field_id) {
            case 1: $this->tcId        = $row->value; break;
            case 2: $this->release     = $row->value; break;
            case 3: $this->EffortEstim = $row->value; break;
            case 4: $this->remaining   = $row->value; break;
            case 6: $this->difficulty  = $row->value; break;
         }
      }

      $this->tcId    = $this->getTC();

      $this->elapsed = $this->getElapsed();

      // Prepare fields
      $this->statusList = array();
   }

   // ----------------------------------------------
   // Ex: vacation or Incident tasks are not production issues.
   //     but tools and doc are production issues.
   public function isSideTaskIssue() {
      global $docCategory;
      global $toolsCategory;
      global $workingProjectType;
      global $sideTaskProjectType;
      
      $query  = "SELECT type FROM `codev_team_project_table` WHERE project_id = $this->projectId";
      $result = mysql_query($query) or die("Query failed: $query");
      $type    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : $workingProjectType;

      if (($sideTaskProjectType == $type) &&
      ($docCategory != $this->categoryId) &&
      ($toolsCategory != $this->categoryId)) {

         //echo "DEBUG $this->bugId is a sideTask.   type=$type<br/>";
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function isVacation() {
      global $vacationCategory;
      global $vacationProject;

      if (($this->projectId == $vacationProject) &&
      ($this->categoryId == $vacationCategory)) {
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function getTC() {
      $query  = "SELECT value FROM `mantis_custom_field_string_table` WHERE field_id='1' AND bug_id=$this->bugId";
      $result = mysql_query($query) or die("Query failed: $query");

      $tcId    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "";

      return $tcId;
   }

   // ----------------------------------------------
   public function getProjectName() {
      $query = "SELECT name FROM `mantis_project_table` WHERE id= $this->projectId";
      $result = mysql_query($query) or die("Query failed: $query");
      $projectName = mysql_result($result, 0);

      return $projectName;
   }

   // ----------------------------------------------
   public function getCategoryName() {
      $query = "SELECT name FROM `mantis_category_table` WHERE id= $this->categoryId";
      $result = mysql_query($query) or die("Query failed: $query");
      $categoryName = mysql_result($result, 0);

      return $categoryName;
   }

   // ----------------------------------------------
   public function getCurrentStatusName() {
      global $statusNames;

      return $statusNames[$this->currentStatus];
   }

   // ----------------------------------------------
   public function getEtaName() {
      global $ETA_names;

      return $ETA_names[$this->eta];
   }

   // ----------------------------------------------
   // Get elapsed from TimeTracking
   public function getElapsed() {
      $elapsed = 0;

      $query     = "SELECT duration FROM `codev_timetracking_table` WHERE bugid=$this->bugId";
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $elapsed += $row->duration;
      }

      return $elapsed;
   }

   // ----------------------------------------------
   // Returns how many time has already been spent on this task
   // REM: if no category specified, then all category.
   public function getElapsed2($startTimestamp, $endTimestamp, $job_id = NULL) {
      $elapsed = 0;

      $query     = "SELECT duration FROM `codev_timetracking_table` WHERE bugid=$this->bugId ".
      "AND  date >= $this->startTimestamp AND date < $this->endTimestamp ";

      if (isset($job_id)) {
         $query .= " AND jobid = $job_id";
      }
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $elapsed += $row->duration;
      }

      return $elapsed;
   }

   // ----------------------------------------------
   // returns an HTML color string "#ff6a6e;" depending on pos/neg drift and current status.
   // returns NULL if $drift=0
   // REM: if $drift is not specified, then $this->drift is used ()
   public function getDriftColor($drift = NULL) {
    global $status_delivered;
    global $status_resolved;
    global $status_closed;
   	
     if (!isset($drift)) {
     	   $drift = $this->getDrift();
     }
    
        if (0 < $drift) {
            if (($status_resolved  != $this->currentStatus) && 
                ($status_delivered != $this->currentStatus) &&
                ($status_closed    != $this->currentStatus)) {
              $color = "#ff6a6e;";
            } else {
              $color = "#fcbdbd;";
            }
        } elseif (0 > $drift) {
          if (($status_resolved != $this->currentStatus) && 
              ($status_delivered != $this->currentStatus) &&
              ($status_closed   != $this->currentStatus)) {
              $color = "#61ed66;";
              } else {
              $color = "#bdfcbd;";
              }
        } else {
          $color = NULL;
        }
     
   	return $color;
   }
   
   
   // ----------------------------------
   // if NEG, then we saved time
   // if 0, then just in time
   // if POS, then there is a drift !

   // elapsed - (EffortEstim - remaining)
   // if bug is Resolved/Closed, then remaining is not used.

   // REM if EffortEstim = 0 then Drift = 0
   public function getDrift() {
      global $status_resolved;
      global $status_closed;

      if (0 == $this->EffortEstim) { return 0; }

      if (($status_resolved == $this->currentStatus) || ($status_closed == $this->currentStatus)) {
         $derive = $this->elapsed - $this->EffortEstim;
      } else {
         $derive = $this->elapsed - ($this->EffortEstim - $this->remaining);
      }

      if (isset($_GET['debug'])) {echo "issue->getDrift(): bugid ".$this->bugId." ".$this->getCurrentStatusName()." derive=$derive (elapsed $this->elapsed - estim $this->EffortEstim)<br/>";}
      return $derive;
   }

   // ----------------------------------
   // if NEG, then we saved time
   // if 0, then just in time
   // if POS, then there is a drift !

   // elapsed - (ETA - remaining)
   // if bug is Resolved/Closed, then remaining is not used.
   
   // REM if ETA = 0 then Drift = 0
   public function getDriftETA() {
      global $status_resolved;
      global $status_closed;
      global $ETA_balance;

      #if (0 == $this->eta) { return 0; }

      if (($status_resolved == $this->currentStatus) || ($status_closed == $this->currentStatus)) {
         $derive = $this->elapsed - $ETA_balance[$this->eta];
      } else {
         $derive = $this->elapsed - ($ETA_balance[$this->eta] - $this->remaining);
      }

      if (isset($_GET['debug'])) {echo "issue->getDriftETA(): bugid ".$this->bugId." ".$this->getCurrentStatusName()." derive=$derive (elapsed $this->elapsed - estim ".$ETA_balance[$this->eta].")<br/>";}
      return $derive;
   }

   // ----------------------------------------------
   public function getTimeTracks($user_id = NULL) {
      $timeTracks = array();

      $query     = "SELECT id, date FROM `codev_timetracking_table` ".
      "WHERE bugid=$this->bugId ";

      if (isset($user_id)) {
         $query .= "AND userid = $user_id";
      }
      $query .= " ORDER BY date";

      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $timeTracks[$row->id] = $row->date;
      }

      return $timeTracks;
   }

   // ----------------------------------------------
   public function getInvolvedUsers($team_id = NULL) {
      $userList = array();

      $query = "SELECT mantis_user_table.id, mantis_user_table.username ".
      "FROM  `mantis_user_table`, `codev_timetracking_table`, `codev_team_user_table`  ".
      "WHERE  codev_timetracking_table.userid = mantis_user_table.id ".
      "AND    codev_timetracking_table.bugid  = $this->bugId ";

      if (isset($team_id)) {
         $query .= "AND codev_team_user_table.team_id = $team_id ".
        "AND codev_team_user_table.user_id = mantis_user_table.id ";
      }

      $query .= " ORDER BY mantis_user_table.username";
         
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $userList[$row->id] = $row->username;
      }

      return $userList;
   }

   // ----------------------------------------------
   // returns statusId at date < $timestamp or current status if $timestamp = NULL
   public function getStatus($timestamp = NULL) {

      if (NULL == $timestamp) {
         $query = "SELECT status FROM `mantis_bug_table` WHERE id = $this->bugId";
         $result = mysql_query($query) or die("Query failed: $query");
         $row = mysql_fetch_object($result);
         $this->currentStatus   = $row->status;

         if (isset($_GET['debug'])) { echo "issue->getStatus(NULL) : bugId=$this->bugId, status=$this->currentStatus<br/>"; }
         return $this->currentStatus;
      }
         
      // if a timestamp is specified, find the latest status change (strictly) before this date
      $query = "SELECT new_value, old_value, date_modified ".
                "FROM `mantis_bug_history_table` ".
                "WHERE bug_id = $this->bugId ".
                "AND field_name='status' ".
                "AND date_modified < $timestamp ".
                "ORDER BY date_modified DESC";

      // get latest result
      $result = mysql_query($query) or die("Query failed: $query");
      if (0 != mysql_num_rows($result)) {
         $row = mysql_fetch_object($result);

         if (isset($_GET['debug'])) { echo "issue->getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId, old_value=$row->old_value, new_value=$row->new_value, date_modified=".date("d F Y", $row->date_modified)."<br/>";}

         return $row->new_value;
      } else {
         if (isset($_GET['debug'])) { echo "issue->getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId not found !<br/>"; }
         return -1;
      }
   }


   // ----------------------------------------------
   // updates DB with new value
   public function setRemaining($remaining) {
      $old_remaining = $this->remaining;

      //echo "DEBUG setRemaining old_value=$old_remaining   new_value=$remaining<br/>";

      // TODO: get field name from DB
      $field_name = "Remaining (RAE)";

      $query = "SELECT * FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId AND field_id = 4";
      $result = mysql_query($query) or die("Query failed: $query");
      if (0 != mysql_num_rows($result)) {

         $query2 = "UPDATE `mantis_custom_field_string_table` SET value = '$remaining' WHERE bug_id=$this->bugId AND field_id = 4";
      } else {
         $query2 = "INSERT INTO `mantis_custom_field_string_table` (`field_id`, `bug_id`, `value`) VALUES ('4', '$this->bugId', '$remaining');";
      }
      $result    = mysql_query($query2) or die("Query failed: $query2");
      $this->remaining = $remaining;

      // Add to history
      $query = "INSERT INTO `mantis_bug_history_table`  (`user_id`, `bug_id`, `field_name`, `old_value`, `new_value`, `type`, `date_modified`) ".
      "VALUES ('".$_SESSION['userid']."','$this->bugId','$field_name', '$old_remaining', '$remaining', '0', '".time()."');";
      mysql_query($query) or die("Query failed: $query");
   }

   // ----------------------------------------------
   // Computes the lifeCycle of the issue (time spent on each status)
   public function computeDurations () {
      global $status_new;
      global $status_feedback;
      global $status_ack;
      global $status_analyzed;
      global $status_accepted;
      global $status_openned;
      global $status_deferred;
      global $status_resolved;
      global $status_delivered;
      global $status_closed;

      $this->statusList[$status_new]      = new Status($status_new, $this->getDuration_new());
      $this->statusList[$status_feedback] = new Status($status_feedback_ATOS, $this->getDuration_other($status_feedback));
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

   // ----------------------------------------------
   protected function getDuration_new ()
   {
      $time = 0;

      global $status_new;

      $current_date = time();

      // If status = 'new',
      // -- the start_date is the bug creation date
      // -- the end_date   is transition where old_value = status or current_date if status unchanged.

      $query = "SELECT status, date_submitted FROM `mantis_bug_table` WHERE id=$this->bugId";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      { // REM: only one line in result, while should be optimized
         $current_status = $row->status;
         $date_submitted = $row->date_submitted;
         //echo "&nbsp;&nbsp; start_date = $date_submitted (date_submitted)<br/>";
      }

      // If status has not changed, then end_date is now.
      if ($status_new == $current_status) {
         //echo "bug still in 'new' state<br/>";
         $time = $current_date - $date_submitted;
      } else {
         // Bug has changed, search history for status changed
         $query = "SELECT date_modified FROM `mantis_bug_history_table` WHERE bug_id=$this->bugId AND field_name = 'status' AND old_value='$status_new'";
         $result = mysql_query($query) or die("Query failed: $query");
         while($row = mysql_fetch_object($result))
         { // REM: only one line in result, while should be optimized
            $date_modified = $row->date_modified;
            //echo "&nbsp;&nbsp; end_date = $date_modified <br/>";
         }

         $time= $date_modified - $date_submitted;
      }

      //echo "duration new $time<br/>";
      return $time;
   }

   // ----------------------------------------------
   protected function getDuration_other ($status)
   {
      $time = 0;

      global $status_new;
      global $status_feedback;
      global $status_ack;
      global $status_analyzed;
      global $status_accepted;
      global $status_openned;
      global $status_deferred;
      global $status_resolved;
      global $status_delivered;
      global $status_closed;

      $current_date = time();

      // Status is not 'new' and not 'feedback'
      //  -- the start_date is transition where new_value = status
      //  -- the end_date   is transition where old_value = status, or current date if no transition found.

      // Find start_date
      $query = "SELECT id, date_modified, old_value, new_value ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND (new_value=$status OR old_value=$status) ORDER BY id";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         //echo "&nbsp;&nbsp; id=$row->id &nbsp;&nbsp;date = $row->date_modified  &nbsp;&nbsp;old_value = $row->old_value  &nbsp;&nbsp;new_value = $row->new_value<br/>";
         $start_date = $row->date_modified;

         // Next line is end_date. if NULL then end_date = current_date
         if ($row = mysql_fetch_object($result)) {
            $end_date = $row->date_modified;
            //echo "&nbsp;&nbsp; id=$row->id &nbsp;&nbsp;date = $row->date_modified  &nbsp;&nbsp;old_value = $row->old_value  &nbsp;&nbsp;new_value = $row->new_value<br/>";
         } else {
            $end_date = $current_date;
            //echo "end_date = current date = $end_date<br/>";
         }
         $intervale =  $end_date - $start_date;
         //echo "&nbsp;&nbsp; intervale = $intervale<br/>";
         $time = $time + ($end_date - $start_date);
      }

      //echo "duration other $time<br/>";
      return $time;
   } // getDuration_other

} // class issue

?>