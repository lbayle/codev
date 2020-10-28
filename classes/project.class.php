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

class Project extends Model {

   const type_workingProject = 0; // DEPRECATED normal projects are type 0
   const type_sideTaskProject = 1; // SuiviOp must be type 1
   const type_noCommonProject = 2; // projects which have only assignedJobs (no common jobs) REM: these projects are not considered as sideTaskProjects
   const type_noStatsProject = 3; // projects that will be excluded from the statistics (ex: FDL)

   // REM: 'type' field in codev_project_category_table
   const cat_st_inactivity  = 1;
   const cat_st_onduty = 2;
   const cat_st_incident = 3;
   const cat_st_tools = 4;
   const cat_st_workshop = 5;
   const cat_mngt_provision = 6; // DEPRECATED see CommandProvision class
   const cat_mngt_regular = 7;

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

   public static $typeNames = array(
      //self::type_workingProject  => "Project", // DEPRECATED
      self::type_noCommonProject => "Regular project", // previously "Project (no common jobs)"
      self::type_sideTaskProject => "SideTasks project",
      self::type_noStatsProject  => "External activity (excluded from statistics)",
   );

   public static $catTypeNames = array(
      self::cat_st_inactivity  => "Inactivity",
      self::cat_st_onduty => "OnDuty",
      self::cat_st_incident => "Incident",
      self::cat_st_tools => "Tools",
      self::cat_st_workshop => "Workshop",
      self::cat_mngt_regular => "Management");

   private $id;
   private $name;
   private $description;
   private $inherit_global;

   private $enabled;
   private $categoryList;
   private $teamTypeList;

   private $bug_resolved_status_threshold;
   private $bug_submit_status;
   private $projectVersionList;

   private $progress;
   private $progressMgr;
   private $drift;
   private $driftMgr;

   /**
    * @var IssueSelection
    */
   private $issueSelection;

   private $bugidListsCache; // cache
   private $categoryCache; // cache
   private $versionCache; // cache
   private $issueTooltipFieldsCache;

   /**
    * @var int[] The version date cache
    */
   private $versionDateCache;

   /**
    * @var bool[]
    */
   private static $existsCache;

   private static $categories = array();

   /**
    * @param int $id The project id
    * @param resource $details The project details
    * @throws Exception if $id = 0
    */
   public function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a Project with id=0 is not allowed.");
         self::$logger->error("EXCEPTION Project constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM {project} ".
                  "WHERE id = ".$sql->db_param();
          $q_params[]=$this->id;

         $result = $sql->sql_query($query, $q_params);
         $row = $sql->fetchObject($result);
      }

      $nbTuples = ($row != FALSE);

      self::$existsCache[$this->id] = $nbTuples;

      if ($nbTuples) {
         $this->name = $row->name;
         $this->description = $row->description;
         $this->enabled = (1 == $row->enabled);
         $this->inherit_global = (1 == $row->inherit_global);
      } else {
         $e = new Exception("Constructor: Project $this->id does not exist in Mantis DB.");
         self::$logger->error("EXCEPTION Project constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }
   }

   /**
    * if SideTaskProject get categories
    */
   public function initializeCategories() {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * FROM codev_project_category_table WHERE project_id = " . $sql->db_param();
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params);

      $this->categoryList = array();
      while ($row = $sql->fetchObject($result)) {
         $this->categoryList[$row->type] = $row->category_id;
      }
   }

   /**
    * @param int $id project_id
    * @return boolean TRUE if Project exists in Mantis DB
    */
   public static function exists($id) {
      if (NULL == $id) {
         self::$logger->warn("exists(): id == NULL.");
         return FALSE;
      }

      if (NULL == self::$existsCache) { self::$existsCache = array(); }

      if (NULL == self::$existsCache[$id]) {
         $sql = AdodbWrapper::getInstance();
         $query  = "SELECT COUNT(id) FROM {project} WHERE id=".$sql->db_param();
         $q_params[]=$id;
         $result = $sql->sql_query($query, $q_params);

         #$found  = (0 != $sql->getNumRows($result)) ? true : false;
         $nbTuples  = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;

         if (1 != $nbTuples) {
            self::$logger->warn("exists($id): found $nbTuples items.");
         }
         self::$existsCache[$id] = (1 == $nbTuples);
      }
      return self::$existsCache[$id];
   }

   /**
    *
    * @static
    * @param $projectName
    * @return projectId or -1 if not found or FALSE on error
    */
   public static function getIdFormName($projectName) {
      if (NULL == $projectName) {
         self::$logger->warn("getIdFormName(): projectName == NULL.");
         return false;
      }
      // check if name exists
      $sql = AdodbWrapper::getInstance();
      $query  = "SELECT id FROM {project} WHERE name=".$sql->db_param();
      $result = $sql->sql_query($query, array($projectName));

      $projectid = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : -1;

      // no error, but -1 if not found
      return $projectid;
   }

