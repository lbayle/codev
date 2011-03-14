<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php




class Project {

   // REM: the values are also the names of the fields in codev_sidetasks_category_table
   public static $keyProjManagement = "cat_management";
   public static $keyIncident       = "cat_incident";
   public static $keyInactivity     = "cat_absence";
   public static $keyTools          = "cat_tools";
   public static $keyDoc            = "cat_doc";
   
	var $id;
	var $name;
	var $description;
	var $type;
	var $jobList;
	var $categoryList;
      
	// -----------------------------------------------
	public function Project($id) {
      $this->id = $id;

      $this->initialize();
   }

   // -----------------------------------------------
   public function initialize() {
      
   	global $sideTaskProjectType;
   	
   	$query  = "SELECT mantis_project_table.name, mantis_project_table.description, codev_team_project_table.type ".
   	          "FROM `mantis_project_table`, `codev_team_project_table` ".
   	          "WHERE mantis_project_table.id = $this->id ".
   	          "AND codev_team_project_table.project_id = $this->id ";
   	
      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);
      
      $this->name        = $row->name;
      $this->description = $row->description;
      $this->type        = $row->type;
      
      // ---- if SideTaskProject get categories
      if ( $this->type == $sideTaskProjectType) {
      	
         $query  = "SELECT * FROM `codev_sidetasks_category_table` WHERE project_id = $this->id ";
         $result = mysql_query($query) or die("Query failed: $query");
         $row = mysql_fetch_object($result);
      
         $this->categoryList = array();
         $this->categoryList[Project::$keyProjManagement] = $row->cat_management;
         $this->categoryList[Project::$keyIncident]       = $row->cat_incident;
         $this->categoryList[Project::$keyInactivity]     = $row->cat_absence;
         $this->categoryList[Project::$keyTools]          = $row->cat_tools;
         $this->categoryList[Project::$keyDoc]            = $row->cat_doc;
      }
      
      #$this->jobList     = $this->getJobList();
   }

   
   // -----------------------------------------------
   public static function addCategoryProjManagement($id, $catName) {
   	echo "DEBUG addCategoryProjManagement : Project::keyProjManagement = ".Project::$keyProjManagement."<br/>";
      Project::addCategory($id, Project::$keyProjManagement, $catName);
   }
   public static function addCategoryInactivity($id, $catName) {
      Project::addCategory($id, Project::$keyInactivity, $catName);
   }
   public static function addCategoryIncident($id, $catName) {
      Project::addCategory($id, Project::$keyIncident, $catName);
   }
   public static function addCategoryTools($id, $catName) {
      Project::addCategory($id, Project::$keyTools, $catName);
   }
   public static function addCategoryDoc($id, $catName) {
      Project::addCategory($id, Project::$keyDoc, $catName);
   }
   
   // -----------------------------------------------
   /**
    * WARN: the $catKey is the name of the field in codev_sidetasks_category_table
    * @param int $id
    * @param string $catKey in (Project::$keyProjManagement, Project::$keyIncident, Project::$keyInactivity, Project::$keyTools, Project::$keyDoc
    * @param string $catName
    */
   private static function addCategory($projectid, $catKey, $catName) {

   	// create category for SideTask Project
      $query = "INSERT INTO `mantis_category_table`  (`project_id`, `user_id`, `name`, `status`) VALUES ('$projectid','0','$catName', '0');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

      // get new category id
      $query  = "SELECT id FROM `mantis_category_table` WHERE name='$catName'";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $catId    = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : -1;
      
      // update codev_sidetasks_category_table
      if (-1 != $catId) {
         $query = "UPDATE `codev_sidetasks_category_table` SET $catKey = $catId WHERE project_id = $projectid";
         mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      }
   }

   
   // -----------------------------------------------
   // Job list depends on project type:
   // if type=1 (SideTask) than only jobs for SideTasks are displayed.
   // if type=0 (Project) then all jobs which codev_project_job_table.project_id = $this->id
   //                     OR codev_job_table.type = $commonJobType (common jobs)  
   public function getJobList() {
   	global $workingProjectType;
   	global $sideTaskProjectType;
   	global $commonJobType;
   	
   	$jobList = array();
   	 
   	if (0 != $this->id) {
	   	if ($sideTaskProjectType == $this->type) {
		      $query  = "SELECT codev_job_table.id, codev_job_table.name ".
		                "FROM `codev_job_table`, `codev_project_job_table` ".
		                "WHERE codev_job_table.id = codev_project_job_table.job_id ".
		                "AND codev_project_job_table.project_id = $this->id";
	   	} elseif ($workingProjectType == $this->type) {
	   		// all other projects
	         $query  = "SELECT codev_job_table.id, codev_job_table.name ".
	                   "FROM `codev_job_table` ".
	                   "LEFT OUTER JOIN  `codev_project_job_table` ".
	                   "ON codev_job_table.id = codev_project_job_table.job_id ".
	                   "WHERE (codev_job_table.type = $commonJobType OR codev_project_job_table.project_id = $this->id)";
	                   
	   	} else {
	   		echo "ERROR Project.getJobList(): unknown project type !";
	   		exit;
	   	}
	      $result = mysql_query($query) or die("Query failed: $query");
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
   public function getIssueList() {
   	
   	$issueList = array();
   	
	   $query = "SELECT DISTINCT id FROM `mantis_bug_table` ".
	            "WHERE project_id=$this->id ".
	            "ORDER BY id DESC";
	   
	   $result = mysql_query($query) or die("Query failed: $query");
	   while($row = mysql_fetch_object($result)) {
	   	$issueList[] = $row->id;
	   }
	   return $issueList;
   }
   
   // -----------------------------------------------
   public function isSideTasksProject() {
   	global $sideTaskProjectType;
   	
		return ($sideTaskProjectType == $this->type);
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
   public function getDocCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList[Project::$keyDoc];
   }
   
}

?>
