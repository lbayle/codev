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

// MANTIS CoDev User Authorization Management

// LoB 23 Jun 2010

include_once "user_cache.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "holidays.class.php";

// =======================================
class User {

	var $id;
	private $name;

   // --------------------
	public function User($user_id) {
	  $this->id = $user_id;
	}

   // --------------------
	public function getName() {

      if (NULL == $this->name) {
         $query = "SELECT mantis_user_table.username ".
                  "FROM  `mantis_user_table` ".
                  "WHERE  id = $this->id";
         $result = mysql_query($query) or die("Query failed: $query");
         $this->name  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "(unknown $this->id)";
      }
      return $this->name;
   }


	// --------------------
	public function getFirstname() {

		$tok = strtok($this->getName(), " "); // 1st token: firstname
      return $tok;
	}

   // --------------------
	public function getLastname() {

      $tok = strtok($this->getName(), " ");  // 1st token: firstname
      $tok = strtok(" ");  // 2nd token: lastname

      return $tok;
   }

   // --------------------
   /** retourne le trigramme. ex: Louis BAYLE => LBA */
   public function getShortname() {

   	if (0 == $this->id) { return "";	}

      $tok1 = strtok($this->getName(), " ");  // 1st token: firstname
      $tok2 = strtok(" ");  // 2nd token: lastname

      if (false == $tok2) {
      	$trigramme = $tok1[0].$tok1[1].$tok1[2];
      } else {
         $trigramme = $tok1[0].$tok2[0].$tok2[1];
      }
      return $trigramme;
   }

  // --------------------
   public function getRealname() {
      $query = "SELECT realname FROM `mantis_user_table` WHERE id = $this->id";
      $result = mysql_query($query) or die("Query failed: $query");
      $userName    = mysql_result($result, 0);
      return $userName;
   }


  // --------------------
	public function isTeamLeader($team_id) {
      $leaderid = Team::getLeaderId($team_id);
		return ($leaderid == $this->id);
	}

   // --------------------
   public function isTeamDeveloper($team_id, $startTimestamp=NULL, $endTimestamp=NULL) {
   	return $this->isTeamMember($team_id, Team::accessLevel_dev, $startTimestamp, $endTimestamp);
   }