   /**
    * Create project, categories & assign N/A job
    * @static
    * @param $projectName
    * @return int|string
    */
   public static function createExternalTasksProject($projectName, $projectDesc) {
      // check if name exists
      $sql = AdodbWrapper::getInstance();
      $query  = "SELECT id FROM {project} WHERE name=".$sql->db_param();
      $result = $sql->sql_query($query, array($projectName));

      $projectid = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : -1;
      if (-1 != $projectid) {
         self::$logger->error("createExternalTasksProject($projectName): Project name already exist");
         return $projectid;
      }

      // create new Project
      $query = "INSERT INTO {project} (name, status, enabled, view_state, access_min, description, category_id, inherit_global) ".
               "VALUES (".$sql->db_param().",50,1,50,10,".$sql->db_param().",1,1);";
      $sql->sql_query($query, array($projectName, $projectDesc));

      $projectid = AdodbWrapper::getInstance()->getInsertId();

      // when creating an new issue, the status is set to 'closed' (External Tasks have no workflow...)
      #REM first call to this function is in install step1, and $statusNames is set in step2. '90' is mantis default value for 'closed'
      $statusNames = NULL; # Constants::$statusNames;
      $status_closed = (NULL != $statusNames) ? array_search('closed', $statusNames) : 90;
      $query = "INSERT INTO {config} (config_id,project_id,user_id,access_reqd,type,value) ".
               "VALUES ('bug_submit_status', ".$sql->db_param().", 0, 90, 1, ".$sql->db_param().")";
      $sql->sql_query($query, array($projectid, $status_closed));

      // Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO {config} (config_id,project_id,user_id,access_reqd,type,value) ".
               "VALUES ('bug_assigned_status', ".$sql->db_param().", 0, 90, 1, ".$sql->db_param().")";
      $sql->sql_query($query, array($projectid, $status_closed));

      // create leave category
      $query = "INSERT INTO {category}  (project_id, user_id, name, status) ".
              "VALUES (".$sql->db_param().",'0','Leave', '0');";
      $sql->sql_query($query, array($projectid));
      $catLeaveId = $sql->getInsertId();

      // create otherInternal category
      $query = "INSERT INTO {category}  (project_id, user_id, name, status) ".
              "VALUES (".$sql->db_param().",'0','Other activity', '0');";
      $sql->sql_query($query, array($projectid));
      $catOtherInternalId = AdodbWrapper::getInstance()->getInsertId();

      // --- update ExternalTasksProject in codev_config_table
      Config::getInstance()->setValue(Config::id_externalTasksProject, $projectid, Config::configType_int, $projectDesc);
      Config::getInstance()->setValue(Config::id_externalTasksCat_leave, $catLeaveId, Config::configType_int);
      Config::getInstance()->setValue(Config::id_externalTasksCat_otherInternal, $catOtherInternalId, Config::configType_int);

      // --- assign ExternalTasksProject specific Job
      #REM: 'N/A' job_id = 1, created by SQL file
      Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);

