<?php /*
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
*/ ?>

<?php

include_once "project.class.php";
include_once "issue_cache.class.php";

// -- COMPUTE DURATIONS --
// Status & Issue classes

// ==============================================================
class Status {

   var $statusId; // new=10, ack=30, ...
   var $duration; // in sec since 1970 (unix timestamp)

   function Status($s, $d) {
      $this->statusId = $s;
      $this->duration = $d;
   }
}


// ==============================================================
class Issue {

   private $logger;

   public $bugId;      // mantis id
   public $projectId;  // Capu, peterpan, etc.
   public $categoryId;
   public $eta;        // DEPRECATED
   public $summary;
   public $dateSubmission;
   public $currentStatus;
   public $priority;
   public $severity;
   public $handlerId;
   public $resolution;
   public $version;  // Product Version
   private $target_version;

   private $relationships; // array[relationshipType][bugId]
	/*
	 * REM:
	 * previous versions of CoDev used the mantis ETA field
	 * to store the 'preliminary Effort Estimation'.
	 * as ETA may already been used by existing projects for other purpose,
	 * a 'prelEffortEstim' customField has been created to replace ETA.
	 * REM2:
	 * Feb.2012 'prelEffortEstim' has been replaced by 'mgrEffortEstim'
	 */

   // -- CodevTT custom fields
   public $tcId;         // TelelogicChange id
   public $remaining;    // RAF
   public $mgrEffortEstim;  // Manager EffortEstim (ex prelEffortEstim/ETA)
   public $effortEstim;  // BI
   public $effortAdd;    // BS
   private $deadLine;
   public $deliveryDate;
   public $deliveryId;   // TODO FDL (FDJ specific)

   // -- computed fields
   public $elapsed;          // total time spent on this issue
   public $statusList;       // array of statusInfo elements

   // -- PRIVATE cached fields
   private $holidays;

   // other cache fields
   public $bug_resolved_status_threshold;

   // ----------------------------------------------
   public function Issue ($id) {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->bugId = $id;

      $this->initialize();
   }

   // ----------------------------------------------
   public function initialize() {

   	global $tcCustomField;
   	global $estimEffortCustomField;
   	global $remainingCustomField;
   	global $addEffortCustomField;
   	global $deadLineCustomField;
   	global $deliveryDateCustomField;
      global $deliveryIdCustomField;
      $mgrEstimEffortCustomField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);

      // Get issue info
      $query = "SELECT * ".
      "FROM `mantis_bug_table` ".
      "WHERE id = $this->bugId";
      $result = mysql_query($query);
       if (!$result) {
              $this->logger->error("Query FAILED: $query");
              $this->logger->error(mysql_error());
              echo "<span style='color:red'>ERROR: Query FAILED</span>";
              exit;
      }
      $row = mysql_fetch_object($result);

      $this->summary         = $row->summary;
      $this->currentStatus   = $row->status;
      $this->dateSubmission  = $row->date_submitted;
      $this->projectId       = $row->project_id;
      $this->categoryId      = $row->category_id;
      $this->eta             = $row->eta; // DEPRECATED
      $this->priority        = $row->priority;
      $this->severity        = $row->severity;
      $this->handlerId       = $row->handler_id;
      $this->resolution      = $row->resolution;
      $this->version         = $row->version;
      $this->target_version  = $row->target_version;

