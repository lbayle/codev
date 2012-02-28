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

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
      Logger::configure(dirname(__FILE__).'/../log4php.xml');
      $logger = Logger::getLogger("default");
      $logger->info("LOG activated !");
   }


include_once "config_mantis.class.php";
include_once "project_cache.class.php";
include_once "jobs.class.php";


class Project {

  const type_workingProject   = 0;     // normal projects are type 0
  const type_sideTaskProject  = 1;     // SuiviOp must be type 1
  const type_noCommonProject  = 2;     // projects which have only assignedJobs (no common jobs) REM: these projects are not considered as sideTaskProjects
  const type_noStatsProject   = 3;     // projects that will be excluded from the statistics (ex: FDL)

  private $logger;

  public static $typeNames = array(Project::type_workingProject  => "Project",
                                   Project::type_noCommonProject => "Project (no common jobs)",
                                   Project::type_noStatsProject  => "Project (stats excluded)",
                                   Project::type_sideTaskProject => "SideTasks");


   // REM: the values are also the names of the fields in codev_sidetasks_category_table
   public static $keyProjManagement = "cat_management";
   public static $keyIncident       = "cat_incident";
   public static $keyInactivity     = "cat_inactivity";
   public static $keyTools          = "cat_tools";
   public static $keyWorkshop       = "cat_workshop";

	var $id;
	var $name;
	var $description;
	var $type;
	var $jobList;
	var $categoryList;

	private $bug_resolved_status_threshold;

	// -----------------------------------------------
	public function Project($id) {
	   $this->logger = Logger::getLogger(__CLASS__);

      $this->id = $id;
	  if (0 != $id) { $this->initialize(); }
   }

   // -----------------------------------------------
   public function initialize() {

   	$query  = "SELECT mantis_project_table.name, mantis_project_table.description, codev_team_project_table.type ".
   	          "FROM `mantis_project_table`, `codev_team_project_table` ".
   	          "WHERE mantis_project_table.id = $this->id ".
   	          "AND codev_team_project_table.project_id = $this->id ";

      $result = mysql_query($query);
      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $row = mysql_fetch_object($result);

      $this->name        = $row->name;
      $this->description = $row->description;
      $this->type        = $row->type;

      // ---- if SideTaskProject get categories
      if ( $this->type == Project::type_sideTaskProject) {

         $query  = "SELECT * FROM `codev_sidetasks_category_table` WHERE project_id = $this->id ";
         $result = mysql_query($query);
         if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
         }
         $row = mysql_fetch_object($result);

         $this->categoryList = array();
         $this->categoryList[Project::$keyProjManagement] = $row->cat_management;
         $this->categoryList[Project::$keyIncident]       = $row->cat_incident;
         $this->categoryList[Project::$keyInactivity]     = $row->cat_inactivity;
         $this->categoryList[Project::$keyTools]          = $row->cat_tools;
         $this->categoryList[Project::$keyWorkshop]          = $row->cat_workshop;
      }

      // get $bug_resolved_status_threshold from mantis_config_table or codev_config_table if not found
      $query  = "SELECT get_project_resolved_status_threshold($this->id) ";
      $result = mysql_query($query);
      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $this->bug_resolved_status_threshold = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;
      #echo "DEBUG $this->name .bug_resolved_status_threshold = $this->bug_resolved_status_threshold<br>\n";

      #echo "DEBUG $this->name type=$this->type categoryList ".print_r($this->categoryList)." ----<br>\n";

