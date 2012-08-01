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

// TODO Remove this import
include_once('classes/project_cache.class.php');

include_once('classes/config.class.php');
include_once('classes/config_mantis.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/issue_selection.class.php');
include_once('classes/jobs.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/project_version.class.php');

include_once('tools.php');

require_once('lib/log4php/Logger.php');

class Project {

  const type_workingProject   = 0;     // normal projects are type 0
  const type_sideTaskProject  = 1;     // SuiviOp must be type 1
  const type_noCommonProject  = 2;     // projects which have only assignedJobs (no common jobs) REM: these projects are not considered as sideTaskProjects
  const type_noStatsProject   = 3;     // projects that will be excluded from the statistics (ex: FDL)

  // REM: 'type' field in codev_project_category_table
  const cat_st_inactivity  = 1;
  const cat_st_onduty      = 2;
  const cat_st_incident    = 3;
  const cat_st_tools       = 4;
  const cat_st_workshop    = 5;
  const cat_mngt_provision = 6;
  const cat_mngt_regular   = 7;


  private $logger;

  public static $typeNames = array(self::type_workingProject  => "Project",
                                   self::type_noCommonProject => "Project (no common jobs)",
                                   self::type_noStatsProject  => "Project (stats excluded)",
                                   self::type_sideTaskProject => "SideTasks");

   var $id;
   var $name;
   var $description;
   private $enabled;
   var $jobList;
   var $categoryList;
   private $teamTypeList;

   private $bug_resolved_status_threshold;
   private $projectVersionList;

   private $progress;
   private $progressMgr;
   private $drift;
   private $driftMgr;

   /**
    * @var IssueSelection
    */
   private $issueSelection;

   /**
    * @var Issue[][] The issues cache
    */
   private $issuesCache;

   private $bugidListsCache;    // cache
   private $categoryCache; // cache
   private $versionCache; // cache

   /**
    * @var int[] The version date cache
    */
   private $versionDateCache;

   /**
    * @var bool[]
    */
   private static $existsCache;

   /**
    * @param int $id The project id
    * @param resource $details The project details
    * @throws Exception if $id = 0
    */
   public function __construct($id, $details) {
      $this->logger = Logger::getLogger(__CLASS__);

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a Project with id=0 is not allowed.");
         $this->logger->error("EXCEPTION Project constructor: ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;

      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The project details
    * @throws Exception If project doesn't exists
    */
   public function initialize($row = NULL) {
      if($row == NULL) {
         $query  = "SELECT * FROM `mantis_project_table` ".
                  "WHERE mantis_project_table.id = '".$this->id."'";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }

      $nbTuples = $row != FALSE;

      self::$existsCache[$this->id] = $nbTuples;

      if ($nbTuples) {
         $this->name        = $row->name;
         $this->description = $row->description;
         $this->enabled     = (1 == $row->enabled);

         // ---- if SideTaskProject get categories
         $query  = "SELECT * FROM `codev_project_category_table` WHERE project_id = '".$this->id."' ";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
         }

         $this->categoryList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result))   {
            $this->categoryList[$row->type] = $row->category_id;
         }

         #echo "DEBUG $this->name type=$this->type categoryList ".print_r($this->categoryList)." ----<br>\n";

         #$this->jobList     = $this->getJobList();
      } else {
         $e = new Exception("Constructor: Project $this->id does not exist in Mantis DB.");
         $this->logger->error("EXCEPTION Project constructor: ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }
   }


   /**
    * @param int $id project_id
    * @return boolean TRUE if Project exists in Mantis DB
    */
   public static function exists($id) {

      global $logger;

      if (NULL == $id) {
         $logger->warn("exists(): $id == NULL.");
         return false;
      }

      if (NULL == self::$existsCache) { self::$existsCache = array(); }

      if (NULL == self::$existsCache[$id]) {
      $query  = "SELECT COUNT(id) FROM `mantis_project_table` WHERE id=$id ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      #$found  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? true : false;
      $nbTuples  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

      if (1 != $nbTuples) {
         $logger->warn("exists($id): found $nbTuples items.");
      }
         self::$existsCache[$id] = (1 == $nbTuples);
      }
      return self::$existsCache[$id];
   }


   // -----------------------------------------------
   /**
   */
   public static function getName($projectId) {

   global $logger;

     $query  = "SELECT mantis_project_table.name ".
                "FROM `mantis_project_table` ".
                "WHERE mantis_project_table.id = $projectId ";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);

      return $row->name;
   }

