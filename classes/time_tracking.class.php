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

require_once('classes/constants.class.php');

include_once('classes/holidays.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/project.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/team.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/timetrack_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

/**
 * TimeTracking facilities
 */
class TimeTracking {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private $startTimestamp;
   private $endTimestamp;

   private $team_id;

   private $prodProjectList;     // projects that are not sideTasks, and not in noStatsProject
   private $sideTaskprojectList;

   private $prodDays;
   private $managementDays;

   private $availableWorkload;
   private $timeDriftStats;
   private $efficiencyRate;

   private $systemDisponibilityRate;

   private $reopenedList;

   private $submittedBugs = NULL;

   /**
    * @var TimeTrack[]
    */
   private $timeTracks;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param int $team_id
    */
   public function __construct($startTimestamp, $endTimestamp, $team_id = NULL) {
      // Note: teamid is null in time_tracking.php because you do not specify it to set timetracks...

      if ((NULL == $team_id) || ($team_id <= 0)) {
         #echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("TimeTracking->team_id not set !");
         self::$logger->error("EXCEPTION TimeTracking constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         //throw $e;
      }

      $this->startTimestamp = $startTimestamp;
      $this->endTimestamp = $endTimestamp;
      $this->team_id = (isset($team_id)) ? $team_id : -1;

      $this->initialize();
   }

   /**
    * Initialize
    */
   public function initialize() {
      $this->prodProjectList = array();
      $this->sideTaskprojectList = array();

      $team = TeamCache::getInstance()->getTeam($this->team_id);
      foreach($team->getProjectsType() as $projectid => $type) {
         switch ($type) {
            case Project::type_sideTaskProject:
               $this->sideTaskprojectList[] = $projectid;
               break;
            case Project::type_workingProject:  // no break;
            case Project::type_noCommonProject:
               $this->prodProjectList[] = $projectid;
               break;
            case  Project::type_noStatsProject:
               // known type, but nothing to do
               break;
            default:
               self::$logger->warn("WARNING: Timetracking->initialize() unknown project type ($type) !");
         }
      }
   }

   /**
    * @return int
    */
   public function getStartTimestamp() {
      return $this->startTimestamp;
   }

   /**
    * @return int
    */
   public function getEndTimestamp() {
      return $this->endTimestamp;
   }

   /**
    * @return int
    */
   public function getTeamid() {
      return $this->team_id;
   }

   /**
    * @return TimeTrack[]
    */
   private function getTimeTracks() {
      if(NULL == $this->timeTracks) {
         $accessLevel_dev = Team::accessLevel_dev;
         $accessLevel_manager = Team::accessLevel_manager;

         // select tasks within timestamp, where user is in the team
         $query = "SELECT timetracking.* ".
                  "FROM `codev_timetracking_table` as timetracking ".
                  "JOIN `codev_team_user_table` as team_user ON timetracking.userid = team_user.user_id ".
                  "WHERE team_user.team_id = $this->team_id ".
                  "AND team_user.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
                  "AND timetracking.date >= $this->startTimestamp AND timetracking.date < $this->endTimestamp;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->timeTracks = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->timeTracks[] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
         }
      }
      return $this->timeTracks;
   }

   /**
    * Returns the number of days worked by the team within the timestamp
    * - Observers excluded
    * @return number
    */
   public function getProdDays() {
      if(!is_numeric($this->prodDays)) {
         $this->prodDays = 0;

         // TODO check patch from FDJ 8b003033391c84142787c3379a518a3ef7283587
         //      "AND (codev_team_user_table.departure_date = 0 or codev_team_user_table.departure_date >=$this->startTimestamp)";

         $timeTracks = $this->getTimeTracks();
         foreach($timeTracks as $timeTrack) {
            // Count only the time spent on $projects
            try {
               if (in_array($timeTrack->getProjectId(), $this->prodProjectList)) {
                  $this->prodDays += $timeTrack->getDuration();
               }
            } catch (Exception $e) {
               self::$logger->error("getProductionDays(): timetrack on task ".$timeTrack->getIssueId()." (duration=".$timeTrack->getDuration().") NOT INCLUDED !");
            }
         }
      }
      return $this->prodDays;
   }

   /**
    * Returns the number of days spent on side tasks EXCEPT Vacations
    * - Observers excluded
    *
    * @param bool $isDeveloppersOnly : do not include time spent by Managers (default = false)
    * @return number
    */
   public function getProdDaysSideTasks($isDeveloppersOnly = false) {
      $prodDays = 0;

      $timeTracks = $this->getTimeTracks();
      foreach($timeTracks as $timeTrack) {
         // do not include Managers
         if ($isDeveloppersOnly) {
            $user = UserCache::getInstance()->getUser($timeTrack->getUserId());
            if (!$user->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) {
               continue; // skip this timeTrack
            }
         }
         try {
            $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());
            if ((in_array ($issue->getProjectId(), $this->sideTaskprojectList)) &&
               (!$issue->isVacation(array($this->team_id)))) {
               $prodDays += $timeTrack->getDuration();
            }
         } catch (Exception $e) {
            self::$logger->error("getProdDaysSideTasks(): issue ".$timeTrack->getIssueId().": ".$e->getMessage());
         }
      }
      return $prodDays;
   }

   /**
    * Returns the number of days spent on Management Tasks
    * - Observers excluded
    * @return number
    */
   public function getManagementDays() {
      if(!is_numeric($this->managementDays)) {
         $this->managementDays = 0;

         $timeTracks = $this->getTimeTracks();
         foreach($timeTracks as $timeTrack) {
            $user = UserCache::getInstance()->getUser($timeTrack->getUserId());

            if ((!$user->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) &&
               (!$user->isTeamManager($this->team_id, $this->startTimestamp, $this->endTimestamp))) {
               self::$logger->warn("getManagementDays(): timetrack ".$timeTrack->getId()." not included because user ".$user->getId()." (".$user->getName().") was not a DEVELOPPER/MANAGER within the timestamp");
               continue; // skip this timeTrack
            }

            try {
               $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());

               if ((in_array($issue->getProjectId(), $this->sideTaskprojectList)) &&
                  ($issue->isProjManagement(array($this->team_id)))) {
                  $this->managementDays += $timeTrack->getDuration();
               }
            } catch (Exception $e) {
               self::$logger->error("getManagementDays(): issue ".$timeTrack->getIssueId().": ".$e->getMessage());
            }
         }

      }
      return $this->managementDays;
   }

   /**
    * @return number
    */
   public function getAvailableWorkload() {
      if(!is_numeric($this->availableWorkload)) {
         $accessLevel_dev = Team::accessLevel_dev;
         #$accessLevel_manager = Team::accessLevel_manager;

         $this->availableWorkload = 0;

         // For all the users of the team
         $query = "SELECT user.* ".
                  "FROM `mantis_user_table` as user ".
                  "JOIN `codev_team_user_table` as team_user ON user.id = team_user.user_id ".
                  "WHERE team_user.team_id = $this->team_id ".
                  //"AND (team_user.access_level = $accessLevel_dev OR team_user.access_level = $accessLevel_manager)".
                  "AND team_user.access_level = $accessLevel_dev;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $user = UserCache::getInstance()->getUser($row->id, $row);
            $this->availableWorkload += $user->getAvailableWorkload($this->startTimestamp, $this->endTimestamp, $this->team_id);
         }
      }

      return $this->availableWorkload;
   }

   /**
    * @param bool $withSupport
    * @return mixed[]
    */
   public function getResolvedDriftStats($withSupport = true) {
      $issueList = $this->getResolvedIssues();
      if (0 != count($issueList)) {
         return $this->getIssuesDriftStats($issueList, $withSupport);
      } else {
         return array();
      }
   }

   /**
    * @return mixed[]
    */
   public function getTimeDriftStats() {
      if(NULL == $this->timeDriftStats) {

         $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

         $issueList = array();

         // all issues which deliveryDate is in the period.
         $query = "SELECT bug.* ".
                  "FROM `mantis_bug_table` as bug ".
                  "JOIN `mantis_custom_field_string_table` as field ON bug.id = field.bug_id ".
                  "WHERE field.field_id = $deliveryDateCustomField ".
                  "AND field.value >= $this->startTimestamp AND field.value < $this->endTimestamp ".
                  "ORDER BY bug.id ASC;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->id, $row);

            // if a deadLine is specified
            if ((in_array($issue->getProjectId(), $this->prodProjectList)) &&
               (NULL != $issue->getDeadLine())) {
               $issueList[] = $issue;
            }
         }
         if (0 != count($issueList)) {
            $this->timeDriftStats = $this->getIssuesTimeDriftStats($issueList);
         } else {
            $this->timeDriftStats = array();
         }
      }

      return $this->timeDriftStats;

   }

   private $resolvedIssues;

   /**
    * Returns all Issues resolved in the period and having not been re-opened
    * @return Issue[] a list of Issue class instances
    */
   public function getResolvedIssues() {
      if(NULL == $this->resolvedIssues) {
         $formatedProjList = implode( ', ', $this->prodProjectList);

         if ("" == $formatedProjList) {
            echo "<div style='color:red'>ERROR getResolvedIssues: no project defined for this team !<br/></div>";
            return 0;
         }

         // all bugs which status changed to 'resolved' whthin the timestamp
         $query = "SELECT bug.* ".
                  "FROM `mantis_bug_table` as bug ".
                  "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id ".
                  "WHERE bug.project_id IN ($formatedProjList) ".
                  "AND history.field_name='status' ".
                  "AND history.date_modified >= $this->startTimestamp AND history.date_modified < $this->endTimestamp ".
                  "AND history.new_value = get_project_resolved_status_threshold(project_id) ".
                  "ORDER BY bug.id DESC;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->resolvedIssues = array();
         $resolvedList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->id, $row);

            // check if the bug has been reopened before endTimestamp
            $latestStatus = $issue->getStatus($this->endTimestamp);
            if (($latestStatus == $issue->getBugResolvedStatusThreshold()) || ($latestStatus == Constants::$status_closed)) {

               // remove duplicated values
               if (!in_array ($issue->getId(), $resolvedList)) {
                  $resolvedList[] = $issue->getId();
                  $this->resolvedIssues[] = $issue;
               }
            } else {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("TimeTracking->getResolvedIssues() REOPENED : bugid = ".$issue->getId());
               }
            }
         }
      }
      return $this->resolvedIssues;
   }

   /**
    * return stats on which Issues where delivered after the DeadLine
    *
    * @param Issue[] $issueList
    * @return mixed[]
    *
    * TODO move this method to IssueSelection class
    */
   public function getIssuesTimeDriftStats(array $issueList) {
      if (NULL == $issueList) {
         echo "<div style='color:red'>ERROR getIssuesTimeDriftStats: Issue List is NULL !<br/></div>";
         return array();
      }
      if (0== count($issueList)) {
         echo "<div style='color:red'>ERROR getIssuesTimeDriftStats: Issue List is empty !<br/></div>";
         return array();
      }

      $nbDriftsNeg = 0;
      $nbDriftsEqual = 0;
      $nbDriftsPos = 0;

      $driftNeg = 0;
      $driftEqual = 0;
      $driftPos = 0;

      $formatedBugidNegList = "";
      $formatedBugidPosList = "";
      foreach ($issueList as $issue) {
         $issueDrift = $issue->getTimeDrift();  // returns an integer or an error string
         if (!is_string($issueDrift)) {

            if ($issueDrift <= 0) {
               $nbDriftsNeg++;
               $driftNeg += $issueDrift;

               if ($formatedBugidNegList != "") {
                  $formatedBugidNegList .= ', ';
               }
               $formatedBugidNegList .= Tools::issueInfoURL($issue->getId(), $issue->getSummary());
            } else {
               $nbDriftsPos++;
               $driftPos += $issueDrift;

               if ($formatedBugidPosList != "") {
                  $formatedBugidPosList .= ', ';
               }
               $formatedBugidPosList .= Tools::issueInfoURL($issue->getId(), $issue->getSummary())."<span title='".T_("nb days")."'>(".round($issueDrift).")<span>";
            }
         }
      }

      return array(
         "driftPos" => $driftPos,
         "driftEqual" => $driftEqual,
         "driftNeg" => $driftNeg,
         "nbDriftsPos" => $nbDriftsPos,
         "nbDriftsEqual" => $nbDriftsEqual,
         "nbDriftsNeg" => $nbDriftsNeg,
         "formatedBugidPosList" => $formatedBugidPosList,
         "formatedBugidNegList" => $formatedBugidNegList
      );
   }

   /**
    * Drift Stats on a given Issue.class List
    *
    * @param Issue[] $issueList
    * @param bool $withSupport
    * @return mixed[] driftStats
    *
    * TODO move this method to IssueSelection class
    */
   public function getIssuesDriftStats(array $issueList, $withSupport = true) {
      if (NULL == $issueList) {
         echo "<div style='color:red'>ERROR getIssuesDriftStats: Issue List is NULL !<br/></div>";
         self::$logger->error("getIssuesDriftStats(): Issue List is NULL !");
         return 0;
      }
      if (0 == count($issueList)) {
         echo "<div style='color:red'>ERROR getIssuesDriftStats: Issue List is empty !<br/></div>";
         self::$logger->error("getIssuesDriftStats(): Issue List is empty !");
         return 0;
      }

      $derive = 0;
      $deriveETA = 0;

      $nbDriftsNeg = 0;
      $nbDriftsEqual = 0;
      $nbDriftsPos = 0;
      $nbDriftsNegETA = 0;
      $nbDriftsEqualETA = 0;
      $nbDriftsPosETA = 0;

      $driftNeg = 0;
      $driftEqual = 0;
      $driftPos = 0;
      $driftNegETA = 0;
      $driftEqualETA = 0;
      $driftPosETA = 0;

      $formatedBugidNegList = "";
      $formatedBugidPosList = "";
      $formatedBugidEqualList = "";
      $bugidEqualList = "";
      foreach ($issueList as $issue) {
         // compute total drift
         $issueDrift = $issue->getDrift($withSupport);
         $derive += $issueDrift;
         $issueDriftMgrEE = $issue->getDriftMgr($withSupport);
         $deriveETA += $issueDriftMgrEE;

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("getIssuesDriftStats() Found : bugid=".$issue->getId().", proj=".$issue->getProjectId().", effortEstim=".$issue->getEffortEstim().", BS=".$issue->getEffortAdd().", elapsed = ".$issue->getElapsed().", drift=$issueDrift, DriftMgrEE=$issueDriftMgrEE");
         }

         // get drift stats. equal is when drif = +-1
         if ($issueDrift < -1) {
            $nbDriftsNeg++;
            $driftNeg += $issueDrift;

            if ($formatedBugidNegList != "") {
               $formatedBugidNegList .= ', ';
            }
            $formatedBugidNegList .= Tools::issueInfoURL($issue->getId(), $issue->getSummary());
         } elseif ($issueDrift > 1){
            $nbDriftsPos++;
            $driftPos += $issueDrift;

            if ($formatedBugidPosList != "") {
               $formatedBugidPosList .= ', ';
            }
            $formatedBugidPosList .= Tools::issueInfoURL($issue->getId(), $issue->getSummary());
         } else {
            $nbDriftsEqual++;
            $driftEqual += $issueDrift;

            if ($formatedBugidEqualList != "") {
               $formatedBugidEqualList .= ', ';
            }
            $formatedBugidEqualList .= Tools::issueInfoURL($issue->getId(), $issue->getSummary());

            if ($bugidEqualList != "") {
               $bugidEqualList .= ', ';
            }
            $bugidEqualList .= $issue->getId();
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
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("derive totale (".Tools::formatDate("%B %Y", $this->startTimestamp).") = $derive");
         self::$logger->debug("derive totale ETA(".Tools::formatDate("%B %Y", $this->startTimestamp).") = $deriveETA");

         self::$logger->debug("Nbre Bugs en derive        : $nbDriftsPos");
         self::$logger->debug("Nbre Bugs a l'equilibre    : $nbDriftsEqual");
         self::$logger->debug("Nbre Bugs en avance        : $nbDriftsNeg");
         self::$logger->debug("Nbre Bugs en derive     ETA: $nbDriftsPosETA");
         self::$logger->debug("Nbre Bugs a l'equilibre ETA: $nbDriftsEqualETA");
         self::$logger->debug("Nbre Bugs en avance     ETA: $nbDriftsNegETA");
      }

      return array(
         "totalDrift" => $derive,
         "totalDriftETA" => $deriveETA,
         "driftPos" => $driftPos,
         "driftEqual" => $driftEqual,
         "driftNeg" => $driftNeg,
         "driftPosETA" => $driftPosETA,
         "driftEqualETA" => $driftEqualETA,
         "driftNegETA" => $driftNegETA,
         "nbDriftsPos" => $nbDriftsPos,
         "nbDriftsEqual" => $nbDriftsEqual,
         "nbDriftsNeg" => $nbDriftsNeg,
         "nbDriftsPosETA" => $nbDriftsPosETA,
         "nbDriftsEqualETA" => $nbDriftsEqualETA,
         "nbDriftsNegETA" => $nbDriftsNegETA,
         "formatedBugidPosList" => $formatedBugidPosList,
         "formatedBugidEqualList" => $formatedBugidEqualList,
         "formatedBugidNegList" => $formatedBugidNegList,
         "bugidEqualList" => $bugidEqualList
      );
   }

   /**
    * Returns an indication on how sideTasks slows down the Production
    * prodRate = nbDays spend on projects / total prodDays * 100
    * REM only Developpers, no managers !
    * @return number
    */
   public function getEfficiencyRate() {
      if(!is_numeric($this->efficiencyRate)) {
         $prodDays = $this->getProdDays();
         $totalProdDays = $prodDays + $this->getProdDaysSideTasks(true);  // only developpers !

         // REM x100 for percentage
         if (0 != $totalProdDays) {
            $this->efficiencyRate = $prodDays / $totalProdDays * 100;
         } else {
            $this->efficiencyRate = 0;
         }
      }

      return $this->efficiencyRate;
   }

   /**
    * Returns an indication on how Environmental problems slow down the production.
    * EnvProblems can be : Citrix Falldow, Continuous pbs, VMS shutdown, SSL connection loss, etc.
    * systemDisponibilityRate = 100 - (nb breakdown hours / prodHours)
    * @return number
    */
   public function getSystemDisponibilityRate() {
      if(!is_numeric($this->systemDisponibilityRate)) {
         // The total time spent by the team doing nothing because of incidents
         $teamIncidentDays = 0;

         $timeTracks = $this->getTimeTracks();
         foreach($timeTracks as $timeTrack) {
            try {
               $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());
               if ($issue->isIncident(array($this->team_id))) {
                  $teamIncidentDays += $timeTrack->getDuration();
                  //echo "DEBUG SystemDisponibility found bugid=$timeTrack->bugId duration=$timeTrack->duration proj=$issue->projectId cat=$issue->categoryId teamIncidentHours=$teamIncidentHours<br/>";
               }
            } catch (Exception $e) {
               self::$logger->warn("getSystemDisponibilityRate(): issue ".$timeTrack->getIssueId().": ".$e->getMessage());
            }
         }

         $prodDays  = $this->getProdDays();

         //echo "DEBUG prodDays $prodDays teamIncidentDays $teamIncidentDays<br/>";

         if (0 != $prodDays) {
            $this->systemDisponibilityRate = 100 - (($teamIncidentDays / ($prodDays + $teamIncidentDays))*100);
         } else {
            $this->systemDisponibilityRate = 0;
         }
      }

      return $this->systemDisponibilityRate;
   }

   /**
    * @param int $job_id
    * @return number
    */
   public function getWorkingDaysPerJob($job_id) {
      $accessLevel_dev = Team::accessLevel_dev;
      $accessLevel_manager = Team::accessLevel_manager;

      $query = "SELECT timetracking.userid, timetracking.bugid, timetracking.duration ".
               "FROM `codev_timetracking_table` as timetracking ".
               "JOIN `codev_team_user_table` as team_user ON timetracking.userid = team_user.user_id ".
               "WHERE timetracking.date >= $this->startTimestamp AND timetracking.date < $this->endTimestamp ".
               "AND timetracking.jobid = $job_id ".
               "AND team_user.team_id = $this->team_id ".
               "AND team_user.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
               "AND timetracking.bugid IN ".
               "(SELECT bug.id ".
               "FROM `mantis_bug_table` as bug ".
               "JOIN `codev_team_project_table` as team_project ON bug.project_id = team_project.project_id ".
               "WHERE team_project.team_id = $this->team_id);";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $workingDaysPerJob = 0;
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $workingDaysPerJob += $row->duration;

         // DEBUG
         //$u = UserCache::getInstance()->getUser($row->userid);
         //$issue = IssueCache::getInstance()->getIssue($row->bugid);
         //    echo "Debug -- getWorkingDaysPerJob -- workingDaysPerJob : team $this->team_id  job $job_id user $row->userid ".$u->getName()." bug $row->bugid ".$issue->summary." duration $row->duration<br/>";
      }
      return $workingDaysPerJob;
   }

   /**
    * @param int $project_id
    * @return number
    */
   public function getWorkingDaysPerProject($project_id) {
      $workingDaysPerProject = 0;

      $timeTracks = $this->getTimeTracks();
      foreach($timeTracks as $timeTrack) {
         try {
            $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());

            if ($issue->getProjectId() == $project_id) {
               $workingDaysPerProject += $timeTrack->getDuration();
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getWorkingDaysPerProject: proj=$project_id, duration=".$timeTrack->getDuration().", bugid=".$timeTrack->getIssueId().", userid=".$timeTrack->getUserId().", ".date("Y-m-d", $timeTrack->getDate()));
               }
            }
         } catch (Exception $e) {
            self::$logger->warn("getWorkingDaysPerProject($project_id) : Issue ".$timeTrack->getIssueId()." not found in Mantis DB.");
         }
      }
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getWorkingDaysPerProject: proj=$project_id, totalDuration=$workingDaysPerProject");
      }
      return $workingDaysPerProject;
   }

   /**
    * Returns an array of (date => duration) containing all days where duration != 1
    * @param int $userid
    * @param bool $isStrictlyTimestamp
    * @return number[]
    */
   public function checkCompleteDays($userid, $isStrictlyTimestamp = FALSE) {
      // Get all dates that must be checked
      $query = "SELECT date, SUM(duration) as count ".
               "FROM `codev_timetracking_table` ".
               "WHERE userid = $userid ";
      if ($isStrictlyTimestamp) {
         $query .= "AND date >= $this->startTimestamp AND date < $this->endTimestamp ";
      }
      $query .= "GROUP BY date ORDER BY date;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $incompleteDays = array(); // unique date => sum durations
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $value = round($row->count, 3);
         $durations[$row->date] = round($row->count, 3);
         if ($value != 1) {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("user $userid incompleteDays[$row->date]=".$value);
            }
            $incompleteDays[$row->date] = $value;
         }
      }

      return $incompleteDays;
   }

   /**
    * Find days which are not 'sat' or 'sun' or FixedHoliday and that have no timeTrack entry.
    * @param int $userid
    * @return number[]
    */
   public function checkMissingDays($userid) {
      $holidays = Holidays::getInstance();

      $missingDays = array();

      $user1 = UserCache::getInstance()->getUser($userid);

      // REM: if $this->team_id not set, then team_id = -1
      if ($this->team_id >= 0) {
         if ((!$user1->isTeamDeveloper($this->team_id, $this->startTimestamp, $this->endTimestamp)) &&
            (!$user1->isTeamManager($this->team_id, $this->startTimestamp, $this->endTimestamp))) {
            // User was not yet present
            return $missingDays;
         }

         $arrivalTimestamp = $user1->getArrivalDate($this->team_id);
         $departureTimestamp = $user1->getDepartureDate($this->team_id);
      } else {
         $arrivalTimestamp = $user1->getArrivalDate();
         $departureTimestamp = $user1->getDepartureDate();
      }
      // reduce timestamp if needed
      $startT = ($arrivalTimestamp > $this->startTimestamp) ? $arrivalTimestamp : $this->startTimestamp;

      $endT = $this->endTimestamp;
      if ((0 != $departureTimestamp) &&($departureTimestamp < $this->endTimestamp)) {
         $endT = $departureTimestamp;
      }

      $weekTimestamps = array();
      $timestamp = $startT;
      while ($timestamp <= $endT) {
         // monday to friday
         if (NULL == $holidays->isHoliday($timestamp)) {
            $weekTimestamps[] = $timestamp;
         }
         $timestamp = strtotime("+1 day",$timestamp);
      }

      $missingDays = array();
      if(count($weekTimestamps) > 0) {
         $query = "SELECT DISTINCT date ".
                  "FROM `codev_timetracking_table` ".
                  "WHERE userid = $userid AND date IN (".implode(', ', $weekTimestamps).");";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $daysWithTimeTracks = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $daysWithTimeTracks[] = $row->date;
         }
         $missingDays = array_diff($weekTimestamps, $daysWithTimeTracks);
      }

      return $missingDays;
   }

   /**
    * returns $durationPerCategory[CategoryName][bugid] = duration
    * @param int $project_id
    * @return array[]
    */
   public function getProjectDetails($project_id) {
      $durationPerCategory = array();

      $timeTracks = $this->getTimeTracks();
      foreach($timeTracks as $timeTrack) {
         try {
            $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());

            if ($issue->getProjectId() == $project_id) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("project[$project_id][" . $issue->getCategoryName() . "]( bug ".$timeTrack->getIssueId().") = ".$timeTrack->getDuration());
               }

               if (!array_key_exists($issue->getCategoryName(), $durationPerCategory)) {
                  $durationPerCategory[$issue->getCategoryName()] = array();
               }
               $durationPerCategory[$issue->getCategoryName()][$timeTrack->getIssueId()] += $timeTrack->getDuration();
            }
         } catch (Exception $e) {
            self::$logger->warn("getProjectDetails($project_id) issue ".$timeTrack->getIssueId()." not found in Mantis DB (duration = ".$timeTrack->getDuration()." on ".date('Y-m-d', $timeTrack->getDate()).')');
         }
      }
      return $durationPerCategory;
   }

   /**
    * Returns a multiple array containing duration for each day of the week.
    * WARNING: the timestamp must NOT exceed 1 week.
    *
    * returns : $weekTracks[bugid][jobid][dayOfWeek] = duration
    *
    * @param int $userid
    * @param bool $isTeamProjOnly if TRUE, return only tracks from projects associated to the team
    * @return array[][]
    */
   public function getWeekDetails($userid, $isTeamProjOnly=false) {
      $weekTracks = array();

      if (!$isTeamProjOnly) {
         // For all bugs in timestamp
         $query = "SELECT bugid, jobid, date, duration ".
                  "FROM `codev_timetracking_table` ".
                  "WHERE date >= $this->startTimestamp AND date < $this->endTimestamp ".
                  "AND userid = $userid;";
      } else {
         $projList = TeamCache::getInstance()->getTeam($this->team_id)->getProjects();
         $formatedProjList = implode( ', ', array_keys($projList));
         $query = "SELECT timetracking.bugid, timetracking.jobid, timetracking.date, timetracking.duration ".
                  "FROM `codev_timetracking_table` as timetracking ".
                  "JOIN `mantis_bug_table` AS bug ON timetracking.bugid = bug.id ".
                  "JOIN `mantis_project_table` AS project ON bug.project_id = project.id ".
                  "WHERE timetracking.userid = $userid ".
                  "AND timetracking.date >= $this->startTimestamp AND timetracking.date < $this->endTimestamp ".
                  "AND bug.project_id in ($formatedProjList);";
      }

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         if (!array_key_exists($row->bugid,$weekTracks)) {
            $weekTracks[$row->bugid] = array();
            $weekTracks[$row->bugid][$row->jobid] = array();
         }
         if (!array_key_exists($row->jobid, $weekTracks[$row->bugid])) {
            $weekTracks[$row->bugid][$row->jobid] = array();
         }
         if (array_key_exists(date('N',$row->date), $weekTracks[$row->bugid][$row->jobid])) {
            $weekTracks[$row->bugid][$row->jobid][date('N',$row->date)] += $row->duration;
         } else {
            $weekTracks[$row->bugid][$row->jobid][date('N',$row->date)] = $row->duration;
         }

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("weekTracks[$row->bugid][$row->jobid][".date('N',$row->date)."] = ".$weekTracks[$row->bugid][$row->jobid][date('N',$row->date)]." ( + $row->duration)");
         }
      }

      return $weekTracks;
   }

   /**
    * return TimeTracks created by the team during the timestamp
    * @param bool $isTeamProjOnly
    * @return array[][] : $projectTracks[projectid][bugid][jobid] = duration
    */
   public function getProjectTracks($isTeamProjOnly=false) {
      $accessLevel_dev = Team::accessLevel_dev;
      $accessLevel_manager = Team::accessLevel_manager;

      // For all bugs in timestamp
      $query = "SELECT bug.id as bugid, bug.project_id, timetracking.jobid, SUM(timetracking.duration) as duration ".
               "FROM `codev_timetracking_table` as timetracking, `codev_team_user_table` as team_user, `mantis_bug_table` as bug, `codev_job_table` as job, `mantis_project_table` as project ".
               "WHERE team_user.user_id = timetracking.userid ".
               "AND bug.id = timetracking.bugid ".
               "AND project.id = bug.project_id ".
               "AND job.id = timetracking.jobid ".
               "AND timetracking.date >= $this->startTimestamp AND timetracking.date < $this->endTimestamp ".
               "AND team_user.team_id = $this->team_id ".
               "AND team_user.access_level IN ($accessLevel_dev, $accessLevel_manager) ";

      if (false != $isTeamProjOnly) {
         $projList = TeamCache::getInstance()->getTeam($this->team_id)->getProjects();
         $formatedProjList = implode( ', ', array_keys($projList));
         $query.= "AND bug.project_id in ($formatedProjList) ";
      }

      $query.= "GROUP BY bug.id, job.id, bug.project_id ORDER BY project.name, bug.id DESC;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $projectTracks = array();

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         if (!array_key_exists($row->project_id, $projectTracks)) {
            $projectTracks[$row->project_id] = array(); // create array for bug_id
            $projectTracks[$row->project_id][$row->bugid] = array(); // create array for jobs
         }
         if (!array_key_exists($row->bugid, $projectTracks[$row->project_id])) {
            $projectTracks[$row->project_id][$row->bugid] = array(); // create array for new jobs
         }
         $projectTracks[$row->project_id][$row->bugid][$row->jobid] = round($row->duration,2);
      }

      return $projectTracks;
   }

   /**
    * returns a list of all the tasks having been reopened in the period
    * (status changed from resolved_threshold to lower value)
    * @return Issue[]
    */
   public function getReopened() {
      if(NULL == $this->reopenedList) {
         $formatedProjList = implode(', ', $this->prodProjectList);

         if ("" == $formatedProjList) {
            echo "<div style='color:red'>ERROR getReopened: no project defined for this team !<br/></div>";
            return 0;
         }

         // all bugs which resolution changed to 'reopened' whthin the timestamp
         $query = "SELECT bug.*" .
                  "FROM `mantis_bug_table` as bug ".
                  "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id " .
                  "WHERE bug.project_id IN ($formatedProjList) " .
                  "AND history.field_name='status' " .
                  "AND history.date_modified >= $this->startTimestamp AND history.date_modified <  $this->endTimestamp " .
                  "AND history.old_value >= get_project_resolved_status_threshold(bug.project_id) " .
                  "AND history.new_value <  get_project_resolved_status_threshold(bug.project_id) " .
                  "GROUP BY bug.id ORDER BY bug.id DESC;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->reopenedList = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            // do not include internal tasks (tasks having no ExternalReference)
            $issue = IssueCache::getInstance()->getIssue($row->id, $row);
            if ((NULL == $issue->getTcId()) || ('' == $issue->getTcId())) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getReopened: issue $row->id excluded (no ExtRef)");
               }
               continue;
            }

            $this->reopenedList[$row->id] = $issue;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getReopened: found issue $row->id");
            }
         }
      }

      return $this->reopenedList;
   }

   /**
    * returns the number of bug that have been submitted in the period
    * @return int
    */
   public function getSubmitted() {
      if(!is_numeric($this->submittedBugs)) {
         // FIXME Why join with codev_team_project_table ?
         $query = "SELECT COUNT(bug.id) as count ".
                  "FROM `mantis_bug_table` as bug ".
                  "JOIN `codev_team_project_table` as team_project ON bug.project_id = team_project.project_id ".
                  "WHERE bug.date_submitted >= $this->startTimestamp AND bug.date_submitted < $this->endTimestamp";

         // Only for specified Projects
         $projects = $this->prodProjectList;
         if (0 != count($projects)) {
            $formatedProjects = implode( ', ', $projects);
            $query .= " AND bug.project_id IN ($formatedProjects)";
         }

         $query .= ";";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->submittedBugs = SqlWrapper::getInstance()->sql_result($result);
      }

      return $this->submittedBugs;
   }

   /**
    * $countReopened / $countSubmitted
    * @return number
    */
   public function getReopenedRate() {
      $countReopened = count($this->getReopened());
      $countSubmitted = $this->getSubmitted();

      $rate = 0;
      if ($countSubmitted != 0)  {
         $rate = $countReopened / $countSubmitted;
      }
      return $rate;
   }

   /**
    * $countReopened / $countResolved
    * @return number
    */
   public function getReopenedRateResolved() {
      $countReopened = count($this->getReopened());
      $countResolved = count($this->getResolvedIssues());

      $rate = 0;
      if ($countResolved != 0)  {
         $rate = $countReopened / $countResolved;
      }
      return $rate;
   }

}

// Initialize complex static variables
TimeTracking::staticInit();

?>