      return $projectid;
   }

   /**
    * @static
    * @param string $projectName
    * @return int|string
    */
   public static function createSideTaskProject($projectName) {
      $mgrEffortEstimCustomField  = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $estimEffortCustomField = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      #$addEffortCustomField = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $backlogCustomField = Config::getInstance()->getValue(Config::id_customField_backlog);
      $deadLineCustomField = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

      $sql = AdodbWrapper::getInstance();

      // check if name exists
      $query  = "SELECT id FROM {project} WHERE name=".$sql->db_param();
      $result = $sql->sql_query($query, array($projectName));

      $projectid = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      // create new Project
      $query = "INSERT INTO {project} (name, status, enabled, view_state, access_min, description, category_id, inherit_global) ".
         "VALUES (".$sql->db_param().",50,1,50,10,".$sql->db_param().",1,0);";
      $sql->sql_query($query, array($projectName, ''));
      $projectid = AdodbWrapper::getInstance()->getInsertId();

      // add custom fields BI,BS,RAE,DeadLine,DeliveryDate
      $query = "INSERT INTO {custom_field_project} (field_id, project_id, sequence) ".
               "VALUES (".$sql->db_param().", ".$sql->db_param().",'2'), ".
               "(".$sql->db_param().", ".$sql->db_param().",'3'), ".
              #"(".$sql->db_param().", ".$sql->db_param().",'4'), ". // addEffort DEPRECATED since v1.4.0
               "(".$sql->db_param().", ".$sql->db_param().",'5'), ".
               "(".$sql->db_param().", ".$sql->db_param().",'6'), ".
               "(".$sql->db_param().", ".$sql->db_param().",'7');";
      $q_params[]=$mgrEffortEstimCustomField;
      $q_params[]=$projectid;
      $q_params[]=$estimEffortCustomField;
      $q_params[]=$projectid;
      $q_params[]=$backlogCustomField;
      $q_params[]=$projectid;
      $q_params[]=$deadLineCustomField;
      $q_params[]=$projectid;
      $q_params[]=$deliveryDateCustomField;
      $q_params[]=$projectid;

      $sql->sql_query($query, $q_params);

      // when creating an new issue, the status is set to 'closed' (External Tasks have no workflow...)
      #REM first call to this function is in install step1, and $statusNames is set in step2. '90' is mantis default value for 'closed'
      Config::setQuiet(TRUE);
      $statusNames = Constants::$statusNames;
      Config::setQuiet(FALSE);
      $status_closed = (NULL != $statusNames) ? array_search('closed', $statusNames) : 90;
      $query = "INSERT INTO {config} (config_id,project_id,user_id,access_reqd,type,value) ".
               "VALUES ('bug_submit_status',  ".$sql->db_param().",'0', '90', '1', ".$sql->db_param().")";
      $sql->sql_query($query, array($projectid, $status_closed));

      // Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO {config} (config_id,project_id,user_id,access_reqd,type,value) ".
               "VALUES ('bug_assigned_status',  ".$sql->db_param().",'0', '90', '1', ".$sql->db_param().")";
      $sql->sql_query($query, array($projectid, $status_closed));

      return $projectid;
   }


   /**
    * @param int $id  projectVersion id in mantis_category_table
    * @return string The category name
    */
   public static function getCategoryName($id) {
      if(!array_key_exists($id, self::$categories)) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT name FROM {category} WHERE id = ".$sql->db_param();
         $q_params[]=$id;
         $result = $sql->sql_query($query, $q_params);
         $categoryName = $sql->sql_result($result, 0);

         self::$categories[$id] = $categoryName;
      }

      return self::$categories[$id];
   }

   /**
    * @static
    * @param int $name
    * @return string The category name
    */
   public static function getCategoryId($name) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id FROM {category} WHERE name=".$sql->db_param();
      $q_params[]=$name;
      $result = $sql->sql_query($query, $q_params);

      return $sql->sql_result($result);
   }

   /**
    * @static
    * @param int $id version id
    * @return string The version name
    */
   public static function getProjectVersionName($id) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT version FROM {project_version} WHERE id = ".$sql->db_param();
      $q_params[]=$id;
      $result = $sql->sql_query($query, $q_params);

      return $sql->sql_result($result);
   }

   /**
    *
    * @param type $project_id
    * @param type $version literal ex: "V15.1"
    * @return string The version timestamp
    */
   public static function getProjectVersionTimestamp($project_id, $version) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT date_order FROM {project_version} ".
               ' WHERE project_id = '.$sql->db_param().
               ' AND version = '.$sql->db_param();
      $q_params[]=$project_id;
      $q_params[]=$version;
      $result = $sql->sql_query($query, $q_params);
      return $sql->sql_result($result);
   }

   public function isEnabled() {
      return $this->enabled;
   }

   /**
    * @param $withGlobal
    * @param $withInherited
    * @return string[] id => categoryName
    */
   public function getCategories($withGlobal = true, $withInherited = true) {

      // Note: cache is deactivated because it is rarely used.
      //if (is_null($this->categoryCache)) {
         $this->categoryCache = array();

         $sql = AdodbWrapper::getInstance();

         // find out if global categories must be added
         $formattedProjects = $this->id;
         if ($withGlobal && $this->inherit_global) {
            $formattedProjects .= ',0';
         }

         if ($withInherited) {
            $queryParents = "SELECT parent_id FROM {project_hierarchy} ".
                    "WHERE child_id = ".$sql->db_param().
                    " AND inherit_parent = 1 ";
            $resultParents = $sql->sql_query($queryParents, array($this->id));

            while($row = $sql->fetchObject($resultParents)) {
               $formattedProjects .= ','.$row->parent_id;
            }
         }

         $query = "SELECT id, name FROM {category} WHERE project_id IN (".$formattedProjects.")";
         $result = $sql->sql_query($query);

         while($row = $sql->fetchObject($result)) {
            $this->categoryCache[$row->id] = $row->name;
         }
      //}
      return $this->categoryCache;
   }

   /**
    * @return sitrng[] id => versionName
    */
   public function getProjectVersions($withObsolete=TRUE) {
      if (NULL == $this->versionCache) {
         $this->versionCache = array();

         $sql = AdodbWrapper::getInstance();
         $query = "SELECT id, version FROM {project_version} WHERE project_id = ".$sql->db_param();
         if (FALSE == $withObsolete) {
            $query .= " AND obsolete=0";
         }
         $result = $sql->sql_query($query, array($this->id));

         while($row = $sql->fetchObject($result)) {
            $this->versionCache[$row->id] = $row->version;
         }
      }
      return $this->versionCache;
   }

   /**
    * Prepare a Mantis Project to be used with CoDevTT:
    * - check/add association to CoDevTT customFields
    * @static
    * @param int $projectid
    */
   public static function prepareProjectToCodev($projectid) {
      /*
       WARN: prepareProjectToCodev cannot be a member method, it has to be static
             because if you call Project::__constructor on a project that has
             not been referenced in any team (codev_team_project_table), then the
             query in initialize() will fail.
      */

      // find out which customFields are already associated
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT field_id FROM {custom_field_project} WHERE project_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($projectid));

      $tcCustomField = Config::getInstance()->getValue(Config::id_customField_ExtId);
      $mgrEffortEstim = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
      $estimEffortCustomField = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      #$addEffortCustomField = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $backlogCustomField = Config::getInstance()->getValue(Config::id_customField_backlog);
      $deadLineCustomField = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);
      #$deliveryIdCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryId);
      $typeCustomField = Config::getInstance()->getValue(Config::id_customField_type);
      $customField_dailyPrice = Config::getInstance()->getValue(Config::id_customField_dailyPrice);

      $existingFields = array();
      while($row = $sql->fetchObject($result)) {
         $existingFields[] = $row->field_id;
      }

      $query = "INSERT INTO {custom_field_project} (field_id, project_id, sequence) ".
               "VALUES ";

      $found = FALSE;
      if (!in_array($typeCustomField, $existingFields))         {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'100'),"; $found = TRUE;
         $q_params[]=$typeCustomField;
         $q_params[]=$projectid;
      }
      /* Note: 101 is reserved for CodevTT_Type_Ano */
      if (!in_array($tcCustomField, $existingFields))           {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'102'),"; $found = TRUE;
         $q_params[]=$tcCustomField;
         $q_params[]=$projectid;
      }
      if (!in_array($mgrEffortEstim, $existingFields))          {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'103'),"; $found = TRUE;
         $q_params[]=$mgrEffortEstim;
         $q_params[]=$projectid;
      }
      if (!in_array($estimEffortCustomField, $existingFields))  {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'104'),"; $found = TRUE;
         $q_params[]=$estimEffortCustomField;
         $q_params[]=$projectid;
      }
      if (!in_array($backlogCustomField, $existingFields))      {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'106'),"; $found = TRUE;
         $q_params[]=$backlogCustomField;
         $q_params[]=$projectid;
      }
      if (!in_array($deadLineCustomField, $existingFields))     {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'107'),"; $found = TRUE;
         $q_params[]=$deadLineCustomField;
         $q_params[]=$projectid;
      }
      if (!in_array($deliveryDateCustomField, $existingFields)) {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'108'),"; $found = TRUE;
         $q_params[]=$deliveryDateCustomField;
         $q_params[]=$projectid;
      }
