<?php


//include_once "constants.php";
//include_once "tools.php";
include_once "issue.class.php";
include_once "user.class.php";

class ConsistencyError {
	
	var $bugId;
	var $userId;
	var $teamId;
	var $desc;
   var $timestamp;
   var $status;
   
   var $severity; // unused
   
	public function ConsistencyError($bugId, $userId, $status, $timestamp, $desc) {
		$this->bugId     = $bugId;
      $this->userId    = $userId;
      $this->status = $status;
      $this->timestamp = $timestamp;
      $this->desc      = $desc;
	}
}


class ConsistencyCheck {
   
	var $projectList;
   
   // ----------------------------------------------
   public function ConsistencyCheck($projectList = NULL) {
   	if (NULL != $projectList) { 
   		$this->projectList = $projectList; 
   	} else {
   		 $projectList = array();
   	}
   }
  
   // ----------------------------------------------
   /**
    * perform all consistency checks
    */
   public function check() {
      
      $cerrList1 = $this->checkAnalyzed();
      $cerrList2 = $this->checkResolved();
      $cerrList3 = $this->checkDeliveryDate();
      $cerrList4 = $this->checkBadRAE();
      $cerrList5 = $this->checkETA();
      $cerrList = array_merge($cerrList1, $cerrList2, $cerrList3, $cerrList4, $cerrList5);
      return $cerrList;
   }
   
   
   // ----------------------------------------------
   /**
    * if $deliveryIssueCustomField is specified, then $deliveryDateCustomField should also be specified.
    */
   public function checkDeliveryDate() {
      global $status_resolved;
      global $status_delivered;
      global $status_closed;
   	
      global $deliveryIdCustomField; // in mantis_custom_field_table 'FDL'
      global $deliveryDateCustomField; // in mantis_custom_field_table  'Liv. Date'
      
      $cerrList = array();
      
      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_resolved, $status_delivered, $status_closed) ";
      
      if (0 != count($this->projectList)) {
      	$formatedProjects = valuedListToSQLFormatedString($this->projectList);
      	$query .= "AND project_id IN ($formatedProjects) ";
      }
      
       $query .="ORDER BY last_updated DESC, bug_id DESC";
      
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $issue = new Issue($row->bug_id);
         
         if ((NULL != $issue->deliveryId) &&  
         	 (NULL == $issue->deliveryDate)) {
               $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status,
                                              $row->last_updated, 
                                              "Date de livraison non renseign&eacute;: si une fiche de livraison est sp&eacute;cifi&eacute;e, alors une date doit y &ecirc;tre associ&eacute;e");
               $cerr->severity = "Error";                                  
               $cerrList[] = $cerr;                                              
         	 }
      }
      return $cerrList;
      
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
        "WHERE status in ($status_analyzed, $status_accepted, $status_openned, $status_deferred) ";
      
      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }
      
      $query .="ORDER BY last_updated DESC, bug_id DESC";
            
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	$issue = new Issue($row->bug_id);
      	
         if (NULL == $issue->effortEstim) {
           $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status,
                                              $row->last_updated, 
                                              "BI non renseign&eacute;: il doit &ecirc;tre &eacute;gal &agrave; temps(Analyse + Dev + Tests)");
            $cerr->severity = "Error";                                  
            $cerrList[] = $cerr;                                              
         }
      	if (NULL == $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated, 
                                              "RAE non renseign&eacute;: il doit &ecirc;tre &eacute;gal &agrave; temps(BI - Analyse)");
            $cerr->severity = "Error";                                  
            $cerrList[] = $cerr;                                              
      	}
         if ($status_analyzed == $row->status) {
             $user = new User($row->handler_id);
             if (! $user->isTeamMember($FDJ_teamid)) {
              $cerr = new ConsistencyError($row->bug_id, 
                                                 $row->handler_id,
                                                 $row->status,
                                                 $row->last_updated, 
                                                 "Apr&egrave;s l'analyse, une fiche doit &ecirc;tre assign&eacute;e &agrave; 'FDJ' pour validation");
               $cerr->severity = "Error";                                  
               $cerrList[] = $cerr;                                              
             }
         	
         }
      }
      
      
      // check if fields correctly set
      
      return $cerrList;
   }
   
   // ----------------------------------------------
   /**
    * fiches resolved dont le RAE != 0
    */ 
   public function checkResolved() {
      
   	global $statusNames;
   	global $status_resolved;
      global $status_delivered;
      global $status_closed;
      
      
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status in ($status_resolved, $status_delivered, $status_closed) ";
      
      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }
      
      $query .="ORDER BY last_updated DESC, bug_id DESC";
            
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
      	$issue = new Issue($row->bug_id);
         
         if (0 != $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status, 
                                              $row->last_updated, 
                                              "Le RAE devrait &ecirc;tre = 0  (et non ".$issue->remaining.")");
            $cerr->severity = "Error";                                  
            $cerrList[] = $cerr;                                              
         }
      }
      
      
      
      return $cerrList;
  	}

  	
  	
   // ----------------------------------------------
   /**
    * fiches NOT resolved with RAE == 0
    */
  	public function checkBadRAE() {
      global $status_new;
      global $status_ack;
  		global $statusNames;
      global $status_resolved;
      global $status_delivered;
      global $status_closed;
      $min_remaining = 0;
      
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status NOT IN ($status_new, $status_ack, $status_resolved, $status_delivered, $status_closed) ";
      
      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "AND project_id IN ($formatedProjects) ";
      }
      
      $query .="ORDER BY last_updated DESC, bug_id DESC";
            
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = new Issue($row->bug_id);
         
         if ($issue->remaining <= $min_remaining) {
           $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status, 
                                              $row->last_updated, 
                                              "RAE == 0: Il se peut que le RAE ne soit pas &agrave; jour.");
            $cerr->severity = "Warning";                                  
            $cerrList[] = $cerr;                                              
         }
      }
      
      
      
      return $cerrList;
  		
   }
   
   // ----------------------------------------------
   /**
    * an ETA should be defined when creating an Issue
    */
   public function checkETA() {
      
   	$cerrList = array();
   	
   	// select all issues
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ";
      
      if (0 != count($this->projectList)) {
         $formatedProjects = valuedListToSQLFormatedString($this->projectList);
         $query .= "WHERE project_id IN ($formatedProjects) ";
      }
      
      $query .="ORDER BY last_updated DESC, bug_id DESC";
            
      $result    = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = new Issue($row->bug_id);
         
         if ( (NULL == $issue->eta) || (0 == $issue->eta)) {
           $cerr = new ConsistencyError($row->bug_id, 
                                              $row->handler_id, 
                                              $row->status, 
                                              $row->last_updated, 
                                              "ETA not set.");
            $cerr->severity = "Error";                                  
            $cerrList[] = $cerr;                                              
         }
      }
      
      
      
      return $cerrList;
   	
   }
   
}


?>