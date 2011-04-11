<?php


class Team {
	
	var $id;
	var $name;
	var $description;
	var $leader_id;
	var $date;
	
   // -------------------------------------------------------
	/**
	 * 
	 * @param unknown_type $teamid
	 */
	public function Team($teamid) {
		 $this->id = $teamid;  

		 $this->initialize();
	}
	
   // -------------------------------------------------------
	/**
	 * 
	 */
	public function initialize() {
      
		$query = "SELECT * FROM `codev_team_table` WHERE id = $this->id";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $row = mysql_fetch_object($result);

      $this->name           = $row->name;
      $this->description    = $row->description;
      $this->leader_id      = $row->leader_id;
      $this->date           = $row->date;
		
	}

   // -------------------------------------------------------
	/**
	 * STATIC insert new team in DB 
	 * 
	 * Team::create($name, $description, $leader_id, $date);
	 * 
    * @return the team id or -1 if not found 
	 */
	public static function create($name, $description, $leader_id, $date) {
		
      // check if Team name exists !
		$teamid = Team::getIdFromName($name);
		
		if ($teamid < 0) { 
		   // create team
         $query = "INSERT INTO `codev_team_table`  (`name`, `description`, `leader_id`, `date`) VALUES ('$name','$description','$leader_id', '$date');";
         mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
         $teamid = mysql_insert_id();
		} else {
			echo "<span style='color:red'>ERROR: Team name '$name' already exists !</span>";
			$teamid = -1;
		}
      return $teamid;
	}
	
   // -------------------------------------------------------
	/**
	 * @param unknown_type $name
    * @return the team id or -1 if not found 
	 */
	public static function getIdFromName($name) {
      $query = "SELECT id FROM `codev_team_table` WHERE name = '$name';";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $teamid = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : (-1);
		
      return $teamid;
	}
	
   // -------------------------------------------------------
	public static function getLeaderId($teamid) { 
	   $query = "SELECT leader_id FROM `codev_team_table` WHERE id = $teamid";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      $leaderid  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;
      
      return $leaderid;
	}
	
   // -------------------------------------------------------
	public static function getProjectList($teamid) {
		
		$projList = array();
		
      $query     = "SELECT codev_team_project_table.project_id, mantis_project_table.name ".
                "FROM `codev_team_project_table`, `mantis_project_table` ".
                "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
                "AND codev_team_project_table.team_id=$teamid ".
                "ORDER BY mantis_project_table.name";
      $result    = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      while($row = mysql_fetch_object($result))
      {
      	$projList[$row->project_id] = $row->name;
      }
      
		return $projList;
	}
	
   // -------------------------------------------------------
	public static function getMemberList($teamid) {
      
      $mList = array();
      
      $query  = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
                "FROM `codev_team_user_table`, `mantis_user_table` ".
                "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
                "AND codev_team_user_table.team_id=$teamid ".
                "ORDER BY mantis_user_table.username";
      $result    = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      while($row = mysql_fetch_object($result))
      {
         $mList[$row->user_id] = $row->username;
      }
      
      return $mList;
   }
   
   // -------------------------------------------------------
   /**
	 * 
	 * @param unknown_type $memberid
	 * @param unknown_type $arrivalTimestamp
	 * @param unknown_type $memberAccess
	 */
	public function addMember($memberid, $arrivalTimestamp, $memberAccess) {
      $query = "INSERT INTO `codev_team_user_table`  (`user_id`, `team_id`, `arrival_date`, `departure_date`, `access_level`) ".
               "VALUES ('$memberid','$this->id','$arrivalTimestamp', '0', '$memberAccess');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
	}
	
	
   // -------------------------------------------------------
	public function setMemberDepartureDate($memberid, $departureTimestamp) {
	  $query = "UPDATE `codev_team_user_table` SET departure_date = $departureTimestamp WHERE user_id = $memberid AND team_id = $this->id;";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
	}
	
	
   // -------------------------------------------------------
	/**
	 * add all members declared in Team $src_teamid (same dates, same access)
	 * users already declared are omitted 
	 * 
	 * @param unknown_type $src_teamid
	 */
   public function addMembersFrom($src_teamid) {
   	
   	$query = "SELECT * from `codev_team_user_table` WHERE team_id = $src_teamid ";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	$user = UserCache::getInstance()->getUser($row->user_id);
      	if (! $user->isTeamMember($this->id)) {
            $this->addMember($row->user_id,$row->arrival_date, $row->access_level);
            
            if (NULL != $row->departure_date) {
            	$this->setMemberDepartureDate($row->user_id, $row->departure_date);
            }
      	}
      }
   	
   }	
	
   // -------------------------------------------------------
   /**
    * 
    * @param unknown_type $projectid
    * @param unknown_type $projecttype
    */
   public function addProject($projectid, $projecttype) {
      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$projectid','$this->id','$projecttype');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
   }
   
   // -------------------------------------------------------
   /**
    * 
    */
   public function addCommonSideTaskProject() {
   	
      global $defaultSideTaskProject;
      global $sideTaskProjectType;

      // TODO check if not already in table !
      
      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$defaultSideTaskProject','$this->id','$sideTaskProjectType');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
   }
   
   // -------------------------------------------------------
   /**
    * 
    * @param unknown_type $projectName
    * @return unknown_type $projectId
    */
   public function createSideTaskProject($projectName) {
      global $sideTaskProjectType;
      
      $projectDesc = "CoDev SideTaskProject for team $this->name";

      $projectid = Project::createSideTaskProject($projectName);

      if (-1 != $projectid) {
      	
         // add new SideTaskProj to the team
         $query = "INSERT INTO `codev_team_project_table` (`project_id`, `team_id`, `type`) ".
                  "VALUES ('$projectid','$this->id','$sideTaskProjectType');";
         mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

         // create entry in codev_sidetasks_category_table
         $query = "INSERT INTO `codev_sidetasks_category_table` (`project_id`) VALUES ('$projectid');";
         mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
      } else {
      	die("ERROR: createSideTaskProject !!!<br/>");
      }
      
      return $projectid;
   }
   
   // -------------------------------------------------------
	/**
	 * 
	 * @param unknown_type $date_create
	 */
   public function setCreationDate($date) {
      
      $query = "UPDATE `codev_team_table` SET date = $date WHERE id = $this->id;";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
   }
	
}

?>