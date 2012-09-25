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

/**
 * COMPUTE DURATIONS
 * Status & Issue classes
 */
class Status {

   /**
    * @var int Status id
    */
   public $statusId; // new=10, ack=30, ...
   public $duration; // in sec since 1970 (unix timestamp)

   function Status($s, $d) {
      $this->statusId = $s;
      $this->duration = $d;
   }
}

class Issue extends Model implements Comparable {

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

   /**
    * @var int Mantis id
    */
   protected $bugId;

   private $projectId;  // Capu, peterpan, etc.
   private $categoryId;
   private $eta;        // DEPRECATED
   private $summary;
   private $dateSubmission;
   private $currentStatus;
   private $priority;
   private $severity;
   private $handlerId;
   private $reporterId;
   private $resolution;
   private $version;  // Product Version
   private $last_updated;
   private $view_state; // public = 10, private = 50

   private $description;
   private $target_version;
   private $relationships = array(); // array[relationshipType][bugId]
   private $issueNoteList;
   private $commandList;
   private $categoryName;

   /*
     * REM:
     * previous versions of CoDev used the mantis ETA field
     * to store the 'preliminary Effort Estimation'.
     * as ETA may already been used by existing projects for other purpose,
     * a 'prelEffortEstim' customField has been created to replace ETA.
     * REM2:
     * Feb.2012 'prelEffortEstim' has been replaced by 'mgrEffortEstim'
     */

   // CodevTT custom fields
   private $tcId;         // TelelogicChange id
   private $backlog;
   private $mgrEffortEstim;  // Manager EffortEstim (ex prelEffortEstim/ETA)
   private $effortEstim;  // BI
   private $effortAdd;    // BS
   private $deadLine;
   private $deliveryDate;
   private $deliveryId;   // TODO FDL (FDJ specific)

   private $tagList; // mantis tags

   /**
    * @var Status[]
    */
   protected $statusList = array();       // array of statusInfo elements

   // PRIVATE cached fields
   private $holidays;

   // other cache fields
   private $bug_resolved_status_threshold;

   private static $existsCache;

   /**
    * @var int[] Cache : Status by timestamp
    */
   private $statusCache;

   /**
    * @var int[]
    */
   private $elapsedCache;

   private $customFieldInitialized;

   /**
    * @param int $id The issue id
    * @param resource $details The issue details
    * @throws Exception if $id = 0
    */
   public function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Constructor: Creating an Issue with id=0 is not allowed.");
         self::$logger->error("EXCEPTION Issue constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->bugId = $id;

      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The issue details
    * @throws Exception If bug doesn't exists
    */
   public function initialize($row = NULL) {
      if($row == NULL) {
         // Get issue info
         $query = "SELECT * FROM `mantis_bug_table` " .
                  "WHERE id = $this->bugId";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }

      $nbTuples = $row != FALSE;

      self::$existsCache[$this->bugId] = $nbTuples;

      if ($nbTuples) {
         $this->summary = $row->summary;
         $this->currentStatus = $row->status;
         $this->dateSubmission = $row->date_submitted;
         $this->projectId = $row->project_id;
         $this->categoryId = $row->category_id;
         $this->eta = $row->eta; // DEPRECATED
         $this->priority = $row->priority;
         $this->severity = $row->severity;
         $this->handlerId = $row->handler_id;
         $this->reporterId = $row->reporter_id;
         $this->resolution = $row->resolution;
         $this->version = $row->version;
         $this->target_version = $row->target_version;
         $this->last_updated = $row->last_updated;
         $this->view_state = $row->view_state;

         //DEBUG $this->getRelationships(2500);
      } else {
         #echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Constructor: Issue $this->bugId does not exist in Mantis DB.");
         self::$logger->error("EXCEPTION Issue constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }
   }

   /**
    * Get custom fields
    */
   public function initializeCustomField() {
      $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);
      $mgrEffortEstimField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $effortEstimField = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      $backlogField = Config::getInstance()->getValue(Config::id_customField_backlog);
      $addEffortField = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $deadLineField = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
      #$deliveryIdField = Config::getInstance()->getValue(Config::id_customField_deliveryId);
      $customFields = array(
         $extIdField, $mgrEffortEstimField, $effortEstimField, $backlogField, $addEffortField, $deadLineField, $deliveryDateField #, $deliveryIdField
      );
      $query = "SELECT field_id, value FROM `mantis_custom_field_string_table` ".
               "WHERE bug_id = ".$this->bugId." ".
               "AND field_id IN (".implode(',',$customFields).");";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         switch ($row->field_id) {
            case $extIdField:
               $this->tcId = $row->value;
               break;
            case $mgrEffortEstimField:
               $this->mgrEffortEstim = $row->value;
               break;
            case $effortEstimField:
               $this->effortEstim = $row->value;
               break;
            case $backlogField:
               $this->backlog = $row->value;
               break;
            case $addEffortField:
               $this->effortAdd = $row->value;
               break;
            case $deadLineField:
               $this->deadLine = $row->value;
               break;
            case $deliveryDateField:
               $this->deliveryDate = $row->value;
               break;
            case $deliveryIdField:
               $this->deliveryId = $row->value;
               break;
         }
      }
   }

   public function initializeTags() {

      $query = "SELECT mantis_tag_table.* FROM `mantis_tag_table` ".
               "JOIN `mantis_bug_tag_table` ON mantis_tag_table.id = mantis_bug_tag_table.tag_id ".
               "WHERE mantis_bug_tag_table.bug_id = ".$this->bugId.";";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $this->tagList = array();
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $this->tagList[$row->id] = $row->name;
      }
   }

   /**
    * @param int $bugid
    * @return bool TRUE if issue exists in Mantis DB
    */
   public static function exists($bugid) {
      if (NULL == $bugid) {
         self::$logger->warn("exists(): bugid == NULL.");
         return FALSE;
      }

      if (NULL == self::$existsCache) { self::$existsCache = array(); }

      if (!array_key_exists($bugid,self::$existsCache)) {
         $query  = "SELECT COUNT(id) FROM `mantis_bug_table` WHERE id=$bugid ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         #$found  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? true : false;
         $nbTuples  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

         if (1 != $nbTuples) {
            self::$logger->warn("exists($bugid): found $nbTuples items.");
         }
         self::$existsCache[$bugid] = (1 == $nbTuples);
      }
      return self::$existsCache[$bugid];
   }


