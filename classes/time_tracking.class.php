<?php
/*
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
*/

require_once('Logger.php');

require_once "constants.php";

include_once "time_track.class.php";
include_once "issue.class.php";
include_once "user.class.php";
include_once "team.class.php";
include_once "holidays.class.php";

/**
 * TimeTracking facilities
 */
class TimeTracking {

  private $logger;

  var $startTimestamp;
  var $endTimestamp;
  var $prodDays;

  var $team_id;

  var $prodProjectList;     // projects that are not sideTasks, and not in noStatsProject
  var $sideTaskprojectList;

  // ----------------------------------------------
  public function __construct($startTimestamp, $endTimestamp, $team_id = NULL) {

    $this->logger = Logger::getLogger(__CLASS__);

//    Note: teamid is null in time_tracking.php because you do not specify it to set timetracks...

    if ((NULL == $team_id) || ($team_id <= 0)) {
       #echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
       $e = new Exception("TimeTracking->team_id not set !");
       $this->logger->error("EXCEPTION TimeTracking constructor: ".$e->getMessage());
       $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
       //throw $e;
    }

    $this->startTimestamp = $startTimestamp;
    $this->endTimestamp   = $endTimestamp;
    $this->team_id       = (isset($team_id)) ? $team_id : -1;

    $this->initialize();
  }

