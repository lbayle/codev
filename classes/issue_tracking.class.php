<?php

// -- Issues tracking
include_once "i18n.inc.php";
include_once "issue.class.php";

class IssueTracking {
  var $issueList; 
  
  var $startTimestamp;
  var $endTimestamp;
  var $displayedStatusList;
  
  var $teamid;
  
  public function IssueTracking($teamid) {
   $this->teamid = $teamid;
  	$this->issueList = array();
  }
    
  public function initialize() {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_deferred;
    global $status_resolved;
    global $status_delivered;
    global $status_closed;
    
    global $sideTaskProjectType;
    global $periodStatsExcludedProjectList;
    
    $this->displayedStatusList = array($status_new, 
                                       $status_ack, 
                                       $status_feedback, 
                                       $status_analyzed,
                                       $status_accepted,
                                       $status_openned,
                                       $status_deferred,
                                       $status_resolved,
                                       $status_delivered,
                                       $status_closed);

                                       
      // only projects for specified team
      $projectList = array();
      $query = "SELECT project_id ".
               "FROM `codev_team_project_table` ".
               "WHERE team_id = $this->teamid";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result)) {
         // remove FDL project
      	if (! in_array($row->project_id, $periodStatsExcludedProjectList))  {
            $projectList[] = $row->project_id;
      	} else {
         }
      }
                                       
                                       
      // sideTaskprojects are excluded
      $query = "SELECT DISTINCT mantis_bug_table.id ".
               "FROM `mantis_bug_table`, `codev_team_project_table` ".
               "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
               "AND codev_team_project_table.type != $sideTaskProjectType ";

      // Only for specified Projects   
      if ((isset($projectList)) && (0 != count($projectList))) {
         $formatedProjects = implode( ', ', $projectList);
         $query .= "AND mantis_bug_table.project_id IN ($formatedProjects)";
      }
      $query .= " ORDER BY mantis_bug_table.id DESC";
      
    //$query = "SELECT id FROM `mantis_bug_table` ORDER BY id DESC";
    $result = mysql_query($query) or die("Query failed: $query");

    while($row = mysql_fetch_object($result))
    {
      $issue = new Issue ($row->id);
      $issue->computeDurations();
      $this->issueList[$row->id] = $issue; 
    }
  }
  
  // ------------------------------------------
  // Table Avancement / fiche
  public function forseingTableDisplay() {
    global $status_delivered;
    global $status_resolved;
    global $status_closed;

    echo "<table>\n";
    echo "<caption>".T_("Progress / Task")."</caption>";
    echo "<tr>\n";
    echo "<th>".T_("Mantis")."</th>\n";
    echo "<th>".T_("TC Issue")."</th>\n";
    echo "<th>".T_("Description")."</th>\n";
    echo "<th>".T_("Submission Date")."</th>\n";
    echo "<th>".T_("ETA")."</th>\n";
    echo "<th title='BI + BS'>".T_("Effort Estim")."</th>\n";
    echo "<th title='".T_("Est effort from TimeTracking")."'>".T_("Elapsed")."</th>\n";
    echo "<th>".T_("Remaining")."</th>\n";
    echo "<th>".T_("Current Status")."</th>\n";
    echo "<th>".T_("Product Version")."</th>\n";
    echo "<th>".T_("Drift")."</th>\n";
    echo "</tr>\n";
  
    foreach ($this->issueList as $bugId => $tmpIssue) {
      // REM do not display SuiviOp tasks
      if (!$tmpIssue->isSideTaskIssue()) {
        echo "<tr>\n";
        echo "<td width='55'>".mantisIssueURL($bugId)."</td>\n";
        echo "<td>$tmpIssue->tcId</td>\n";
        echo "<td>$tmpIssue->summary</td>\n";
        echo "<td>".date("d M Y", $tmpIssue->dateSubmission)."</td>\n";
        echo "<td>".$tmpIssue->getEtaName()."</td>\n";
        echo "<td title='$tmpIssue->effortEstim + $tmpIssue->effortAdd'>".($tmpIssue->effortEstim + $tmpIssue->effortAdd)."</td>\n";
        echo "<td>".$tmpIssue->elapsed."</td>\n";
        echo "<td>".$tmpIssue->remaining."</td>\n";
        echo "<td>".$tmpIssue->getCurrentStatusName()."</td>\n";
        echo "<td>".$tmpIssue->version."</td>\n";
        $derive = $tmpIssue->getDrift(false);
        echo "<td style='background-color: ".$tmpIssue->getDriftColor($derive)."'>".($derive)."</td>\n";
        echo "</tr>\n";
      }
    }
    echo "</table>\n";
  }
    
  // ------------------------------------------
  // Table Repartition du temps par status
  public function durationsTableDisplay() {
    global $statusNames;
    
    echo "<div>\n";
    
    echo "<table>\n";
    echo "<caption>".T_("Time allocation by status")."</caption>";
    echo "<tr>\n";
    echo "<th>Mantis</th>\n";
    echo "<th>".T_("TC Issue")."</th>\n";
    echo "<th>".T_("Description")."</th>\n";
    foreach($this->displayedStatusList as $status) {
      echo "<th>".$statusNames[$status]."</th>\n";
    }
    echo "</tr>\n";
      
    foreach ($this->issueList as $bugId => $tmpIssue) {
      // REM do not display SuiviOp tasks
      if (!$tmpIssue->isSideTaskIssue()) {
        echo "<tr>\n";
        echo "<td>".mantisIssueURL($tmpIssue->bugId)."</td>\n";
        echo "<td>$tmpIssue->tcId</td>\n";
        echo "<td>$tmpIssue->summary</td>\n";
        
        foreach($this->displayedStatusList as $status) {
          $res = getDurationLiteral($tmpIssue->statusList[$status]->duration);
          if ($status == $tmpIssue->currentStatus) {
            echo "<td>$res</td>\n";
          } else {
            echo "<td>$res</td>\n";
          }
        }
        echo "</tr>\n";
      }
    }
    echo "</table>\n";
    echo "</div>\n";
  }

  
} // end class IssueTracking

?>