   // --------------------
   // REM isTeamObserver not used for now
   public function isTeamObserver($team_id, $startTimestamp=NULL, $endTimestamp=NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_observer, $startTimestamp, $endTimestamp);
   }

   // --------------------
   public function isTeamManager($team_id, $startTimestamp=NULL, $endTimestamp=NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_manager, $startTimestamp, $endTimestamp);
   }

   // --------------------
   public function isTeamMember($team_id, $accessLevel=NULL, $startTimestamp=NULL, $endTimestamp=NULL) {

      $query = "SELECT COUNT(id) FROM `codev_team_user_table` ".
               "WHERE team_id = $team_id ".
               "AND user_id = $this->id ";

      if (NULL != $accessLevel) {
         $query .= "AND access_level = $accessLevel ";
      }

      if ((NULL != $startTimestamp) && (NULL != $endTimestamp)) {
         $query .= "AND arrival_date < $endTimestamp AND ".
                   "(departure_date >= $startTimestamp OR departure_date = 0)";
          // REM: if departure_date = 0, then user stays until the end of the world.
      }

      $result = mysql_query($query) or die("Query failed: $query");
      $nbTuples  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;

      return (0 != $nbTuples);
   }



   // --------------------
   /** if no team specified, choose the oldest arrival date */
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

      //echo "DEBUG arrivalDate = ".date('Y - m - d', $arrival_date)."<br>";

      return $arrival_date;
   }

   // --------------------
   /** if no team specified, choose the most future departureDate
    *  or '0' if still active in a team
    *  */
   public function getDepartureDate($team_id = NULL) {
   	$departureDate = 0;
      $query = "SELECT departure_date FROM `codev_team_user_table` ".
               "WHERE user_id = $this->id ";
      if (isset($team_id)) {
               $query .= "AND team_id = $team_id";
      }
      $result = mysql_query($query) or die("Query failed: $query");

      //search the departure date
      // if the user is active in a team the departure date is '0'
      while($row = mysql_fetch_object($result))
      {
      	if ((NULL==$row->departure_date )||(0==$row->departure_date )){
      		$departureDate = 0;
      		break;
      	}
      	if ($row->departure_date > $departureDate) {
            $departureDate = $row->departure_date;
         }
      }

      //echo "DEBUG departureDate = ".date('Y - m - d', $departureDate)."<br>";

      return $departureDate;
   }

   // --------------------
   /**
    * returns an array $daysOf[day] = $row->duration;
    * where day in [1..31]
    * REM: period cannot exceed 1 month.
    */
   public function getDaysOfInMonth($startTimestamp, $endTimestamp) {
    $daysOf = array();  // day => duration

    $query     = "SELECT bugid, date, duration ".
                 "FROM `codev_timetracking_table` ".
                 "WHERE date >= $startTimestamp AND date <= $endTimestamp ".
                 "AND userid = $this->id";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result)) {

      $issue = IssueCache::getInstance()->getIssue($row->bugid);
      if ($issue->isVacation()) {
      	if (isset($daysOf[date("j", $row->date)])) {
           $daysOf[date("j", $row->date)] += $row->duration;
      	} else {
           $daysOf[date("j", $row->date)]  = $row->duration;
      	}
        //echo "DEBUG user $this->userid daysOf[".date("j", $row->date)."] = ".$daysOf[date("j", $row->date)]." (+$row->duration)<br/>";
      }
    }
    return $daysOf;
  }

   // --------------------
   /**
    *
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    */
   public function getAstreintesInMonth($startTimestamp, $endTimestamp) {
    $astreintes = array();  // day => duration

    $query     = "SELECT bugid, date, duration ".
                 "FROM `codev_timetracking_table` ".
                 "WHERE date >= $startTimestamp AND date <= $endTimestamp ".
                 "AND userid = $this->id";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result)) {

      $issue = IssueCache::getInstance()->getIssue($row->bugid);
      if ($issue->isAstreinte()) {
        $astreintes[date("j", $row->date)] += $row->duration;
        //echo "DEBUG user $this->userid astreintes[".date("j", $row->date)."] = ".$astreintes[date("j", $row->date)]." (+$row->duration)<br/>";
      }
    }
    return $astreintes;
  }

  // --------------------
  /**
   * concat durations of all ExternalTasksProject issues.
   * 
   * returns an array $extTasks[timestamp] = $row->duration;
   *
   */
   public function getExternalTasksInPeriod($startTimestamp, $endTimestamp) {
	  $extTasks = array();  // timestamp => duration
	  
	  $extTasksProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
	  
	  $query     = "SELECT bugid, date, duration ".
                   "FROM `codev_timetracking_table` ".
                   "WHERE date >= $startTimestamp AND date <= $endTimestamp ".
                   "AND userid = $this->id";
     $result    = mysql_query($query) or die("Query failed: $query");

     while($row = mysql_fetch_object($result)) {
		  $issue = IssueCache::getInstance()->getIssue($row->bugid);
		  if ($issue->projectId == $extTasksProjId) {
			  if (isset($row->date)) {
			     $extTasks[$row->date] += $row->duration;
			  } else {
			     $extTasks[$row->date]  = $row->duration;
			  }
		  	  #echo "DEBUG user $this->id ExternalTasks[".date("j", $row->date)."] = ".$extTasks[date("j", $row->date)]." (+$row->duration)<br/>";
		  }
	  }
	  	return $extTasks;
  }
  
  
  // --------------------
  /**
   * Nb working days in the period (no holidays, no external tasks)
   * 
   * @param unknown_type $startTimestamp
   * @param unknown_type $endTimestamp
   * @param unknown_type $team_id
   */
   public function getProductionDaysForecast($startTimestamp, $endTimestamp, $team_id = NULL) {

      $holidays = Holidays::getInstance();

      $prodDaysForecast = 0;
      $nbOpenDaysInPeriod = 0;

   	$arrivalDate   = $this->getArrivalDate($team_id);
   	$departureDate = $this->getDepartureDate($team_id);

   	if ($arrivalDate   > $endTimestamp)   return 0;

   	// if not specified, $departureDate = $endTimestamp
   	if (0 == $departureDate) {$departureDate = $endTimestamp; }

   	if ($departureDate < $startTimestamp) return 0;

      // restrict timestamp to the period where the user is working on the project
      $startT = ($arrivalDate > $startTimestamp) ? $arrivalDate : $startTimestamp;
      $endT   = ($departureDate < $endTimestamp) ? $departureDate : $endTimestamp;


      // get $nbOpenDaysInPeriod
      for ($i = $startT; $i <= $endT; $i += (60 * 60 * 24)) {
         // monday to friday
         if (NULL == $holidays->isHoliday($i)) {
            $nbOpenDaysInPeriod++;
        }
      }

      $nbDaysOf = array_sum($this->getDaysOfInMonth($startT, $endT));
      $prodDaysForecast = $nbOpenDaysInPeriod - $nbDaysOf;

      // remove externalTasks timetracks
      $nbExternal = array_sum($this->getExternalTasksInPeriod($startT, $endT));
      $prodDaysForecast -= $nbExternal;
      
      
      #echo "user $this->id timestamp = ".date('Y-m-d', $startT)." to ".date('Y-m-d', $endT)." =>  ($nbOpenDaysInPeriod - ".$nbDaysOf.") = $prodDaysForecast <br/>";

      return $prodDaysForecast;
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

   // --------------------
   // returns the teams i'm member of.
   public function getDevTeamList() {
      return $this->getTeamList(Team::accessLevel_dev);
   }

   // --------------------
   // returns the teams i'm observer of.
   public function getObservedTeamList() {
      return $this->getTeamList(Team::accessLevel_observer);
   }

   // --------------------
   // returns the teams i'm Manager of.
   public function getManagedTeamList() {
      return $this->getTeamList(Team::accessLevel_manager);
   }

   // --------------------
   // returns the teams
   public function getTeamList($accessLevel = NULL) {

   	if (NULL == $accessLevel) { $accessLevel = Team::accessLevel_dev; }

      $teamList = array();

      $query = "SELECT codev_team_table.id, codev_team_table.name ".
               "FROM `codev_team_user_table`, `codev_team_table` ".
               "WHERE codev_team_user_table.user_id = $this->id ".
               "AND   codev_team_user_table.team_id = codev_team_table.id ".
               "AND   codev_team_user_table.access_level = $accessLevel ".
      "ORDER BY codev_team_table.name";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $teamList[$row->id] = $row->name;
         #echo "getTeamList(".Team::$accessLevelNames[$accessLevel].") FOUND $row->id - $row->name<br/>";
      }

      return $teamList;
   }


   // --------------------
   /*
    * returns an array of project_id=>project_name that the user is involved in,
    * depending on the teams the user belongs to.
    *
    * @param teamList       if NULL then return projects from all the teams the user belongs to
    * @param noStatsProject if false, the noStatsProject will not be returned in the list
    */
   public function getProjectList($teamList = NULL, $noStatsProject = true) {

      $projList = array();

      if (NULL == $teamList) {
      	// if not specified, get projects from the teams I'm member of.
         $teamList = $this->getTeamList();
      }
      if (0 != count($teamList)) {
	      $formatedTeamList = implode( ', ', array_keys($teamList));

	      $query = "SELECT DISTINCT codev_team_project_table.project_id, mantis_project_table.name ".
	               "FROM `codev_team_project_table`, `mantis_project_table`".
	               "WHERE codev_team_project_table.team_id IN ($formatedTeamList) ".
	               "AND codev_team_project_table.project_id = mantis_project_table.id ";

	      if (!$noStatsProject) {
	      	 $query .= "AND codev_team_project_table.type <> ".Project::type_noStatsProject." ";
	      }

	      $query .= "ORDER BY mantis_project_table.name";

           if (isset($_GET['debug_sql'])) { echo "User.getProjectList(): query = $query<br/>"; }

	      $result = mysql_query($query) or die("Query failed: $query");
	      while($row = mysql_fetch_object($result)) {
	      	$projList[$row->project_id] = $row->name;
	      }
      } else {
      	// this happens if User is not a Developper (Manager or Observer)
         //echo "<div style='color:red'>ERROR: User is not member of any team !</div><br>";
      }

      return $projList;
   }

   // --------------------
   // returns the tasks I can work on.
   // depending on: the projects associated to this user in mantis_project_user_list_table.
   // this list is displayed in timeTracking.php
   public function getPossibleWorkingTasksList($projList = NULL) {

   	$issueList = array();
   	if (NULL == $projList) {
   	  $projList = $this->getProjectList();
   	}

   	if (0 == count($projList)) {
   		// this happens if User is not a Developper (Manager or Observer)
   		//echo "<div style='color:red'>ERROR: no project associated to this team !</div><br>";
   		return array();
   	}

      $formatedProjList = implode( ', ', array_keys($projList));


      $query = "SELECT DISTINCT id FROM `mantis_bug_table` WHERE project_id IN ($formatedProjList) ORDER BY id DESC";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
         	$issueList[] = $row->id;
      }

   	return $issueList;
   }

   // --------------------
   /**
    * sum the RAE (or prelEffortEstim if no RAE defined) of all the opened Issues assigned to me.
    */
   public function getWorkload($projList = NULL) {

      $totalRemaining = 0;

      if (NULL == $projList) {
        $projList = $this->getProjectList();
      }

      if (0 == count($projList)) {
         // this happens if User is not a Developper (Manager or Observer)
         //echo "<div style='color:red'>ERROR: no project associated to this team !</div><br>";
         return $totalRemaining;
      }

      $formatedProjList = implode( ', ', array_keys($projList));

   	// find all issues i'm working on
      $query = "SELECT DISTINCT id FROM `mantis_bug_table` ".
               "WHERE project_id IN ($formatedProjList) ".
               "AND handler_id = $this->id ".
               "ORDER BY id DESC";

      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id);
         
         if ($issue->currentStatus < $issue->bug_resolved_status_threshold) {
            if (NULL != $issue->remaining) {
            	$totalRemaining += $issue->remaining;
            } else if (NULL != $issue->prelEffortEstim) {
               $totalRemaining += $issue->prelEffortEstim;
            }
         }
      }


   	return $totalRemaining;
   }


   // --------------------
   /**
    * Returns the Issues assigned to me.
    * the issue list is ordered by priority.
    *
    * priority criteria are:
    * - opened
    * - deadLine
    * - priority
    *
    * @return Issue list
    */
   public function getAssignedIssues($projList = NULL) {

      $issueList = array();

      if (NULL == $projList) {$projList = $this->getProjectList();}
      $formatedProjList = implode( ', ', array_keys($projList));


      $query = "SELECT DISTINCT mantis_bug_table.id AS bug_id ".
               "FROM `mantis_bug_table` ".
               "WHERE mantis_bug_table.project_id IN ($formatedProjList) ".
               "AND mantis_bug_table.handler_id = $this->id ".
               "ORDER BY id DESC";

      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
      	$issue = IssueCache::getInstance()->getIssue($row->bug_id);
      	if ($issue->currentStatus < $issue->bug_resolved_status_threshold) {
            $issueList[] = $issue;
      	}
      }
