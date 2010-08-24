<?php

// -- TimeTracking facilities --

include_once "time_track.class.php";
include_once "holidays.class.php";
include_once "../reports/issue.class.php";
include_once "../auth/user.class.php";

class TimeTracking {
  var $startTimestamp;
  var $endTimestamp;
  var $prodDays;

  var $team_id;
        
  var $prodProjectList;     // list of projects that are considered as not beeing sideTasks
  var $sideTaskprojectList;

  // ----------------------------------------------
  public function TimeTracking($startTimestamp, $endTimestamp, $team_id = -1) {

    $this->startTimestamp = $startTimestamp;
    $this->endTimestamp   = $endTimestamp;
    $this->team_id       = $team_id;
      
    #if (-1 == $team_id) {
      #       echo "WARN: TimeTracking->team_id not set !<br>";
      #}
      $this->initialize();
  }

  // ----------------------------------------------
  public function initialize() {     
    $this->prodProjectList     = array();
    $this->sideTaskprojectList = array();
                
    $query = "SELECT project_id, type FROM `codev_team_project_table` WHERE team_id = $this->team_id";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      if (0 == $row->type) {
        $this->prodProjectList[]     = $row->project_id;
      } else {
        $this->sideTaskprojectList[] = $row->project_id;
      }
    }
  }
             
  // ----------------------------------------------
  // Returns the number of days worked by the team within the timestamp
  public function getProdDays() {
    return $this->getProductionDays($this->prodProjectList);
  }
   
  // ----------------------------------------------
  // Returns the number of days worked by the team within the timestamp
  private function getProductionDays($projects) {
    $prodDays = 0;

    $query     = "SELECT codev_timetracking_table.id, codev_timetracking_table.userid, codev_timetracking_table.bugid ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id";
      
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $timeTrack = new TimeTrack($row->id);
            
      // Count only the time spent on $projects
      if (in_array ($timeTrack->projectId, $projects)) {
        $prodDays += $timeTrack->duration;
      }
    }
    return $prodDays;
  }
        
  // ----------------------------------------------
  // Returns the number of days spent on side tasks 
  public function getProdDaysSideTasks() {   
    $prodDays = 0;

    $query     = "SELECT codev_timetracking_table.id, codev_timetracking_table.userid, codev_timetracking_table.bugid ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id";
      
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $timeTrack = new TimeTrack($row->id);
            
      // Count only the time spent on $projects
      if ((in_array ($timeTrack->projectId, $this->sideTaskprojectList)) &&
          (!$timeTrack->isVacation())) {
        $prodDays += $timeTrack->duration;
      }
    }
    return $prodDays;
  }

  // ----------------------------------------------
  public function getProductionDaysForecast() {
    global $globalHolidaysList;
        
    $teamProdDaysForecast = 0;
    $nbOpenDaysInPeriod = 0;
        
    for ($i = $this->startTimestamp; $i <= $this->endTimestamp; $i += (60 * 60 * 24)) {
      $dayOfWeek = date("N", $i);
                
      if (($dayOfWeek < 6) && (!in_array(date("Y-m-d", $i), $globalHolidaysList))) { 
        $nbOpenDaysInPeriod++; 
      }
    }
    //echo "DEBUG nbOpenDaysInPeriod = $nbOpenDaysInPeriod<br/>";
        
    // For all the users of the team
    $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
      "FROM  `codev_team_user_table`, `mantis_user_table` ".
      "WHERE  codev_team_user_table.team_id = $this->team_id ".
      "AND    codev_team_user_table.user_id = mantis_user_table.id ".
      "ORDER BY mantis_user_table.username";   
   
    $result = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $holidays = new Holidays($row->user_id, 1900); // year unused
      $daysOf = $holidays->getDaysOfInPeriod($this->startTimestamp, $this->endTimestamp);
                 
      $nbDaysOf = 0;
      foreach ($daysOf as $day => $value) { $nbDaysOf += $value; }
                 
      $teamProdDaysForecast += $nbOpenDaysInPeriod - $nbDaysOf;
      //echo "DEBUG user $row->user_id vacation = $nbDaysOf teamProdDaysForecast $teamProdDaysForecast <br/>";
    }

    return $teamProdDaysForecast;
  }
   
  // ----------------------------------------------
  public function getProductivityRateSideTasks() {
    return $this->getProductivRate($this->sideTaskprojectList);
  }
   
  // ----------------------------------------------
  public function getProductivityRate() {
    return $this->getProductivRate($this->prodProjectList);
  }
   
  // ----------------------------------------------
  // Returns an indication on how many Issues are Resolved in a given timestamp.
  // REM: an issue that has been reopened before endTimestamp will NOT be recorded.
    
  // ProductivityRate = nbResolvedIssues * IssueDifficulty / prodDays

  // $projects: $prodProjectList or $sideTaskprojectList or your own selection.
  private function getProductivRate($projects) {        
    global $status_resolved;
    global $status_closed;
    global $ETA_balance;
    
    $resolvedList = array();
    $productivityRate = 0;
    
    $prodDays = $this->getProductionDays($projects);
    if (0 == $prodDays) {
    	return 0;
    }
    
    // --------
    foreach ($projects as $prid) {
       if ($formatedProjList != "") { $formatedProjList .= ', ';}
       $formatedProjList .= $prid;
    }
    // all bugs which status changed to 'resolved' whthin the timestamp
    $query = "SELECT mantis_bug_table.id ,".
                    "mantis_bug_table.eta, ".
                    "mantis_bug_history_table.new_value, ".
                    "mantis_bug_history_table.old_value, ".
                    "mantis_bug_history_table.date_modified ".
      "FROM `mantis_bug_table`, `mantis_bug_history_table` ".
      "WHERE mantis_bug_table.id = mantis_bug_history_table.bug_id ".
      "AND mantis_bug_table.project_id IN ($formatedProjList)".
      "AND mantis_bug_history_table.field_name='status' ".
      "AND mantis_bug_history_table.date_modified >= $this->startTimestamp ".
      "AND mantis_bug_history_table.date_modified <  $this->endTimestamp ".
      "AND mantis_bug_history_table.new_value = $status_resolved ".
      "ORDER BY mantis_bug_table.id DESC";
    if (isset($_GET['debug'])) { echo "getProductivRate QUERY = $query <br/>"; }
    
    $result = mysql_query($query) or die("Query failed: $query");
    
    while($row = mysql_fetch_object($result)) {
    	
    	// check if the bug has been reopened before endTimestamp
      $issue = new Issue($row->id);
      $latestStatus = $issue->getStatus($this->endTimestamp);
    	if (($latestStatus == $status_resolved) || ($latestStatus == $status_closed)) {

    		// remove doubloons    		
	      if (!in_array ($row->id, $resolvedList)) {
	         if (isset($_GET['debug'])) { echo "getProductivRate Found : bugid = $row->id, old_status=$row->old_value, new_status=$row->new_value, eta=".$ETA_balance[$row->eta]." date_modified=".date("d F Y", $row->date_modified)."<br/>"; }
	      
	         $resolvedList[] = $row->id;
	         $productivityRate += $ETA_balance[$row->eta];
	      }
    	} else {
    		if (isset($_GET['debug'])) { echo "getProductivRate ----REOPENED---- : bugid = $row->id<br/>"; }
    	} 
    	
    }
    
    // -------
    if (isset($_GET['debug'])) { echo "getProductivRate: productivityRate = $productivityRate / $prodDays<br/>"; }
    
    $productivityRate /= $prodDays;

    return $productivityRate;
  }
  
  // ----------------------------------------------
  // Returns an indication on how sideTasks slows down the Production
  // prodRate = nbDays spend on projects / total prodDays * 100

  // projects: list of projects that are considered as not beeing sideTasks
  // prodDays: the number of days worked by the team within the timestamp
  public function getEfficiencyRate() {       
    $prodDays      =             $this->getProdDays();
    $totalProdDays = $prodDays + $this->getProdDaysSideTasks();

    // REM x100 for percentage
    if (0 != $totalProdDays) {
      $prodRate = $prodDays / $totalProdDays * 100;
    } else {
      $prodRate = 0;
    }

    return $prodRate;
  }
        
  // ----------------------------------------------
  // Returns an indication on how Environmental problems slow down the production.
  // EnvProblems can be : Citrix Falldow, Continuous pbs, VMS shutdown, SSL connection loss, etc.

  // A specific task is created in $EnvPbProjectName each time production is stopped.
  // The Est.Effort (BI) field contains the total amount of hours lost by the team
  // during this System breakdown.

  // systemDisponibilityRate = 100 - (nb breakdown hours / prodHours)
  public function getSystemDisponibilityRate() {
    global $IncidentProject;  // SuiviOp
    global $IncidentCategory;  // SuiviOp.Incidents
      
    // The total time spent by the team doing nothing because of incidents
    $teamIncidentDays = 0;

    // Find nb hours spent on SuiviOp.Incidents
    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, codev_timetracking_table.duration ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $issue = new Issue($row->bugid);
   
      if (($issue->projectId  == $IncidentProject) &&
          ($issue->categoryId == $IncidentCategory)) {
                  
        $teamIncidentDays += $row->duration;
        //echo "DEBUG SystemDisponibility found bugid=$row->bugid duration=$row->duration proj=$issue->projectId cat=$issue->categoryId teamIncidentHours=$teamIncidentHours<br/>";
      }
    }

    $prodDays  = $this->getProdDays();

    //echo "DEBUG prodDays $prodDays teamIncidentDays $teamIncidentDays<br/>";

    if (0 != $prodDays) {
      $systemDisponibilityRate = 100 - ($teamIncidentDays / $prodDays);
    } else {
      $systemDisponibilityRate = 0;
    }

    return $systemDisponibilityRate;
  }

  // ----------------------------------------------
  public function getWorkingDaysPerJob($job_id) {
    $workingDaysPerJob = 0;

    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, codev_timetracking_table.duration ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_timetracking_table.jobid = $job_id ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id";
      
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $workingDaysPerJob += $row->duration;
    }
    return $workingDaysPerJob;
  }
        
  // ----------------------------------------------
  public function getWorkingDaysPerProject($project_id) {
    $workingDaysPerProject = 0;

    // Find nb hours spent on the given project
    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, codev_timetracking_table.duration ".
      "FROM `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND   codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND   codev_team_user_table.team_id = $this->team_id";

    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $issue = new Issue($row->bugid);
   
      if ($issue->projectId  == $project_id) {
        $workingDaysPerProject += $row->duration;
      }
    }
    return $workingDaysPerProject;
  }

  // ----------------------------------------------
  // Returns an array of (date => duration) containing all days where duration != 1
  public function checkCompleteDays($userid, $isStrictlyTimestamp = FALSE) {
    $incompleteDays = array();
    $durations = array();          // unique date => sum durations

    // Get all dates that must be checked
    if ($isStrictlyTimestamp) {
      $query     = "SELECT date, duration FROM `codev_timetracking_table` ".
        "WHERE userid = $userid AND date >= $this->startTimestamp AND date < $this->endTimestamp ".
        "ORDER BY date DESC";
    } else {
      $query     = "SELECT date, duration FROM `codev_timetracking_table` WHERE userid = $userid ORDER BY date DESC";
    }
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $durations[$row->date] += $row->duration;
      //echo "durations[$row->date] = ".number_format($durations[$row->date], 5)." (+$row->duration)<br/>";
    }

    // Check
    foreach ($durations as $date => $value) {
      // REM: it looks like PHP has some difficulties to compare a float to '1' !!!
      if (($value < 0.999999999999999) || ($value > 1.000000000000001)) {
        if (isset($_GET['debug'])) { echo "incompleteDays[$date]=".$value."<br/>"; }
        $incompleteDays[$date] = $value;
      }
    }
                
    return $incompleteDays;
  }

  // ----------------------------------------------
  // Find days which are not 'sat' or 'sun' and that have no timeTrack entry.
  public function checkMissingDays($userid) {
    global $globalHolidaysList;
                
    $missingDays = array();

    $user1 = new User($userid);
    $arrivalTimestamp = $user1->getArrivalDate();      
      
    if ($arrivalTimestamp > $this->endTimestamp) {
      // User was not yet present
      return $missingDays;
    }
      
    // User arrived after $this->startTimestamp
    $startT = ($arrivalTimestamp > $this->startTimestamp) ? $arrivalTimestamp : $this->startTimestamp;
      
    $startDayOfYear = date("z", $startT);   
    $endDayOfYear   = date("z", $this->endTimestamp);

    for ($i = $endDayOfYear; $i >= $startDayOfYear; $i--) {
        
      $timestamp = dayofyear2timestamp($i);
      $dayOfWeek = date("N",$timestamp);

      // monday to friday
      if (($dayOfWeek < 6) && (!in_array(date("Y-m-d", $timestamp), $globalHolidaysList))) {                 
                
        $query     = "SELECT COUNT(date) FROM `codev_timetracking_table` WHERE userid = $userid AND date = $timestamp";
        $result    = mysql_query($query) or die("Query failed: $query");
        $nbTuples  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;
            
        if (0 == $nbTuples) {
          //echo "missingDays ".dayofyear2date($i)." <br/>";
          $missingDays[] = $timestamp;
        }
      }
    }

    return $missingDays;
  }

  // ----------------------------------------------
  public function getProjectDetails($project_id) {
    $durationPerCategory = array();

    // Find nb hours spent on the given project
    $query     = "SELECT bugid, duration FROM `codev_timetracking_table` ".
      "WHERE date >= $this->startTimestamp AND date < $this->endTimestamp ";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      $issue = new Issue($row->bugid);
      if ($issue->projectId  == $project_id) {
        $durationPerCategory[$issue->getCategoryName()] += $row->duration;
      }
    }
    return $durationPerCategory;
  }

  // ----------------------------------------------
  // Returns a multiple array containing duration for each day of the week.
  // WARNING: the timestamp must NOT exceed 1 week.

  // returns : $weekTracks[bugid][jobid][dayOfWeek] = duration
  public function getWeekDetails($userid) {       
    $weekTracks = array();
                
    // For all bugs in timestamp
    $query     = "SELECT bugid, jobid, date, duration FROM `codev_timetracking_table` ".
      "WHERE date >= $this->startTimestamp AND date < $this->endTimestamp AND userid = $userid";
    $result    = mysql_query($query) or die("Query failed: $query");
    while($row = mysql_fetch_object($result))
    {
      if (null == $weekTracks[$row->bugid]) {
        $weekTracks[$row->bugid] = array();
        $weekTracks[$row->bugid][$row->jobid] = array();
      }
      if (null == $weekTracks[$row->bugid][$row->jobid]) {
        $weekTracks[$row->bugid][$row->jobid] = array();
      }
      $weekTracks[$row->bugid][$row->jobid][date('N',$row->date)] += $row->duration;
      //echo "weekTracks[$row->bugid][$row->jobid][".date('N',$row->date)."] = ".$weekTracks[$row->bugid][$row->jobid][date('N',$row->date)]." ( + $row->duration)<br/>";
    }
                
    return $weekTracks;
  }
 
} // class TimeTracking

?>