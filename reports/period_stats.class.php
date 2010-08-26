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

  public function PeriodStats($startTimestamp, $endTimestamp) {
    $this->startTimestamp = $startTimestamp;
    $this->endTimestamp = $endTimestamp;

    $this->statusCountList = array();
    $this->statusIssueList = array();

    //echo "this->startTimestamp = $this->startTimestamp<br/>";
    //echo "this->endTimestamp = $this->endTimestamp<br/>";
  }

  // Returns a list of bugId which status is $status
  public function getIssueList($status) {
    return $this->statusIssueList[$status];
  }

  // Returns the number of issues which status is $status
  public function getNbIssues($status) {
    return count($this->statusIssueList[$status]);
  }

  public function computeStats() {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_resolved;
    global $status_closed;

    //$this->statusCountList[$status_feedback_ATOS] = 0;
    //$this->statusCountList[$status_feedback_FDJ] = 0;

    $this->statusCountList["submitted"]      = 0;
    $this->statusCountList[$status_new]      = 0;
    $this->statusCountList[$status_feedback] = 0;
    $this->statusCountList[$status_ack]      = 0;
    $this->statusCountList[$status_analyzed] = 0;
    $this->statusCountList[$status_accepted] = 0;
    $this->statusCountList[$status_openned]  = 0;
    $this->statusCountList[$status_resolved] = 0;
    $this->statusCountList[$status_closed]   = 0;

    $this->statusIssueList["submitted"]      = array();
    $this->statusIssueList[$status_new]      = array();
    $this->statusIssueList[$status_feedback] = array();
    $this->statusIssueList[$status_ack]      = array();
    $this->statusIssueList[$status_analyzed] = array();
    $this->statusIssueList[$status_accepted] = array();
    $this->statusIssueList[$status_openned]  = array();
    $this->statusIssueList[$status_resolved] = array();
    $this->statusIssueList[$status_closed]   = array();

    // Compute stats
    $this->countIssues_submitted();
    $this->statusCountList[$status_new] = $this->countIssues_new();
    $this->countIssues_other();
  }

  // Count the nb of issues submitted in [startTimestamp, endTimestamp]
  // REM: sideTaskprojects are excluded
  private function countIssues_submitted() {
    $this->statusCountList["submitted"] = 0;

    // sideTaskprojects are excluded (type != 0)
    $query = "SELECT mantis_bug_table.id, mantis_bug_table.date_submitted, mantis_bug_table.project_id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.date_submitted >= $this->startTimestamp AND mantis_bug_table.date_submitted < $this->endTimestamp ".
      "AND mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type = 0";
                
    $result = mysql_query($query) or die("Query failed: $query");

    while($row = mysql_fetch_object($result))
    {
      $this->statusCountList["submitted"]++;
      $this->statusIssueList["submitted"][] = $row->id;
                        
      //echo "DEBUG submitted $row->id   date < ".date("m Y", $this->endTimestamp)." project $row->project_id <br/>";
    }

    return $count_new;
  }

  // Count the nb of 'new' issues in [startTimestamp, endTimestamp]
  private function countIssues_new() {
    global $status_new;
      
    $count_new = -1;
      
    $this->statusCountList[$status_new] = 0;
      
    // TODO 
    return $count_new;
  }

  // REM: sideTaskprojects are excluded
  private function countIssues_other() {

  	 // select all but SuiviOp.
    $query = "SELECT mantis_bug_table.id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type = 0";
                        
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
    /*if (isset($_GET['debug'])) {
      echo "date < ".date("m Y", $this->endTimestamp)."<br/>";
      foreach ($this->statusIssueList as $state => $bugList) {
        foreach ($bugList as $bug) {
          echo "#$bug ($state)<br/>";
        }
      }
    }*/
  }
      
  // -------------------------------------------------
  // Find all issues at $status within timestamp, and compute total Drift
  // REM: sideTaskprojects are excluded
  public function getDrift($status) {          
    global $statusNames;
                
    $derive = 0;

    $query = "SELECT mantis_bug_table.id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type = 0";
                        
    $result = mysql_query($query) or die("Query failed: $query");

    // For each bugId
    while($row = mysql_fetch_object($result))
    {
      $bugId1 = $row->id;
      // Find most recent transitions where $startTimestamp <= date < $endTimestamp
      $query2 = "SELECT bug_id, new_value, old_value, date_modified FROM `mantis_bug_history_table` ".
        "WHERE field_name='status' AND bug_id =$bugId1 ".
        "AND date_modified >= $this->startTimestamp AND date_modified < $this->endTimestamp ORDER BY id DESC";
      $result2 = mysql_query($query2) or die("Query failed: $query2");

      if (0 != mysql_num_rows($result2)) {
        $row2 = mysql_fetch_object($result2);

        if ($row2->new_value == $status) {
          $issue = new Issue($bugId1);

          // -- compute total drift
          $issueDrift = $issue->getDrift();
          $derive    += $issueDrift;
          if (isset($_GET['debug'])) { echo "PeriodStats->getDrift($status,$bugId1,proj$issue->projectId)=".$issueDrift."<br/>"; }
        }
      }
    }
    if (isset($_GET['debug'])) { 
      echo ("derive totale ($statusNames[$status]/".date("F Y", $this->startTimestamp).") = $derive<br/>");
    }
    
    
    return $derive;
  }

  // -------------------------------------------------
  // Find all issues at $status within timestamp, and compute total Drift
  // REM: sideTaskprojects are excluded
  public function getDriftStats($status) {          
    global $statusNames;
                
    $derive = 0;
    $deriveETA = 0;

    $nbDriftsNeg   = 0;
    $nbDriftsEqual = 0;
    $nbDriftsPos   = 0;
    $nbDriftsNegETA   = 0;
    $nbDriftsEqualETA = 0;
    $nbDriftsPosETA   = 0;
    
    $driftNeg   = 0;
    $driftEqual = 0;
    $driftPos   = 0;
    $driftNegETA   = 0;
    $driftEqualETA = 0;
    $driftPosETA   = 0;
    
    $query = "SELECT mantis_bug_table.id ".
      "FROM `mantis_bug_table`, `codev_team_project_table` ".
      "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.type = 0";
                        
    $result = mysql_query($query) or die("Query failed: $query");

    // For each bugId
    while($row = mysql_fetch_object($result))
    {
      $bugId1 = $row->id;
      // Find most recent transitions where $startTimestamp <= date < $endTimestamp
      $query2 = "SELECT bug_id, new_value, old_value, date_modified FROM `mantis_bug_history_table` ".
        "WHERE field_name='status' AND bug_id =$bugId1 ".
        "AND date_modified >= $this->startTimestamp AND date_modified < $this->endTimestamp ORDER BY id DESC";
      $result2 = mysql_query($query2) or die("Query failed: $query2");

      if (0 != mysql_num_rows($result2)) {
        $row2 = mysql_fetch_object($result2);

        if ($row2->new_value == $status) {
          $issue = new Issue($bugId1);

          // -- compute total drift
          $issueDrift = $issue->getDrift();
          $derive    += $issueDrift;
          if (isset($_GET['debug'])) { echo "PeriodStats->getDrift($status,$bugId1,proj$issue->projectId)=".$issueDrift."<br/>"; }
          
          $issueDriftETA = $issue->getDriftETA();
          $deriveETA += $issueDriftETA;
          if (isset($_GET['debug'])) { echo "PeriodStats->getDriftETA($status,$bugId1,proj$issue->projectId)=".$issueDriftETA."<br/>"; }
          
          // get drift stats. equal is when drif = +-1
          if ($issueDrift < -1) {
            $nbDriftsNeg++;
            $driftNeg += $issueDrift;
          } elseif ($issueDrift > 1){
            $nbDriftsPos++;
            $driftPos += $issueDrift;
          } else {
            $nbDriftsEqual++;
            $driftEqual += $issueDrift;
          }

          if ($issueDriftETA < -1) {
            $nbDriftsNegETA++;
            $driftNegETA += $issueDriftETA;
          } elseif ($issueDriftETA > 1){
            $nbDriftsPosETA++;
            $driftPosETA += $issueDriftETA;
          } else {
            $nbDriftsEqualETA++;
            $driftEqualETA += $issueDriftETA;
          }
        
        }
      }
    }
    if (isset($_GET['debug'])) { 
      echo ("derive totale ($statusNames[$status]/".date("F Y", $this->startTimestamp).") = $derive<br/>");
      echo ("derive totale ETA($statusNames[$status]/".date("F Y", $this->startTimestamp).") = $deriveETA<br/>");
      
      echo("Nbre Bugs en dérive        : $nbDriftsPos<br/>");
      echo("Nbre Bugs a l'equilibre    : $nbDriftsEqual<br/>");
      echo("Nbre Bugs en avance        : $nbDriftsNeg<br/>");
      echo("Nbre Bugs en dérive     ETA: $nbDriftsPosETA<br/>");
      echo("Nbre Bugs a l'equilibre ETA: $nbDriftsEqualETA<br/>");
      echo("Nbre Bugs en avance     ETA: $nbDriftsNegETA<br/>");
    }
    
    $driftStats = array();
    $driftStats["totalDrift"]       = $derive;
    $driftStats["totalDriftETA"]    = $deriveETA;
    $driftStats["driftPos"]         = $driftPos;
    $driftStats["driftEqual"]       = $driftEqual;
    $driftStats["driftNeg"]         = $driftNeg;
    $driftStats["driftPosETA"]      = $driftPosETA;
    $driftStats["driftEqualETA"]    = $driftEqualETA;
    $driftStats["driftNegETA"]      = $driftNegETA;
    $driftStats["nbDriftsPos"]      = $nbDriftsPos;
    $driftStats["nbDriftsEqual"]    = $nbDriftsEqual;
    $driftStats["nbDriftsNeg"]      = $nbDriftsNeg;
    $driftStats["nbDriftsPosETA"]   = $nbDriftsPosETA;
    $driftStats["nbDriftsEqualETA"] = $nbDriftsEqualETA;
    $driftStats["nbDriftsNegETA"]   = $nbDriftsNegETA;
    
    
    return $driftStats;
  }
  
  
  // -------------------------------------------------
  // Returns a string containing an html table line with the counts
  public function displayOneLineHtmlTable() {           
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_resolved;
    global $status_closed;

    $derive = $this->getDrift($status_resolved) + $this->getDrift($status_closed);

    $tableLine = "<tr>\n";
    $tableLine .= "<td>".date("F Y", $this->startTimestamp)."</td>\n";

    $res = $this->statusCountList["submitted"];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_new];
    $tableLine .= "<td>$res</td>\n"; // TODO new
    $res = $this->statusCountList[$status_ack];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_feedback];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_analyzed];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_accepted];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_openned];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_resolved];
    $tableLine .= "<td>$res</td>\n";
    $res = $this->statusCountList[$status_closed];
    $tableLine .= "<td>$res</td>\n";
    $tableLine .= "<td>$derive</td>\n";
    $tableLine .= "</tr>\n";
                 
    return $tableLine;             
  }
} // end class PeriodStats

?>
