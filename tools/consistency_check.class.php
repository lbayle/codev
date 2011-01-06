<?php


//include_once "constants.php";
//include_once "tools.php";
include_once "../reports/issue.class.php";
include_once "../auth/user.class.php";

class ConsistencyError {
	
	var $bugId;
	var $userId;
	var $teamId;
	var $desc;
   var $timestamp;
   var $status;
   
	public function ConsistencyError($bugId, $userId, $status, $timestamp, $desc) {
		$this->bugId     = $bugId;
      $this->userId    = $userId;
      $this->status = $status;
      $this->timestamp = $timestamp;
      $this->desc      = $desc;
	}
}


class ConsistencyCheck {
  
   // ----------------------------------------------
   public function ConsistencyCheck() {
   }
  
   // ----------------------------------------------
   // fiches analyzed dont BI non renseignes
   // fiches analyzed dont RAE non renseignes
   public function checkAnalyzed() {
   	
   	global $status_analyzed;
      global $status_accepted;
      global $status_openned;
      global $status_deferred;
      global $status_resolved;
      global $status_delivered;
      global $status_closed;
   	global $FDJ_teamid;
   	
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_analyzed, $status_accepted, $status_openned, $status_deferred) ".
        "ORDER BY bug_id DESC";
      
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	$issue = new Issue($row->bug_id);
      	
         if (NULL == $issue->effortEstim) {
           $cerrList[] = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status,
                                              $row->last_updated, 
                                              "BI not set !");
         }
      	if (NULL == $issue->remaining) {
           $cerrList[] = new ConsistencyError($row->bug_id, 
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated, 
                                              "Remaining not set !");
         }
         if ($status_analyzed == $row->status) {
             $user = new User($row->handler_id);
             if (! $user->isTeamMember($FDJ_teamid)) {
              $cerrList[] = new ConsistencyError($row->bug_id, 
                                                 $row->handler_id,
                                                 $row->status,
                                                 $row->last_updated, 
                                                 "Should be assigned to FDJ");
             }
         	
         }
      }
      
      
      // check if fields correctly set
      
      return $cerrList;
   }
   
   // ----------------------------------------------
   // fiches resolved dont le RAE != 0
   public function checkResolved() {
      
   	global $statusNames;
   	global $status_resolved;
      global $status_delivered;
      global $status_closed;
      
      
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_resolved, $status_delivered, $status_closed) ".
        "ORDER BY last_updated DESC, bug_id DESC";
      
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
      	$issue = new Issue($row->bug_id);
         
         if (0 != $issue->remaining) {
           $cerrList[] = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status, 
                                              $row->last_updated, 
                                              "Remaining = $issue->remaining &nbsp;&nbsp; (should be 0)");
         }
      }
      
      
      
      return $cerrList;
  	}
  
}


?>