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

include_once "constants.php";
//include_once "tools.php";

include_once "issue.class.php";
include_once "user.class.php";
include_once "project.class.php";

class ConsistencyError {

   private $logger;

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

   protected $logger;

	var $projectList;

   // ----------------------------------------------
   public function __construct($projectList = NULL) {
      $this->logger = Logger::getLogger(__CLASS__);

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

      $cerrList2 = $this->checkResolved();
      #$cerrList3 = $this->checkDeliveryDate();
      $cerrList4 = $this->checkBadRemaining();
      $cerrList5 = $this->checkMgrEffortEstim();
      $cerrList6 = $this->checkTimeTracksOnNewIssues();
      #$cerrList = array_merge($cerrList2, $cerrList3, $cerrList4, $cerrList5);
      $cerrList = array_merge($cerrList2, $cerrList4, $cerrList5, $cerrList6);
      return $cerrList;
   }


   // ----------------------------------------------
   /**
    * if $deliveryIssueCustomField is specified, then $deliveryDateCustomField should also be specified.
    */
   public function checkDeliveryDate() {

      $deliveryIdCustomField     = Config::getInstance()->getValue(Config::id_customField_deliveryId);
      $deliveryDateCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
        "FROM `mantis_bug_table` ".
        "WHERE status >= get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
      	$formatedProjects = implode( ', ', array_keys($this->projectList));
      	$query .= "AND project_id IN ($formatedProjects) ";
      }

       $query .="ORDER BY last_updated DESC, bug_id DESC";

       $result = mysql_query($query);
	    if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
       }
       while($row = mysql_fetch_object($result))
      {
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if ((NULL != $issue->deliveryId) &&
         	 (NULL == $issue->deliveryDate)) {
               $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Delivery date not specified: If a delivery sheet is specified, then a Delivery Date is requested."));
               $cerr->severity = T_("Error");
               $cerrList[] = $cerr;
         	 }
      }
      return $cerrList;

   }


   // ----------------------------------------------
   /**
    * fiches resolved dont le RAE != 0
    */
   public function checkResolved() {

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
               "FROM `mantis_bug_table` ".
               "WHERE status >= get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
      	$issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if (0 != $issue->remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining should be 0 (not $issue->remaining)."));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
  	}



   // ----------------------------------------------
   /**
    * fiches NOT resolved with RAE == 0
    */
  	public function checkBadRemaining() {
      global $status_new;
      global $status_acknowledged;

      $min_remaining = 0;

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
               "FROM `mantis_bug_table` ".
               "WHERE status NOT IN ($status_new, $status_acknowledged) ".
               "AND status < get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

  	   $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

         if ($issue->remaining <= $min_remaining) {
           $cerr = new ConsistencyError($row->bug_id,
                                              $row->handler_id,
                                              $row->status,
                                              $row->last_updated,
                                              T_("Remaining == 0: Remaining may not be up to date."));
            $cerr->severity = T_("Warning");
            $cerrList[] = $cerr;
         }
      }

      return $cerrList;

   }

   // ----------------------------------------------
   /**
    * a mgrEffortEstim should be defined when creating an Issue
    */
   public function checkMgrEffortEstim() {

   	$cerrList = array();

   	// select all issues
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
               "FROM `mantis_bug_table` ".
               "WHERE status < get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {

      	// --- except SideTasksProjects (they don't have a MgrEffortEstim field)
      	$prjListNoSideTasks = $this->projectList; // copy
         foreach ($prjListNoSideTasks as $id => $name) {
         	$p = ProjectCache::getInstance()->getProject($id);
         	if (true == $p->isSideTasksProject()) {
               unset($prjListNoSideTasks[$id]);
         	}
         }

         if (0 != count($prjListNoSideTasks)) {
             $formatedProjects = implode( ', ', array_keys($prjListNoSideTasks));
             $query .= "AND project_id IN ($formatedProjects) ";
         }
      } else {
      	// TODO except SideTasksProjects
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);

	         if ((NULL   == $issue->mgrEffortEstim) ||
	             ('' == $issue->mgrEffortEstim)     ||
	             ('0' == $issue->mgrEffortEstim)) {

	           $cerr = new ConsistencyError($row->bug_id,
	                                              $row->handler_id,
	                                              $row->status,
	                                              $row->last_updated,
	                                              T_("MgrEffortEstim not set."));
	            $cerr->severity = T_("Error");
	            $cerrList[] = $cerr;
	         }

      }



      return $cerrList;

   }

   /**
    * if you spend some time on a task, 
    * then it's status is probably 'ack' or 'open' but certainly not 'new'
    */
   function checkTimeTracksOnNewIssues() {

    global $status_new;
    global $statusNames;
    
    $cerrList = array();

    // select all issues which current status is 'new'
      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
               "FROM `mantis_bug_table` ".
               "WHERE status = $status_new ";

      if (0 != count($this->projectList)) {
        $formatedProjects = implode( ', ', array_keys($this->projectList));
        $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY handler_id, bug_id DESC";

       $result = mysql_query($query);
       if (!$result) {
              $this->logger->error("Query FAILED: $query");
              $this->logger->error(mysql_error());
              echo "<span style='color:red'>ERROR: Query FAILED</span>";
              exit;
      }
      while($row = mysql_fetch_object($result))
      {
        $issue = IssueCache::getInstance()->getIssue($row->bug_id);
        $elapsed = $issue->getElapsed();

        if (0 != $elapsed) {

        	// error
            $cerr = new ConsistencyError($row->bug_id,
                                                  $row->handler_id,
                                                  $row->status,
                                                  $row->last_updated,
                                                  T_("Status should not be")." '".$statusNames[$status_new]."' (".T_("elapsed")." = ".$elapsed.")");
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
        }
      }      

      return $cerrList;
   }


}


?>