   public function isPrivate() {
      // public:10 private:50
      return (50 == $this->view_state);
   }
   public function isPublic() {
      // public:10 private:50
      return (10 == $this->view_state);
   }

   /**
    * @return string The issue description
    */
   public function getDescription() {
      if (NULL == $this->description) {
         $query = "SELECT description FROM `mantis_bug_text_table` WHERE id = ".$this->bugId.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
         $this->description = $row->description;
      }
      return $this->description;
   }

   /**
    * Get mantis tags
    * 
    * @return string[]  id => tagName
    */
   public function getTagList() {
      if (!$this->tagList) {
         $this->initializeTags();
      }
      return $this->tagList;
   }


   /**
    * @return IssueNote[]
    */
   public function getIssueNoteList() {
      if (NULL == $this->issueNoteList) {
         $query = "SELECT id FROM `mantis_bugnote_table` WHERE bug_id = ".$this->bugId.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->issueNoteList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->issueNoteList[$row->id] = new IssueNote($row->id);
         }
      }
      return $this->issueNoteList;
   }

   /**
    * returns a Holidays class instance
    * @return Holidays
    */
   private function getHolidays() {
      if (NULL == $this->holidays) {
         $this->holidays = Holidays::getInstance();
      }
      return $this->holidays;
   }


   /**
    * @return bool true if issue status >= bug_resolved_status_threshold
    */
   public function isResolved() {
      return ($this->currentStatus >= $this->getBugResolvedStatusThreshold());
   }

   public function getBugResolvedStatusThreshold() {
      if(NULL == $this->bug_resolved_status_threshold) {
         try {
            $project = ProjectCache::getInstance()->getProject($this->projectId);
            $this->bug_resolved_status_threshold = $project->getBugResolvedStatusThreshold();
         } catch (Exception $e) {
            self::$logger->error("getBugResolvedStatusThreshold() issue $this->bugId: ".$e->getMessage());
            $this->bug_resolved_status_threshold = Config::getInstance()->getValue(Constants::$bug_resolved_status_threshold);
            self::$logger->warn("getBugResolvedStatusThreshold(): using default BugResolvedStatusThreshold ($this->bug_resolved_status_threshold)");
         }
      }
      return $this->bug_resolved_status_threshold;
   }

   /**
    * WARNING (best effort)
    *
    * Ex: vacation or Incident tasks are not production issues.
    *     but tools and workshop are production issues.
    *
    * Note: the project type is specific to a team, so you need to specify
    * a team list. see Project::isSideTasksProject() for more info
    * @param int[] $teamidList
    * @return bool true if Tools or Workshop category
    * @throws Exception
    */
   public function isSideTaskIssue(array $teamidList = NULL) {
      $project = ProjectCache::getInstance()->getProject($this->projectId);

      try {
         if (($project->isSideTasksProject($teamidList)) &&
            ($project->getCategory(Project::cat_st_tools) != $this->categoryId) &&
            ($project->getCategory(Project::cat_st_workshop)   != $this->categoryId)) {

            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("$this->bugId is a sideTask.");
            }
            return TRUE;
         }
      } catch (Exception $e) {
         self::$logger->warn("isSideTaskIssue(): ".$e->getMessage());
         throw $e;
      }
      return FALSE;
   }

   /**
    * WARNING (best effort)
    *
    * check if issue is in a SideTaskProject AND in the Inactivity category.
    *
    * Note: the project type is specific to a team, so you need to specify
    * a team list. see Project::isSideTasksProject() for more info
    *
    * @param int[] $teamidList
    * @return bool true if Inactivity task
    * @throws Exception
    */
   public function isVacation(array $teamidList = NULL) {
      $project = ProjectCache::getInstance()->getProject($this->projectId);

      try {
         if (($project->isSideTasksProject($teamidList)) &&
            ($project->getCategory(Project::cat_st_inactivity) == $this->categoryId)) {

            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("$this->bugId is Vacation.");
            }
            return TRUE;
         }
      } catch (Exception $e) {
         self::$logger->warn("isVacation(): ".$e->getMessage());
         throw $e;
      }
      return FALSE;
   }

   /**
    * WARNING (best effort)
    *
    * check if issue is in a SideTaskProject AND in the Incident category.
    *
    * the project type is specific to a team, so you need to specify
    * a team list. see Project::isSideTasksProject() for more info
    *
    * @param int[] $teamidList
    * @return bool true if Incident task
    * @throws Exception
    */
   public function isIncident(array $teamidList = NULL) {
      $project = ProjectCache::getInstance()->getProject($this->projectId);
      try {
         if (($project->isSideTasksProject($teamidList)) &&
            ($project->getCategory(Project::cat_st_incident) == $this->categoryId)) {

            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("$this->bugId is a Incident.");
            }
            return TRUE;
         }
      } catch (Exception $e) {
         self::$logger->warn("isIncident(): ".$e->getMessage());
         throw $e;
      }

      return FALSE;
   }

   /**
    * WARNING (best effort)
    *
    * check if issue is in a SideTaskProject AND in the Inactivity category.
    *
    * Note: the project type is specific to a team, so you need to specify
    * a team list. see Project::isSideTasksProject() for more info
    *
    * @param int[] $teamidList
    * @return bool true if ProjectManagement task
    * @throws Exception
    */
   public function isProjManagement(array $teamidList = NULL) {
      $project = ProjectCache::getInstance()->getProject($this->projectId);

      try {
         if (($project->isSideTasksProject($teamidList)) &&
            ($project->getCategory(Project::cat_mngt_regular) == $this->categoryId)) {

            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("$this->bugId is a ProjectManagement task.");
            }
            return TRUE;
         }
      } catch (Exception $e) {
         self::$logger->warn("isProjManagement(): ".$e->getMessage());
         throw $e;
      }
      return FALSE;
   }

   /**
    * @return bool
    */
   public function isAstreinte() {
      // TODO translate astreinte = "on duty"
      $astreintesTaskList = Config::getInstance()->getValue(Config::id_astreintesTaskList); // fiches de SuiviOp:Inactivite qui sont des astreintes
      if (NULL == $astreintesTaskList) {
         $astreintesTaskList = array();
      }

      if (in_array($this->bugId, $astreintesTaskList)) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug($this->bugId." is an Astreinte.");
         }
         return TRUE;
      }
      return FALSE;
   }

   public function getTargetVersion() {
      return $this->target_version;
   }

   public function getTcId() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->tcId;
   }

   /**
    * Issue deadLine
    *
    * if deadLineCustomField is set, return this value,
    * else if TargetVersion date is specified return it,
    * else return NULL
    * @return int
    */
   public function getDeadLine() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }

      // if exist return customField value
      // REM: already set in initialize()
      if (NULL != $this->deadLine) { return $this->deadLine; }

      // check if
      if (NULL != $this->target_version) {
         $project = ProjectCache::getInstance()->getProject($this->projectId);
         return $project->getVersionDate($this->target_version);
      }

      return NULL;
   }

   public function getProjectName() {
      $project = ProjectCache::getInstance()->getProject($this->projectId);
      return $project->getName();

      /*
      $query = "SELECT name FROM `mantis_project_table` WHERE id= $this->projectId";
      $result = SqlWrapper::getInstance()->sql_query($query) or die("Query failed: $query");
      $projectName = SqlWrapper::getInstance()->sql_result($result, 0);

      return $projectName;
      */
   }

   /**
    * @return string The category name
    */
   public function getCategoryName() {
      if (NULL == $this->categoryName) {
         $this->categoryName = Project::getCategoryName($this->categoryId);
      }

      return $this->categoryName;
   }

   public function getCurrentStatusName() {
      return Constants::$statusNames[$this->currentStatus];
   }

   public function getPriorityName() {
      return Constants::$priority_names[$this->priority];
   }

   public function getSeverityName() {
      return Constants::$severity_names[$this->severity];
   }

   public function getResolutionName() {
      return Constants::$resolution_names[$this->resolution];
   }

   /**
    * Get elapsed from TimeTracking
    * @param int $job_id if no category specified, then all category.
    * @return int
    */
   public function getElapsed($job_id = NULL) {  // TODO $doRefresh = false

      if(NULL == $this->elapsedCache) {
         $this->elapsedCache = array();
      }

      $key = 'j'.$job_id;
      if(!array_key_exists("$key", $this->elapsedCache)) {
         $query = "SELECT SUM(duration) as duration ".
                  "FROM `codev_timetracking_table` ".
                  "WHERE bugid = ".$this->bugId;

         if (isset($job_id)) {
            $query .= " AND jobid = $job_id";
         }

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->elapsedCache["$key"] = round(SqlWrapper::getInstance()->sql_result($result),2);
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getElapsed(job=".$job_id."): set elapsedCache[$key] = ".$this->elapsedCache["$key"]);
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getElapsed(job=".$job_id."): ".$this->elapsedCache["$key"]);
      }
      return $this->elapsedCache["$key"];
   }

   /**
    * @return int the nb of days needed to finish the issue.
    * if status >= resolved, return 0.
    * if the 'backlog' (BL) field is not defined, return effortEstim
    */
   public function getDuration() {
      if ($this->isResolved()) {
         return 0;
      }
      $issueDuration = NULL;

      // WARN: in PHP '0' and NULL are same, so you need to use is_null() !

      // determinate issue duration (Backlog, BI, MgrEffortEstim)
      $bl = $this->getBacklog();
      if ( !is_null($bl) && is_numeric($bl)) {
         $issueDuration = $bl;
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getDuration(): ".$this->bugId."): return backlog : ".$issueDuration);
         }
      }
      else {
         $issueDuration = $this->getEffortEstim();
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getDuration(): ".$this->bugId."): return effortEstim : ".$issueDuration);
         }
      }

      if (is_null( $this->getEffortEstim())) {
         self::$logger->warn("getDuration(".$this->bugId."): duration = NULL ! (because backlog AND effortEstim == NULL)");
      }
      return $issueDuration;
   }

   /**
    * @return int the nb of days needed to finish the issue.
    * if status >= resolved, return 0.
    * if the 'backlog' (BL) field is not defined, return mgrEffortEstim
    */
   public function getDurationMgr() {
      if ($this->isResolved()) {
         return 0;
      }
      $issueDuration = NULL;

      // WARN: in PHP '0' and NULL are same, so you need to use is_null() !

      // determinate issue duration (Backlog, BI, MgrEffortEstim)
      $bl = $this->getBacklog();
      if ( !is_null($bl) && is_numeric($bl)) {
         $issueDuration = $bl;
      }
      else {
         $issueDuration = $this->getMgrEffortEstim();
      }

      if (is_null($this->getMgrEffortEstim())) {
         self::$logger->warn("getDuration(".$this->bugId."): duration = NULL ! (because backlog AND mgrEffortEstim == NULL)");
      }
      return $issueDuration;
   }

   /**
    * reestimated = elapsed + duration
    * @return int reestimated
    */
   public function getReestimated() {
      $reestimated = $this->getElapsed() + $this->getDuration();
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getReestimated(".$this->bugId.") = $reestimated : elapsed = ".$this->getElapsed()." + Duration = ".$this->getDuration());
      }
      return $reestimated;
   }

   /**
    * reestimated = elapsed + durationMgr
    * @return int reestimated
    */
   public function getReestimatedMgr() {
      return $this->getElapsed() + $this->getDurationMgr();
   }

   /**
    * TODO: NOT FINISHED, ADAPT TO ALL RELATIONSHIP TYPES
    * get list of Relationships
    * @param type = 2500 or 2501
    * @return int[] : array(issue_id);
    */
   public function getRelationships($type) {
      // TODO
      $complementaryType = (2500 == $type) ? 2501 : 2500;

      if (!array_key_exists($type, $this->relationships)) {
         $this->relationships[$type] = array();

         $query = 'SELECT * FROM `mantis_bug_relationship_table` '.
                  'WHERE (source_bug_id='.$this->bugId.' '.
                  'AND relationship_type='.$type.') '.
                  'OR (destination_bug_id='.$this->bugId.' '.
                  'AND relationship_type='.$complementaryType.');';

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            if($row->source_bug_id == $this->bugId) {
               // normal
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("relationships: [$type] $this->bugId -> $row->destination_bug_id\n");
               }
               $this->relationships[$type][] = $row->destination_bug_id;
            } elseif($row->destination_bug_id == $this->bugId) {
               // complementary
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("relationships: [$type] $this->bugId -> $row->source_bug_id\n");
               }
               $this->relationships[$type][] = $row->source_bug_id;
            }
         }
      }

      return $this->relationships[$type];
   }

   /**
    * @return int the timestamp of the first TimeTrack
    */
   public function startDate() {
      $query = "SELECT MIN(date) ".
               "FROM `codev_timetracking_table` ".
               "WHERE bugid = ".$this->bugId.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_result($result, 0);
   }

   /**
    * @return int the timestamp of the latest TimeTrack
    */
   public function endDate() {
      $query = "SELECT MAX(date) ".
               "FROM `codev_timetracking_table` ".
               "WHERE bugid = ".$this->bugId.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_result($result, 0);
   }

   /**
    * @param int $drift
    * @return string an HTML color string "ff6a6e" depending on pos/neg drift and current status. NULL if $drift=0
    * REM: if $drift is not specified, then $this->drift is used ()
    */
   public function getDriftColor($drift = NULL) {
      if (!isset($drift)) {
         $drift = $this->getDrift(FALSE);
      }

      if (0 < $drift) {
         if ($this->currentStatus < $this->getBugResolvedStatusThreshold()) {
            $color = "ff6a6e";
         } else {
            $color = "fcbdbd";
         }
      } elseif (0 > $drift) {
         if ($this->currentStatus < $this->getBugResolvedStatusThreshold()) {
            $color = "61ed66";
         } else {
            $color = "bdfcbd";
         }
      } else {
         $color = NULL;
      }

      return $color;
   }

   /**
    * Effort deviation, compares elapsed to effortEstim
    *
    * formula: elapsed - (effortEstim - backlog)
    * if bug is Resolved/Closed, then backlog is not used.
    * if EffortEstim = 0 then Drift = 0
    *
    * @param bool $withSupport
    * @return int drift: if NEG, then we saved time, if 0, then just in time, if POS, then there is a drift !
    */
   public function getDrift_old($withSupport = TRUE) {
      $totalEstim = $this->getEffortEstim() + $this->getEffortAdd();

      if (0 == $totalEstim) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("bugid ".$this->bugId." if EffortEstim == 0 then Drift = 0");
         }
         return 0;
      }

      if ($withSupport) {
         $myElapsed = $this->getElapsed();
      } else {
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
         $myElapsed = $this->getElapsed() - $this->getElapsed($job_support);
      }