      // Get custom fields
      $query2 = "SELECT field_id, value FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId";
      $result2 = mysql_query($query2);
	   if (!$result2) {
    	      $this->logger->error("Query FAILED: $query2");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
            while($row = mysql_fetch_object($result2))
      {
         switch ($row->field_id) {
            case $tcCustomField:              $this->tcId            = $row->value; break;
            case $mgrEstimEffortCustomField:  $this->mgrEffortEstim  = $row->value; break;
            case $estimEffortCustomField:     $this->effortEstim     = $row->value; break;
            case $remainingCustomField:       $this->remaining       = $row->value; break;
            case $addEffortCustomField:       $this->effortAdd       = $row->value; break;
            case $deadLineCustomField:        $this->deadLine        = $row->value; break;
            case $deliveryDateCustomField:    $this->deliveryDate    = $row->value; break;
            case $deliveryIdCustomField:      $this->deliveryId      = $row->value; break;
         }
      }

      $this->tcId    = $this->getTC();

      $this->elapsed = $this->getElapsed();

      $this->deadLine = $this->getDeadLine(); // if customField NOT found, get target_version date

      $project = ProjectCache::getInstance()->getProject($this->projectId);
      $this->bug_resolved_status_threshold = $project->getBugResolvedStatusThreshold();

      // Prepare fields
      $this->statusList = array();
      $this->relationships = array();

      //DEBUG $this->getRelationships(2500);
   }


   /**
    * returns a Holidays class instance
    */
   private function getHolidays() {
   	if (NULL == $this->holidays) { $this->holidays = Holidays::getInstance(); }
   	return $this->holidays;
   }