  // ----------------------------------------------
  public function initialize() {

    $this->prodProjectList     = array();
    $this->sideTaskprojectList = array();

    $query = "SELECT project_id, type FROM `codev_team_project_table` WHERE team_id = $this->team_id";
    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    $teamidList = array($this->team_id);
    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
       $project1 = ProjectCache::getInstance()->getProject($row->project_id);
       $ptype = $project1->getProjectType($teamidList);
    	 switch ($ptype) {

    	   case Project::type_sideTaskProject:
    	      $this->sideTaskprojectList[] = $row->project_id;
    	      break;
    	   case Project::type_workingProject:  // no break;
    	   case Project::type_noCommonProject:
    	      $this->prodProjectList[]     = $row->project_id;
              break;
           case  Project::type_noStatsProject:
              // known type, but nothing to do
              break;
           default:
              echo "WARNING: Timetracking->initialize() unknown project type ($row->type) !<br/>";
    	}
    }
  }

  // ----------------------------------------------
  /**
   * Returns the number of days worked by the team within the timestamp
   */
  public function getProdDays() {
    return $this->getProductionDays($this->prodProjectList);
  }

  // ----------------------------------------------
  /**
   * Returns the number of days worked by the team within the timestamp
   * - Observers excluded
   */
  private function getProductionDays($projects) {

    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;

    $prodDays = 0;

    $query     = "SELECT codev_timetracking_table.id, codev_timetracking_table.userid, codev_timetracking_table.bugid ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id ".
      "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

// TODO check patch from FDJ 8b003033391c84142787c3379a518a3ef7283587
//      "AND (codev_team_user_table.departure_date = 0 or codev_team_user_table.departure_date >=$this->startTimestamp)";


    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id);

      // Count only the time spent on $projects
      if (in_array ($timeTrack->projectId, $projects)) {
        $prodDays += $timeTrack->duration;
      }
    }
    return $prodDays;
  }

  // ----------------------------------------------
  /** Returns the number of days spent on side tasks EXCEPT Vacations
   * - Observers excluded
   *
   * @param $isDeveloppersOnly : do not include time spent by Managers (default = false)
   */
  public function getProdDaysSideTasks($isDeveloppersOnly = false) {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;
    $prodDays = 0;

    // select tasks within timestamp, where user is in the team
    $query     = "SELECT codev_timetracking_table.id, codev_timetracking_table.userid, codev_timetracking_table.bugid ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id ".
      "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id);

      // do not include Managers
      if (true == $isDeveloppersOnly) {
      	$user = UserCache::getInstance()->getUser($timeTrack->userId);
      	if (false == $user->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) {
      		continue; // skip this timeTrack
      	}
      }
      try {
	      $issue = IssueCache::getInstance()->getIssue($row->bugid);
	      if ((in_array ($issue->projectId, $this->sideTaskprojectList)) &&
			      (!$issue->isVacation(array($this->team_id)))) {
		      $prodDays += $timeTrack->duration;
	      }
      } catch (Exception $e) {
      	$this->logger->error("getProdDaysSideTasks(): issue $issue->bugId: ".$e->getMessage());
      }
    }
    return $prodDays;
  }

  // ----------------------------------------------
  /** Returns the number of days spent on Management Tasks
   * - Observers excluded
   */
   public function getManagementDays() {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;
     $prodDays = 0;

     // select tasks within timestamp, where user is in the team
     $query     = "SELECT codev_timetracking_table.id, codev_timetracking_table.userid, codev_timetracking_table.bugid ".
           "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
           "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
           "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
           "AND    codev_team_user_table.team_id = $this->team_id ".
           "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

     $result = SqlWrapper::getInstance()->sql_query($query);
     if (!$result) {
        echo "<span style='color:red'>ERROR: Query FAILED</span>";
        exit;
     }

     while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
     {
        $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id);

        $user = UserCache::getInstance()->getUser($timeTrack->userId);

        if ((!$user->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) &&
            (!$user->isTeamManager($this->team_id, $this->startTimestamp, $this->endTimestamp))) {
           $this->logger->warn("getManagementDays(): timetrack $row->id not included because user $user->id (".$user->getName().") was not a DEVELOPPER/MANAGER within the timestamp");
           continue; // skip this timeTrack
        }

        try {
           $issue = IssueCache::getInstance()->getIssue($row->bugid);
           
	        if ((in_array ($issue->projectId, $this->sideTaskprojectList)) &&
			        ($issue->isProjManagement(array($this->team_id)))) {
		        $prodDays += $timeTrack->duration;
	        }
        } catch (Exception $e) {
	        $this->logger->error("getManagementDays(): issue $issue->bugId: ".$e->getMessage());
        }
     }
     return $prodDays;
  }


  // ----------------------------------------------
  public function getAvailableWorkload() {
    $accessLevel_dev     = Team::accessLevel_dev;
    #$accessLevel_manager = Team::accessLevel_manager;

    $teamProdDaysForecast = 0;

    // For all the users of the team
    $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
      "FROM  `codev_team_user_table`, `mantis_user_table` ".
      "WHERE  codev_team_user_table.team_id = $this->team_id ".
      "AND    codev_team_user_table.access_level = $accessLevel_dev ".
      //"AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager)".
      "AND    codev_team_user_table.user_id = mantis_user_table.id ".
      "ORDER BY mantis_user_table.username";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      $user = UserCache::getInstance()->getUser($row->user_id);
      $teamProdDaysForecast += $user->getAvailableWorkload($this->startTimestamp, $this->endTimestamp, $this->team_id);
    }

    return $teamProdDaysForecast;
  }





  // ----------------------------------------------
  public function getResolvedDriftStats($withSupport = true) {

    $issueList = $this->getResolvedIssues($this->prodProjectList);
    if (0 != count($issueList)) {
      return $this->getIssuesDriftStats($issueList, $withSupport);
    } else {
    	return array();
    }

  }

  // ----------------------------------------------
  public function getTimeDriftStats() {

    global $deliveryDateCustomField;

    $issueList = array();

    // all issues which deliveryDate is in the period.
    $query = "SELECT bug_id FROM `mantis_custom_field_string_table` ".
             "WHERE field_id = $deliveryDateCustomField ".
             "AND value >= $this->startTimestamp ".
             "AND value <  $this->endTimestamp ".
             "ORDER BY bug_id ASC";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $issue = IssueCache::getInstance()->getIssue($row->bug_id);

      // if a deadLine is specified
      if ((in_array($issue->projectId, $this->prodProjectList)) &&
          (NULL != $issue->getDeadLine())) {
      	$issueList[] = $issue;
      }
    }
    if (0 != count($issueList)) {
      return $this->getIssuesTimeDriftStats($issueList);
    } else {
    	return array();
    }

  }

  // -------------------------------------------------
  /**
   * Returns all Issues resolved in the period and having not been re-opened
   *
   * @param $projects  if NULL: prodProjectList
   * @return a list of Issue class instances
   */
  public function getResolvedIssues($projects = NULL) {
    global $status_closed;

    $resolvedList = array();
    $issueList = array();

    // --------
    if (NULL == $projects) {$projects = $this->prodProjectList;}
    $formatedProjList = implode( ', ', $projects);

    if ("" == $formatedProjList) {
      echo "<div style='color:red'>ERROR getResolvedIssues: no project defined for this team !<br/></div>";
      return 0;
    }

    // all bugs which status changed to 'resolved' whthin the timestamp
    $query = "SELECT mantis_bug_table.id, ".
      "mantis_bug_history_table.new_value, ".
      "mantis_bug_history_table.old_value, ".
      "mantis_bug_history_table.date_modified ".
      "FROM `mantis_bug_table`, `mantis_bug_history_table` ".
      "WHERE mantis_bug_table.id = mantis_bug_history_table.bug_id ".
      "AND mantis_bug_table.project_id IN ($formatedProjList) ".
      "AND mantis_bug_history_table.field_name='status' ".
      "AND mantis_bug_history_table.date_modified >= $this->startTimestamp ".
      "AND mantis_bug_history_table.date_modified <  $this->endTimestamp ".
      "AND mantis_bug_history_table.new_value = get_project_resolved_status_threshold(project_id) ".
      "ORDER BY mantis_bug_table.id DESC";

    if (isset($_GET['debug'])) { echo "getDrift_new QUERY = $query <br/>"; }

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $issue = IssueCache::getInstance()->getIssue($row->id);

      // check if the bug has been reopened before endTimestamp
      $latestStatus = $issue->getStatus($this->endTimestamp);
       if (($latestStatus == $issue->getBugResolvedStatusThreshold()) || ($latestStatus == $status_closed)) {

        // remove doubloons
        if (!in_array ($issue->bugId, $resolvedList)) {

          $resolvedList[] = $issue->bugId;
          $issueList[] = $issue;
        }
      } else {
        if (isset($_GET['debug'])) { echo "TimeTracking->getResolvedIssues() REOPENED : bugid = $issue->bugId<br/>"; }
      }
    }
    return $issueList;
  }



  // ----------------------------------------------
  /**
   * return stats on which Issues where delivered after the DeadLine
   *
   * @param array $issueList
   *
   * TODO move this method to IssueSelection class
   */
  public function getIssuesTimeDriftStats($issueList) {

    $nbDriftsNeg   = 0;
    $nbDriftsEqual = 0;
    $nbDriftsPos   = 0;

    $driftNeg   = 0;
    $driftEqual = 0;
    $driftPos   = 0;

    if (NULL == $issueList) {
      echo "<div style='color:red'>ERROR getIssuesTimeDriftStats: Issue List is NULL !<br/></div>";
      return array();
    }
    if (0== count($issueList)) {
      echo "<div style='color:red'>ERROR getIssuesTimeDriftStats: Issue List is empty !<br/></div>";
      return array();
    }


    foreach ($issueList as $issue) {


    	$issueDrift = $issue->getTimeDrift();  // returns an integer or an error string
    	if (! is_string($issueDrift)) {

    		if ($issueDrift <= 0) {

            $nbDriftsNeg++;
            $driftNeg += $issueDrift;

            if ($formatedBugidNegList != "") { $formatedBugidNegList .= ', '; }
            $formatedBugidNegList .= issueInfoURL($issue->bugId, $issue->summary);

         } else {
            $nbDriftsPos++;
            $driftPos += $issueDrift;

            if ($formatedBugidPosList != "") { $formatedBugidPosList .= ', '; }
            $formatedBugidPosList .= issueInfoURL($issue->bugId, $issue->summary)."<span title='".T_("nb days")."'>(".round($issueDrift).")<span>";
         }
    	}
    } // foreach

    $driftStats = array();
    $driftStats["driftPos"]         = $driftPos;
    $driftStats["driftEqual"]       = $driftEqual;
    $driftStats["driftNeg"]         = $driftNeg;
    $driftStats["nbDriftsPos"]      = $nbDriftsPos;
    $driftStats["nbDriftsEqual"]    = $nbDriftsEqual;
    $driftStats["nbDriftsNeg"]      = $nbDriftsNeg;
    $driftStats["formatedBugidPosList"]   = $formatedBugidPosList;
    $driftStats["formatedBugidNegList"]   = $formatedBugidNegList;

    return $driftStats;
  }



  // -------------------------------------------------
  /** Drift Stats on a given Issue.class List
   *
   * @param array $issueList
   * @param boolean $withSupport
   * @return array driftStats
   *
   * TODO move this method to IssueSelection class
  */

  public function getIssuesDriftStats($issueList, $withSupport = true) {

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


    if (NULL == $issueList) {
      echo "<div style='color:red'>ERROR getIssuesDriftStats: Issue List is NULL !<br/></div>";
      $this->logger->error("getIssuesDriftStats(): Issue List is NULL !");
      return 0;
    }
    if (0== count($issueList)) {
      echo "<div style='color:red'>ERROR getIssuesDriftStats: Issue List is empty !<br/></div>";
      $this->logger->error("getIssuesDriftStats(): Issue List is empty !");
      return 0;
    }


    foreach ($issueList as $issue) {

          // -- compute total drift
          $issueDrift     = $issue->getDrift($withSupport);
          $derive        += $issueDrift;
          $issueDriftMgrEE  = $issue->getDriftMgr($withSupport);
          $deriveETA     += $issueDriftMgrEE;

          $this->logger->debug("getIssuesDriftStats() Found : bugid=$issue->bugId, proj=$issue->projectId, effortEstim=$issue->effortEstim, BS=$issue->effortAdd, elapsed = $issue->elapsed, drift=$issueDrift, DriftMgrEE=$issueDriftMgrEE");

            // get drift stats. equal is when drif = +-1
            if ($issueDrift < -1) {
              $nbDriftsNeg++;
              $driftNeg += $issueDrift;

              if ($formatedBugidNegList != "") { $formatedBugidNegList .= ', '; }
              $formatedBugidNegList .= issueInfoURL($issue->bugId, $issue->summary);

            } elseif ($issueDrift > 1){
              $nbDriftsPos++;
              $driftPos += $issueDrift;

              if ($formatedBugidPosList != "") { $formatedBugidPosList .= ', '; }
              $formatedBugidPosList .= issueInfoURL($issue->bugId, $issue->summary);
            } else {
              $nbDriftsEqual++;
              $driftEqual += $issueDrift;

              if ($formatedBugidEqualList != "") { $formatedBugidEqualList .= ', '; }
              $formatedBugidEqualList .= issueInfoURL($issue->bugId, $issue->summary);

              if ($bugidEqualList != "") { $bugidEqualList .= ', '; }
              $bugidEqualList .= $issue->bugId;
            }

            if ($issueDriftMgrEE < -1) {
              $nbDriftsNegETA++;
              $driftNegETA += $issueDriftMgrEE;
            } elseif ($issueDriftMgrEE > 1){
              $nbDriftsPosETA++;
              $driftPosETA += $issueDriftMgrEE;
            } else {
              $nbDriftsEqualETA++;
              $driftEqualETA += $issueDriftMgrEE;
            }
    } // foreach


    $this->logger->debug("derive totale ($statusNames[$status]/".formatDate("%B %Y", $this->startTimestamp).") = $derive");
    $this->logger->debug("derive totale ETA($statusNames[$status]/".formatDate("%B %Y", $this->startTimestamp).") = $deriveETA");

    $this->logger->debug("Nbre Bugs en derive        : $nbDriftsPos");
    $this->logger->debug("Nbre Bugs a l'equilibre    : $nbDriftsEqual");
    $this->logger->debug("Nbre Bugs en avance        : $nbDriftsNeg");
    $this->logger->debug("Nbre Bugs en derive     ETA: $nbDriftsPosETA");
    $this->logger->debug("Nbre Bugs a l'equilibre ETA: $nbDriftsEqualETA");
    $this->logger->debug("Nbre Bugs en avance     ETA: $nbDriftsNegETA");

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
    $driftStats["formatedBugidPosList"]   = $formatedBugidPosList;
    $driftStats["formatedBugidEqualList"] = $formatedBugidEqualList;
    $driftStats["formatedBugidNegList"]   = $formatedBugidNegList;
    $driftStats["bugidEqualList"]   = $bugidEqualList;




    return $driftStats;
  }


  // ----------------------------------------------
  /**
   Returns an indication on how sideTasks slows down the Production
   prodRate = nbDays spend on projects / total prodDays * 100
   REM only Developpers, no managers !

  */
  public function getEfficiencyRate() {
    $prodDays      =             $this->getProdDays();
    $totalProdDays = $prodDays + $this->getProdDaysSideTasks(true);  // only developpers !

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

  // systemDisponibilityRate = 100 - (nb breakdown hours / prodHours)
  public function getSystemDisponibilityRate() {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;

    // The total time spent by the team doing nothing because of incidents
    $teamIncidentDays = 0;

    // Find nb hours spent on SuiviOp.Incidents
    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, codev_timetracking_table.duration ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id ".
      "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      try {
         $issue = IssueCache::getInstance()->getIssue($row->bugid);
	      if ($issue->isIncident(array($this->team_id))) {

		      $teamIncidentDays += $row->duration;
		      //echo "DEBUG SystemDisponibility found bugid=$row->bugid duration=$row->duration proj=$issue->projectId cat=$issue->categoryId teamIncidentHours=$teamIncidentHours<br/>";
	      }
      } catch (Exception $e) {
	      $this->logger->warn("getSystemDisponibilityRate(): issue $issue->bugId: ".$e->getMessage());
      }
    }

    $prodDays  = $this->getProdDays();

    //echo "DEBUG prodDays $prodDays teamIncidentDays $teamIncidentDays<br/>";

    if (0 != $prodDays) {
      $systemDisponibilityRate = 100 - (($teamIncidentDays / ($prodDays + $teamIncidentDays))*100);
    } else {
      $systemDisponibilityRate = 0;
    }

    return $systemDisponibilityRate;
  }

  // ----------------------------------------------
  public function getWorkingDaysPerJob($job_id) {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;
    $workingDaysPerJob = 0;

    $query     = "SELECT codev_timetracking_table.userid, codev_timetracking_table.bugid, codev_timetracking_table.duration ".
      "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE  codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND    codev_timetracking_table.jobid = $job_id ".
      "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND    codev_team_user_table.team_id = $this->team_id ".
      "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ".
      "AND    codev_timetracking_table.bugid IN ".
      "(SELECT mantis_bug_table.id ".
      "FROM `mantis_bug_table` , `codev_team_project_table` ".
      "WHERE mantis_bug_table.project_id = codev_team_project_table.project_id ".
      "AND codev_team_project_table.team_id =  $this->team_id) ";


    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      $workingDaysPerJob += $row->duration;


      // ---- DEBUG
      //$u = UserCache::getInstance()->getUser($row->userid);
      //$issue = IssueCache::getInstance()->getIssue($row->bugid);
      //    echo "Debug -- getWorkingDaysPerJob -- workingDaysPerJob : team $this->team_id  job $job_id user $row->userid ".$u->getName()." bug $row->bugid ".$issue->summary." duration $row->duration<br/>";
    }
    return $workingDaysPerJob;
  }

  // ----------------------------------------------
  public function getWorkingDaysPerProject($project_id) {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;
  	 $workingDaysPerProject = 0;

    // Find nb hours spent on the given project
    $query     = "SELECT codev_timetracking_table.* ".
      "FROM `codev_timetracking_table`, `codev_team_user_table` ".
      "WHERE codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
      "AND   codev_team_user_table.user_id = codev_timetracking_table.userid ".
      "AND   codev_team_user_table.team_id = $this->team_id ".
      "AND  (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      try {
         $issue = IssueCache::getInstance()->getIssue($row->bugid);

         if ($issue->projectId  == $project_id) {
         $workingDaysPerProject += $row->duration;
         $this->logger->debug("getWorkingDaysPerProject: proj=$project_id, duration=$row->duration, bugid=$row->bugid, userid=$row->userid, ".date("Y-m-d", $row->date));
         }
      } catch (Exception $e) {
          $this->logger->warn("getWorkingDaysPerProject($project_id) : Issue $row->bugid not found in Mantis DB.");
      }
    }
    $this->logger->debug("getWorkingDaysPerProject: proj=$project_id, totalDuration=$workingDaysPerProject");
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
        "ORDER BY date";
    } else {
      $query     = "SELECT date, duration FROM `codev_timetracking_table` WHERE userid = $userid ORDER BY date";
    }
    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      $durations[$row->date] += round($row->duration, 3);
    }

    // Check
    foreach ($durations as $date => $value) {
      // REM: it looks like PHP has some difficulties to compare a float to '1' !!!
      #if (($value < 0.999999999999999) || ($value > 1.000000000000001)) {

      if (round($value, 3) != 1) {
        $this->logger->debug("user $userid incompleteDays[$date]=".$value);
        $incompleteDays[$date] = $value;
      }
    }

    return $incompleteDays;
  }

  // ----------------------------------------------
  // Find days which are not 'sat' or 'sun' or FixedHoliday and that have no timeTrack entry.
  public function checkMissingDays($userid) {

    $holidays = Holidays::getInstance();

    $missingDays = array();

    $user1 = UserCache::getInstance()->getUser($userid);

    // REM: if $this->team_id not set, then team_id = -1
    if ($this->team_id >= 0) {
	    if (( ! $user1->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) &&
          ( ! $user1->isTeamManager($this->team_id, $this->startTimestamp, $this->endTimestamp))) {
	    	// User was not yet present
	      return $missingDays;
	    }

      $arrivalTimestamp   = $user1->getArrivalDate($this->team_id);
      $departureTimestamp = $user1->getDepartureDate($this->team_id);
    } else {
      $arrivalTimestamp   = $user1->getArrivalDate();
      $departureTimestamp = $user1->getDepartureDate();

    }
    // reduce timestamp if needed
    $startT = ($arrivalTimestamp > $this->startTimestamp) ? $arrivalTimestamp : $this->startTimestamp;

    $endT = $this->endTimestamp;
    if ((0 != $departureTimestamp) &&($departureTimestamp < $this->endTimestamp)) {
       $endT   = $departureTimestamp;
    }

    $timestamp = $startT;
    while ($timestamp <= $endT) {

      // monday to friday
      $h = $holidays->isHoliday($timestamp);
      if (NULL == $h) {
        $query     = "SELECT COUNT(date) FROM `codev_timetracking_table` WHERE userid = $userid AND date = $timestamp";
        $result    = SqlWrapper::getInstance()->sql_query($query);
        if (!$result) {
        	echo "<span style='color:red'>ERROR: Query FAILED</span>";
        	exit;
        }

        $nbTuples  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

        if (0 == $nbTuples) {
          $missingDays[] = $timestamp;
        }
      }
      $timestamp = strtotime("+1 day",$timestamp);;
    }

    return $missingDays;
  }


  // ----------------------------------------------
  /**
   * returns $durationPerCategory[CategoryName][bugid] = duration
   * @param int $project_id
   */
  public function getProjectDetails($project_id) {
    $accessLevel_dev     = Team::accessLevel_dev;
    $accessLevel_manager = Team::accessLevel_manager;
    $durationPerCategory = array();

    // Find nb hours spent on the given project by this team
    $query     = "SELECT codev_timetracking_table.bugid, codev_timetracking_table.duration, codev_timetracking_table.date, codev_timetracking_table.userid ".
                 "FROM  `codev_timetracking_table`, `codev_team_user_table` ".
                 "WHERE codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
                 "AND    codev_team_user_table.user_id = codev_timetracking_table.userid ".
                 "AND    codev_team_user_table.team_id = $this->team_id ".
                 "AND    (codev_team_user_table.access_level = $accessLevel_dev OR codev_team_user_table.access_level = $accessLevel_manager) ";

    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

       try {
         $issue = IssueCache::getInstance()->getIssue($row->bugid);

            if ($issue->projectId == $project_id) {
               $this->logger->debug("project[$project_id][" . $issue->getCategoryName() . "]( bug $row->bugid) = $row->duration");

               if (NULL == $durationPerCategory[$issue->getCategoryName()]) {
                  $durationPerCategory[$issue->getCategoryName()] = array();
               }
               $durationPerCategory[$issue->getCategoryName()][$row->bugid]+= $row->duration;
            }
         } catch (Exception $e) {
            $this->logger->warn("getProjectDetails($project_id) issue $row->bugid not found in Mantis DB (duration = $row->duration, user $row->userid on ".date('Y-m-d', $row->date).')');
         }
      }
    return $durationPerCategory;
  }

  // ----------------------------------------------
  /**
   * Returns a multiple array containing duration for each day of the week.
   * WARNING: the timestamp must NOT exceed 1 week.
   *
   * returns : $weekTracks[bugid][jobid][dayOfWeek] = duration
   *
   * @param unknown_type $userid
   * @param unknown_type $isTeamProjOnly if TRUE, return only tracks from projects associated to the team
   */
  public function getWeekDetails($userid, $isTeamProjOnly=false) {
    $weekTracks = array();

    if (false == $isTeamProjOnly) {
      // For all bugs in timestamp
      $query     = "SELECT bugid, jobid, date, duration ".
                   "FROM `codev_timetracking_table` ".
                   "WHERE date >= $this->startTimestamp AND date < $this->endTimestamp ".
                   "AND userid = $userid ";

    } else {
    	$projList = Team::getProjectList($this->team_id);
    	$formatedProjList = implode( ', ', array_keys($projList));
      $query     = "SELECT codev_timetracking_table.bugid, codev_timetracking_table.jobid, codev_timetracking_table.date, codev_timetracking_table.duration ".
                   "FROM `codev_timetracking_table`, `mantis_bug_table`, `mantis_project_table` ".
                   "WHERE date >= $this->startTimestamp AND date < $this->endTimestamp ".
                   "AND userid = $userid ".
    	             "AND mantis_bug_table.id     = codev_timetracking_table.bugid ".
                   "AND mantis_project_table.id = mantis_bug_table.project_id ".
    	             "AND mantis_bug_table.project_id in ($formatedProjList)";

    }
    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      if (null == $weekTracks[$row->bugid]) {
        $weekTracks[$row->bugid] = array();
        $weekTracks[$row->bugid][$row->jobid] = array();
      }
      if (null == $weekTracks[$row->bugid][$row->jobid]) {
        $weekTracks[$row->bugid][$row->jobid] = array();
      }
      $weekTracks[$row->bugid][$row->jobid][date('N',$row->date)] += $row->duration;


       $this->logger->debug("weekTracks[$row->bugid][$row->jobid][".date('N',$row->date)."] = ".$weekTracks[$row->bugid][$row->jobid][date('N',$row->date)]." ( + $row->duration)");
    }

    return $weekTracks;
  }

    // -----------------------------------------------
   // return TimeTracks created by the team during the timestamp
   // returns : $projectTracks[projectid][bugid][jobid] = duration
   public function getProjectTracks($isTeamProjOnly=false) {
      $accessLevel_dev     = Team::accessLevel_dev;
      $accessLevel_manager = Team::accessLevel_manager;

      $projectTracks = array();

    // For all bugs in timestamp
    $query     = "SELECT  mantis_bug_table.project_id, codev_timetracking_table.bugid, codev_timetracking_table.jobid, duration ".
                 "FROM `codev_timetracking_table`, `codev_team_user_table`, `mantis_bug_table`, `codev_job_table`, `mantis_project_table` ".
                 "WHERE codev_timetracking_table.date >= $this->startTimestamp AND codev_timetracking_table.date < $this->endTimestamp ".
                 "AND   codev_team_user_table.user_id = codev_timetracking_table.userid ".
                 "AND   codev_team_user_table.team_id = $this->team_id ".
                 "AND   codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
                 "AND   mantis_bug_table.id     = codev_timetracking_table.bugid ".
                 "AND   mantis_project_table.id = mantis_bug_table.project_id ".
                 "AND   codev_job_table.id      = codev_timetracking_table.jobid ";

    if (false != $isTeamProjOnly) {
      $projList = Team::getProjectList($this->team_id);
      $formatedProjList = implode( ', ', array_keys($projList));
    	$query.= "AND mantis_bug_table.project_id in ($formatedProjList) ";
    }

    $query.= "ORDER BY mantis_project_table.name, bugid DESC, codev_job_table.name";

    $result    = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }

    while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
    {
      if (NULL == $projectTracks[$row->project_id]) {
        $projectTracks[$row->project_id] = array(); // create array for bug_id
        $projectTracks[$row->project_id][$row->bugid] = array(); // create array for jobs
      }
      if (NULL == $projectTracks[$row->project_id][$row->bugid]) {
        $projectTracks[$row->project_id][$row->bugid] = array(); // create array for new jobs
      }
      $projectTracks[$row->project_id][$row->bugid][$row->jobid] += $row->duration;
    }

    return $projectTracks;
   }


  // ----------------------------------------------
  /**
   * returns a list of all the tasks hving been reopened in the period
   * @param unknown_type $projects
   */
   public function getReopened($projects = NULL) {

    global $resolution_fixed;     # 20
    global $resolution_reopened;  # 30;

    $reopenedList = array();

    // --------
    if (NULL == $projects) {
       $projects = $this->prodProjectList;
    }

    $formatedProjList = implode( ', ', $projects);

    $formatedResolutionValues = "$resolution_fixed";

    if ("" == $formatedProjList) {
       echo "<div style='color:red'>ERROR getReopened: no project defined for this team !<br/></div>";
       return 0;
    }

    // all bugs which resolution changed to 'reopened' whthin the timestamp
    $query = "SELECT mantis_bug_table.id, ".
                    "mantis_bug_history_table.new_value, ".
                    "mantis_bug_history_table.old_value, ".
                    "mantis_bug_history_table.date_modified ".
             "FROM `mantis_bug_table`, `mantis_bug_history_table` ".
             "WHERE mantis_bug_table.id = mantis_bug_history_table.bug_id ".
             "AND mantis_bug_table.project_id IN ($formatedProjList) ".
             "AND mantis_bug_history_table.field_name='resolution' ".
             "AND mantis_bug_history_table.date_modified >= $this->startTimestamp ".
             "AND mantis_bug_history_table.date_modified <  $this->endTimestamp ".
             "AND mantis_bug_history_table.new_value = $resolution_reopened ".
             "AND mantis_bug_history_table.old_value IN ($formatedResolutionValues) ".
             "ORDER BY mantis_bug_table.id DESC";

    if (isset($_GET['debug'])) { echo "getReopened QUERY = $query <br/>"; }

    $result = SqlWrapper::getInstance()->sql_query($query);
    if (!$result) {
    	echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	exit;
    }


    while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

       // do not include internal tasks (tasks having no ExternalReference)
       $issue = IssueCache::getInstance()->getIssue($row->id);
       if ((NULL == $issue->tcId) || (NULL == $issue->tcId)) {
          $this->logger->debug("getReopened: issue $row->id excluded (no ExtRef)");
          continue;
       }

       if ( ! in_array($row->id, $reopenedList)) {
           $reopenedList[] = $row->id;
       }
    }

   return $reopenedList;
   }

  // ----------------------------------------------
  /**
   * returns a list of bug_id that have been submitted in the period
   */
   public function getSubmitted($projects = NULL) {

      $submittedList = array();

      if (NULL == $projects) {
         $projects = $this->prodProjectList;
      }

      $query = "SELECT DISTINCT mantis_bug_table.id, mantis_bug_table.date_submitted, mantis_bug_table.project_id ".
               "FROM `mantis_bug_table`, `codev_team_project_table` ".
               "WHERE mantis_bug_table.date_submitted >= $this->startTimestamp AND mantis_bug_table.date_submitted < $this->endTimestamp ".
               "AND mantis_bug_table.project_id = codev_team_project_table.project_id ";

      // Only for specified Projects
      if (0 != count($projects)) {
         $formatedProjects = implode( ', ', $projects);
         $query .= "AND mantis_bug_table.project_id IN ($formatedProjects) ";
      }
      if (isset($_GET['debug_sql'])) { echo "getSubmitted(): query = $query<br/>"; }

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
      	echo "<span style='color:red'>ERROR: Query FAILED</span>";
      	exit;
      }


      while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
      {
         $submittedList[] = $row->id;

         if (isset($_GET['debug'])) {
            echo "DEBUG submitted $row->id   date < ".formatDate("%b %y", $this->endTimestamp)." project $row->project_id <br/>";
         }
      }

      return $submittedList;
   }

  // ----------------------------------------------
  /**
   * $countReopened / $countSubmitted
   *
   * @param unknown_type $projects
   */
   public function getReopenedRate($projects = NULL) {

      if (NULL == $projects) {
         $projects = $this->prodProjectList;
      }

      $reopenedList = $this->getReopened($projects);
      $countReopened = count($reopenedList);

      $countSubmitted = count($this->getSubmitted($projects));

      if ($countSubmitted != 0)  {
        $rate=($countReopened / $countSubmitted);
      }
      return $rate;
   }

} // class TimeTracking

?>