/*  This field should not be added by default, as it is only used for a very specific plugin (SellingPriceForPeriod)
      if (!in_array($customField_dailyPrice, $existingFields)) {
         $query .= "(".$sql->db_param().", ".$sql->db_param().",'109'),"; $found = TRUE;
         $q_params[]=$customField_dailyPrice;
         $q_params[]=$projectid;
      }
 */
      #if (!in_array($deliveryIdCustomField, $existingFields))   { $query .= "('$deliveryIdCustomField',   '$this->id','109'),"; $found = TRUE; }

      if ($found) {
         // replace last ',' with a ';' to finish query
         $pos = strlen($query) - 1;
         $query[$pos] = ';';

         // add missing custom fields
         $sql->sql_query($query, $q_params);
      }
   }

   public function addCategoryProjManagement($catName) {
      return $this->addCategory(self::cat_mngt_regular, $catName);
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

   /**
    * @param string $catType in (Project::cat_mngt_regular, ...)
    * @param string $catName
    * @return int
    */
   private function addCategory($catType, $catName) {
      // create category for SideTask Project
      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO {category}  (project_id, user_id, name, status)"
         . " VALUES (".$sql->db_param().",'0',".$sql->db_param().", '0')";
      $sql->sql_query($query, array($this->id, $catName));

      $catId = $sql->getInsertId();

      $query = "SELECT * FROM codev_project_category_table"
         . " WHERE project_id=".$sql->db_param()
         . " AND type=".$sql->db_param();
      $result = $sql->sql_query($query, array($this->id, $catType));

      if (0 != $sql->getNumRows($result)) {
         // should not happen...
         $query = "UPDATE codev_project_category_table"
            . " SET category_id = ".$sql->db_param()
            . " WHERE project_id =".$sql->db_param()
            . " AND type=".$sql->db_param();
         $q_params[]=$catId;
         $q_params[]=$this->id;
         $q_params[]=$catType;
      } else {
         $query = "INSERT INTO codev_project_category_table  (project_id, category_id, type)"
            . " VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
         $q_params[]=$this->id;
         $q_params[]=$catId;
         $q_params[]=$catType;
      }
      $sql->sql_query($query, $q_params);

      $this->categoryList[$catType] = $catId;

      return $catId;
   }

   public function addIssueProjManagement($issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory(self::cat_mngt_regular);
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
   }

   public function addIssueInactivity($issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory(self::cat_st_inactivity);
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
   }

   public function addIssueIncident($issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory(self::cat_st_incident);
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
   }

   public function addIssueTools($issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory(self::cat_st_tools);
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
   }

   public function addIssueWorkshop($issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory(self::cat_st_workshop);
      return $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
   }

   private function addSideTaskIssue($catType, $issueSummary, $issueDesc="...") {
      $cat_id = $this->getCategory($catType);
      $bugt_id = $this->addIssue($cat_id, $issueSummary, $issueDesc, Constants::$status_closed);
      return $bugt_id;
   }

   public function addIssue($cat_id, $issueSummary, $issueDesc, $issueStatus) {
      $today  = Tools::date2timestamp(date("Y-m-d"));
      $priority = 10;
      $reproducibility = 100;

      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO {bug_text}  (description, steps_to_reproduce, additional_information)"
         . " VALUES (".$sql->db_param().", '', '')";
      $sql->sql_query($query, array($issueDesc));
      $bug_text_id = $sql->getInsertId();

      $query = "INSERT INTO {bug} (project_id, category_id, summary, priority, reproducibility, status, bug_text_id, date_submitted, last_updated) ".
               "VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
      $q_params[]=$this->id;
      $q_params[]=$cat_id;
      $q_params[]=$issueSummary;
      $q_params[]=$priority;
      $q_params[]=$reproducibility;
      $q_params[]=$issueStatus;
      $q_params[]=$bug_text_id;
      $q_params[]=$today;
      $q_params[]=$today;

      $sql->sql_query($query, $q_params);
      $bugt_id = $sql->getInsertId();

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("addIssue(): project_id=$this->id, category_id=$cat_id, priority=$priority, reproducibility=$reproducibility, status=$issueStatus, bug_text_id=$bug_text_id, date_submitted=$today, last_updated=$today");
      }
      return $bugt_id;
   }

   public function getBugResolvedStatusThreshold() {
      if($this->bug_resolved_status_threshold == NULL) {
         // get $bug_resolved_status_threshold from mantis_config_table or codev_config_table if not found
         $sql = AdodbWrapper::getInstance();
         $query  = "SELECT get_project_resolved_status_threshold(".$sql->db_param().") ";
         $result = $sql->sql_query($query, array($this->id));
         $this->bug_resolved_status_threshold = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : NULL;
         #echo "DEBUG $this->name .getBugResolvedStatusThreshold() = $this->bug_resolved_status_threshold<br>\n";
      }
      return $this->bug_resolved_status_threshold;
   }

   /**
    * get status on issue creation
    *
    * @return type
    */
   public function getBugSubmitStatus() {
      if(is_null($this->bug_submit_status)) {

         $sql = AdodbWrapper::getInstance();
         $query  = "SELECT value FROM {config} ".
                 "WHERE config_id = 'bug_submit_status'".
                 " AND project_id =".$sql->db_param();

         $result = $sql->sql_query($query, array($this->id), TRUE, 1); // LIMIT 1

         if (0 != $sql->getNumRows($result)) {
            $this->bug_submit_status = $sql->sql_result($result, 0);
         } else {
            $this->bug_submit_status = Constants::$status_new;
         }
         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug("$this->name getBugSubmitStatus() = $this->bug_submit_status");
         }
         #echo "DEBUG $this->name getBugSubmitStatus() = $this->bug_submit_status<br>\n";
      }
      return $this->bug_submit_status;

   }

   /**
    * --- WORKAROUND --- DO NOT USE THIS METHOD ---
    * @return int|string
    */
   private function getDefaultType() {
      self::$logger->error("WORKAROUND method getDefaultType() should not be used !");

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT type FROM codev_team_project_table WHERE project_id = ".$sql->db_param();
      $result = $sql->sql_query($query, array($this->id));

      return (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : -1;
   }

   /**
    * Job list depends on project type:
    * if type=Project::type_sideTaskProject
    * then only jobs for SideTasks are displayed.
    * if Project::type_workingProject
    * then all jobs which codev_project_job_table.project_id = $this->id
    * OR codev_job_table.type = Job::type_commonJob (common jobs)
    * @param int $type
    * @return string[]
    */
   public function getJobList($type = NULL) {
      $commonJobType = Job::type_commonJob;
      $sql = AdodbWrapper::getInstance();

      $jobList = array();

      // TODO to be removed once $type m324 bug fixed
      if (!isset($type)) {
         $type = $this->getDefaultType();
         $e = new Exception("project $this->id type not specified ! (assume type=$type)");
         self::$logger->error("EXCEPTION Project.getJobList(): ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }

      // SPECIAL CASE: externalTasksProject is a type_noStatsProject that has only 'N/A' jobs
      if ($this->id == Config::getInstance()->getValue(Config::id_externalTasksProject)) {
         $type = self::type_sideTaskProject;
      }

      if (0 != $this->id) {
         switch ($type) {
            case self::type_sideTaskProject:
               $query = "SELECT job.id, job.name ".
                        " FROM codev_job_table as job ".
                        " JOIN codev_project_job_table as project_job ON job.id = project_job.job_id ".
                        " WHERE project_job.project_id = ".$sql->db_param();
               $q_params[]=$this->id;
               break;
            case self::type_workingProject:  // no break;
            case self::type_noStatsProject:  // no break;
            case self::type_noCommonProject:
               $query = "SELECT job.id, job.name ".
                        " FROM codev_job_table as job ".
                        " LEFT OUTER JOIN codev_project_job_table as project_job ".
                        " ON job.id = project_job.job_id ".
                        " WHERE project_job.project_id =  ".$sql->db_param().
                        " ORDER BY job.name ASC";
               $q_params[]=$this->id;
               break;
            case (-1):
               // WORKAROUND no type specified, return all available jobs
               $query  = "SELECT * FROM codev_job_table";
               break;
            default:
               echo "ERROR Project.getJobList($type): unknown project type ($type) !";
               $e = new Exception("getJobList($type): unknown project type ($type)");
               self::$logger->error("EXCEPTION getJobList: ".$e->getMessage());
               self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
               return $jobList;
         }

         $result = $sql->sql_query($query, $q_params);

         if (0 != $sql->getNumRows($result)) {
            while($row = $sql->fetchObject($result)) {
               $jobList[$row->id] = $row->name;
            }
         }
      }
      return $jobList;
   }

   /**
    * returns issues list
    * @param int $handler_id (if 0, all users)
    * @param bool $isHideResolved
    * @param int $hideStatusAndAbove if 0:hide none
    * @return Issue[] : array[]
    */
   public function getIssues($handler_id = 0, $isHideResolved = FALSE, $hideStatusAndAbove = 0, $isHideForbidenStatus = 0, $teamid = 0) {

      if (NULL == $this->bugidListsCache) { $this->bugidListsCache = array(); }

      $key = $handler_id;
      $key .= ($isHideResolved) ? '_true' : '_false';
      $key .= '_'.$hideStatusAndAbove;
      $key .= '_'.$isHideForbidenStatus;

      if (!array_key_exists($key, $this->bugidListsCache)) {
         $issueList = array();
         $sql = AdodbWrapper::getInstance();

         $query = "SELECT * FROM {bug} ".
                  "WHERE project_id= ".$sql->db_param();
         $q_params[]=$this->id;
         if (0 != $handler_id) {
            $query  .= " AND handler_id =  ".$sql->db_param();
            $q_params[]=$handler_id;
         }
         if ($isHideResolved) {
            $query  .= " AND status < get_project_resolved_status_threshold(project_id) ";
         }
         if (0 != $hideStatusAndAbove) {
            $query  .= " AND status <  ".$sql->db_param();
            $q_params[]=$hideStatusAndAbove;
         }
         if (($isHideForbidenStatus) && (0 != $teamid)) {
            $team = TeamCache::getInstance()->getTeam($teamid);
            $ttForbidenStatusList = $team->getTimetrackingForbidenStatusList($this->id);
            if (!empty($ttForbidenStatusList)) {
               $str = implode(',', array_keys($ttForbidenStatusList));
               $query  .= " AND status NOT IN (".$str.') ';
            }
         }
         $query  .= " ORDER BY id DESC";
         $result = $sql->sql_query($query, $q_params);

         while($row = $sql->fetchObject($result)) {
            $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);;
         }
         $this->bugidListsCache[$key] = array_keys($issueList);
      } else {
         $issueList = Issue::getIssues($this->bugidListsCache[$key]);
      }
      return $issueList;
   }

   /**
    * returns a list of team_id where the project is defined in
    *
    * @return string[] array[teamid] = type
    */
   public function getTeamTypeList() {
      if (NULL == $this->teamTypeList) {
         $this->teamTypeList = array();
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_team_project_table WHERE project_id = ".$sql->db_param();
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);

         while($row = $sql->fetchObject($result)) {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getTeamTypeList: proj $row->project_id team $row->team_id type $row->type");
            }
            $this->teamTypeList["$row->team_id"] = $row->type;
         }
      }
      return $this->teamTypeList;
   }

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
    * @param int[] $teamidList the teams to check. if NULL, check all teams
    *
    * @return int project type
    * @throws Exception if cannot determinate
    */
   public function getProjectType(array $teamidList = NULL) {
      // init teams informations
      $this->getTeamTypeList();

      // if project not defined in any team, then how should I know if sideTask or not ?!
      if (0 == count($this->teamTypeList)) {
         $msg = "Could not determinate type for project $this->id (empty teamList)";
         self::$logger->warn("getProjectType(): EXCEPTION $msg");
         throw new Exception($msg);
      }

      // teams not specified, check all teams where project is defined.
      if (NULL == $teamidList) {
         $teamidList = array_keys($this->teamTypeList);
      }

      // compare results
      $globalType = NULL;
      foreach ($teamidList as $teamid) {
         if (!array_key_exists($teamid,$this->teamTypeList)) {
            // project not defined for this team, skip it.
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getProjectType(): team $teamid skipped: Project $this->id not defined fot this team.");
            }
            continue;
         }

         if (NULL == $globalType) {
            // first team: set value
            $globalType = $this->teamTypeList["$teamid"];

         } else {
            // next teams: compare to first team
            if ($globalType != $this->teamTypeList["$teamid"]) {
               $msg = "Could not determinate type for project $this->id ! (depends on team)";
               self::$logger->warn("getProjectType(): EXCEPTION $msg");
               throw new Exception($msg);
            }
         }
      }

      if (self::$logger->isDebugEnabled()) {
         $formattedList = implode(',', $teamidList);
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getProjectType($formattedList): project $this->id type = $globalType");
         }
      }
      return $globalType;
   }

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
    * @param int[] $teamidList the teams to check. if NULL, check all teams
    * @return bool
    * @throws Exception if cannot determinate
    */
   public function isSideTasksProject(array $teamidList = NULL) {
      try {
         $type = $this->getProjectType($teamidList);
      } catch (Exception $e) {
         self::$logger->warn("isSideTasksProject(): ".$e->getMessage());
         throw $e;
      }
      return (self::type_sideTaskProject == $type);
   }

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
    * @param int[] $teamidList the teams to check. if NULL, check all teams
    * @return bool
    * @throws Exception if cannot determinate
    */
   public function isNoStatsProject(array $teamidList = NULL) {
      try {
         $type = $this->getProjectType($teamidList);
      } catch (Exception $e) {
         self::$logger->warn("isNoStatsProject(): ".$e->getMessage());
         throw $e;
      }
      return (self::type_noStatsProject == $type);
   }

   /**
    * @return bool true if ExternalTasksProject
    */
   public function isExternalTasksProject() {
      return $this->id == Config::getInstance()->getValue(Config::id_externalTasksProject);
   }

   /**
    * get Workflow transitions from Mantis DB
    *
    * mantis_config_table - config_id='status_enum_workflow'
    *
    * NOTE: on a fresh mantis install, status_enum_workflow does not exist in the DB !
    *
    * @return array[] of serialized status list
    */
   function getWorkflowTransitions() {
      $sql = AdodbWrapper::getInstance();

      // ORDER BY is important, it will ensure to return the project specific value before the generic (0) value
      $query = "SELECT * FROM {config} ".
               "WHERE project_id IN (0, ".$sql->db_param().") ".
               " AND config_id = 'status_enum_workflow' ".
               " ORDER BY project_id DESC";
      $q_params[]=$this->id;

      $result = $sql->sql_query($query, $q_params);

      if (0 == $sql->getNumRows($result)) {
         // return default from config.ini file
         self::$logger->debug("Default status_enum_workflow for project $this->id");
         return Constants::$status_enum_workflow;
      }
      $row = $sql->fetchObject($result);
      $serialized = $row->value;

      if ((NULL == $serialized) || ("" == $serialized)) {
         self::$logger->error("Bad status_enum_workflow for project $this->id");
         return NULL;
      }

      if (!Tools::isMantisV1_2()) {
         $unserialized = json_decode( $serialized, true );
         if (NULL === $unserialized) {
            self::$logger->error("Config::status_enum_workflow json_decode error: ".json_last_error_msg());
         }
      } else {
         $unserialized = unserialize($serialized);
      }
      if (NULL == $unserialized) {
         self::$logger->error("getWorkflowTransitions: Could not get status_enum_workflow for project $this->id: ".$serialized);
      }
      return $unserialized;
   }

   /**
    * get Workflow transitions from Mantis DB
    *
    * mantis_config_table - config_id='status_enum_workflow'
    * @return array[]
    */
   function getWorkflowTransitionsFormatted() {


      $unserialized = $this->getWorkflowTransitions();

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
    * @return mixed[]
    */
   function getProjectConfig() {
      // find all srcProj specific config
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * FROM {config} ".
               "WHERE project_id = ".$sql->db_param();
      $q_params[]=$this->id;

      $result = $sql->sql_query($query, $q_params);

      $configItems = array();
      while($row = $sql->fetchObject($result)) {
         $configItems[$row->config_id] = $row->value;
      }
      return $configItems;
   }

   /**
    *  apply sourceProject config (workflow, thresholds, ...) to destProject
    *
    * @static
    * @param int $srcProjectId
    * @param int $destProjectId
    * @param bool $strict if true, delete all destProject config
    *               if false, only replace config found in srcProject
    * @return string
    */
   static function cloneAllProjectConfig($srcProjectId, $destProjectId, $strict=TRUE) {
      // find all srcProj specific config
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT DISTINCT config_id FROM {config} ".
               "WHERE project_id = ".$sql->db_param();

      #if(self::$logger->isDebugEnabled()) {
      #   self::$logger->debug("cloneAllProjectConfig: Src query=$query");
      #}

      $result = $sql->sql_query($query, array($srcProjectId));
      $srcConfigList = array();
      while($row = $sql->fetchObject($result)) {
         $srcConfigList[] = $row->config_id;
      }

      // remove all destProject config
      $formatedSrcConfigList = $formatedTeamMembers = implode( ', ', $srcConfigList);
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("cloneAllProjectConfig: SrcConfigList=$formatedSrcConfigList");
      }

      $query = "DELETE FROM {config} ".
               "WHERE project_id=".$sql->db_param();
      $q_params[]=$destProjectId;
      if (!$strict) {
         // delete only config defined for srcProject
         $query .= " AND config_id IN (".$formatedSrcConfigList.") ";
      }
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("cloneAllProjectConfig: deleteQuery = $query");
      }
      $sql->sql_query($query, $q_params);

      //--- clone all srcProj config to destProj
      foreach ($srcConfigList as $cid) {
         $query2 = "INSERT INTO {config} ".
                  "(project_id, config_id, user_id, access_reqd, type, value) ".
                  "VALUES (".$sql->db_param() .
                  "   (SELECT config_id, user_id, access_reqd, type, value ".
                  "    FROM {config} ".
                  "    WHERE project_id= ".$sql->db_param().
                  "    AND config_id=".$sql->db_param().")) ";
         $q_params2[]=$destProjectId;
         $q_params2[]=$srcProjectId;
         $q_params2[]=$cid;
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("cloneAllProjectConfig: cloneQuery = $query2");
         }
         $sql->sql_query($query2, $q_params2);
      }

      return "SUCCESS ! (".count($srcConfigList)." config items cloned.)";
   }

   /**
    * returns an array of ProjectVersion instances
    * key=version, value= ProjectVersion
    *
    * @param int $team_id (TODO)
    * @return ProjectVersion[]
    */
   #public function getVersionList($team_id = NULL) {
   public function getVersionList() {
      if (is_null($this->projectVersionList)) {
         $this->projectVersionList = array();
         $issueList = $this->getIssues();
         foreach ($issueList as $issue) {
            $tagVersion = "VERSION_".$issue->getTargetVersion();

            if (!array_key_exists($tagVersion, $this->projectVersionList)) {
               $this->projectVersionList[$tagVersion] = new ProjectVersion($this->id, $issue->getTargetVersion());
            }
            $this->projectVersionList[$tagVersion]->addIssue($issue->getId());
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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT date_order FROM {project_version} ".
                  "WHERE project_id= ".$sql->db_param().
                  " AND version=".$sql->db_param();
         $q_params[]=$this->id;
         $q_params[]=$target_version;
         $result = $sql->sql_query($query, $q_params);

         $targetVersionDate = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("$this->id target_version date = ".date("Y-m-d", $targetVersionDate));
         }
         $this->versionDateCache[$target_version] = ($targetVersionDate <= 1) ? NULL : $targetVersionDate;
      }

      return $this->versionDateCache[$target_version];
   }

   /**
    * @return number
    */
   public function getProgress() {
      if (is_null($this->progress)) {
         $this->progress = $this->getIssueSelection()->getProgress();
      }
      return $this->progress;
   }

   /**
    * @return IssueSelection
    */
   public function getIssueSelection() {
      if(is_null($this->issueSelection)) {
         $this->issueSelection = new IssueSelection($this->name);
         $issueList = $this->getIssues();
         foreach ($issueList as $issue) {
            try {
               $this->issueSelection->addIssue($issue->getId());
            } catch (Exception $e) {
               self::$logger->warn("getIssueSelection: ".$e->getMessage());
            }
         }
      }
      return $this->issueSelection;
   }

   /**
    * @return number[] array(nbDays, percent)
    */
   public function getDrift() {
      if (is_null($this->drift)) {
         $this->drift = $this->getIssueSelection()->getDrift();
      }
      return $this->drift;
   }

   /**
    * @return number[] array(nbDays, percent)
    */
   public function getDriftMgr() {
      if (is_null($this->driftMgr)) {
         $this->driftMgr = $this->getIssueSelection()->getDriftMgr();
      }
      return $this->driftMgr;
   }

   /**
    * Get projets
    * @return string[] The projects : name[id]
    */
   public static function getProjects() {
      try {
         $sql = AdodbWrapper::getInstance();
         $query = 'SELECT id, name FROM {project} ORDER BY name';
         $result = $sql->sql_query($query);
      } catch (Exception $e) {
         return NULL;
      }

      $projects = array();
      while($row = $sql->fetchObject($result)) {
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

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * FROM {bug} " .
               " WHERE project_id IN (".$formatedProjList.") " .
               " ORDER BY id DESC";
      $result = $sql->sql_query($query);
      $issueList = array();
      if (0 != $sql->getNumRows($result)) {
         while ($row = $sql->fetchObject($result)) {
            $issueList[] = IssueCache::getInstance()->getIssue($row->id, $row);
         }
         return $issueList;
      }
      return $issueList;
   }

   public function getCategory($type) {
      if(NULL == $this->categoryList) {
         $this->initializeCategories();
      }
      if(array_key_exists($type,$this->categoryList)) {
         return $this->categoryList[$type];
      } else {
         return NULL;
      }
   }

   public function getCategoryList() {
      if(NULL == $this->categoryList) {
         $this->initializeCategories();
      }
      return $this->categoryList;
   }

   /**
    * @return int
    */
   public function getId() {
      return $this->id;
   }

   /**
    * @return string
    */
   public function getName() {
      return $this->name;
   }

   public function getDescription() {
      return $this->description;
   }

   /**
    * return a list of customFields defined for this project (in mantis)
    *
    * @return array id => name
    */
   public function getCustomFieldsList() {

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT {custom_field_project}.field_id, {custom_field}.name ".
              "FROM {custom_field_project}, {custom_field} ".
              "WHERE {custom_field_project}.project_id =  ".$sql->db_param().
              " AND {custom_field}.id = {custom_field_project}.field_id ".
              " ORDER BY {custom_field}.name";
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params);

      $customFieldsList = array();
      while ($row = $sql->fetchObject($result)) {
         $customFieldsList["$row->field_id"] = $row->name;
      }
      return $customFieldsList;
   }


   /**
    * Returns the fields to display in the Issue tooltip
    *
    * fields can be
    * - mantis_bug_table columns (ex: project_id, status)
    * - customField id prefixed with 'custom_' (ex: custom_23)
    * - CodevTT calculated field prefixed with 'codevtt_' (ex: codevtt_drift)
    *
    * @param int $teamid
    * @param int $userid
    * @return array
    */
   public function getIssueTooltipFields($teamid = 0, $userid = 0) {

      $key = 'team'.$teamid.'_user'.$userid;
      if (is_null($this->issueTooltipFieldsCache)) { $this->issueTooltipFieldsCache = array(); }

      if (!array_key_exists($key, $this->issueTooltipFieldsCache)) {
         $sql = AdodbWrapper::getInstance();
         $query =  "SELECT value FROM codev_config_table WHERE config_id = ".$sql->db_param();
         $query .= " AND project_id IN (0, ".$sql->db_param().") ";
         $q_params[]=Config::id_issueTooltipFields;
         $q_params[]=$this->id;
         if (0 != $teamid) {
            // TODO FIXME if team not specified (timetracking.php must be fixed) then this request will skip
            // all 'team' specific settings and systematicaly return the team=0 response.
            // the if (0 != $teamid) will at least return the team specific settings, and the biggest teamid
            // will be chosen.
            // Note: once teamid selector added to timetracking.php, remove the if condition
            $query .= " AND team_id IN (0, ".$sql->db_param().") ";
            $q_params[]=$teamid;
         }
         $query .= " AND user_id IN (0, ".$sql->db_param().") ";
         $q_params[]=$userid;
         $query .= " ORDER by project_id DESC, team_id DESC, user_id DESC";

         #if(self::$logger->isDebugEnabled()) {
         #   self::$logger->debug("getIssueTooltipFields($teamid, $userid) query = $query");
         #}

         $result = $sql->sql_query($query, $q_params);

         if (0 != $sql->getNumRows($result)) {
            $serialized = $sql->sql_result($result, 0);

            $unserialized = unserialize($serialized);
            $this->issueTooltipFieldsCache[$key] = $unserialized;
         } else {
            // TODO get default value (project_id = 0)
            self::$logger->error('no issueTooltipFields found for project '.$this->id);
         }
      }
      return $this->issueTooltipFieldsCache[$key];
   }

   /**
    * store fields to display in the Issue tooltip
    *
    * fields can be
    * - mantis_bug_table columns (ex: project_id, status)
    * - customField id prefixed with 'custom_' (ex: custom_23)
    * - CodevTT fields prefixed with 'codevtt_' (ex: codevtt_commands)
    *
    *  if $fieldList == NULL, delete IssueTooltip custo for this project
    *
    * @param array $fieldList
    * @param int $teamid
    * @param int $userid
    */
   public function setIssueTooltipFields ($fieldList = NULL, $teamid = 0, $userid = 0) {
      if (!is_null($fieldList)) {
         $serialized = serialize($fieldList);
         Config::setValue('issue_tooltip_fields', $serialized, Config::configType_string,
                 'fields to be displayed in issue tooltip', $this->id, $userid, $teamid);

         $key = 'team'.$teamid.'_user'.$userid;
         if (is_null($this->issueTooltipFieldsCache)) { $this->issueTooltipFieldsCache = array(); }
         $this->issueTooltipFieldsCache[$key] = $fieldList;
      } else {
         // if $fieldList NULL, remove issueTooltip custo fot this project
         Config::deleteValue(Config::id_issueTooltipFields, array($userid, $this->id, $teamid, 0, 0, 0));
         unset($this->issueTooltipFieldsCache[$key]);
      }
   }

   /**
    *
    * @param type $memberId
    * @return boolean : true if the project has the member, false if not
    */
   public function hasMember($memberId) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT count(1) as count FROM {project_user_list}"
         . " WHERE user_id = ".$sql->db_param()
         . " AND project_id = ".$sql->db_param();
      $q_params[]=$memberId;
      $q_params[]=$this->id;

      $result = $sql->sql_query($query, $q_params);

      $count = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;
      if($count != 0) {
         return true;
      } else {
         return false;
      }
   }

   public function hasCustomField($customFieldId) {

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT count(1) as count FROM {custom_field_project}"
         . " WHERE field_id = ".$sql->db_param()
         . " AND project_id = ".$sql->db_param();
      $q_params[]=$customFieldId;
      $q_params[]=$this->id;

      $result = $sql->sql_query($query, $q_params);

      $count = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;
      if($count != 0) {
         return true;
      } else {
         return false;
      }
   }
}

Project::staticInit();

