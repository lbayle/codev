<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php




class Project {

	var $id;
	var $name;
	var $description;
	var $type;
	var $jobList;
	
	// -----------------------------------------------
	public function Project($id) {
      $this->id = $id;
      
      $this->initialize();
   }

   // -----------------------------------------------
   public function initialize() {
      
   	$query  = "SELECT mantis_project_table.name, mantis_project_table.description, codev_team_project_table.type ".
   	          "FROM `mantis_project_table`, `codev_team_project_table` ".
   	          "WHERE mantis_project_table.id = $this->id ".
   	          "AND mantis_project_table.id = codev_team_project_table.project_id ";
   	
      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);
      
      $this->name        = $row->name;
      $this->description = $row->description;
      $this->type        = $row->type;
      
      $this->jobList     = $this->getJobList();
   	
   }
   
   // -----------------------------------------------
   // Job list depends on project type:
   // if type=1 (SideTask) than only jobs for SideTasks are displayed.
   // if type=0 (Project) then all jobs which codev_job_table.projectid = $this->id OR 0 (common jobs)  
   public function getJobList() {
   	
   	$jobList = array();
   	 
   	if (O != $this->id) {
	   	if (1 == $this->type) {
		      $query  = "SELECT id, name ".
		                "FROM `codev_job_table` ".
		                "WHERE codev_job_table.projectid = $this->id";
	   	} elseif (0 == $this->type) {
	   		// all other projects
	         $query  = "SELECT id, name ".
	                   "FROM `codev_job_table` ".
	                   "WHERE (codev_job_table.projectid = $this->id OR codev_job_table.projectid = 0)";
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
   
	
}

?>
