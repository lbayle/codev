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

// -- CALCULATE DURATIONS --
// Status & Issue classes

class PeriodStats {
  var $startTimestamp;
  var $endTimestamp;

  // The number of issue which current state is 'status' within the timestamp
  var $statusCountList;

  // The bugIds of issues which current state are 'status' within the timestamp
  // REM: $statusIssueList is an array containing lists of bugIds
  var $statusIssueList;

  var $submittedList;
  var $deltaResolvedList;


  // The projects NOT listed here will be excluded from statistics
  var $projectList;

  // The Projects which type is NOT listed here will be excluded from statistics
  var $projectTypeList;

  // -------------------------------------------------
  public function PeriodStats($startTimestamp, $endTimestamp) {

  	global $workingProjectType;
  	global $noCommonProjectType;

    $this->startTimestamp = $startTimestamp;
    $this->endTimestamp = $endTimestamp;

    $this->statusCountList     = array();
    $this->statusIssueList     = array();



    $this->projectList     = array();
    $this->projectTypeList = array();

    // default values
    $this->projectTypeList[] = $workingProjectType;
    $this->projectTypeList[] = $noCommonProjectType;
  }

  // -------------------------------------------------
  // Returns a list of bugId which status is $status
  public function getIssueList($status) {
    return $this->statusIssueList[$status];
  }

  // -------------------------------------------------
  // Returns the number of issues which status is $status
  public function getNbIssues($status) {
    return count($this->statusIssueList[$status]);
  }

  // -------------------------------------------------
  public function computeStats() {
    global $status_new;

    $statusNames = Config::getInstance()->getValue("statusNames");
    ksort($statusNames);

    foreach ($statusNames as $s => $sname) {
      $this->statusCountList[$s] = 0;
      $this->statusIssueList[$s] = array();
    }

    // Compute stats
    $this->statusCountList[$status_new] = $this->countIssues_new();
    $this->countIssues_other();
  }


  // -------------------------------------------------
  public function computeSubmittedResolved() {

    // Compute SubmittedResolved
    $this->submittedList     = $this->countIssues_submitted();
    $this->deltaResolvedList = $this->countIssues_deltaResolved();
  }



  // -------------------------------------------------
  // Count the nb of issues submitted in [startTimestamp, endTimestamp]
  // REM: select only projectTypes in $projectTypeList
  // REM: select only projects in $projectList, if $projectList = 0 then ALL projects.
  private function countIssues_submitted() {

    $submittedList = array();

    $formatedProjectTypes = implode( ', ', $this->projectTypeList);

    // sideTaskprojects are excluded
    $query = "SELECT DISTINCT mantis_bug_table.id, mantis_bug_table.date_submitted, mantis_bug_table.project_id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.date_submitted >= $this->startTimestamp AND mantis_bug_table.date_submitted < $this->endTimestamp ".
      "AND mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type IN ($formatedProjectTypes) ";

    // Only for specified Projects
    if ((isset($this->projectList)) && (0 != count($this->projectList))) {
        	$formatedProjects = implode( ', ', $this->projectList);
    	$query .= "AND mantis_bug_table.project_id IN ($formatedProjects)";
    }
    if (isset($_GET['debug_sql'])) { echo "countIssues_submitted(): query = $query<br/>"; }

    $result = mysql_query($query) or die("Query failed: $query");
    
    if (isset($_GET['debug_sql'])) {
        echo "Query countIssues_submitted : $query <br/>";
    } 
    
    while($row = mysql_fetch_object($result))
    {
    	$submittedList[] = $row->id;
                        
     if (isset($_GET['debug'])) { 
      	echo "DEBUG submitted countIssues_submitted $row->id   date < ".date("m Y", $this->endTimestamp)." project $row->project_id <br/>";
      }
   }

    return count($submittedList);
  }

  // -------------------------------------------------
  // Count the nb of 'new' issues in [startTimestamp, endTimestamp]
  private function countIssues_new() {
    global $status_new;

    $count_new = -1;

    $this->statusCountList[$status_new] = 0;

    // TODO countIssues_new()
    return $count_new;
  }

