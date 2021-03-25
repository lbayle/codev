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

   private $submittedBugs = NULL; // array {'ExtRefOnly, 'All'}

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
      if(self::$logger->isDebugEnabled()) {
         if ((NULL == $team_id) || ($team_id <= 0)) {
            $e = new Exception("TimeTracking->team_id not set !");
            self::$logger->error("EXCEPTION TimeTracking constructor: ".$e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         }
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
            case Project::type_regularProject:
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
   public function getTimeTracks() {
      if(NULL == $this->timeTracks) {
         $accessLevel_dev = Team::accessLevel_dev;
         $accessLevel_manager = Team::accessLevel_manager;
         $sql = AdodbWrapper::getInstance();

         // select tasks within timestamp, where user is in the team
         // WARN: users having left the team will be included, this is a pre-filter.
         $query = "SELECT timetracking.* ".
                  " FROM codev_timetracking_table as timetracking ".
                  " JOIN codev_team_user_table as team_user ON timetracking.userid = team_user.user_id ".
                  " WHERE team_user.team_id = ".$sql->db_param().
                  " AND team_user.access_level IN (".$sql->db_param().", ".$sql->db_param().") ".
                  " AND timetracking.date >= ".$sql->db_param().
                  " AND timetracking.date <= ".$sql->db_param();

         $q_params[]= $this->team_id;
         $q_params[]=$accessLevel_dev;
         $q_params[]=$accessLevel_manager;
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;
         $result = $sql->sql_query($query, $q_params);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->timeTracks = array();
         while($row = $sql->fetchObject($result)) {

            $tt = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
            $user = UserCache::getInstance()->getUser($tt->getUserId());
            $timestamp = $tt->getDate();

            // check that the user was in the team at the timetrack's date.
            if ($user->isTeamMember($this->team_id, NULL, $timestamp, $timestamp)) {
               $this->timeTracks[] = $tt;
            }


         }
      }
      return $this->timeTracks;
   }

   /**
    * Returns the number of days worked by the team within the timestamp
    * - Observers excluded
    * @return number
    */
   private function getProdDays() {
      if(!is_numeric($this->prodDays)) {
         $this->prodDays = 0;

         // TODO check patch from FDJ 8b003033391c84142787c3379a518a3ef7283587
         //      " AND (codev_team_user_table.departure_date = 0 or codev_team_user_table.departure_date >=$this->startTimestamp)";

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
   private function getProdDaysSideTasks($isDeveloppersOnly = false) {
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
               (!$issue->isVacation($this->team_id))) {
               $prodDays += $timeTrack->getDuration();
            }
         } catch (Exception $e) {
            self::$logger->error("getProdDaysSideTasks(): issue ".$timeTrack->getIssueId().": ".$e->getMessage());
         }
      }
      return $prodDays;
   }

   /**
    * @return number
    */
   public function getAvailableWorkload() {
      if(!is_numeric($this->availableWorkload)) {
         $accessLevel_dev = Team::accessLevel_dev;
         $accessLevel_manager = Team::accessLevel_manager;
         $sql = AdodbWrapper::getInstance();

         $this->availableWorkload = 0;

         // For all the users of the team
         $query = "SELECT user.* ".
                  "FROM {user} as user ".
                  "JOIN codev_team_user_table as team_user ON user.id = team_user.user_id ".
                  "WHERE team_user.team_id =  ".$sql->db_param().
                  " AND team_user.access_level IN (".$sql->db_param().", ".$sql->db_param().")";

         $result = $sql->sql_query($query, array($this->team_id, $accessLevel_dev, $accessLevel_manager));
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = $sql->fetchObject($result)) {
            $user = UserCache::getInstance()->getUser($row->id, $row);
            $this->availableWorkload += $user->getAvailableWorkforce($this->startTimestamp, $this->endTimestamp, $this->team_id);
         }
      }

      return $this->availableWorkload;
   }

   /**
    *
    * Note: reopened issues not included
    *
    * @param bool $withSupport
    * @return mixed[]
    */
   public function getResolvedDriftStats($withSupport = true, $extRefOnly=FALSE) {
      $issueList = $this->getResolvedIssues($extRefOnly);
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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT bug.* ".
                  " FROM {bug} as bug ".
                  " JOIN {custom_field_string} as field ON bug.id = field.bug_id ".
                  " WHERE field.field_id =  ".$sql->db_param().
                  " AND field.value >= ".$sql->db_param()." AND field.value < ".$sql->db_param().
                  " ORDER BY bug.id ASC";

         $result = $sql->sql_query($query, array($deliveryDateCustomField, $this->startTimestamp, $this->endTimestamp));
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = $sql->fetchObject($result)) {
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
   public function getResolvedIssues($extRefOnly = FALSE, $withReopened=FALSE) {

      $key = ($extRefOnly) ? 'ExtRefOnly' : 'All';
      if ($withReopened) { $key .= 'WithReopened';}

      if(is_null($this->resolvedIssues)) { $this->resolvedIssues = array(); }
      if(is_null($this->resolvedIssues[$key])) {

         $sql = AdodbWrapper::getInstance();
         $formatedProjList = implode( ', ', $this->prodProjectList);
         $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);


         if ("" == $formatedProjList) {
            echo "<div style='color:red'>ERROR getResolvedIssues: no project defined for this team !<br/></div>";
            return 0;
         }

         // all bugs which status changed to 'resolved' whthin the timestamp
         $query = "SELECT bug.* ".
                  "FROM {bug} as bug ";
         if ($extRefOnly) {
            $query .= ", {custom_field_string} ";
         }

         $query .= ", {bug_history} as history ".
                  " WHERE bug.project_id IN (".$formatedProjList.") ".
                  " AND bug.id = history.bug_id ";

         if ($extRefOnly) {
            $query .= " AND {custom_field_string}.bug_id = bug.id ";
            $query .= " AND {custom_field_string}.field_id = ".$sql->db_param();
            $query .= " AND {custom_field_string}.value <> '' ";
            $q_params[]=$extIdField;
         }

           $query .= " AND history.field_name='status' ".
                  " AND history.date_modified >= ".$sql->db_param().
                  " AND history.date_modified < ".$sql->db_param().
                  " AND history.new_value = get_project_resolved_status_threshold(project_id) ".
                  " ORDER BY bug.id DESC";
           $q_params[]=$this->startTimestamp;
           $q_params[]=$this->endTimestamp;

         $result = $sql->sql_query($query, $q_params);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->resolvedIssues[$key] = array();
         $resolvedList = array();
         while($row = $sql->fetchObject($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->id, $row);

            if (!$withReopened) {
               // skip if the bug has been reopened before endTimestamp
               $latestStatus = $issue->getStatus($this->endTimestamp);
               if ($latestStatus < $issue->getBugResolvedStatusThreshold()) {
                  continue;
               }
            }
            // remove duplicated values
            if (!in_array ($issue->getId(), $resolvedList)) {
               $resolvedList[] = $issue->getId();
               $this->resolvedIssues[$key][] = $issue;
            }
         }
      }
      return $this->resolvedIssues[$key];
   }

   /**
    * return stats on which Issues where delivered after the DeadLine
    *
    * @param Issue[] $issueList
    * @return mixed[]
    *
    * TODO move this method to IssueSelection class
    */
   private function getIssuesTimeDriftStats(array $issueList) {
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
   private function getIssuesDriftStats(array $issueList, $withSupport = true) {
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
         $issueDrift = $issue->getDrift();
         $derive += $issueDrift;
         $issueDriftMgrEE = $issue->getDriftMgr();
         $deriveETA += $issueDriftMgrEE;

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
    * Calculates the time each user spends on the teams projects.
    */
   public function getWorkingDaysPerProjectPerUser($withNoStats = true, $withDisabled = true, $sideTasksProjects = true) {

      $team = TeamCache::getInstance()->getTeam($this->team_id);
      $projectIds = array_keys($team->getProjects($withNoStats, $withDisabled, $sideTasksProjects));

      $projDataList = array();
      $memberList = array();
      $allProjTotalElapsed = 0;

      // get tracks of Dev/Mgr (active within the timestamp)
      $timeTracks = $this->getTimeTracks();
      foreach ($timeTracks as $timeTrack) {
         try {
            $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());
            $projectId = $issue->getProjectId();
            $userId = $timeTrack->getUserId();

            if (!in_array($projectId, $projectIds)) {
               continue;
            }

            // create member
            if (!array_key_exists($userId, $memberList)) {
               $user = UserCache::getInstance()->getUser($userId);
               $memberList[$userId] = array(
                   'name' => $user->getRealname(),
                   'totalElapsed' => 0
               );
            }

            // create project
            if (!array_key_exists($projectId, $projDataList)) {
               $prj = ProjectCache::getInstance()->getProject($projectId);
               $projDataList[$projectId] = array(
                   'name' => $prj->getName(),
                   'totalElapsed' => 0,
                   'usersData' => array()
               );
            }

            // process track data
            $elapsed = $timeTrack->getDuration();
            $projDataList[$projectId]['totalElapsed'] += $elapsed;
            $projDataList[$projectId]['usersData'][$userId] += $elapsed;
            $memberList[$userId]['totalElapsed'] += $elapsed;
            $allProjTotalElapsed += $elapsed;
         } catch (Exception $exp) {
            // XXX show some error on the screen since the data is wrong
            self::$logger->warn("getWorkingDaysPerProjectPerUser: issue " . $timeTrack->getIssueId() . " not found in Mantis DB.");
         }
      }

      $data = array(
          'userDataList' => $memberList,
          'projDataList' => $projDataList,
          'allProjTotalElapsed' => $allProjTotalElapsed
      );

      //echo nl2br(print_r($data, true));

      return $data;
   }

   /**
    * Returns an array of (date => duration) containing all days where duration != 1
    * @param int $userid
    * @param bool $isStrictlyTimestamp
    * @return number[]
    */
   public function checkCompleteDays($userid, $isStrictlyTimestamp = FALSE) {
      // Get all dates that must be checked
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT date, SUM(duration) as count ".
               " FROM codev_timetracking_table ".
               " WHERE userid = ".$sql->db_param();
      $q_params[]=$userid;
      if ($isStrictlyTimestamp) {
         $query .= " AND date >= ".$sql->db_param()." AND date < ".$sql->db_param();
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;
      }
      $query .= " GROUP BY date ORDER BY date";

      $result = $sql->sql_query($query, $q_params);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $incompleteDays = array(); // unique date => sum durations
      while($row = $sql->fetchObject($result)) {
         $value = round($row->count, 3);
         //$durations[$row->date] = round($row->count, 3);
         if ($value != 1) {
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

      if(count($weekTimestamps) > 0) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT DISTINCT date ".
                  " FROM codev_timetracking_table ".
                  " WHERE userid = ".$sql->db_param().
                  " AND date IN (".implode(', ', $weekTimestamps).")";
         $q_params[]=$userid;

         $result = $sql->sql_query($query, $q_params);

         $daysWithTimeTracks = array();
         while($row = $sql->fetchObject($result)) {
            $daysWithTimeTracks[] = $row->date;
         }
         $missingDays = array_diff($weekTimestamps, $daysWithTimeTracks);
      }

      return $missingDays;
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
      $sql = AdodbWrapper::getInstance();

      if (!$isTeamProjOnly) {
         // For all bugs in timestamp
         $query = "SELECT bugid, jobid, date, duration ".
                  " FROM codev_timetracking_table ".
                  " WHERE date >= ".$sql->db_param()." AND date < ".$sql->db_param().
                  " AND userid = ".$sql->db_param();
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;
         $q_params[]=$userid;
      } else {
         $projList = TeamCache::getInstance()->getTeam($this->team_id)->getProjects();
         $formatedProjList = implode( ', ', array_keys($projList));
         $query = "SELECT timetracking.bugid, timetracking.jobid, timetracking.date, timetracking.duration ".
                  " FROM codev_timetracking_table as timetracking ".
                  " JOIN {bug} AS bug ON timetracking.bugid = bug.id ".
                  " JOIN {project} AS project ON bug.project_id = project.id ".
                  " WHERE timetracking.userid =  ".$sql->db_param().
                  " AND timetracking.date >= ".$sql->db_param().
                  " AND timetracking.date <  ".$sql->db_param().
                  " AND bug.project_id IN (".$formatedProjList.")";
         $q_params[]=$userid;
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;
      }

      $result = $sql->sql_query($query, $q_params);

      while($row = $sql->fetchObject($result)) {
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
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT bug.id as bugid, bug.project_id, timetracking.jobid, SUM(timetracking.duration) as duration ".
               " FROM codev_timetracking_table as timetracking, codev_team_user_table as team_user, {bug} as bug, codev_job_table as job, {project} as project ".
               " WHERE team_user.user_id = timetracking.userid ".
               " AND bug.id = timetracking.bugid ".
               " AND project.id = bug.project_id ".
               " AND job.id = timetracking.jobid ".
               " AND timetracking.date >= ".$sql->db_param().
               " AND timetracking.date < ".$sql->db_param().
               " AND team_user.team_id = ".$sql->db_param().
               " AND team_user.access_level IN (".$sql->db_param().", ".$sql->db_param().") ";
      $q_params[]=$this->startTimestamp;
      $q_params[]=$this->endTimestamp;
      $q_params[]=$this->team_id;
      $q_params[]=$accessLevel_dev;
      $q_params[]=$accessLevel_manager;

      if (false != $isTeamProjOnly) {
         $projList = TeamCache::getInstance()->getTeam($this->team_id)->getProjects();
         $formatedProjList = implode( ', ', array_keys($projList));
         $query.= " AND bug.project_id in (".$formatedProjList.") ";
      }

      $query.= " GROUP BY bug.id, job.id, bug.project_id ORDER BY project.name, bug.id DESC;";

      $result = $sql->sql_query($query, $q_params);

      $projectTracks = array();

      while($row = $sql->fetchObject($result)) {
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
    *
    * Note: internal tasks (tasks having no ExternalReference) NOT INCLUDED
    *
    * @return Issue[]
    */
   public function getReopened() {
      if(is_null($this->reopenedList)) {
         $formatedProjList = implode(', ', $this->prodProjectList);

         if ("" == $formatedProjList) {
            echo "<div style='color:red'>ERROR getReopened: no project defined for this team !<br/></div>";
            return 0;
         }
         $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);

         // all bugs which resolution changed to 'reopened' whthin the timestamp
         // having an ExternalReference
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT bug.*" .
                  " FROM {custom_field_string}, {bug} as bug ".
                  " JOIN {bug_history} as history ON bug.id = history.bug_id " .
                  " WHERE bug.project_id IN (".$formatedProjList.") " .
                  " AND {custom_field_string}.bug_id = bug.id ".
                  " AND {custom_field_string}.field_id = ".$sql->db_param().
                  " AND {custom_field_string}.value <> '' ".
                  " AND history.field_name='status' " .
                  " AND history.date_modified >= ".$sql->db_param().
                  " AND history.date_modified < ".$sql->db_param().
                  " AND history.old_value >= get_project_resolved_status_threshold(bug.project_id) " .
                  " AND history.new_value <  get_project_resolved_status_threshold(bug.project_id) " .
                  " GROUP BY bug.id ORDER BY bug.id DESC;";
         $q_params[]=$extIdField;
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;

         $result = $sql->sql_query($query, $q_params);

         $this->reopenedList = array();
         while ($row = $sql->fetchObject($result)) {

            $issue = IssueCache::getInstance()->getIssue($row->id, $row);

            $this->reopenedList[$row->id] = $issue;
         }
      }

      #echo "getReopened $query RESULT=".count($this->reopenedList)."<br>";
      return $this->reopenedList;
   }

   /**
    * returns the number of bug that have been submitted in the period
    * @return int
    */
   public function getSubmitted($extRefOnly = FALSE) {
      $key = ($extRefOnly) ? 'ExtRefOnly' : 'All';

      if(is_null($this->submittedBugs)) { $this->submittedBugs = array(); }

      if(!is_numeric($this->submittedBugs[$key])) {

         $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);
         $sql = AdodbWrapper::getInstance();

         $query = "SELECT COUNT(bug.id) as count ".
                  " FROM {bug} as bug ";
         if ($extRefOnly) {
            $query .= ", {custom_field_string} ";
         }
         $query .= " WHERE bug.date_submitted >= ".$sql->db_param().
                   " AND bug.date_submitted < ".$sql->db_param();
         $q_params[]=$this->startTimestamp;
         $q_params[]=$this->endTimestamp;

         // Only for specified Projects
         $projects = $this->prodProjectList;
         if (0 != count($projects)) {
            $formatedProjects = implode( ', ', $projects);
            $query .= " AND bug.project_id IN (".$formatedProjects.") ";
         }
         if ($extRefOnly) {
            $query .= " AND {custom_field_string}.field_id =  ".$sql->db_param();
            $query .= " AND {custom_field_string}.bug_id = bug.id ";
            $query .= " AND {custom_field_string}.value <> '' ";
            $q_params[]=$extIdField;
         }
         $query .= ";";

         $result = $sql->sql_query($query, $q_params);

         $this->submittedBugs[$key] = $sql->sql_result($result);
      }

      return $this->submittedBugs[$key];
   }

   /**
    * $countReopened / $countResolved
    *
    * Note: internal tasks (tasks having no ExternalReference) NOT INCLUDED
    *
    * @return number
    */
   public function getReopenedRateResolved() {
      $countReopened = count($this->getReopened());
      $extRefOnly = TRUE;
      $withReopened = TRUE;
      $countResolved = count($this->getResolvedIssues($extRefOnly, $withReopened));
      $rate = 0;
      if ($countResolved != 0)  {
         $rate = $countReopened / $countResolved;
      }
      return $rate;
   }

}

// Initialize complex static variables
TimeTracking::staticInit();