      #$this->jobList     = $this->getJobList();
   }

   // -----------------------------------------------
   /**
   */
   public static function getName($projectId) {

   global $logger;

  	$query  = "SELECT mantis_project_table.name ".
   	          "FROM `mantis_project_table` ".
   	          "WHERE mantis_project_table.id = $projectId ";

      $result = mysql_query($query);
      if (!$result) {
    	      $logger->error("Query FAILED: $query");
    	      $logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $row = mysql_fetch_object($result);

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
      $result = mysql_query($query);
      if (!$result) {
    	      $logger->error("Query FAILED: $query");
    	      $logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $projectid    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      //--- create new Project
      $query = "INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `description`, `category_id`, `inherit_global`) ".
               "VALUES ('$projectName','50','1','50','10','$projectDesc','1','1');";
      $result = mysql_query($query);
         if (!$result) {
    	      $logger->error("Query FAILED: $query");
    	      $logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $projectid = mysql_insert_id();


      //--- when creating an new issue, the status is set to 'closed' (External Tasks have no workflow...)
      #REM first call to this function is in install step1, and $statusNames is set in step2. '90' is mantis default value for 'closed'
      $statusNames = NULL; # Config::getInstance()->getValue(Config::id_statusNames);
      $status_closed = (NULL != $statusNames) ? array_search('closed', $statusNames) : 90;
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_submit_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      //--- Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_assigned_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
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

      $estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      $addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort);
      $remainingCustomField    = Config::getInstance()->getValue(Config::id_customField_remaining);
      $deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
      $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

      // check if name exists
      $query  = "SELECT id FROM `mantis_project_table` WHERE name='$projectName'";
      $result = mysql_query($query);
      if (!$result) {
    	      $logger->error("Query FAILED: $query");
    	      $logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $projectid    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : -1;
      if (-1 != $projectid) {
         echo "ERROR: Project name already exists ($projectName)<br/>\n";
         return -1;
      }

      // create new Project
      $query = "INSERT INTO `mantis_project_table` (`name`, `status`, `enabled`, `view_state`, `access_min`, `description`, `category_id`, `inherit_global`) ".
               "VALUES ('$projectName','50','1','50','10','$projectDesc','1','0');";
      $result = mysql_query($query);
      if (!$result) {
    	      $logger->error("Query FAILED: $query");
    	      $logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      $projectid = mysql_insert_id();

      // prepare for category add
      $query = "INSERT INTO `codev_sidetasks_category_table` (`project_id`) VALUES ('$projectid');";
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      // add custom fields BI,BS,RAE,DeadLine,DeliveryDate
      $query = "INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) ".
               "VALUES ('$estimEffortCustomField',  '$projectid','3'), ".
                      "('$addEffortCustomField',    '$projectid','4'), ".
                      "('$remainingCustomField',    '$projectid','5'), ".
                      "('$deadLineCustomField',     '$projectid','6'), ".
                      "('$deliveryDateCustomField', '$projectid','7');";
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
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
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      // Status to set auto-assigned issues to 'closed'
      $query = "INSERT INTO `mantis_config_table` (`config_id`,`project_id`,`user_id`,`access_reqd`,`type`,`value`) ".
               "VALUES ('bug_assigned_status',  '$projectid','0', '90', '1', '$status_closed');";
      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      return $projectid;
   }

   // -----------------------------------------------
   /**
    * Prepare a Mantis Project to be used with CoDevTT:
    * - check/add association to CoDevTT customFields
    */
   public function prepareProjectToCodev() {

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
	  $query = "SELECT field_id FROM `mantis_custom_field_project_table` WHERE 	project_id = $this->id";
     $result = mysql_query($query);
     if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
     }
	  while($row = mysql_fetch_object($result))
      {
          $existingFields[] = $row->field_id;
      }

      $query = "INSERT INTO `mantis_custom_field_project_table` (`field_id`, `project_id`, `sequence`) ".
               "VALUES ";

	  $found = false;
	  if (!in_array($tcCustomField, $existingFields))           { $query .= "('$tcCustomField',           '$this->id','101'),"; $found = true; }
	  if (!in_array($mgrEffortEstim, $existingFields))         { $query .= "('$mgrEffortEstim',         '$this->id','102'),"; $found = true; }
	  if (!in_array($estimEffortCustomField, $existingFields))  { $query .= "('$estimEffortCustomField',  '$this->id','103'),"; $found = true; }
	  if (!in_array($addEffortCustomField, $existingFields))    { $query .= "('$addEffortCustomField',    '$this->id','104'),"; $found = true; }
	  if (!in_array($remainingCustomField, $existingFields))    { $query .= "('$remainingCustomField',    '$this->id','105'),"; $found = true; }
	  if (!in_array($deadLineCustomField, $existingFields))     { $query .= "('$deadLineCustomField',     '$this->id','106'),"; $found = true; }
	  if (!in_array($deliveryDateCustomField, $existingFields)) { $query .= "('$deliveryDateCustomField', '$this->id','107'),"; $found = true; }
	  #if (!in_array($deliveryIdCustomField, $existingFields))   { $query .= "('$deliveryIdCustomField',   '$this->id','108'),"; $found = true; }

	  if ($found) {
	  	  // replace last ',' with a ';' to finish query
	  	  $pos = strlen($query) - 1;
	  	  $query[$pos] = ';';

        // add missing custom fields
	     $result = mysql_query($query);
        if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
        }
	  }

   }

   // -----------------------------------------------
   public function addCategoryProjManagement($catName) {
      return $this->addCategory(Project::$keyProjManagement, $catName);
   }
   public function addCategoryInactivity($catName) {
      return $this->addCategory(Project::$keyInactivity, $catName);
   }
   public function addCategoryIncident($catName) {
      return $this->addCategory(Project::$keyIncident, $catName);
   }
   public function addCategoryTools($catName) {
      return $this->addCategory(Project::$keyTools, $catName);
   }
   public function addCategoryWorkshop($catName) {
      return $this->addCategory(Project::$keyWorkshop, $catName);
   }

   // -----------------------------------------------
   /**
    * WARN: the $catKey is the name of the field in codev_sidetasks_category_table
    * @param string $catKey in (Project::$keyProjManagement, Project::$keyIncident, Project::$keyInactivity, Project::$keyTools, Project::$keyWorkshop
    * @param string $catName
    */
   private function addCategory($catKey, $catName) {

   	// create category for SideTask Project
      $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) VALUES ('$this->id','0','$catName', '0');";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
            $catId = mysql_insert_id();

      $query = "UPDATE `codev_sidetasks_category_table` SET $catKey = $catId WHERE project_id = $this->id";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      $this->categoryList[$catKey] = $catId;

      return $catId;
   }


   // -----------------------------------------------
   public function addIssueProjManagement($issueSummary, $issueDesc=" ") {
   	  global $status_closed;
      $bugt_id = $this->addSideTaskIssue(Project::$keyProjManagement, $issueSummary, $issueDesc);

      $query  = "UPDATE `mantis_bug_table` SET status = '$status_closed' WHERE id='$bugt_id'";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      return $bugt_id;
   }
   public function addIssueInactivity($issueSummary, $issueDesc=" ") {
   	global $status_closed;
      $bugt_id = $this->addSideTaskIssue(Project::$keyInactivity, $issueSummary, $issueDesc);

      $query  = "UPDATE `mantis_bug_table` SET status = '$status_closed' WHERE id='$bugt_id'";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

      return $bugt_id;
   }
   public function addIssueIncident($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyIncident, $issueSummary, $issueDesc);
   }
   public function addIssueTools($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyTools, $issueSummary, $issueDesc);
   }
   public function addIssueWorkshop($issueSummary, $issueDesc=" ") {
      return $this->addSideTaskIssue(Project::$keyWorkshop, $issueSummary, $issueDesc);
   }

   // -----------------------------------------------
   private function addSideTaskIssue($catKey, $issueSummary, $issueDesc) {

   	global $status_closed;
   	$cat_id = $this->categoryList["$catKey"];
      $today  = date2timestamp(date("Y-m-d"));

      $query = "INSERT INTO `mantis_bug_text_table`  (`description`) VALUES ('$issueDesc');";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
      $bug_text_id = mysql_insert_id();

   	$query = "INSERT INTO `mantis_bug_table`  (`project_id`, `category_id`, `summary`, `priority`, `reproducibility`, `status`, `bug_text_id`, `date_submitted`, `last_updated`) ".
   	         "VALUES ('$this->id','$cat_id','$issueSummary','10','100','$status_closed','$bug_text_id', '$today', '$today');";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
   	$bugt_id = mysql_insert_id();

   	return $bugt_id;
   }

   // -----------------------------------------------
   /*
    *
    */
   public function addIssue($cat_id, $issueSummary, $issueDesc, $issueStatus) {

      $today  = date2timestamp(date("Y-m-d"));

      $query = "INSERT INTO `mantis_bug_text_table`  (`description`) VALUES ('$issueDesc');";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
      $bug_text_id = mysql_insert_id();

   	$query = "INSERT INTO `mantis_bug_table`  (`project_id`, `category_id`, `summary`, `priority`, `reproducibility`, `status`, `bug_text_id`, `date_submitted`, `last_updated`) ".
   	         "VALUES ('$this->id','$cat_id','$issueSummary','10','100','$issueStatus','$bug_text_id', '$today', '$today');";
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
   	$bugt_id = mysql_insert_id();

   	return $bugt_id;
   }


   public function getBugResolvedStatusThreshold() {
   	#echo "DEBUG $this->name .getBugResolvedStatusThreshold() = $this->bug_resolved_status_threshold<br>\n";
   	return $this->bug_resolved_status_threshold;
   }

   // -----------------------------------------------
   // Job list depends on project type:
   // if type=1 (SideTask) than only jobs for SideTasks are displayed.
   // if type=0 (Project) then all jobs which codev_project_job_table.project_id = $this->id
   //                     OR codev_job_table.type = Job::type_commonJob (common jobs)
   public function getJobList() {
   	$commonJobType       = Job::type_commonJob;

   	$jobList = array();

   	$type = $this->type;

   	// SPECIAL CASE: externalTasksProject is a type_noStatsProject that has only 'N/A' jobs
      if ($this->id == Config::getInstance()->getValue(Config::id_externalTasksProject)) {
      	$type = Project::type_sideTaskProject;
      }

   	if (0 != $this->id) {

       switch ($type) {
          case Project::type_sideTaskProject:
	         $query  = "SELECT codev_job_table.id, codev_job_table.name ".
		               "FROM `codev_job_table`, `codev_project_job_table` ".
		               "WHERE codev_job_table.id = codev_project_job_table.job_id ".
		               "AND codev_project_job_table.project_id = $this->id";
             break;
          case Project::type_noCommonProject:
	         $query  = "SELECT codev_job_table.id, codev_job_table.name ".
	                   "FROM `codev_job_table` ".
	                   "LEFT OUTER JOIN  `codev_project_job_table` ".
	                   "ON codev_job_table.id = codev_project_job_table.job_id ".
	                   "WHERE (codev_project_job_table.project_id = $this->id)".
                       "ORDER BY codev_job_table.name ASC";
    	     break;
          case Project::type_workingProject:  // no break;
          case Project::type_noStatsProject:
	   	      // all other projects
	         $query  = "SELECT codev_job_table.id, codev_job_table.name ".
	                   "FROM `codev_job_table` ".
	                   "LEFT OUTER JOIN  `codev_project_job_table` ".
	                   "ON codev_job_table.id = codev_project_job_table.job_id ".
	                   "WHERE (codev_job_table.type = $commonJobType OR codev_project_job_table.project_id = $this->id)";
    	     break;
	      default:
	   	     echo "ERROR Project.getJobList(): unknown project type ($this->type) !";
       }

   	$result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
       if (0 != mysql_num_rows($result)) {
		   	while($row = mysql_fetch_object($result))
		      {
		         $jobList[$row->id] = $row->name;
		      }
	    }
   	}
      return $jobList;
   }

   // -----------------------------------------------
   public function getIssueList($handler_id = 0, $isHideResolved = false) {

   	$issueList = array();

	   $query = "SELECT DISTINCT id FROM `mantis_bug_table` ".
	            "WHERE project_id=$this->id ";
       if (0 != $handler_id) {
          $query  .= "AND handler_id = $handler_id ";
       }
       if ($isHideResolved) {
          $query  .= "AND status < get_project_resolved_status_threshold(project_id) ";
       }

	   $query  .= "ORDER BY id DESC";

      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
	   while($row = mysql_fetch_object($result)) {
	   	$issueList[] = $row->id;
	   }
	   return $issueList;
   }

   // -----------------------------------------------
   public function isSideTasksProject() {
   	$sideTaskProjectType = Project::type_sideTaskProject;

		return ($sideTaskProjectType == $this->type);
	}

   // -----------------------------------------------
   public function isNoStatsProject() {
		return (Project::type_noStatsProject == $this->type);
	}


   // -----------------------------------------------
	public function getManagementCategoryId() {
		if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyProjManagement];
   }
   public function getIncidentCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyIncident];
   }
   public function getInactivityCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyInactivity];
   }
   public function getToolsCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyTools];
   }
   public function getWorkshopCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyWorkshop];
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
         $sarr = doubleExplode(':', ',', $sList);
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

      $result = mysql_query($query);
      if (!$result) {
        $this->logger->error("Query FAILED: $query");
        $this->logger->error(mysql_error());
        echo "<span style='color:red'>ERROR: Query FAILED</span>";
        exit;
      }
      $configItems = array();

      while($row = mysql_fetch_object($result)) {
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
	   $query = "SELECT config_id FROM `mantis_config_table` ".
	            "WHERE project_id=$srcProjectId ";
      $logger->debug("cloneAllProjectConfig: Src query=$query");

      $result = mysql_query($query);
      if (!$result) {
      	$logger->error("Query FAILED: $query");
      	$logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }
      $srcConfigList = array();
	   while($row = mysql_fetch_object($result)) {
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
      $result = mysql_query($query);
      if (!$result) {
      	$this->logger->error("Query FAILED: $query");
      	$this->logger->error(mysql_error());
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
	      $result = mysql_query($query);
	      if (!$result) {
	      	$this->logger->error("Query FAILED: $query");
	      	$this->logger->error(mysql_error());
	      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
	      	exit;
         }
   	}

   	return "SUCCESS ! (".count($srcConfigList)." config items cloned.)";
   }


}

?>