  // -------------------------------------------------
  // REM: select only projectTypes in $projectTypeList
  // REM: select only projects in $projectList, if $projectList = 0 then ALL projects.
  private function countIssues_other() {

    $formatedProjectTypes = implode( ', ', $this->projectTypeList);


  	 // select all but SideTasks & rem 'doublons'
    $query = "SELECT DISTINCT mantis_bug_table.id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type IN ($formatedProjectTypes) ";


    // Only for specified Projects
        if ((isset($this->projectList)) && (0 != count($this->projectList))) {
         $formatedProjects = implode( ', ', $this->projectList);
      $query .= "AND mantis_bug_table.project_id IN ($formatedProjects) ";
    }
        if (isset($_GET['debug_sql'])) { echo "countIssues_other(): query = $query<br/>"; }

    $result = mysql_query($query) or die("Query failed: $query");

    // For each bugId
    while($row = mysql_fetch_object($result))
    {
      $bugId1 = $row->id;
      // Find most recent transitions where date < $endTimestamp
      $query2 = "SELECT bug_id, new_value, old_value, date_modified ".
        "FROM `mantis_bug_history_table` ".
        "WHERE field_name='status' ".
        "AND bug_id =$bugId1 ".
        "AND date_modified < $this->endTimestamp ".
        "ORDER BY id DESC";

      $result2 = mysql_query($query2) or die("Query failed: $query2");

      if (0 != mysql_num_rows($result2)) {
        $row2 = mysql_fetch_object($result2);

        $this->statusCountList[$row2->new_value]++;
        $this->statusIssueList[$row2->new_value][] = $bugId1;
      }
    }
    if (isset($_GET['debug'])) {
      echo "date < ".date("m Y", $this->endTimestamp)."<br/>";
      foreach ($this->statusIssueList as $state => $bugList) {
        foreach ($bugList as $bug) {
          echo "#$bug ($state)<br/>";
        }
      }
    }
  }


  // -------------------------------------------------
  // REM returns the number of issues resolved in that period
  // reopened issues are excluded
  function countIssues_deltaResolved() {

    $resolved_status_threshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);

    $resolvedList = array();
    $issueList = array();

    $formatedProjectTypes = implode( ', ', $this->projectTypeList);

    // all bugs which status changed to 'resolved' whthin the timestamp
    $query = "SELECT mantis_bug_table.id, ".
      "mantis_bug_history_table.new_value, ".
      "mantis_bug_history_table.old_value, ".
      "mantis_bug_history_table.date_modified ".
      "FROM `mantis_bug_table`, `mantis_bug_history_table`, `codev_team_project_table` ".

      "WHERE mantis_bug_table.id = mantis_bug_history_table.bug_id ".
      "AND   mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type IN ($formatedProjectTypes) ".

      "AND mantis_bug_history_table.field_name='status' ".
      "AND mantis_bug_history_table.date_modified >= $this->startTimestamp ".
      "AND mantis_bug_history_table.date_modified <  $this->endTimestamp ".
      "AND mantis_bug_history_table.new_value = $resolved_status_threshold ";

    // Only for specified Projects
    if ((isset($this->projectList)) && (0 != count($this->projectList))) {
         $formatedProjects = implode( ', ', $this->projectList);
      $query .= "AND mantis_bug_table.project_id IN ($formatedProjects) ";
    }

    $query .= "ORDER BY mantis_bug_table.id DESC";


    if (isset($_GET['debug'])) { echo "countIssues_deltaResolved QUERY = $query <br/>"; }

    $result = mysql_query($query) or die("Query FAILED: $query");

    while($row = mysql_fetch_object($result)) {
      $issue = IssueCache::getInstance()->getIssue($row->id);

      // check if the bug has been reopened before endTimestamp
      $latestStatus = $issue->getStatus($this->endTimestamp);
      if ($latestStatus >= $resolved_status_threshold) {
        // remove doubloons
        if (!in_array ($issue->bugId, $resolvedList)) {

          $resolvedList[] = $issue->bugId;
          $issueList[] = $issue;
        }
      } else {
        if (isset($_GET['debug'])) { echo "PeriodStats->countIssues_deltaResolved() REOPENED : bugid = $issue->bugId<br/>"; }
      }
    }

    return count($issueList);
  }





} // end class PeriodStats

?>