   // ----------------------------------------------
   // Ex: vacation or Incident tasks are not production issues.
   //     but tools and workshop are production issues.
   public function isSideTaskIssue() {

      $project = ProjectCache::getInstance()->getProject($this->projectId);

      if (($project->isSideTasksProject()) &&
          ($project->getToolsCategoryId() != $this->categoryId) &&
          ($project->getWorkshopCategoryId()   != $this->categoryId)) {

         $this->logger->debug("$this->bugId is a sideTask.   type=$type");
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function isVacation() {

      $project = ProjectCache::getInstance()->getProject($this->projectId);

      if (($project->isSideTasksProject()) &&
          ($project->getInactivityCategoryId() == $this->categoryId)) {

         $this->logger->debug("$this->bugId is Vacation.");
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function isIncident() {

      $project = ProjectCache::getInstance()->getProject($this->projectId);

      if (($project->isSideTasksProject()) &&
          ($project->getIncidentCategoryId() == $this->categoryId)) {

         $this->logger->debug("$this->bugId is a Incident.");
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function isProjManagement() {

      $project = ProjectCache::getInstance()->getProject($this->projectId);

      if (($project->isSideTasksProject()) &&
          ($project->getManagementCategoryId() == $this->categoryId)) {

         $this->logger->debug("$this->bugId is a ProjectManagement task.");
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function isAstreinte() {

   	global $astreintesTaskList;

      if (in_array($this->bugId, $astreintesTaskList)) {

         $this->logger->debug("$this->bugId is an Astreinte.");
         return true;
      }
      return false;
   }

   // ----------------------------------------------
   public function getTargetVersion() {

      return $this->target_version;
   }

   // ----------------------------------------------
   public function getTC() {

   	global $tcCustomField;

      $query  = "SELECT value FROM `mantis_custom_field_string_table` WHERE field_id='$tcCustomField' AND bug_id=$this->bugId";

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }

      $tcId    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "";

      return $tcId;
   }

   // ----------------------------------------------
   /**
    * Issue deadLine
    *
    * if deadLineCustomField is set, return this value,
    * else if TargetVersion date is specified return it,
    * else return NULL
    *
    */
   public function getDeadLine() {

   	// if exist return customField value
   	// REM: already set in initialize()
   	if (NULL != $this->deadLine) { return $this->deadLine; }

   	// check if
   	if (NULL != $this->target_version) {
   	   $query = "SELECT date_order FROM `mantis_project_version_table` ".
   	            "WHERE project_id=$this->projectId ".
   	            "AND version='$this->target_version'";
   	   $result = mysql_query($query);
	      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }
   	   $targetVersionDate = mysql_result($result, 0);

   	   $this->logger->debug("$this->bugId target_version date = ".date("Y-m-d", $targetVersionDate));
   	   return ($targetVersionDate <= 1) ? NULL : $targetVersionDate;
   	}

   	return NULL;
   }

   // ----------------------------------------------
   public function getProjectName() {

   	$project = ProjectCache::getInstance()->getProject($this->projectId);
   	return $project->name;

   	/*
      $query = "SELECT name FROM `mantis_project_table` WHERE id= $this->projectId";
      $result = mysql_query($query) or die("Query failed: $query");
      $projectName = mysql_result($result, 0);

      return $projectName;
      */
   }

   // ----------------------------------------------
   public function getCategoryName() {
      $query = "SELECT name FROM `mantis_category_table` WHERE id= $this->categoryId";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $categoryName = mysql_result($result, 0);

      return $categoryName;
   }

   // ----------------------------------------------
   public function getCurrentStatusName() {
      global $statusNames;

      return $statusNames[$this->currentStatus];
   }


   // ----------------------------------------------
   public function getPriorityName() {
      global $priorityNames;

      return $priorityNames[$this->priority];
   }

   // ----------------------------------------------
   public function getResolutionName() {
      global $resolutionNames;

      return $resolutionNames[$this->resolution];
   }


   // ----------------------------------------------
   /**
    * Get elapsed from TimeTracking
    * @param unknown_type $job_id   if no category specified, then all category.
    */
   public function getElapsed($job_id = NULL) {  // TODO $doRefresh = false

      $elapsed = 0;

      $query     = "SELECT duration FROM `codev_timetracking_table` WHERE bugid=$this->bugId";

      if (isset($job_id)) {
         $query .= " AND jobid = $job_id";
      }

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $elapsed += $row->duration;
      }

      return $elapsed;
   }

   /**
    * Returns the nb of days needed to finish the issue.
    * if the 'remaining' (RAF) field is not defined, return effortEstim or mgrEffortEstim
    */
   public function getRemaining() {
      // determinate issue duration (Remaining, BI, PrelEffortEstim)
      if       (NULL != $this->remaining)   { $issueDuration = $this->remaining; }
      elseif   (NULL != $this->effortEstim) { $issueDuration = $this->effortEstim; }
      else                                  { $issueDuration = $this->mgrEffortEstim; }
      return $issueDuration;
   }

   /**
    * TODO: NOT FINISHED, ADAPT TO ALL RELATIONSHIP TYPES
    *
    * get list of Relationships
    *
    * @param type = 2500 or 2501
    * @return array(issue_id);
    */
   public function getRelationships($type) {

      // TODO
      $complementaryType = (2500 == $type) ? 2501 : 2500;

   	  if (NULL == $this->relationships[$type]) {
         $this->relationships[$type] = array();

   	     // normal
         $query = "SELECT * FROM `mantis_bug_relationship_table` ".
                  "WHERE source_bug_id=$this->bugId ".
                  "AND relationship_type = $type";
   	   $result = mysql_query($query);
	      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }
         while($row = mysql_fetch_object($result))
         {
            $this->logger->debug("relationships: [$type] $this->bugId -> $row->destination_bug_id\n");
            $this->relationships[$type][] = $row->destination_bug_id;
         }
         // complementary
         $query = "SELECT * FROM `mantis_bug_relationship_table` ".
                  "WHERE destination_bug_id=$this->bugId ".
                  "AND relationship_type = ".$complementaryType;
   	   $result = mysql_query($query);
	      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }
         while($row = mysql_fetch_object($result))
         {
            $this->logger->debug("relationships: [$type] $this->bugId -> $row->source_bug_id\n");
            $this->relationships[$type][] = $row->source_bug_id;
         }
   	  }

      return $this->relationships[$type];
   }

   // ----------------------------------------------
   /**
    * returns the timestamp of the first TimeTrack
    */
   public function startDate() {

   	$query = "SELECT MIN(date) FROM `codev_timetracking_table` WHERE bugid=$this->bugId ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   	$startDate = mysql_result($result, 0);

   	return $startDate;
   }

   // ----------------------------------------------
   /**
    * returns the timestamp of the latest TimeTrack
    */
   public function endDate() {

   	$query = "SELECT MAX(date) FROM `codev_timetracking_table` WHERE bugid=$this->bugId ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   	$endDate = mysql_result($result, 0);
   	return $endDate;
   }

   // ----------------------------------------------
   // returns an HTML color string "ff6a6e" depending on pos/neg drift and current status.
   // returns NULL if $drift=0
   // REM: if $drift is not specified, then $this->drift is used ()
   public function getDriftColor($drift = NULL) {

     if (!isset($drift)) {
     	   $drift = $this->getDrift(false);
     }

        if (0 < $drift) {
        	if ($this->currentStatus < $this->bug_resolved_status_threshold) {
              $color = "ff6a6e";
            } else {
              $color = "fcbdbd";
            }
        } elseif (0 > $drift) {
        	if ($this->currentStatus < $this->bug_resolved_status_threshold) {
              $color = "61ed66";
              } else {
              $color = "bdfcbd";
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

   // elapsed - (effortEstim - remaining)
   // if bug is Resolved/Closed, then remaining is not used.

   // REM if EffortEstim = 0 then Drift = 0
   public function getDrift($withSupport = true) {

      $totalEstim = $this->effortEstim + $this->effortAdd;

      if ($withSupport) {
      	$myElapsed = $this->elapsed;
      } else {
        $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
      	$myElapsed = $this->elapsed - $this->getElapsed($job_support);
      }

      if (0 == $totalEstim) { return 0; }

	  if ($this->currentStatus >= $this->bug_resolved_status_threshold) {
         $derive = $myElapsed - $totalEstim;
      } else {
         $derive = $myElapsed - ($totalEstim - $this->remaining);
      }

      $this->logger->debug("bugid ".$this->bugId." ".$this->getCurrentStatusName()." derive=$derive (elapsed $this->elapsed - estim $totalEstim)");
      return $derive;
   }

   // ----------------------------------
   // if NEG, then we saved time
   // if 0, then just in time
   // if POS, then there is a drift !

   // elapsed - (PrelEffortEstim - remaining)
   // if bug is Resolved/Closed, then remaining is not used.

   // REM if PrelEffortEstim = 0 then Drift = 0
   public function getDriftMgrEE($withSupport = true) {

      if (0 == $this->elapsed ) {
         $this->logger->debug("bugid ".$this->bugId." if elapsed == 0 then Drift = 0");
         return 0;
      }

      if ($withSupport) {
         $myElapsed = $this->elapsed;
      } else {
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
         $myElapsed = $this->elapsed - $this->getElapsed($job_support);
      }

	  if ($this->currentStatus >= $this->bug_resolved_status_threshold) {
         $derive = $myElapsed - $this->mgrEffortEstim;
      } else {
         $derive = $myElapsed - ($this->mgrEffortEstim - $this->remaining);
      }

      $this->logger->debug("bugid ".$this->bugId." ".$this->getCurrentStatusName()." derive=$derive (elapsed $this->elapsed - estim ".$this->mgrEffortEstim.")");
      return $derive;
   }


   // ----------------------------------------------
   /**
    * check if the Issue has been delivered in time (before the  DeadLine)
    * formula: (DeadLine - DeliveryDate)
    *
    *
    * @return int nb days drift (except holidays)
    *         if <= 0, Issue delivered in time
    *         if  > 0, Issue NOT delivered in time !
    *         OR "Error" string if could not be determinated. REM: check with is_string($timeDrift)
    */
   public function getTimeDrift() {

   	if ((NULL != $this->deadLine) && (NULL != $this->deliveryDate)) {
   		$timeDrift = $this->deliveryDate - $this->deadLine;

   		// convert seconds to days (24 * 60 * 60) = 86400
   		$timeDrift /=  86400 ;

   		// remove weekends & holidays
   		$holidays = $this->getHolidays();
   		if ($this->deliveryDate < $this->deadLine) {
   		    $nbHolidays = $holidays->getNbHolidays($this->deliveryDate, $this->deadLine);
   		} else {
             $nbHolidays = $holidays->getNbHolidays($this->deadLine, $this->deliveryDate);
   		}
        $this->logger->debug("TimeDrift for issue $this->bugId = ($this->deliveryDate - $this->deadLine) / 86400 = $timeDrift (- $nbHolidays holidays)");

		if ($timeDrift > 0) {
   			$timeDrift -= $nbHolidays;
		} else {
			$timeDrift += $nbHolidays;
		}
   	} else {
         $timeDrift = "Error";
   		$this->logger->warn("could not determinate TimeDrift for issue $this->bugId: deadline=<$this->deadLine> deliveryDate=<$this->deliveryDate>");
   	}
   	return  $timeDrift;
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

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $timeTracks[$row->id] = $row->date;
      }

      return $timeTracks;
   }

   // ----------------------------------------------
   /**
    * returns the date of the oldest TimeTrack
    * @param unknown_type $user_id
    */
   public function getStartTimestamp($user_id = NULL) {
      $timeTracks = array();

      $query     = "SELECT id, date FROM `codev_timetracking_table` ".
      "WHERE bugid=$this->bugId ";

      if (isset($user_id)) {
         $query .= "AND userid = $user_id";
      }
      $query .= " ORDER BY date";

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $row = mysql_fetch_object($result);

      return $row->date;
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

      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
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
         $result = mysql_query($query);
         if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = mysql_fetch_object($result);
         $this->currentStatus   = $row->status;

         $this->logger->debug("getStatus(NULL) : bugId=$this->bugId, status=$this->currentStatus");
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
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      if (0 != mysql_num_rows($result)) {
         $row = mysql_fetch_object($result);

         $this->logger->debug("getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId, old_value=$row->old_value, new_value=$row->new_value, date_modified=".date("d F Y", $row->date_modified));

         return $row->new_value;
      } else {
         $this->logger->debug("getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId not found !");
         return -1;
      }
   }


   // ----------------------------------------------
   // updates DB with new value
   public function setRemaining($remaining) {

   	global $remainingCustomField;

      $old_remaining = $this->remaining;

      $this->logger->debug("setRemaining old_value=$old_remaining   new_value=$remaining");

      // TODO should be done only once... in Constants singleton ?
      $query  = "SELECT name FROM `mantis_custom_field_table` WHERE id='$remainingCustomField'";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $field_name    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "Remaining (RAF)";


      $query = "SELECT * FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId AND field_id = $remainingCustomField";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      if (0 != mysql_num_rows($result)) {

         $query2 = "UPDATE `mantis_custom_field_string_table` SET value = '$remaining' WHERE bug_id=$this->bugId AND field_id = $remainingCustomField";
      } else {
         $query2 = "INSERT INTO `mantis_custom_field_string_table` (`field_id`, `bug_id`, `value`) VALUES ('$remainingCustomField', '$this->bugId', '$remaining');";
      }
      $result = mysql_query($query2);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query2");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $this->remaining = $remaining;

      // Add to history
      $query = "INSERT INTO `mantis_bug_history_table`  (`user_id`, `bug_id`, `field_name`, `old_value`, `new_value`, `type`, `date_modified`) ".
      "VALUES ('".$_SESSION['userid']."','$this->bugId','$field_name', '$old_remaining', '$remaining', '0', '".time()."');";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }

   // ----------------------------------------------
   // Computes the lifeCycle of the issue (time spent on each status)
   // TODO: rename computeDurationsPerStatus
   public function computeDurations () {
   	global $status_new;

      $statusNames = Config::getInstance()->getValue(Config::id_statusNames);
      ksort($statusNames);

      foreach ($statusNames as $s => $sname) {
         if ($status_new == $s) {
            $this->statusList[$s] = new Status($s, $this->getDuration_new());
         } else {
            $this->statusList[$s] = new Status($s, $this->getDuration_other($s));
         }
      }
   }

   // ----------------------------------------------
   // TODO: rename getDurationForStatusNew
   protected function getDuration_new ()
   {
      $time = 0;

      global $status_new;

      $current_date = time();

      // If status = 'new',
      // -- the start_date is the bug creation date
      // -- the end_date   is transition where old_value = status or current_date if status unchanged.

      // If status has not changed, then end_date is now.
      if ($status_new == $this->currentStatus) {
         //echo "bug still in 'new' state<br/>";
         $time = $current_date - $this->dateSubmission;
      } else {
         // Bug has changed, search history for status changed
         $query = "SELECT date_modified FROM `mantis_bug_history_table` WHERE bug_id=$this->bugId AND field_name = 'status' AND old_value='$status_new'";
         $result = mysql_query($query);
	      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }
         $date_modified    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;

         if (0 == $date_modified) {
         	// some SideTasks, are created with status='closed' and have never been set to 'new'.
         	$time = 0;
         } else {
            $time = $date_modified - $this->dateSubmission;
         }
      }

      //echo "duration new $time<br/>";
      return $time;
   }


   // ----------------------------------------------
   // TODO: rename getDurationForStatus
   protected function getDuration_other ($status)
   {
      $time = 0;

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
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $this->logger->debug("id=$row->id date = $row->date_modified old_value = $row->old_value new_value = $row->new_value");
         $start_date = $row->date_modified;

         // Next line is end_date. if NULL then end_date = current_date
         if ($row = mysql_fetch_object($result)) {
            $end_date = $row->date_modified;
            $this->logger->debug("id=$row->id date = $row->date_modified  old_value = $row->old_value new_value = $row->new_value");
         } else {
            $end_date = $current_date;
            $this->logger->debug("end_date = current date = $end_date");
         }
         $intervale =  $end_date - $start_date;
         $this->logger->debug("intervale = $intervale");
         $time = $time + ($end_date - $start_date);
      }

      $this->logger->debug("duration other $time");
      return $time;
   } // getDuration_other


   // ----------------------------------------------
   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $issueB
    *
    * @param Issue $issueB the object to compare to
    */
   function compareTo($issueB) {

      global $status_open;

      // if IssueB constrains IssueA, then IssueB is higher priority
      $AconstrainsList = $this->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      $BconstrainsList = $issueB->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      if (in_array($this->bugId, $BconstrainsList)) {
      	// B constrains A
         $this->logger->trace("compareTo $this->bugId < $issueB->bugId (B constrains A)");
      	return false;
      }
      if (in_array($issueB->bugId, $AconstrainsList)) {
      	// A constrains B
         $this->logger->trace("compareTo $this->bugId > $issueB->bugId (A constrains B)");
      	return true;
      }


      // Tasks currently open are higher priority
      if (($this->currentStatus == $status_open) && ($issueB->currentStatus != $status_open)) {
            $this->logger->trace("compareTo $this->bugId > $issueB->bugId (status_openned)");
            return  true;
      }
      if (($issueB->currentStatus == $status_open) && ($this->currentStatus != $status_open)) {
            $this->logger->trace("compareTo $this->bugId < $issueB->bugId (status_openned)");
            return  false;
      }

      // the one that has NO deadLine is lower priority
      if ((NULL != $this->deadLine) && (NULL == $issueB->deadLine)) {
         $this->logger->trace("compareTo $this->bugId > $issueB->bugId (B no deadline)");
         return  true;
      }
      if ((NULL == $this->deadLine) && (NULL != $issueB->deadLine)) {
         $this->logger->trace("compareTo $this->bugId < $issueB->bugId (A no deadline)");
         return  false;
      }

      // the soonest deadLine has priority
      if ($this->deadLine < $issueB->deadLine) {
         $this->logger->trace("compareTo $this->bugId > $issueB->bugId (deadline)");
         return  true;
      }
      if ($this->deadLine > $issueB->deadLine) {
         $this->logger->trace("compareTo $this->bugId < $issueB->bugId (deadline)");
         return  false;
      }

      // if same deadLine, check priority attribute
      if ($this->priority > $issueB->priority) {
         $this->logger->trace("compareTo $this->bugId > $issueB->bugId (priority attr)");
         return  true;
      }
      if ($this->priority < $issueB->priority) {
         $this->logger->trace("compareTo $this->bugId < $issueB->bugId (priority attr)");
         return  false;
      }

      // if same deadLine, same priority: check severity attribute
      if ($this->severity > $issueB->severity) {
      	$this->logger->trace("compareTo $this->bugId > $issueB->bugId (severity attr)");
      	return  true;
      }
      if ($this->severity < $issueB->severity) {
      	$this->logger->trace("compareTo $this->bugId < $issueB->bugId (severity attr)");
      	return  false;
      }


      // if IssueA constrains nobody, and IssueB constrains IssueX, then IssueB is higher priority
      if (count($AconstrainsList) > count($BconstrainsList)) {
      	// A constrains more people, so A is higher priority
         $this->logger->trace("compareTo $this->bugId > $issueB->bugId (A constrains more people)");
      	return true;
      }

      $this->logger->trace("compareTo $this->bugId <= $issueB->bugId (B constrains more people)");
      return false;
   }



   /**
    * Returns the Estimated Date of Arrival, depending on user's holidays and other timetracks
    *
    * @param $beginTimestamp              the start day
    * @param $availTimeOnBeginTimestamp   On the start day, part of the day may already have
    *                                     been spent on other issues. this param defines how much
    *                                     time is left for this issue.
    *                                     if NULL, use user->getAvailableTime($beginTimestamp)
    * @param $userid                      if NULL, use assignedTo user
    *
    * @return array(endTimestamp, $availTimeOnEndTimestamp)
    *          $availTimeOnEndTimestamp can be re-injected in the next call to this function
    */
   public function computeEstimatedDateOfArrival($beginTimestamp, $availTimeOnBeginTimestamp=NULL, $userid=NULL) {

      // find user in charge of this issue
      if (NULL != $userid) {
         $user = UserCache::getInstance()->getUser($userid);

      } else {
      	if (NULL != $this->handlerId) {
      		$user = UserCache::getInstance()->getUser($this->handlerId);
      	} else {
      		// issue not assigned to anybody
      		$user = NULL;
      	}
      }

      // we need to be absolutely sure that time is 00:00:00
      $timestamp = mktime(0, 0, 0, date("m", $beginTimestamp), date("d", $beginTimestamp), date("Y", $beginTimestamp));

      $tmpDuration = $this->getRemaining();

      $this->logger->debug("computeEstimatedDateOfArrival: user=".$user->getName()." tmpDuration = $tmpDuration begindate=".date('Y-m-d', $timestamp));

      // first day depends only on $availTimeOnBeginTimestamp
      if (NULL == $availTimeOnBeginTimestamp) {
         $availTime = $user->getAvailableTime($timestamp);
      } else {
         $availTime = $availTimeOnBeginTimestamp;
      }
      $tmpDuration -= $availTime;
      $this->logger->debug("computeEstimatedDateOfArrival: 1st ".date('Y-m-d', $timestamp)." tmpDuration (-$availTime) = $tmpDuration");

      // --- next days
      while ($tmpDuration > 0) {
         $timestamp = strtotime("+1 day",$timestamp);

         if (NULL != $user) {
         	$availTime = $user->getAvailableTime($timestamp);
         	$tmpDuration -= $availTime;
            $this->logger->debug("computeEstimatedDateOfArrival: ".date('Y-m-d', $timestamp)." tmpDuration = $tmpDuration");
         } else {
         	// if not assigned, just check for global holidays
         	if (NULL == Holidays::getInstance()->isHoliday($timestamp)) {
   	           $tmpDuration -= 1; // it's not a holiday, so complete day available.
   	        }
         }
      }
      $endTimestamp = $timestamp;

      // if $tmpDuration < 0 this means that this issue will be finished before
      // the end of the day. So the remaining time must be reported to be available
      // fot the next issue to be worked on.
      $availTimeOnEndTimestamp = abs($tmpDuration);

      $this->logger->debug("computeEstimatedDateOfArrival: $this->bugId.computeEstimatedEndTimestamp(".date('Y-m-d', $beginTimestamp).", $availTimeOnBeginTimestamp, $userid) = [".date('Y-m-d', $endTimestamp).",$availTimeOnEndTimestamp]");
      return array($endTimestamp, $availTimeOnEndTimestamp);
   }

   /**
    * returns the timestamp of the first time that
    * the issue switched to status 'status'
    *
    * @return timestamp or NULL if not found
    */
   public function getFirstStatusOccurrence($status) {

      global $status_new;

      if ($status_new == $status) {
      	return $this->dateSubmission;
      }

      $query = "SELECT date_modified ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND new_value=$status ORDER BY id";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $timestamp  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

      if (NULL == $timestamp) {
         $this->logger->debug("issue $this->bugId: getFirstStatusOccurrence($status)  NOT FOUND !");
      } else {
         $this->logger->debug("issue $this->bugId: getFirstStatusOccurrence($status) = ".date('Y-m-d', $timestamp));
      }

      return $timestamp;
   }

   /**
    * returns the timestamp of the latest time that
    * the issue switched to status 'status'
    *
    * @return timestamp or NULL if not found
    */
   public function getLatestStatusOccurrence($status) {

      global $status_new;

      if ($status_new == $status) {
      	return $this->dateSubmission;
      }

      $query = "SELECT date_modified ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND new_value=$status ORDER BY id DESC";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $timestamp  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

      if (NULL == $timestamp) {
         $this->logger->debug("issue $this->bugId: getLatestStatusOccurrence($status)  NOT FOUND !");
      } else {
         $this->logger->debug("issue $this->bugId: getLatestStatusOccurrence($status) = ".date('Y-m-d', $timestamp));
      }

      return $timestamp;
   }

   /**
    * returns a progress rate (depending on Remaining)
    * formula: (BI+BS - RAF) / (BI+BS)
    *
    * 1 = 100% finished
    * 0.5 = 50% done
    * 0 = 0% done
    */
   public function getProgress_old() {

      if ($this->currentStatus >= $this->bug_resolved_status_threshold) {
         return 1; // issue is finished
      }

      // totalEffort is (BI+BS) or if not exist, PEE
      if (NULL != $this->effortEstim) {
         $totalEffort = $this->effortEstim + $this->effortAdd;
      } else {
      	$totalEffort = $this->mgrEffortEstim;
      }

      // if no Remaining set, 0% done
      if (NULL == $this->remaining) {
      	return 0;
      }

      // nominal case
      if ($this->remaining <= $totalEffort) {
         $progress = ($totalEffort - $this->remaining) / $totalEffort;   // (T-R)/T
      } else {
      	$progress = $totalEffort / ($totalEffort - $this->remaining);   // T/(T+R)
      }

      if (($progress < 0) || ($progress > 1)) {
      	$this->logger->error("Progress value = $progress = $totalEffort / ($totalEffort - $this->remaining)");
      }

      return $progress;
   }


   /**
    * returns a progress rate (depending on Remaining)
    * formula2: Elapsed / (Elapsed+RAF)
    *
    * 1 = 100% finished
    * 0.5 = 50% done
    * 0 = 0% done
    */
   public function getProgress() {

      if ($this->currentStatus >= $this->bug_resolved_status_threshold) {
         return 1; // issue is finished, 100% done.
      }

      // no time spent on task, 0% done
      if ((NULL == $this->elapsed) || (0 == $this->elapsed)) { return 0; }

      // if no Remaining set, 0% done
      if ((NULL == $this->remaining) || (0 == $this->remaining)) { return 0; }

      // nominal case
      $progress = $this->elapsed / ($this->elapsed + $this->remaining);   // (T-R)/T


      $this->logger->debug("issue $this->bugId Progress = $progress % = $this->elapsed / ($this->elapsed + $this->remaining)");

      return $progress;
   }


} // class issue

?>