/*
      // if Elapsed     = 0 then Drift = 0
      if (0 == $myElapsed) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("bugid ".$this->bugId." if Elapsed == 0 then Drift = 0");
         }
         return 0;
      }
*/
      if ($this->currentStatus >= $this->getBugResolvedStatusThreshold()) {
         $derive = $myElapsed - $totalEstim;
      } else {
         $derive = $myElapsed - ($totalEstim - $this->getBacklog());
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getDrift_old(".$this->bugId.") derive=$derive (elapsed ".$this->getElapsed()." - estim $totalEstim)");
      }
      return round($derive,3);
   }

   public function getDrift($withSupport = TRUE) {
      $totalEstim = $this->getEffortEstim() + $this->getEffortAdd();
      $derive = $this->getReestimated() - $totalEstim;

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getDrift(".$this->bugId.") derive=$derive (reestimated ".$this->getReestimated()." - estim ".$totalEstim.")");
      }
      return round($derive,3);
   }

   /**
    * Effort deviation, compares Reestimated to mgrEffortEstim
    *
    * OLD formula: elapsed - (MgrEffortEstim - backlog)
    * NEW formula: reestimated - MgrEffortEstim = (elapsed + durationMgr) - MgrEffortEstim
    *
    * @param boolean $withSupport
    * @return int drift: if NEG, then we saved time, if 0, then just in time, if POS, then there is a drift !
    */
   public function getDriftMgr($withSupport = TRUE) {
/*
      if ($withSupport) {
         $myElapsed = $this->elapsed;
      } else {
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
         $myElapsed = $this->elapsed - $this->getElapsed($job_support);
      }
*/
      $derive = $this->getReestimatedMgr() - $this->getMgrEffortEstim();

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("bugid ".$this->bugId." ".$this->getCurrentStatusName()." derive=$derive (reestimatedMgr ".$this->getReestimatedMgr()." - estim ".$this->getMgrEffortEstim().")");
      }
      return round($derive,3);
   }

   /**
    * check if the Issue has been delivered in time (before the  DeadLine)
    * formula: (DeadLine - DeliveryDate)
    * @return int nb days drift (except holidays)
    *         if <= 0, Issue delivered in time
    *         if  > 0, Issue NOT delivered in time !
    *         OR "Error" string if could not be determinated. REM: check with is_string($timeDrift)
    */
   public function getTimeDrift() {
      if (NULL != $this->getDeliveryDate() && NULL != $this->getDeadLine()) {
         $timeDrift = $this->getDeliveryDate() - $this->getDeadLine();

         // convert seconds to days (24 * 60 * 60) = 86400
         $timeDrift /=  86400 ;

         // remove weekends & holidays
         $holidays = $this->getHolidays();
         if ($this->getDeliveryDate() < $this->getDeadLine()) {
            $nbHolidays = $holidays->getNbHolidays($this->getDeliveryDate(), $this->getDeadLine());
         } else {
            $nbHolidays = $holidays->getNbHolidays($this->getDeadLine(), $this->getDeliveryDate());
         }
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("TimeDrift for issue $this->bugId = (".$this->getDeliveryDate()." - ".$this->getDeadLine().") / 86400 = $timeDrift (- $nbHolidays holidays)");
         }

         if ($timeDrift > 0) {
            $timeDrift -= $nbHolidays;
         } else {
            $timeDrift += $nbHolidays;
         }
      } else {
         $timeDrift = "Error";
         self::$logger->warn("could not determinate TimeDrift for issue $this->bugId: deadline=<".$this->getDeadLine()."> deliveryDate=<".$this->getDeliveryDate().">");
      }
      return  $timeDrift;
   }

   /**
    * @param int $userid
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return TimeTrack[]
    */
   public function getTimeTracks($userid = NULL, $startTimestamp = NULL, $endTimestamp = NULL) {
      $timeTracks = array();

      $query = "SELECT * FROM `codev_timetracking_table` ".
               "WHERE bugid = ".$this->bugId." ";

      if (isset($userid)) {
         $query .= "AND userid = $userid ";
      }
      if (isset($startTimestamp)) {
         $query .= "AND date >= $startTimestamp ";
      }
      if (isset($endTimestamp)) {
         $query .= "AND date <= $endTimestamp ";
      }
      $query .= " ORDER BY date";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $timeTracks[$row->id] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
      }

      return $timeTracks;
   }

   /**
    * @param int $team_id
    * @return string[]
    */
   public function getInvolvedUsers($team_id = NULL) {
      $userList = array();

      $query = "SELECT user.id, user.username ".
               "FROM `mantis_user_table` as user, `codev_timetracking_table` as tt, `codev_team_user_table`  ".
               "WHERE  tt.userid = user.id ".
               "AND tt.bugid  = ".$this->bugId." ";

      if (isset($team_id)) {
         $query .= "AND codev_team_user_table.team_id = $team_id ".
                   "AND codev_team_user_table.user_id = user.id ";
      }

      $query .= " ORDER BY user.username";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $userList[$row->id] = $row->username;
      }

      return $userList;
   }

   /**
    * @param number $timestamp
    * @return int statusId at date < $timestamp or current status if $timestamp = NULL
    */
   public function getStatus($timestamp = NULL) {
      if(NULL == $this->statusCache) {
         $this->statusCache = array();
      }

      $key = 't'.$timestamp;
      if(!array_key_exists($key,$this->statusCache)) {
         if (NULL == $timestamp) {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getStatus(NULL) : bugId=$this->bugId, status=$this->currentStatus");
            }
            $this->statusCache[$key] = $this->currentStatus;
         } else {
            // if a timestamp is specified, find the latest status change (strictly) before this date
            $query = "SELECT new_value, old_value, date_modified ".
                     "FROM `mantis_bug_history_table` ".
                     "WHERE bug_id = $this->bugId ".
                     "AND field_name='status' ".
                     "AND date_modified < $timestamp ".
                     "ORDER BY date_modified DESC";

            // get latest result
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
               $row = SqlWrapper::getInstance()->sql_fetch_object($result);

               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId, old_value=$row->old_value, new_value=$row->new_value, date_modified=".date("d F Y", $row->date_modified));
               }

               $this->statusCache[$key] = $row->new_value;
            } else {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getStatus(".date("d F Y", $timestamp).") : bugId=$this->bugId not found !");
               }
               $this->statusCache[$key] = -1;
            }
         }
      }
      return $this->statusCache[$key];
   }

   /**
    * updates DB with new value
    * @param int $backlog
    */
   public function setBacklog($backlog) {
      $backlogCustomField = Config::getInstance()->getValue(Config::id_customField_backlog);

      $old_backlog = $this->getBacklog();

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("setBacklog old_value=$old_backlog   new_value=$backlog");
      }

      // TODO should be done only once... in Constants singleton ?
      $query = "SELECT name FROM `mantis_custom_field_table` WHERE id = $backlogCustomField";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $field_name    = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : "Backlog (BL)";

      $query = "SELECT * FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId AND field_id = $backlogCustomField";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {

         $query2 = "UPDATE `mantis_custom_field_string_table` SET value = '$backlog' WHERE bug_id=$this->bugId AND field_id = $backlogCustomField";
      } else {
         $query2 = "INSERT INTO `mantis_custom_field_string_table` (`field_id`, `bug_id`, `value`) VALUES ('$backlogCustomField', '$this->bugId', '$backlog');";
      }
      $result = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $this->backlog = $backlog;

      // Add to history
      $query = "INSERT INTO `mantis_bug_history_table`  (`user_id`, `bug_id`, `field_name`, `old_value`, `new_value`, `type`, `date_modified`) ".
               "VALUES ('".$_SESSION['userid']."','$this->bugId','$field_name', '$old_backlog', '$backlog', '0', '".time()."');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * Computes the lifeCycle of the issue (time spent on each status)
    */
   public function computeDurationsPerStatus () {
      // get only statuses defined for this project
      $project = ProjectCache::getInstance()->getProject($this->projectId);
      $wfTrans = $project->getWorkflowTransitions();
      $statusNames = NULL;
      if (NULL != $wfTrans) { $statusNames = $wfTrans[0]; }
      
      if (NULL == $statusNames) {
         // if none defined, get all mantis statuses
         $statusNames = Constants::$statusNames;
         ksort($statusNames);
      }

      $this->statusList[Constants::$status_new] = new Status(Constants::$status_new, $this->getDurationForStatusNew());

      foreach ($statusNames as $s => $sname) {
         if (Constants::$status_new != $s) {
            $this->statusList[$s] = new Status($s, $this->getDurationForStatus($s));
         }
      }
   }

   protected function getDurationForStatusNew() {
      $current_date = time();

      // If status = 'new',
      // the start_date is the bug creation date
      // the end_date   is transition where old_value = status or current_date if status unchanged.

      // If status has not changed, then end_date is now.
      if (Constants::$status_new == $this->currentStatus) {
         //echo "bug still in 'new' state<br/>";
         $time = $current_date - $this->dateSubmission;
      } else {
         // Bug has changed, search history for status changed
         $query = "SELECT date_modified FROM `mantis_bug_history_table` WHERE bug_id=$this->bugId AND field_name = 'status' AND old_value='".Constants::$status_new."'";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $date_modified    = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

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

   /**
    * @param string $status
    * @return int
    */
   protected function getDurationForStatus($status) {
      $time = 0;

      $current_date = time();

      // Status is not 'new' and not 'feedback'
      // the start_date is transition where new_value = status
      // the end_date   is transition where old_value = status, or current date if no transition found.

      // Find start_date
      $query = "SELECT id, date_modified, old_value, new_value ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND (new_value=$status OR old_value=$status) ORDER BY id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("id=$row->id date = $row->date_modified old_value = $row->old_value new_value = $row->new_value");
         }
         $start_date = $row->date_modified;

         // Next line is end_date. if NULL then end_date = current_date
         if ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $end_date = $row->date_modified;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("id=$row->id date = $row->date_modified  old_value = $row->old_value new_value = $row->new_value");
            }
         } else {
            $end_date = $current_date;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("end_date = current date = $end_date");
            }
         }
         $intervale =  $end_date - $start_date;
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("intervale = $intervale");
         }
         $time = $time + ($end_date - $start_date);
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("duration other $time");
      }
      return $time;
   }

   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $issueB
    *
    * @param Issue $issueB the object to compare to
    * @return bool True if equals
    */
   function compareTo($issueB) {
      // if IssueB constrains IssueA, then IssueB is higher priority
      $AconstrainsList = $this->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      $BconstrainsList = $issueB->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      if (in_array($this->bugId, $BconstrainsList)) {
         // B constrains A
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (B constrains A)");
         }
         return FALSE;
      }
      if (in_array($issueB->bugId, $AconstrainsList)) {
         // A constrains B
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (A constrains B)");
         }
         return TRUE;
      }

      // Tasks currently open are higher priority
      if (($this->currentStatus == Constants::$status_open) && ($issueB->currentStatus != Constants::$status_open)) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (status_openned)");
         }
         return  TRUE;
      }
      if (($issueB->currentStatus == Constants::$status_open) && ($this->currentStatus != Constants::$status_open)) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (status_openned)");
         }
         return  FALSE;
      }

      // the one that has NO deadLine is lower priority
      if ((NULL != $this->getDeadLine()) && (NULL == $issueB->getDeadLine())) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (B no deadline)");
         }
         return  TRUE;
      }
      if ((NULL == $this->getDeadLine()) && (NULL != $issueB->getDeadLine())) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (A no deadline)");
         }
         return  FALSE;
      }

      // the soonest deadLine has priority
      if ($this->getDeadLine() < $issueB->getDeadLine()) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (deadline)");
         }
         return  TRUE;
      }
      if ($this->getDeadLine() > $issueB->getDeadLine()) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (deadline)");
         }
         return  FALSE;
      }

      // if same deadLine, check priority attribute
      if ($this->priority > $issueB->priority) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (priority attr)");
         }
         return  TRUE;
      }
      if ($this->priority < $issueB->priority) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (priority attr)");
         }
         return  FALSE;
      }

      // if same deadLine, same priority: check severity attribute
      if ($this->severity > $issueB->severity) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (severity attr)");
         }
         return  TRUE;
      }
      if ($this->severity < $issueB->severity) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId < $issueB->bugId (severity attr)");
         }
         return  FALSE;
      }

      // if IssueA constrains nobody, and IssueB constrains IssueX, then IssueB is higher priority
      if (count($AconstrainsList) > count($BconstrainsList)) {
         // A constrains more people, so A is higher priority
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compareTo $this->bugId > $issueB->bugId (A constrains more people)");
         }
         return TRUE;
      }

      if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
         self::$logger->trace("compareTo $this->bugId <= $issueB->bugId (B constrains more people)");
      }
      return FALSE;
   }

   /**
    * Sort by asc
    * @param Issue $issueA
    * @param Issue $issueB
    * @return int 1 if $issueB is higher priority, -1 if $issueB is lower, 0 if equals
    */
   public static function compare(Comparable $issueA, Comparable $issueB) {
      // if IssueB constrains IssueA, then IssueB is higher priority
      $AconstrainsList = $issueA->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      $BconstrainsList = $issueB->getRelationships( BUG_CUSTOM_RELATIONSHIP_CONSTRAINS );
      if (in_array($issueA->bugId, $BconstrainsList)) {
         // B constrains A
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (B constrains A)");
         }
         return 1;
      } else if (in_array($issueB->bugId, $AconstrainsList)) {
         // A constrains B
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (A constrains B)");
         }
         return -1;
      }

      // Tasks currently open are higher priority
      if (($issueB->currentStatus == Constants::$status_open) && ($issueA->currentStatus != Constants::$status_open)) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (status_openned)");
         }
         return 1;
      } else if (($issueA->currentStatus == Constants::$status_open) && ($issueB->currentStatus != Constants::$status_open)) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (status_openned)");
         }
         return -1;
      }

      // the one that has NO deadLine is lower priority
      if ((NULL == $issueA->getDeadLine()) && (NULL != $issueB->getDeadLine())) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (A no deadline)");
         }
         return 1;
      } else if ((NULL != $issueA->getDeadLine()) && (NULL == $issueB->getDeadLine())) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (B no deadline)");
         }
         return -1;
      }

      // the soonest deadLine has priority
      if ($issueA->getDeadLine() > $issueB->getDeadLine()) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (deadline)");
         }
         return 1;
      } else if ($issueA->getDeadLine() < $issueB->getDeadLine()) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (deadline)");
         }
         return -1;
      }

      // if same deadLine, check priority attribute
      if ($issueA->priority < $issueB->priority) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (priority attr)");
         }
         return 1;
      } else if ($issueA->priority > $issueB->priority) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (priority attr)");
         }
         return -1;
      }

      // if same deadLine, same priority: check severity attribute
      if ($issueA->severity < $issueB->severity) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (severity attr)");
         }
         return 1;
      } else if ($issueA->severity > $issueB->severity) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (severity attr)");
         }
         return -1;
      }

      // if IssueA constrains nobody, and IssueB constrains IssueX, then IssueB is higher priority
      if (count($AconstrainsList) < count($BconstrainsList)) {
         // B constrains more people, so B is higher priority
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId (B constrains more people)");
         }
         return 1;
      } else if (count($AconstrainsList) > count($BconstrainsList)) {
         // A constrains more people, so A is higher priority
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId (A constrains more people)");
         }
         return -1;
      }

      if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
         self::$logger->trace("no important diff find, so we compare the bugid : $issueA->bugId <=> $issueB->bugId");
      }

      // Lower if the bug id, higher is the priority
      if($issueA->bugId > $issueB->bugId) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId > $issueB->bugId");
         }
         return 1;
      } else if($issueA->bugId < $issueB->bugId) {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId < $issueB->bugId");
         }
         return -1;
      } else {
         if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("compare $issueA->bugId = $issueB->bugId");
         }
         return 0;
      }
   }

   /**
    * Returns the Estimated Date of Arrival, depending on user's holidays and other timetracks
    *
    * @param int $beginTimestamp              the start day
    * @param int $availTimeOnBeginTimestamp   On the start day, part of the day may already have
    *                                     been spent on other issues. this param defines how much
    *                                     time is left for this issue.
    *                                     if NULL, use user->getAvailableTime($beginTimestamp)
    * @param int $userid                      if NULL, use assignedTo user
    * @return mixed[] array(endTimestamp, $availTimeOnEndTimestamp)
    *          $availTimeOnEndTimestamp can be re-injected in the next call to this function
    */
   public function computeEstimatedDateOfArrival($beginTimestamp, $availTimeOnBeginTimestamp=NULL, $userid=NULL) {
      // find user in charge of this issue
      if (NULL != $userid) {
         $user = UserCache::getInstance()->getUser($userid);

      } else {
         if (NULL != $this->getHandlerId()) {
            $user = UserCache::getInstance()->getUser($this->getHandlerId());
         } else {
            // issue not assigned to anybody
            $user = NULL;
         }
      }

      // we need to be absolutely sure that time is 00:00:00
      $timestamp = mktime(0, 0, 0, date("m", $beginTimestamp), date("d", $beginTimestamp), date("Y", $beginTimestamp));

      $tmpDuration = $this->getDuration();

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("computeEstimatedDateOfArrival: user=".$user->getName()." tmpDuration = $tmpDuration begindate=".date('Y-m-d', $timestamp));
      }

      // first day depends only on $availTimeOnBeginTimestamp
      if (NULL == $availTimeOnBeginTimestamp) {
         $availTime = $user->getAvailableTime($timestamp);
      } else {
         $availTime = $availTimeOnBeginTimestamp;
      }
      $tmpDuration -= $availTime;
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("computeEstimatedDateOfArrival: 1st ".date('Y-m-d', $timestamp)." tmpDuration (-$availTime) = $tmpDuration");
      }

      // --- next days
      while ($tmpDuration > 0) {
         $timestamp = strtotime("+1 day",$timestamp);

         if (NULL != $user) {
            $availTime = $user->getAvailableTime($timestamp);
            $tmpDuration -= $availTime;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("computeEstimatedDateOfArrival: ".date('Y-m-d', $timestamp)." tmpDuration = $tmpDuration");
            }
         } else {
            // if not assigned, just check for global holidays
            if (NULL == Holidays::getInstance()->isHoliday($timestamp)) {
               $tmpDuration -= 1; // it's not a holiday, so complete day available.
            }
         }
      }
      $endTimestamp = $timestamp;

      // if $tmpDuration < 0 this means that this issue will be finished before
      // the end of the day. So the backlog time must be reported to be available
      // fot the next issue to be worked on.
      $availTimeOnEndTimestamp = abs($tmpDuration);

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("computeEstimatedDateOfArrival: $this->bugId.computeEstimatedEndTimestamp(".date('Y-m-d', $beginTimestamp).", $availTimeOnBeginTimestamp, $userid) = [".date('Y-m-d', $endTimestamp).",$availTimeOnEndTimestamp]");
      }
      return array($endTimestamp, $availTimeOnEndTimestamp);
   }

   /**
    * returns the timestamp of the first time that
    * the issue switched to status 'status'
    * @param unknown_type $status
    * @return int timestamp or NULL if not found
    */
   public function getFirstStatusOccurrence($status) {
      if (Constants::$status_new == $status) {
         return $this->dateSubmission;
      }

      $query = "SELECT date_modified ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND new_value=$status ORDER BY id LIMIT 1";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $timestamp  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : NULL;

      if (NULL == $timestamp) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue $this->bugId: getFirstStatusOccurrence($status)  NOT FOUND !");
         }
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue $this->bugId: getFirstStatusOccurrence($status) = ".date('Y-m-d', $timestamp));
         }
      }

      return $timestamp;
   }

   /**
    * returns the timestamp of the latest time that
    * the issue switched to status 'status'
    * @param unknown_type $status
    * @return int timestamp or NULL if not found
    */
   public function getLatestStatusOccurrence($status) {
      if (Constants::$status_new == $status) {
         return $this->dateSubmission;
      }

      $query = "SELECT date_modified ".
               "FROM `mantis_bug_history_table` ".
               "WHERE bug_id=$this->bugId ".
               "AND field_name = 'status' ".
               "AND new_value=$status ORDER BY id DESC LIMIT 1";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $timestamp  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : NULL;

      if (NULL == $timestamp) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue $this->bugId: getLatestStatusOccurrence($status)  NOT FOUND !");
         }
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("issue $this->bugId: getLatestStatusOccurrence($status) = ".date('Y-m-d', $timestamp));
         }
      }

      return $timestamp;
   }

   /**
    * @return number a progress rate (depending on Backlog)
    * formula2: Elapsed / (Elapsed+RAF)
    *
    * 1 = 100% finished
    * 0.5 = 50% done
    * 0 = 0% done
    */
   public function getProgress() {
      if ($this->currentStatus >= $this->getBugResolvedStatusThreshold()) {
         return 1; // issue is finished, 100% done.
      }

      // no time spent on task, 0% done
      if ((NULL == $this->getElapsed()) || (0 == $this->getElapsed())) { return 0; }

      // if no Backlog set, 100% done (this is not a normal case, an Alert is raised by ConsistencyCheck)
      if (is_null($this->getBacklog()) || (0 == $this->getBacklog())) { return 1; }

      // nominal case
      $progress = $this->getElapsed() / $this->getReestimated();   // (T-R)/T

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("issue $this->bugId Progress = $progress % = ".$this->getElapsed()." / (".$this->getElapsed()." + ".$this->getBacklog().")");
      }

      return $progress;
   }

   /**
    * A, issue can be included in several Comands from different teams.
    *
    * This returns the list of Commands where this Issue is defined.
    *
    * @return string[] : array[command_id] = commandName
    */
   public function getCommandList() {
      if (NULL == $this->commandList) {
         $this->commandList = array();
         
         $query = "SELECT command.* FROM `codev_command_table` as command ".
                  "JOIN `codev_command_bug_table` as command_bug ON command.id = command_bug.command_id ".
                  "WHERE command_bug.bug_id = ".$this->bugId.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // a Command can belong to more than one commandset
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            //$cmd = CommandCache::getInstance()->getCommand($row->id, $row);
            $this->commandList[$row->id] = $row->name;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("Issue $this->bugId is in command $row->id (".$row->name.")");
            }
         }
      }
      return $this->commandList;
   }

   /**
    * @param type $value
    */
   public function setHandler($value) {
      $query = "UPDATE `mantis_bug_table` SET handler_id = '$value' WHERE id=$this->bugId ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }
   
   /**
    * @param type $value
    */
   public function setTargetVersion($value) {
      $query = "SELECT version from `mantis_project_version_table` WHERE id=$value ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      $version = $row->version;
         
      $query = "UPDATE `mantis_bug_table` SET target_version = '$version' WHERE id=$this->bugId ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   private function setCustomField($field_id, $value) {
      $query = "SELECT * FROM `mantis_custom_field_string_table` WHERE bug_id=$this->bugId AND field_id = $field_id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {

         $query2 = "UPDATE `mantis_custom_field_string_table` SET value = '$value' WHERE bug_id=$this->bugId AND field_id = $field_id";
      } else {
         $query2 = "INSERT INTO `mantis_custom_field_string_table` (`field_id`, `bug_id`, `value`) VALUES ('$field_id', '$this->bugId', '$value');";
      }
      $result = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * update DB and current instance
    * @param int $value
    */
   public function setExternalRef($value) {
      $extRefCustomField = Config::getInstance()->getValue(Config::id_customField_ExtId);
      $this->setCustomField($extRefCustomField, $value);
      $this->tcId = $value;
   }

   /**
    * update DB and current instance
    * @param type $value
    */
   public function setEffortEstim($value) {
      $extRefCustomField = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      $this->setCustomField($extRefCustomField, $value);
      $this->effortEstim = $value;
   }

   /**
    * update DB and current instance
    * @param type $value
    */
   public function setMgrEffortEstim($value) {
      $extRefCustomField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $this->setCustomField($extRefCustomField, $value);
      $this->mgrEffortEstim = $value;
   }

   /**
    * update DB and current instance
    * @param type $value
    */
   public function setDeadline($value) {
      $extRefCustomField = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $this->setCustomField($extRefCustomField, $value);
      $this->deadLine = $value;
   }
   
   /**
    * Get backlog at a specific date.
    * if date is nopt specified, return current backlog.
    * 
    * @param int $timestamp
    * @return int backlog or NULL if no backlog update found in history before timestamp
    */
   public function getBacklog($timestamp = NULL) {
      if (is_null($timestamp)) {
         if(!$this->customFieldInitialized) {
            $this->customFieldInitialized = true;
            $this->initializeCustomField();
         }
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getBacklog($this->bugId) : (from cache) ".$this->backlog);
         }
         return $this->backlog;
      }

      // find the field_name for the Backlog customField
      // (this should not be here, it's a general info that may be accessed elsewhere)
      $backlogCustomFieldId = Config::getInstance()->getValue(Config::id_customField_backlog);

      // TODO should be done only once... in Constants singleton ?
      // find in bug history when was the latest update of the Backlog before $timestamp
      $query = "SELECT * FROM `mantis_bug_history_table` ".
               "WHERE field_name = (SELECT name FROM `mantis_custom_field_table` WHERE id = $backlogCustomFieldId) ".
               "AND bug_id = '$this->bugId' ".
               "AND date_modified <= '$timestamp' ".
               "ORDER BY date_modified DESC LIMIT 1 ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         // the first result is the closest to the given timestamp
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
         $this->backlog = $row->new_value;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getBacklog(".date("Y-m-d H:i:s", $row->date_modified).") old_value = $row->old_value new_value $row->new_value userid = $row->user_id field_name = $row->field_name");
         }

      } else {
         // no backlog update found in history, return NULL
         // WARN: test the result with is_null() !!!
         $this->backlog = NULL;
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getBacklog($this->bugId) : (from DB) ".$this->backlog);
      }
      return $this->backlog;
   }

   /**
    * Get issues from an issue id list
    * @param array $issueIds The issue id list
    * @return Issue[] The issues
    */
   public static function getIssues(array $issueIds) {
      // avoid same ids in the list
      $issueIds = array_unique($issueIds);
      
      $issues = array();
      
      $newIssueIds = array();
      foreach($issueIds as $issueId) {
         if(IssueCache::getInstance()->exists($issueId)) {
            $issues[$issueId] = IssueCache::getInstance()->getIssue($issueId);
         } else {
            $newIssueIds[] = $issueId;
         }
      }
         
      if(count($newIssueIds) > 0) {
         $query = "SELECT * FROM `mantis_bug_table` " .
                  "WHERE id IN (".implode(', ', $newIssueIds).")";
         
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issues[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
         }
      }
         
      return $issues;
   }

   /**
    * @return TimeTrack
    */
   public function getFirstTimetrack() {
      $query = "SELECT * from `codev_timetracking_table` ".
               "WHERE bugid = $this->bugId ".
               "ORDER BY date ASC LIMIT 1";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $timeTrack = NULL;
      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
         $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
      }
      return $timeTrack;
   }

   /**
    * @return TimeTrack
    */
   public function getLatestTimetrack() {
      $query = "SELECT * from `codev_timetracking_table` ".
               "WHERE bugid = $this->bugId ".
               "ORDER BY date DESC LIMIT 1";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $timeTrack = NULL;
      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
         $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
      }
      return $timeTrack;
   }

   /**
    * @return int Mantis id
    */
   public function getId() {
      return $this->bugId;
   }

   /**
    * @return int Project id
    */
   public function getProjectId() {
      return $this->projectId;
   }

   /**
    * @return int Category id
    */
   public function getCategoryId() {
      return $this->categoryId;
   }

   /**
    * @return string Summary
    */
   public function getSummary() {
      return $this->summary;
   }

   /**
    * @return int Submission date
    */
   public function getDateSubmission() {
      return $this->dateSubmission;
   }

   /**
    * @return string Current status
    */
   public function getCurrentStatus() {
      return $this->currentStatus;
   }

   /**
    * @return int Handler id
    */
   public function getHandlerId() {
      return $this->handlerId;
   }

   public function getPriority() {
      return $this->priority;
   }

   public function getSeverity() {
      return $this->severity;
   }

   /**
    * @return int
    */
   public function getReporterId() {
      return $this->reporterId;
   }

   public function getVersion() {
      return $this->version;
   }

   public function getLastUpdate() {
      return $this->last_updated;
   }

   public function getMgrEffortEstim() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->mgrEffortEstim;
   }

   public function getEffortEstim() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->effortEstim;
   }

   public function getEffortAdd() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->effortAdd;
   }

   /**
    * @return int
    */
   public function getDeliveryDate() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->deliveryDate;
   }

   /**
    * @return int
    */
   public function getDeliveryId() {
      if(!$this->customFieldInitialized) {
         $this->customFieldInitialized = true;
         $this->initializeCustomField();
      }
      return $this->deliveryId;
   }

   /**
    * @return Status[]
    */
   public function getStatusList() {
      return $this->statusList;
   }

   /**
    * @return string Id / ExternalId or Id
    */
   public function getFormattedIds() {
      $formatedId = $this->getId();
      $externalId = $this->getTcId();
      if($externalId) {
         $formatedId .= ' / '.$externalId;
      }
      return $formatedId;
   }

}

Issue::staticInit();

?>
