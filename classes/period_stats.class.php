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

include_once "project.class.php";

// -- CALCULATE DURATIONS --
// Status & Issue classes

class PeriodStats {

	private $logger;

	var $startTimestamp;
  var $endTimestamp;

  // The number of issue which current state is 'status' within the timestamp
  var $statusCountList;

  // The bugIds of issues which current state are 'status' within the timestamp
  // REM: $statusIssueList is an array containing lists of bugIds
  var $statusIssueList;

  // The projects NOT listed here will be excluded from statistics
  var $projectList;

  // The Projects which type is NOT listed here will be excluded from statistics
  var $projectTypeList;

  // -------------------------------------------------
  public function PeriodStats($startTimestamp, $endTimestamp) {
     $this->logger = Logger::getLogger(__CLASS__);

    $this->startTimestamp = $startTimestamp;
    $this->endTimestamp = $endTimestamp;

    $this->statusCountList     = array();
    $this->statusIssueList     = array();



    $this->projectList     = array();
    $this->projectTypeList = array();

    // default values
    $this->projectTypeList[] = Project::type_workingProject;
    $this->projectTypeList[] = Project::type_noCommonProject;
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

        $result = mysql_query($query);
      if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }

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

      $result2 = mysql_query($query2);
      if (!$result2) {
      	$this->logger->error("Query FAILED: $query2");
      	$this->logger->error(mysql_error());
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }

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

} // end class PeriodStats

?>
