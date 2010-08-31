<?php 

// MANTIS CoDev User Authorization Management

// LoB 23 Jun 2010


// =======================================
class User {

	var $id;
	
   // --------------------
	public function User($user_id) {
	  $this->id = $user_id;	
	}
	
   // --------------------
	public function isTeamLeader($team_id) {
		$query = "SELECT leader_id FROM `codev_team_table` WHERE id = $team_id";
      $result = mysql_query($query) or die("Query failed: $query");
      $leaderid = mysql_result($result, 0);
      
		return ($leaderid == $this->id);
	}
	
   // --------------------
   public function isTeamMember($team_id) {
      
      $query = "SELECT COUNT(id) FROM `codev_team_user_table` WHERE team_id = $team_id AND user_id = $this->id";
      $result = mysql_query($query) or die("Query failed: $query");
      $nbTuples  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;
      
      return (0 != $nbTuples);
   }

   // --------------------
   public function getArrivalDate($team_id = NULL) {
      
   	$arrival_date = time();
   	
      $query = "SELECT arrival_date FROM `codev_team_user_table` ".
               "WHERE user_id = $this->id ";
      if (isset($team_id)) {
               $query .= "AND team_id = $team_id";
      }
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	if ($row->arrival_date < $arrival_date) {
      		$arrival_date = $row->arrival_date;
      	}
      }
      
      #echo "DEBUG arrival_date = ".date('Y - m - d', $arrival_date)."<br>";
      
      return $arrival_date;
   }
   
   // --------------------
	// returns the teams i'm member of.
	public function getTeamList() {
		
		$teamList = array();
		
      $query = "SELECT codev_team_table.id, codev_team_table.name ".
               "FROM `codev_team_user_table`, `codev_team_table` ".
               "WHERE codev_team_user_table.user_id = $this->id ".
               "AND   codev_team_user_table.team_id = codev_team_table.id ".
               "ORDER BY codev_team_table.name";
      $result = mysql_query($query) or die("Query failed: $query");
		while($row = mysql_fetch_object($result))
      {
      	$teamList[$row->id] = $row->name;
      }
      
      return $teamList;
	}

   // --------------------
   // returns the teams i'm leader of.
   public function getLeadedTeamList() {
      
      $teamList = array();
      
      $query = "SELECT DISTINCT id, name FROM `codev_team_table` WHERE leader_id = $this->id  ORDER BY name";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $teamList[$row->id] = $row->name;
         #echo "getLeadedTeamList FOUND $row->id - $row->name<br/>";
      }
      
      return $teamList;
   }

   public function getProjectList() {
      
      $projList = array();
   	
      $teamList = $this->getTeamList();
      foreach ($teamList as $tid => $tname)
      {
         if ($formatedTeamList != "") { $formatedTeamList .= ', ';}
         $formatedTeamList .= $tid;
      }
      
      $query = "SELECT DISTINCT project_id FROM `codev_team_project_table` WHERE team_id IN ($formatedTeamList)";
      
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
      	$projList[] = $row->project_id;
      }
      return $projList;
   }
   
   
   // returns the tasks I can work on.
   // depending on: the projects associated to this user in mantis_project_user_list_table.
   // this list is displayed in timeTracking.php
   public function getPossibleWorkingTasksList() {
   	
   	$issueList = array();
   	
   	$projList = $this->getProjectList();
   	
   	if (0 == count($projList)) {
   		echo "<div style='color:red'>ERROR: no project associated to this team !</div><br><br>";
   		return array();
   	}
   	
	   foreach ($projList as $prid)
	   {
	      if ($formatedProjList != "") { $formatedProjList .= ', ';}
	      $formatedProjList .= $prid;
	   }
	   
   	
      $query = "SELECT DISTINCT id FROM `mantis_bug_table` WHERE project_id IN ($formatedProjList) ORDER BY id DESC";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
         	$issueList[] = $row->id;
      }
   	
   	return $issueList;
   }
   
}




?>