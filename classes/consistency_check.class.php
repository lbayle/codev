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

class ConsistencyError {

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

   public $bugId;
   public $userId;
   public $teamId;
   public $desc;
   public $timestamp;
   public $status;

   public $severity; // unused

   public function ConsistencyError($bugId, $userId, $status, $timestamp, $desc) {
      $this->bugId = $bugId;
      $this->userId = $userId;
      $this->status = $status;
      $this->timestamp = $timestamp;
      $this->desc = $desc;
   }

   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $activityB
    *
    * @param ConsistencyError $cerrB the object to compare to
    * @return bool
    */
   function compareTo($cerrB) {
      // the oldest activity should be in front of the list
      if ($this->bugId > $cerrB->bugId) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("activity.compareTo FALSE (".$this->bugId." >  ".$cerrB->bugId.")");
         }
         return false;
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("activity.compareTo TRUE  (".$this->bugId." <= ".$cerrB->bugId.")");
         }
         return true;
      }
   }

}

ConsistencyError::staticInit();

class ConsistencyCheck {

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

   var $projectList;

   public function __construct($projectList = NULL) {
      if (NULL != $projectList) {
         $this->projectList = $projectList;
      } else {
         $this->projectList = array();
      }
   }

   /**
    * perform all consistency checks
    * @return ConsistencyError[]
    */
   public function check() {
      #self::$logger->debug("checkResolved");
      $cerrList2 = $this->checkResolved();

      #$cerrList3 = $this->checkDeliveryDate();

      #self::$logger->debug("checkBadBacklog");
      $cerrList4 = $this->checkBadBacklog();

      #self::$logger->debug("checkMgrEffortEstim");
      $cerrList5 = $this->checkMgrEffortEstim();

      #self::$logger->debug("checkTimeTracksOnNewIssues");
      $cerrList6 = $this->checkTimeTracksOnNewIssues();

      #self::$logger->debug("done.");

      #$cerrList = array_merge($cerrList2, $cerrList3, $cerrList4, $cerrList5);
      $cerrList = array_merge($cerrList2, $cerrList4, $cerrList5, $cerrList6);

      Tools::usort($cerrList);

      return $cerrList;
   }

   /**
    * if $deliveryIssueCustomField is specified, then $deliveryDateCustomField should also be specified.
    * @return ConsistencyError[]
    */
   public function checkDeliveryDate() {
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT * ".
         "FROM `{bug}` ".
         "WHERE status >= get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .="ORDER BY last_updated DESC, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         if ((NULL != $issue->getDeliveryId()) &&
            (NULL == $issue->getDeliveryDate())) {
            $cerr = new ConsistencyError($row->id,
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

   /**
    * fiches resolved dont le RAE != 0
    * @return ConsistencyError[]
    */
   public function checkResolved() {
      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT * ".
               "FROM `{bug}` ".
               "WHERE status >= get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .= "ORDER BY last_updated DESC, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         if (0 != $issue->getBacklog()) {
            $cerr = new ConsistencyError($row->id,
               $row->handler_id,
               $row->status,
               $row->last_updated,
               T_("Backlog should be 0 (not ".$issue->getBacklog().")."));
            $cerr->severity = T_("Error");

            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

   /**
    * fiches NOT resolved with RAE == 0
    * @return ConsistencyError[]
    */
   public function checkBadBacklog() {
      $min_backlog = 0;

      $cerrList = array();

      // select all issues which current status is 'analyzed'
      $query = "SELECT * FROM `{bug}` ".
               #"WHERE status NOT IN (".Constants::$status_new.", ".Constants::$status_acknowledged.") ".
               "WHERE status NOT IN (".Constants::$status_new.") ".
               "AND status < get_project_resolved_status_threshold(project_id) ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .= "ORDER BY last_updated DESC, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         if ($issue->getBacklog() <= $min_backlog) {
            $cerr = new ConsistencyError($row->id,
               $row->handler_id,
               $row->status,
               $row->last_updated,
               T_("Backlog == 0: Backlog may not be up to date."));
            $cerr->severity = T_("Warning");

            $cerrList[] = $cerr;
         }
      }

      return $cerrList;

   }

   /**
    * a mgrEffortEstim should be defined when creating an Issue
    * @return ConsistencyError[]
    */
   public function checkMgrEffortEstim() {
      $cerrList = array();

      // select all issues
      $query = "SELECT * ".
               "FROM `{bug}` ".
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

      $query .= "ORDER BY last_updated DESC, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         // check if fields correctly set
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         if ((NULL   == $issue->getMgrEffortEstim()) ||
            ('' == $issue->getMgrEffortEstim())     ||
            ('0' == $issue->getMgrEffortEstim())) {

            $cerr = new ConsistencyError($row->id,
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
    * @return ConsistencyError[]
    */
   function checkTimeTracksOnNewIssues() {
      $cerrList = array();

      // select all issues which current status is 'new'
      $query = "SELECT * ".
               "FROM `{bug}` ".
               "WHERE status = ".Constants::$status_new." ";

      if (0 != count($this->projectList)) {
         $formatedProjects = implode( ', ', array_keys($this->projectList));
         $query .= "AND project_id IN ($formatedProjects) ";
      }

      $query .= "ORDER BY handler_id, bug_id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $elapsed = $issue->getElapsed();

         if (0 != $elapsed) {

            // error
            $cerr = new ConsistencyError($row->id,
               $row->handler_id,
               $row->status,
               $row->last_updated,
               T_("Status should not be")." '".Constants::$statusNames[Constants::$status_new]."' (".T_("elapsed")." = ".$elapsed.")");
            $cerr->severity = T_("Error");

            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

}

ConsistencyCheck::staticInit();

?>
