<?php


class Team {
	
	var $id;
	var $name;
	var $description;
	var $leader_id;
	var $date;
	
	/**
	 * 
	 * @param unknown_type $teamid
	 */
	public function Team($teamid) {
		 $this->id = $teamid;  

		 $this->initialize();
	}
	
	/**
	 * 
	 */
	public function initialize() {
      
		$query = "SELECT * FROM `codev_team_table` WHERE id = $this->id";
      $result = mysql_query($query) or die("Query failed: $query");
      $row = mysql_fetch_object($result);

      $this->name           = $row->name;
      $this->description    = $row->description;
      $this->leader_id      = $row->leader_id;
      $this->date           = $row->date;
		
	}

	/**
	 * STATIC insert new team in DB 
	 * 
	 * Team::create($name, $description, $leader_id, $date);
	 */
	public static function create($name, $description, $leader_id, $date) {
      $query = "INSERT INTO `codev_team_table`  (`name`, `description`, `leader_id`, `date`) VALUES ('$name','$description','$leader_id', '$date');";
      mysql_query($query) or die("Query failed: $query");
      
	}
	
	/**
	 * 
	 * @param unknown_type $name
	 */
	public static function getIdFromName($name) {
      $query = "SELECT id FROM `codev_team_table` WHERE name = '$name';";
      $result = mysql_query($query) or die("Query failed: $query");
      $teamid = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "-1";
		
      return $teamid;
	}
	
	public static function getLeaderId($teamid) { 
	   $query = "SELECT leader_id FROM `codev_team_table` WHERE id = $teamid";
      $result = mysql_query($query) or die("Query failed: $query");
      $leaderid  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;
      
      return $leaderid;
	}
	
	
	/**
	 * 
	 * @param unknown_type $memberid
	 * @param unknown_type $arrivalTimestamp
	 * @param unknown_type $memberAccess
	 */
	public function addMember($memberid, $arrivalTimestamp, $memberAccess) {
      $query = "INSERT INTO `codev_team_user_table`  (`user_id`, `team_id`, `arrival_date`, `departure_date`, `access_level`) ".
               "VALUES ('$memberid','$this->id','$arrivalTimestamp', '0', '$memberAccess');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
	}
	
	
	/**
	 * 
	 * @param unknown_type $date_create
	 */
   public function setCreationDate($date) {
      
      $query = "UPDATE `codev_team_table` SET date = $date WHERE id = $this->id;";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
   }
	
}

?>