/*
      echo "DEBUG List to sort:<br/>";
      foreach ($issueList as $i) {
      	echo "$i->bugId<br/>";
      }
*/
      // quickSort the list
      $sortedList = qsort($issueList);

/*
   	echo "DEBUG after Sort<br/>";
      foreach ($sortedList as $i) {
         echo "$i->bugId<br/>";
      }
*/
      return $sortedList;
   }


   // --------------------
   /**
    * Returns the Issues that I monitor.
    * the issue list is ordered by priority.
    *
    * priority criteria are:
    * - opened
    * - deadLine
    * - priority
    *
    * @return Issue list
    */
   public function getMonitoredIssues($projList = NULL) {

      $issueList = array();

      if (NULL == $projList) {$projList = $this->getProjectList();}
      $formatedProjList = implode( ', ', array_keys($projList));


      $query = "SELECT DISTINCT bug_id ".
               "FROM `mantis_bug_monitor_table` ".
               "WHERE user_id = $this->id ".
               "ORDER BY bug_id DESC";

      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
      	  $issue = IssueCache::getInstance()->getIssue($row->bug_id);
      	  if ( $issue->currentStatus < $issue->bug_resolved_status_threshold) {
               $issueList[] = $issue;
      	  }
      }
      // quickSort the list
      $sortedList = qsort($issueList);

      return $sortedList;
   }

   // --------------------
   /**
    * check Timetracks & fixed holidays
    * and returns how many time is available for work on this day.
    */
   public function getAvailableTime($timestamp) {

      // we need to be absolutely sure that time is 00:00:00
      $timestamp = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));

   	  // if it's a holiday or a WE, then no work possible
      if(NULL != Holidays::getInstance()->isHoliday($timestamp)) {
         //echo "DEBUG getAvailableTime:".date('Y-m-d', $timestamp)." isHoliday<br/>\n";
   	     return 0;
   	  }

   	  // now check for Timetracks, the time left is free time to work
      $query = "SELECT SUM(duration) ".
               "FROM `codev_timetracking_table` ".
               "WHERE userid = $this->id ".
               "AND date = $timestamp";
      $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

      $data = mysql_fetch_array($result);
      $sum= round($data[0], 2);

      $availTime = (1 - $sum <= 0) ? 0 : (1 - $sum);

      //echo "DEBUG getAvailableTime ".date('Y-m-d', $timestamp).": TotalDurations=".$sum."  availTime=$availTime<br/>\n";
      return $availTime;


      }
} // class


?>