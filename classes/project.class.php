<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php




class Project {

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
   	          "AND mantis_project_table.id = codev_team_project_table.project_id ";
   	
      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);
      
      $this->name        = $row->name;
      $this->description = $row->description;
      $this->type        = $row->type;
      
      // ---- if SideTaskProject get categories
      if ( $this->type == $sideTaskProjectType) {
         $query  = "SELECT * FROM `codev_sidetasks_category_table` ";
         $result = mysql_query($query) or die("Query failed: $query");
         $row = mysql_fetch_object($result);
      
         $this->categoryList = array();
         $this->categoryList["management"] = $row->cat_management;
         $this->categoryList["incident"]   = $row->cat_incident;
         $this->categoryList["absence"]    = $row->cat_absence;
         $this->categoryList["tools"]      = $row->cat_tools;
         $this->categoryList["doc"]        = $row->cat_doc;
      }
      
      #$this->jobList     = $this->getJobList();
   	
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
   	 
   	if (O != $this->id) {
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
   	return $this->categoryList["management"];
   }
   public function getIncidentCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList["incident"];
   }
   public function getAbsenceCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList["absence"];
   }
   public function getToolsCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList["tools"];
   }
   public function getDocCategoryId() {
      if (NULL == $this->categoryList) return NULL;
   	return $this->categoryList["doc"];
   }
   
}

?>
