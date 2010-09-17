<?php

// -- FDJ specificities for IssueTracking

include_once "issue.class.php";
include_once "issue_fdj.class.php";
include_once "issue_tracking.class.php";

class IssueTrackingFDJ extends IssueTracking {
        
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
    global $status_feedback_ATOS;
    global $status_feedback_FDJ;
    
    global $workingProjectType;
    global $periodStatsExcludedProjectList;
    
    $this->displayedStatusList = array($status_new, 
                                       $status_ack, 
                                       $status_feedback_ATOS, 
                                       $status_feedback_FDJ, 
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
               "AND codev_team_project_table.type = $workingProjectType ";

      // Only for specified Projects   
      if ((isset($projectList)) && (0 != count($projectList))) {
         $formatedProjects = simpleListToSQLFormatedString($projectList);
         $query .= "AND mantis_bug_table.project_id IN ($formatedProjects)";
      }
                                       
      $query .= " ORDER BY mantis_bug_table.id DESC";
      
                                       
    //$query = "SELECT id FROM `mantis_bug_table` ORDER BY id DESC";
    $result = mysql_query($query) or die("Query failed: $query");

    while($row = mysql_fetch_object($result))
    {
      $issue = new IssueFDJ ($row->id);
      $issue->computeDurations();
      $this->issueList[$row->id] = $issue; 
    }
  }
} // class

?>