   // -----------------------------------------------
   /**
    *
    * @param unknown_type $projectName
    */
   public static function createExternalTasksProject($projectName) {

      global $logger;

      //--- check if name exists
      $query  = "SELECT id FROM `mantis_project_table` WHERE name='$projectName'";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $projectid    = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      //--- create new Project
      $query = "INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `description`, `category_id`, `inherit_global`) ".
               "VALUES ('$projectName','50','1','50','10','$projectDesc','1','1');";
      $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $projectid = SqlWrapper::getInstance()->sql_insert_id();


      //--- when creating an new issue, the status is set to 'closed' (External Tasks have no workflow...)
      #REM first call to this function is in install step1, and $statusNames is set in step2. '90' is mantis default value for 'closed'
      $statusNames = NULL; # Config::getInstance()->getValue(Config::id_statusNames);
      $status_closed = (NULL != $statusNames) ? array_search('closed', $statusNames) : 90;
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_submit_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      //--- Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_assigned_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return $projectid;
   }

   // -----------------------------------------------
   /**
    *
    * @param unknown_type $projectName
    */
   public static function createSideTaskProject($projectName) {

      global $logger;
      $mgrEffortEstimCustomField  = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      $addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $remainingCustomField    = Config::getInstance()->getValue(Config::id_customField_remaining);
      $deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

      // check if name exists
      $query  = "SELECT id FROM `mantis_project_table` WHERE name='$projectName'";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $projectid    = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      // create new Project
      $query = "INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `description`, `category_id`, `inherit_global`) ".
               "VALUES ('$projectName','50','1','50','10','$projectDesc','1','0');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $projectid = SqlWrapper::getInstance()->sql_insert_id();


      // add custom fields BI,BS,RAE,DeadLine,DeliveryDate
      $query = "INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) ".
               "VALUES ('$mgrEffortEstimCustomField', '$projectid','2'), ".
                      "('$estimEffortCustomField',    '$projectid','3'), ".
                      "('$addEffortCustomField',      '$projectid','4'), ".
                      "('$remainingCustomField',      '$projectid','5'), ".
                      "('$deadLineCustomField',       '$projectid','6'), ".
                      "('$deliveryDateCustomField',   '$projectid','7');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // when creating an new issue, the status is set to 'closed' (External Tasks have no workflow...)
      #REM first call to this function is in install step1, and $statusNames is set in step2. '90' is mantis default value for 'closed'
      Config::setQuiet(true);
      $statusNames = Config::getInstance()->getValue(Config::id_statusNames);
      Config::setQuiet(false);
      $status_closed = (NULL != $statusNames) ? array_search('closed', $statusNames) : 90;
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_submit_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_assigned_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return $projectid;
   }

   /**
    * @param int $id  projectVersion id in mantis_category_table
    * @return string The category name
    */
   public static function getCategoryName($id) {

         $query = "SELECT name FROM `mantis_category_table` WHERE id= $id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $categoryName = SqlWrapper::getInstance()->sql_result($result, 0);

      return $categoryName;
   }

   /**
    * @param int $id  projectVersion id in mantis_category_table
    * @return string The category name
    */
   public static function getCategoryId($name) {

         $query = "SELECT id FROM `mantis_category_table` WHERE name='$name'";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $categoryId = SqlWrapper::getInstance()->sql_result($result, 0);

      return $categoryId;
   }


   /**
    * @return string The version name
    */
   public static function getProjectVersionName($id) {

         $query = "SELECT version FROM `mantis_project_version_table` WHERE id= $id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $versionName = SqlWrapper::getInstance()->sql_result($result, 0);

      return $versionName;
   }

   public function isEnabled() {
      return $this->enabled;
   }

   /**
    * @return array id => categoryName
    */
   public function getCategories() {

      if (NULL == $this->categoryCache) {

         $this->categoryCache = array();

         $query = "SELECT id, name FROM `mantis_category_table` WHERE project_id= $this->id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $this->categoryCache[$row->id] = $row->name;
         }
      }
      return $this->categoryCache;
   }

   /**
    * @return array id => versionName
    */
   public function getProjectVersions() {

      if (NULL == $this->versionCache) {

         $this->versionCache = array();

         $query = "SELECT id, version FROM `mantis_project_version_table` WHERE project_id= $this->id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $this->versionCache[$row->id] = $row->version;
         }
      }
      return $this->versionCache;
   }

   // -----------------------------------------------
   /**
    * Prepare a Mantis Project to be used with CoDevTT:
    * - check/add association to CoDevTT customFields
    */
   public static function prepareProjectToCodev($projectid) {
      
      /*
       WARN: prepareProjectToCodev cannot be a member method, it has to be static
             because if you call Project::__constructor on a project that has
             not been referenced in any team (codev_team_project_table), then the
             query in initialize() will fail.
      */

      $tcCustomField           = Config::getInstance()->getValue(Config::id_customField_ExtId);
      $mgrEffortEstim         = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      $addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $remainingCustomField    = Config::getInstance()->getValue(Config::id_customField_remaining);
      $deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
      #$deliveryIdCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryId);

      $existingFields = array();

     // find out which customFields are already associated
     $query = "SELECT field_id FROM `mantis_custom_field_project_table` WHERE    project_id = $projectid";
     $result = SqlWrapper::getInstance()->sql_query($query);
     if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
     }
     while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
      {
          $existingFields[] = $row->field_id;
      }

      $query = "INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) ".
               "VALUES ";

     $found = false;
     if (!in_array($tcCustomField, $existingFields))           { $query .= "('$tcCustomField',           '$projectid','101'),"; $found = true; }
     if (!in_array($mgrEffortEstim, $existingFields))          { $query .= "('$mgrEffortEstim',          '$projectid','102'),"; $found = true; }
     if (!in_array($estimEffortCustomField, $existingFields))  { $query .= "('$estimEffortCustomField',  '$projectid','103'),"; $found = true; }
     if (!in_array($addEffortCustomField, $existingFields))    { $query .= "('$addEffortCustomField',    '$projectid','104'),"; $found = true; }
     if (!in_array($remainingCustomField, $existingFields))    { $query .= "('$remainingCustomField',    '$projectid','105'),"; $found = true; }
     if (!in_array($deadLineCustomField, $existingFields))     { $query .= "('$deadLineCustomField',     '$projectid','106'),"; $found = true; }
     if (!in_array($deliveryDateCustomField, $existingFields)) { $query .= "('$deliveryDateCustomField', '$projectid','107'),"; $found = true; }
     #if (!in_array($deliveryIdCustomField, $existingFields))   { $query .= "('$deliveryIdCustomField',   '$this->id','108'),"; $found = true; }

     if ($found) {
          // replace last ',' with a ';' to finish query
          $pos = strlen($query) - 1;
          $query[$pos] = ';';

        // add missing custom fields
        $result = SqlWrapper::getInstance()->sql_query($query);
        if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
        }
     }

   }

   // -----------------------------------------------
   public function addCategoryProjManagement($catName) {
      return $this->addCategory(self::cat_mngt_regular, $catName);
   }
   public function addCategoryMngtProvision($catName) {
      return $this->addCategory(self::cat_mngt_provision, $catName);
   }
   public function addCategoryInactivity($catName) {
      return $this->addCategory(self::cat_st_inactivity, $catName);
   }
   public function addCategoryIncident($catName) {
      return $this->addCategory(self::cat_st_incident, $catName);
   }
   public function addCategoryTools($catName) {
      return $this->addCategory(self::cat_st_tools, $catName);
   }
   public function addCategoryWorkshop($catName) {
      return $this->addCategory(self::cat_st_workshop, $catName);
   }

   // -----------------------------------------------
   /**
    * 
    * @param string $catType in (Project::cat_mngt_regular, ...)
    * @param string $catName
    */
   private function addCategory($catType, $catName) {

      // create category for SideTask Project
      $formattedCatName = SqlWrapper::getInstance()->sql_real_escape_string($catName);
      $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) VALUES ('$this->id','0','$formattedCatName', '0');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
      }

      $catId = SqlWrapper::getInstance()->sql_insert_id();

      // ------
      $query = "SELECT * FROM `codev_project_category_table` WHERE project_id='$this->id' AND type='$catType';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
      }

      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         // should not happen...
         $query = "UPDATE `codev_project_category_table` SET category_id = $catId WHERE project_id ='$this->id' AND type='$catType';";
      } else {
         $query = "INSERT INTO `codev_project_category_table`  (`project_id`, `category_id`, `type`) VALUES ('$this->id','$catId','$catType');";
      }
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
      }

      // ------
      $this->categoryList[$catType] = $catId;

      return $catId;
   }


   // -----------------------------------------------
   public function addIssueProjManagement($issueSummary, $issueDesc=" ") {
      global $status_closed;
      $cat_id = $this->categoryList[self::cat_mngt_regular];
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
   }
   
   public function addIssueInactivity($issueSummary, $issueDesc=" ") {
      global $status_closed;
      $cat_id = $this->categoryList[self::cat_st_inactivity];
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
   }

   public function addIssueIncident($issueSummary, $issueDesc=" ") {
      global $status_closed;
      $cat_id = $this->categoryList[self::cat_st_incident];
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
   }

   public function addIssueTools($issueSummary, $issueDesc=" ") {
      global $status_closed;
      $cat_id = $this->categoryList[self::cat_st_tools];
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
   }

   public function addIssueWorkshop($issueSummary, $issueDesc=" ") {
      global $status_closed;
      $cat_id = $this->categoryList[self::cat_st_workshop];
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
   }

   // -----------------------------------------------
   private function addSideTaskIssue($catType, $issueSummary, $issueDesc) {

      global $status_closed;
      $cat_id = $this->categoryList["$catType"];

      $bugt_id = $this->addIssue($cat_id, $issueSummary, $issueDesc, $status_closed);
      return $bugt_id;
   }

   // -----------------------------------------------
   /*
    *
    */
   public function addIssue($cat_id, $issueSummary, $issueDesc, $issueStatus) {

      $today  = Tools::date2timestamp(date("Y-m-d"));
      $priority = 10;
      $reproducibility = 100;

      $formattedIssueDesc = SqlWrapper::getInstance()->sql_real_escape_string($issueDesc);
      $query = "INSERT INTO `mantis_bug_text_table`  (`description`) VALUES ('$formattedIssueDesc');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $bug_text_id = SqlWrapper::getInstance()->sql_insert_id();

      $formattedIssueSummary = SqlWrapper::getInstance()->sql_real_escape_string($issueSummary);
      $query = "INSERT INTO `mantis_bug_table`  (`project_id`, `category_id`, `summary`, `priority`, `reproducibility`, `status`, `bug_text_id`, `date_submitted`, `last_updated`) ".
               "VALUES ('$this->id','$cat_id','$formattedIssueSummary','$priority','$reproducibility','$issueStatus','$bug_text_id', '$today', '$today');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $bugt_id = SqlWrapper::getInstance()->sql_insert_id();

      $this->logger->debug("addIssue(): project_id=$this->id, category_id=$cat_id, priority=$priority, reproducibility=$reproducibility, status=$issueStatus, bug_text_id=$bug_text_id, date_submitted=$today, last_updated=$today");
      return $bugt_id;
   }


   public function getBugResolvedStatusThreshold() {
      if($this->bug_resolved_status_threshold == NULL) {
         // get $bug_resolved_status_threshold from mantis_config_table or codev_config_table if not found
         $query  = "SELECT get_project_resolved_status_threshold($this->id) ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->bug_resolved_status_threshold = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : NULL;
         #echo "DEBUG $this->name .getBugResolvedStatusThreshold() = $this->bug_resolved_status_threshold<br>\n";
      }
      return $this->bug_resolved_status_threshold;
   }


   /**
    * --- WORKAROUND --- DO NOT USE THIS METHOD ---
    *
    */
   private function getDefaultType() {

      $this->logger->error("WORKAROUND method getDefaultType() should not be used !");

      $query="SELECT type FROM `codev_team_project_table` WHERE codev_team_project_table.project_id = $this->id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $type = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : -1;

      return $type;
   }


   // -----------------------------------------------
   // Job list depends on project type:
   // if type=Project::type_sideTaskProject
   //    then only jobs for SideTasks are displayed.
   // if Project::type_workingProject
   //    then all jobs which codev_project_job_table.project_id = $this->id
   //                     OR codev_job_table.type = Job::type_commonJob (common jobs)
   public function getJobList($type = NULL) {
      $commonJobType       = Job::type_commonJob;

      $jobList = array();

      // TODO to be removed once $type m324 bug fixed
      if (!isset($type)) {
         $type = $this->getDefaultType();
         $e = new Exception("project $this->id type not specified ! (assume type=$type)");
         $this->logger->error("EXCEPTION Project.getJobList(): ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }

      // SPECIAL CASE: externalTasksProject is a type_noStatsProject that has only 'N/A' jobs
      if ($this->id == Config::getInstance()->getValue(Config::id_externalTasksProject)) {
         $type = self::type_sideTaskProject;
      }

      if (0 != $this->id) {

       switch ($type) {
          case self::type_sideTaskProject:
            $query  = "SELECT codev_job_table.id, codev_job_table.name ".
                     "FROM `codev_job_table`, `codev_project_job_table` ".
                     "WHERE codev_job_table.id = codev_project_job_table.job_id ".
                     "AND codev_project_job_table.project_id = $this->id";
             break;
          case self::type_noCommonProject:
            $query  = "SELECT codev_job_table.id, codev_job_table.name ".
                      "FROM `codev_job_table` ".
                      "LEFT OUTER JOIN  `codev_project_job_table` ".
                      "ON codev_job_table.id = codev_project_job_table.job_id ".
                      "WHERE (codev_project_job_table.project_id = $this->id)".
                       "ORDER BY codev_job_table.name ASC";
            break;
          case self::type_workingProject:  // no break;
          case self::type_noStatsProject:
               // all other projects
            $query  = "SELECT codev_job_table.id, codev_job_table.name ".
                      "FROM `codev_job_table` ".
                      "LEFT OUTER JOIN  `codev_project_job_table` ".
                      "ON codev_job_table.id = codev_project_job_table.job_id ".
                      "WHERE (codev_job_table.type = $commonJobType OR codev_project_job_table.project_id = $this->id)";
            break;
          case (-1):
             // WORKAROUND no type specified, return all available jobs
             $query  = "SELECT * FROM `codev_job_table`";
             break;
         default:
              echo "ERROR Project.getJobList($type): unknown project type ($type) !";
              $e = new Exception("getJobList($type): unknown project type ($type)");
              $this->logger->error("EXCEPTION TimeTracking constructor: ".$e->getMessage());
              $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
              return $jobList;
       }

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
       if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
            while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
            {
               $jobList[$row->id] = $row->name;
            }
       }
      }
      return $jobList;
   }

   /**
    * returns bugId list
    *
    * @param int $handler_id (if 0, all users)
    * @param bool $isHideResolved
    *
    * @return int[] : array[bugid]
    * @see Use ProjectCache::getInstance()->getProject($id)->getIssues($handler_id, $isHideResolved) if you need Issue[] and not just the IssueId[]
    */
   public function getBugidList($handler_id = 0, $isHideResolved = false) {

      if (NULL == $this->bugidListsCache) { $this->bugidListsCache = array(); }


      $key= ($isHideResolved) ? $handler_id.'_true' : $handler_id.'_false';

      if (NULL == $this->bugidListsCache[$key]) {

         $issueList = array();

         $query = "SELECT id FROM `mantis_bug_table` ".
                  "WHERE project_id=$this->id ";
         if (0 != $handler_id) {
            $query  .= "AND handler_id = $handler_id ";
         }
         if ($isHideResolved) {
            $query  .= "AND status < get_project_resolved_status_threshold(project_id) ";
         }

         $query  .= "ORDER BY id DESC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issueList[] = $row->id;
         }
         
         $this->bugidListsCache[$key] = $issueList;
      }
      return $this->bugidListsCache[$key];
   }

   /**
    * returns issues list
    * @param int $handler_id (if 0, all users)
    * @param bool $isHideResolved
    * @return Issue[] : array[]
    */
   public function getIssues($handler_id = 0, $isHideResolved = false) {

      if (NULL == $this->bugidListsCache) { $this->bugidListsCache = array(); }

      $key = ($isHideResolved) ? $handler_id.'_true' : $handler_id.'_false';

      if (!array_key_exists($key, $this->bugidListsCache)) {

         $issueList = array();

         $query = "SELECT * FROM `mantis_bug_table` ".
            "WHERE project_id=$this->id ";
         if (0 != $handler_id) {
            $query  .= "AND handler_id = $handler_id ";
         }
         if ($isHideResolved) {
            $query  .= "AND status < get_project_resolved_status_threshold(project_id) ";
         }

         $query  .= "ORDER BY id DESC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);;
         }

         $this->bugidListsCache[$key] = array_keys($issueList);
      }

      $bugidList = $this->getBugidList($handler_id, $isHideResolved);

      $issues = array();
      foreach($bugidList as $bugid) {
         $issues[] = IssueCache::getInstance()->getIssue($bugid);
      }
      return $issues;
   }

   /**
    * returns a list of team_id where the project is defined in
    *
    * @return array[teamid] = type
    */
   public function getTeamTypeList() {
      if (NULL == $this->teamTypeList) {
         $this->teamTypeList = array();
         $query = "SELECT * FROM `codev_team_project_table` WHERE project_id = $this->id ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
         {
            $this->logger->debug("getTeamTypeList: proj $row->project_id team $row->team_id type $row->type");
            $this->teamTypeList["$row->team_id"] = $row->type;
         }
      }
      return $this->teamTypeList;
   }



   // -----------------------------------------------
   /**
    * WARNING (best effort)
    *
    * the project type is specific to a team: a project
    * can be defined as "SideTask" in teamA AND as "NoStats" in
    * TeamB AND as "Project" in teamC.
    * - if you know the team, then do not use this method, use the Team class...
    *
    * This method will check all teams to which the project is associated.
    * - if ALL teams define the project as same type, then return type
    * if it is mixed, then an exception is thrown, because you cannot know.
    *
    * @param $teamidList the teams to check. if NULL, check all teams
    *
    * @return int project type
    *
    * @throws exception if cannot determinate
    */
    public function getProjectType($teamidList = NULL) {

       // --- init teams informations
       $this->getTeamTypeList();

       // if project not defined in any team, then how should I know if sideTask or not ?!
       if (0 == count($this->teamTypeList)) {
           $msg = "Could not determinate type for project $this->id (empty teamList)";
          $this->logger->warn("getProjectType(): EXCEPTION $msg");
          throw new Exception($msg);
       }

       // --- teams not specified, check all teams where project is defined.
       if (NULL == $teamidList) {
          $teamidList = array_keys($this->teamTypeList);
       }

       // --- compare results
       $globalType = NULL;
       foreach ($teamidList as $teamid) {

          if (NULL == $this->teamTypeList["$teamid"]) {
             // project not defined for this team, skip it.
             $this->logger->debug("getProjectType(): team $teamid skipped: Project $this->id not defined fot this team.");
             continue;
          }

          if (NULL == $globalType) {
             // first team: set value
             $globalType = $this->teamTypeList["$teamid"];
             
          } else {
             // next teams: compare to first team
             if ($globalType != $this->teamTypeList["$teamid"]) {
                 $msg = "Could not determinate type for project $this->id ! (depends on team)";
                 $this->logger->warn("getProjectType(): EXCEPTION $msg");
                throw new Exception($msg);
             }
          }
       }

       if ($this->logger->isDebugEnabled()) {
          $formattedList = implode(',', $teamidList);
          $this->logger->debug("getProjectType($formattedList): project $this->id type = $globalType");
       }
       return $globalType;
   }

   // -----------------------------------------------
   /**
    * WARNING (best effort)
    *
    * the project type is specific to a team: a project
    * can be defined as "SideTask" in teamA AND as "NoStats" in
    * TeamB AND as "Project" in teamC.
    * - if you know the team, please use Team::isSideTasksProject($projectid)
    *
    * This method will check all teams to which the project is associated.
    * - if ALL teams define the project as "SideTask" then return true.
    * - if NO  team  define the project as "SideTask" then return false.
    * if it is mixed, then an exception is thrown, because you cannot know.
    *
    * @param $teamidList the teams to check. if NULL, check all teams
    *
    * @throws exception if cannot determinate
    */
    public function isSideTasksProject($teamidList = NULL) {
       try {
          $type = $this->getProjectType($teamidList);
       } catch (Exception $e) {
          $this->logger->warn("isSideTasksProject(): ".$e->getMessage());
          throw $e;
       }
       return (self::type_sideTaskProject == $type);
    }


   // -----------------------------------------------
   /**
    * WARNING (best effort)
    *
    * the project type is specific to a team: a project
    * can be defined as "SideTask" in teamA AND as "NoStats" in
    * TeamB AND as "Project" in teamC.
    * - if you know the team, please use Team::isNoStatsProject($projectid)
    *
    * This method will check all teams to which the project is associated.
    * - if ALL teams define the project as "NoStats" then return true.
    * - if NO  team  define the project as "NoStats" then return false.
    * if it is mixed, then an exception is thrown, because you cannot know.
    *
    * @param $teamidList the teams to check. if NULL, check all teams
    *
    * @throws exception if cannot determinate
    */
   public function isNoStatsProject($teamidList = NULL) {
       try {
          $type = $this->getProjectType($teamidList);
       } catch (Exception $e) {
          $this->logger->warn("isNoStatsProject(): ".$e->getMessage());
          throw $e;
       }
       return (self::type_noStatsProject == $type);
   }

   /**
    * 
    * @return bool true if ExternalTasksProject
    */
   public function isExternalTasksProject() {

      return $this->id == Config::getInstance()->getValue(Config::id_externalTasksProject);
   }

   // -----------------------------------------------
   public function getManagementCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_mngt_regular];
   }
   public function getMngtProvisionCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_mngt_provision];
   }
   public function getIncidentCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_st_incident];
   }
   public function getInactivityCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_st_inactivity];
   }
   public function getToolsCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_st_tools];
   }
   public function getWorkshopCategoryId() {
      if (NULL == $this->categoryList) return NULL;
      return $this->categoryList[self::cat_st_workshop];
   }


   // -----------------------------------------------
   /**
    * get Workflow transitions from Mantis DB
    *
    * mantis_config_table - config_id='status_enum_workflow'
    *
    *
    */

   function getWorkflowTransitions() {

      $serialized = ConfigMantis::getInstance()->getValue('status_enum_workflow', $this->id);

      if ((NULL == $serialized) || ("" == $serialized)) {
         $this->logger->debug("No workflow defined for project $this->id");
         return NULL;
      }

      $unserialized = unserialize($serialized);

      $statusTitles = array();
      $wfTrans = array();
      // find all statuses
      foreach ( $unserialized as $line => $sList) {
         $sarr = Tools::doubleExplode(':', ',', $sList);
         ksort($sarr);
         $wfTrans[$line] = $sarr;
         foreach ($sarr as $status => $statusName) {
            $statusTitles[$status] = $statusName;
         }
      }

      // add titles
      ksort($statusTitles);
      $wfTrans[0] = $statusTitles;
      ksort($wfTrans);

      return $wfTrans;
   }

   /**
    *
    */
   function getProjectConfig() {

      //--- find all srcProj specific config
      $query = "SELECT config_id FROM `mantis_config_table` ".
                "WHERE project_id=$this->id ";
      $this->logger->debug("getProjectConfig: Src query=$query");

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
        echo "<span style='color:red'>ERROR: Query FAILED</span>";
        exit;
      }
      $configItems = array();

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
        $configItems[$row->config_id] = ConfigMantis::getInstance()->getValue($row->config_id, $this->id);;
      }

      return $configItems;
   }


   // -----------------------------------------------
   /**
    *  apply sourceProject config (workflow, thresholds, ...) to destProject
    *
    * @param strict if true, delete all destProject config
    *               if false, only replace config found in srcProject
    */
   static function cloneAllProjectConfig($srcProjectId, $destProjectId, $strict=true) {
      global $logger;


      //--- find all srcProj specific config
      $query = "SELECT DISTINCT config_id FROM `mantis_config_table` ".
               "WHERE project_id=$srcProjectId ";
      $logger->debug("cloneAllProjectConfig: Src query=$query");

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $srcConfigList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $srcConfigList[] = $row->config_id;
      }

      //--- remove all destProject config
      $formatedSrcConfigList = $formatedTeamMembers = implode( ', ', $srcConfigList);
      $logger->debug("cloneAllProjectConfig: SrcConfigList=$formatedSrcConfigList");

      $query = "DELETE FROM `mantis_config_table` ".
               "WHERE project_id=$destProjectId ";
      if (false == $strict) {
         // delete only config defined for srcProject
         $query .= "AND config_id IN ($formatedSrcConfigList) ";
      }
      $logger->debug("cloneAllProjectConfig: deleteQuery = $query");
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      //--- clone all srcProj config to destProj
      foreach ($srcConfigList as $cid) {

         $query = "INSERT INTO `mantis_config_table` ".
                  "(config_id, project_id, user_id, access_reqd, type, value) ".
                  "   (SELECT config_id, $destProjectId, user_id, access_reqd, type, value ".
                  "    FROM `mantis_config_table` ".
                  "    WHERE project_id=$srcProjectId ".
                  "    AND config_id='$cid') ";
         $logger->debug("cloneAllProjectConfig: cloneQuery = $query");
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }

      return "SUCCESS ! (".count($srcConfigList)." config items cloned.)";
   }


   /**
    * returns an array of ProjectVersion instances
    * key=version, value= ProjectVersion
    *
    *
    * @param unknown_type $team_id (TODO)
    * @return ProjectVersion[]
    */
   #public function getVersionList($team_id = NULL) {
   public function getVersionList() {
        if (NULL == $this->projectVersionList) {
           $this->projectVersionList = array();
           $issueList = $this->getIssues();
           foreach ($issueList as $issue) {
              $tagVersion = "VERSION_".$issue->getTargetVersion();

              if (!array_key_exists($tagVersion, $this->projectVersionList)) {
                 $this->projectVersionList[$tagVersion] = new ProjectVersion($this->id, $issue->getTargetVersion());
              }
              $this->projectVersionList[$tagVersion]->addIssue($issue->bugId);
           }

         ksort($this->projectVersionList);
        }

      return $this->projectVersionList;
   }

   /**
    * Get the version date
    * @param int $target_version The target version
    * @return int The version date
    */
   public function getVersionDate($target_version) {
      if(NULL == $this->versionDateCache) {
         $this->versionDateCache = array();
      }

      if(!array_key_exists($target_version,$this->versionDateCache)) {
         $query = "SELECT date_order FROM `mantis_project_version_table` ".
            "WHERE project_id=$this->id ".
            "AND version='$target_version'";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $targetVersionDate = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

         $this->logger->debug("$this->id target_version date = ".date("Y-m-d", $targetVersionDate));
         $this->versionDateCache[$target_version] = ($targetVersionDate <= 1) ? NULL : $targetVersionDate;
      }

      return $this->versionDateCache[$target_version];
   }

   /**
    * @return number
    */
   public function getProgress() {
      if (NULL == $this->progress) {
        $this->progress = $this->getIssueSelection()->getProgress();
      }
      return $this->progress;
   }

   /**
    * @return number
    */
   public function getProgressMgr() {
      if (NULL == $this->progressMgr) {
         $this->progressMgr = $this->getIssueSelection()->getProgressMgr();
      }
      return $this->progressMgr;
   }

   /**
    * @return IssueSelection
    */
   private function getIssueSelection() {
      if(NULL == $this->issueSelection) {
         $this->issueSelection = new IssueSelection($this->name);
         $issueList = $this->getIssues();
         foreach ($issueList as $issue) {
            try {
               $this->issueSelection->addIssue($issue->bugId);
            } catch (Exception $e) {
               $this->logger->warn("getIssueSelection: ".$e->getMessage());
            }
         }
      }
      return $this->issueSelection;
   }

   /**
    * @return array(nbDays, percent)
    */
   public function getDrift() {
      if (NULL == $this->drift) {
         $this->drift = $this->getIssueSelection()->getDrift();
      }
      return $this->drift;
   }

   /**
    * @return array(nbDays, percent)
    */
   public function getDriftMgr() {
      if (NULL == $this->driftMgr) {
         $this->driftMgr = $this->getIssueSelection()->getDriftMgr();
      }
      return $this->driftMgr;
   }

   /**
    * Get projets
    * @return array string[int] The projects : name[id]
    */
   public static function getProjects() {
      $query = 'SELECT id, name FROM `mantis_project_table` ORDER BY name;';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return NULL;
      }

      $projects = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $projects[$row->id] = $row->name;
      }

      return $projects;
   }

   /**
    * @static
    * @param int[] $projectIds
    * @return Issue[]
    */
   public static function getProjectIssues($projectIds) {
      $formatedProjList = implode( ', ', $projectIds);

      $query = "SELECT * FROM `mantis_bug_table` " .
         "WHERE project_id IN ($formatedProjList) " .
         "ORDER BY id DESC";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $issueList = array();
      if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issueList[] = IssueCache::getInstance()->getIssue($row->id, $row);
         }
         return $issueList;
      }
      return $issueList;
   }

}

?>
