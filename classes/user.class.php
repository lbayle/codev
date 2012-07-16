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

// TODO Remove this import
include_once('classes/user_cache.class.php');

include_once('classes/config.class.php');
include_once('classes/holidays.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/project.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/team.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/timetrack_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

/**
 * MANTIS CoDev User Authorization Management
 * @author LoB
 * @date 23 Jun 2010
 */
class User {
   
   /**
    * @var int The id 
    */
   public $id;

   /**
    * @var Logger The logger 
    */
   private static $logger;
   
   /**
    * @var array string[int] name[id] All users
    */
   private static $users;
   
   
   private $init = FALSE;
   
   /**
    * @var string The name 
    */
   private $name;
   
   /**
    * @var string The first name 
    */
   private $firstName;
   
   /**
    * @var string The last name 
    */
   private $lastName;
   
   /**
    * @var string The last name 
    */
   private $shortName;
   
   /**
    * @var string The real name 
    */
   private $realName;
   
   /**
    * @var array Filters 
    */
   private $timetrackingFilters;
   
   /**
    * @var array string[int] name[id] The leaded teams
    */
   private $leadedTeams;
   
   /**
    * @var array Issue[] The monitored issues
    */
   private $monitoredIssues;
   
   /**
    *
    * @var array Cache of team menber
    */
   private $teamMemberCache;
   
   /**
    * @var array Cache of arrival date
    */
   private $arrivalDateCache;
   
   /**
    * @var array Cache of depature date
    */
   private $departureDateCache;

   private $devTeamList;
   private $observedTeamList;
   private $managedTeamList;
   private $allTeamList;

   /**
    * @param int $user_id The user id
    */
   public function __construct($user_id) {
      $this->id = $user_id;
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   /**
    * Initialize the user 
    */
   private function initialize() {
      if(!$this->init) {
         $query = "SELECT username, realname " .
                  "FROM `mantis_user_table` " .
                  "WHERE id = $this->id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         if(SqlWrapper::getInstance()->sql_num_rows($result)) {
            $row = SqlWrapper::getInstance()->sql_fetch_object($result);
            $this->name = $row->username;
            $this->realName = $row->realname;
         } else {
            $this->name = "(unknown $this->id)";
         }
         $this->init = TRUE;
      }
   }

   /**
    * Get the user id from a name
    * @param string $name The username
    * @return int The userid
    */
   public static function getUserId($name) {
      $query = "SELECT id FROM `mantis_user_table` WHERE username='$name'";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $commandid = SqlWrapper::getInstance()->sql_result($result, 0);

      return $commandid;
   }

   /**
    * Get the name
    * @return string The name 
    */
   public function getName() {
      if (NULL == $this->name) {
         $this->initialize();
      }
      return $this->name;
   }

   /**
    * Get the first name 
    * @return string The first name 
    */
   public function getFirstname() {
      if (NULL == $this->firstName) {
         $this->initialize();
         $this->firstName = strtok($this->getName(), " "); // 1st token: firstname
      }
      return $this->firstName;
   }

   /**
    * Get the last name 
    * @return string The last name 
    */
   public function getLastname() {
      if (NULL == $this->lastName) {
         $this->initialize();
         $tok = strtok($this->getName(), " ");  // 1st token: firstname
         $tok = strtok(" ");  // 2nd token: lastname
      }
      return $this->lastName;
   }

   /**
    * Get the short name. ex: Louis BAYLE => LBA
    * @return string The short name 
    */
   public function getShortname() {
      if (NULL == $this->shortName) {
         $this->initialize();
         if (0 == $this->id) {
            $this->shortName = "";
         } else {
            $tok1 = strtok($this->getName(), " ");  // 1st token: firstname
            $tok2 = strtok(" ");  // 2nd token: lastname

            if (false == $tok2) {
               $this->shortName = $tok1[0] . $tok1[1] . $tok1[2];
            } else {
               $this->shortName = $tok1[0] . $tok2[0] . $tok2[1];
            }
         }
      }
      return $this->shortName;
   }

   /**
    * Get the real name
    * @return string The real name 
    */
   public function getRealname() {
      if (NULL == $this->realName) {
         $this->initialize();
      }
      return $this->realName;
   }

   /**
    * @param int $team_id
    * @return bool
    */
   public function isTeamLeader($team_id) {
      $team = TeamCache::getInstance()->getTeam($team_id);
      return ($team->leader_id == $this->id);
   }

   /**
    * @param int $team_id
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return bool 
    */
   public function isTeamDeveloper($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_dev, $startTimestamp, $endTimestamp);
   }

   /**
    * REM isTeamObserver not used for now
    * @param int $team_id
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return bool 
    */
   public function isTeamObserver($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_observer, $startTimestamp, $endTimestamp);
   }

   /**
    * @param int $team_id
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return bool 
    */
   public function isTeamManager($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_manager, $startTimestamp, $endTimestamp);
   }

   /**
    * @param int $team_id
    * @param int $accessLevel
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return bool 
    */
   public function isTeamMember($team_id, $accessLevel = NULL, $startTimestamp = NULL, $endTimestamp = NULL) {
      if (NULL == $this->teamMemberCache) {
         $this->teamMemberCache = array();
      }

      $key = $team_id . '_' . $accessLevel . ' ' . $startTimestamp . ' ' . $endTimestamp;

      if (!array_key_exists($key, $this->teamMemberCache)) {

         $query = "SELECT COUNT(id) FROM `codev_team_user_table` " .
                 "WHERE team_id = $team_id " .
                 "AND user_id = $this->id ";

         if (NULL != $accessLevel) {
            $query .= "AND access_level = $accessLevel ";
         }

         if ((NULL != $startTimestamp) && (NULL != $endTimestamp)) {
            $query .= "AND arrival_date < $endTimestamp AND " .
                    "(departure_date >= $startTimestamp OR departure_date = 0)";
            // REM: if departure_date = 0, then user stays until the end of the world.
         }

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $nbTuples = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

         $this->teamMemberCache[$key] = (0 != $nbTuples);
      }
      return $this->teamMemberCache[$key];
   }

   /**
    * if no team specified, choose the oldest arrival date
    * @param int $team_id
    * @return unknown_type 
    */
   public function getArrivalDate($team_id = NULL) {
      if (NULL == $this->arrivalDateCache) {
         $this->arrivalDateCache = array();
      }
      
      $key = 't'.$team_id;
      if (!array_key_exists($key, $this->arrivalDateCache)) {
         $arrival_date = time();

         $query = "SELECT arrival_date FROM `codev_team_user_table` " .
                  "WHERE user_id = $this->id ";
         if (isset($team_id)) {
            $query .= "AND team_id = $team_id";
         }
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            if ($row->arrival_date < $arrival_date) {
               $arrival_date = $row->arrival_date;
            }
         }
         
         $this->arrivalDateCache[$key] = $arrival_date;

         //echo "DEBUG arrivalDate = ".date('Y - m - d', $arrival_date)."<br>";
      }

      return $this->arrivalDateCache[$key];
   }

   /** 
    * if no team specified, choose the most future departureDate
    * or '0' if still active in a team
    * @param int $team_id
    * @return unknown_type 
    */
   public function getDepartureDate($team_id = NULL) {
      if (NULL == $this->departureDateCache) {
         $this->departureDateCache = array();
      }
      
      $key = 't'.$team_id;
      if (!array_key_exists($key, $this->departureDateCache)) {
         $departureDate = 0;

         $query = "SELECT departure_date FROM `codev_team_user_table` " .
                 "WHERE user_id = $this->id ";
         if (isset($team_id)) {
            $query .= "AND team_id = $team_id";
         }
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            if ((NULL == $row->departure_date ) || (0 == $row->departure_date )) {
               $departureDate = 0;
               break;
            }
            if ($row->departure_date > $departureDate) {
               $departureDate = $row->departure_date;
            }
         }

         $this->departureDateCache[$key] = $departureDate;

         //echo "DEBUG departureDate = ".date('Y - m - d', $departureDate)."<br>";
      }

      return $this->departureDateCache[$key];
   }

   /**
    * returns an array $daysOf[date] = $row->duration;
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return mixed[][]   array(date => array('duration','type','title'))
    */
   public function getDaysOfInPeriod($startTimestamp, $endTimestamp) {
      $daysOf = array();  // day => duration

      $query = "SELECT bugid, date, duration " .
               "FROM `codev_timetracking_table` " .
               "WHERE date >= $startTimestamp AND date <= $endTimestamp " .
               "AND userid = $this->id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $teamidList = array_keys($this->getTeamList());
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         try {
            $issue = IssueCache::getInstance()->getIssue($row->bugid);

            if ($issue->isVacation($teamidList)) {
               if (isset($daysOf[$row->date])) {
                  $daysOf[$row->date]['duration'] += $row->duration;
               } else {
                  $daysOf[$row->date] = array( 'duration' => $row->duration,
                                               'type' => 'Inactivity',  // TODO
                                               'color' => 'A8FFBD',  // TODO (light green)
                                               'title' => $issue->summary
                                             );
               }
               #echo "DEBUG user $this->userid daysOf[".date("j", $row->date)."] = ".$daysOf[date("j", $row->date)]." (+$row->duration)<br/>";
            }
         } catch (Exception $e) {
            self::$logger->error("getDaysOfInPeriod(): issue $row->bugid: " . $e->getMessage());
         }
      }
      return $daysOf;
   }

   /**
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return unknown_type 
    */
   public function getAstreintesInMonth($startTimestamp, $endTimestamp) {
      $astreintes = array();  // day => duration

      $query = "SELECT bugid, date, duration " .
               "FROM `codev_timetracking_table` " .
               "WHERE date >= $startTimestamp AND date <= $endTimestamp " .
               "AND userid = $this->id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         try {
            $issue = IssueCache::getInstance()->getIssue($row->bugid);
            if ($issue->isAstreinte()) {
               if (isset($astreintes[$row->date])) {
                  $astreintes[$row->date]['duration'] += $row->duration;
               } else {
                  $astreintes[$row->date] = array( 'duration' => $row->duration,
                                                'type' => 'onDuty',  // TODO
                                                'color' => 'F8FFA8',  // TODO (yellow)
                                                'title' => $issue->summary
                                             );
               }
               //echo "DEBUG user $this->userid astreintes[".date("j", $row->date)."] = ".$astreintes[date("j", $row->date)]." (+$row->duration)<br/>";
            }
         } catch (Exception $e) {
            self::$logger->error("getAstreintesInMonth(): issue $row->bugid: " . $e->getMessage());
         }

      }
      return $astreintes;
   }

   /**
    * concat durations of all ExternalTasksProject issues.
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @return array $extTasks[timestamp] = $row->duration;
    */
   public function getExternalTasksInPeriod($startTimestamp, $endTimestamp) {
      $extTasks = array();  // timestamp => duration

      $extTasksProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $leaveTaskId = Config::getInstance()->getValue(Config::id_externalTask_leave);
#echo "leaveTaskId $leaveTaskId<br>";
      $query = "SELECT bugid, date, duration " .
              "FROM `codev_timetracking_table` " .
              "WHERE date >= $startTimestamp AND date <= $endTimestamp " .
              "AND userid = $this->id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         try {
            $issue = IssueCache::getInstance()->getIssue($row->bugid);
            if ($issue->projectId == $extTasksProjId) {
               if (isset($extTasks[$row->date])) {
                  $extTasks[$row->date]['duration'] += $row->duration;
               } else {

                  if ($leaveTaskId == $issue->bugId) {
                     $color = 'A8FFBD';  // TODO (light green)
                     $type = 'Inactivity';
                  } else {
                     $color = '75FFDA';  // TODO (green2)
                     $type = 'ExternalTask';
                  }

                  $extTasks[$row->date] = array( 'duration' => $row->duration,
                                                'type' => $type,  // TODO
                                                'color' => $color,  // TODO (green2)
                                                'title' => $issue->summary
                                             );
               }
               self::$logger->debug("user $this->id ExternalTasks[" . date("j", $row->date) . "] = " . $extTasks[date("j", $row->date)] . " (+$row->duration)");
            }
         } catch (Exception $e) {
            self::$logger->warn("getExternalTasksInPeriod: " . $e->getMessage());
         }
      }
      return $extTasks;
   }

   /**
    * Nb working days in the period (no holidays, no external tasks)
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @param int $team_id
    * @return int 
    */
   public function getAvailableWorkload($startTimestamp, $endTimestamp, $team_id = NULL) {
      $holidays = Holidays::getInstance();

      $prodDaysForecast = 0;
      $nbOpenDaysInPeriod = 0;

      $arrivalDate = $this->getArrivalDate($team_id);
      $departureDate = $this->getDepartureDate($team_id);

      if ($arrivalDate > $endTimestamp) {
         self::$logger->warn("getAvailableWorkload userId=$this->id user.arrivalDate=" . date("Y-m-d", $arrivalDate) . " > .endTimestamp=" . date("Y-m-d", $endTimestamp));
         return 0;
      }

      // if not specified, $departureDate = $endTimestamp
      if (0 == $departureDate) {
         $departureDate = $endTimestamp;
      }

      if ($departureDate < $startTimestamp)
         return 0;

      // restrict timestamp to the period where the user is working on the project
      $startT = ($arrivalDate > $startTimestamp) ? $arrivalDate : $startTimestamp;
      $endT = ($departureDate < $endTimestamp) ? $departureDate : $endTimestamp;

      self::$logger->debug("getAvailableWorkload user.startT=" . date("Y-m-d", $startT) . " user.endT=" . date("Y-m-d", $endT));

      // get $nbOpenDaysInPeriod
      for ($i = $startT; $i <= $endT; $i += (60 * 60 * 24)) {
         // monday to friday
         if (NULL == $holidays->isHoliday($i)) {
            $nbOpenDaysInPeriod++;
         }
      }

      $nbDaysOf = array_sum($this->getDaysOfInPeriod($startT, $endT));
      $prodDaysForecast = $nbOpenDaysInPeriod - $nbDaysOf;

      // remove externalTasks timetracks
      $nbExternal = array_sum($this->getExternalTasksInPeriod($startT, $endT));
      $prodDaysForecast -= $nbExternal;


      self::$logger->debug("user $this->id timestamp = " . date('Y-m-d', $startT) . " to " . date('Y-m-d', $endT) . " =>  ($nbOpenDaysInPeriod - " . $nbDaysOf . ") = $prodDaysForecast");

      return $prodDaysForecast;
   }

   /**
    * Nb days spent on tasks in the period (no holidays, no external tasks)
    *
    * Note: including non-inactivity sideTasks (cat_management, cat_tools, cat_workshop)
    *
    * (consommé sur la periode)
    *
    * @param unknown_type $startTimestamp
    * @param unknown_type $endTimestamp
    * @param int $team_id
    * @return array[bug_id] = duration
    */
   public function getWorkloadPerTask($startTimestamp, $endTimestamp, $team_id = NULL) {
      $workloadPerTaskList = array();

      $query = "SELECT id FROM `codev_timetracking_table` " .
              "WHERE date >= $startTimestamp AND date <= $endTimestamp " .
              "AND userid = $this->id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $team = TeamCache::getInstance()->getTeam($team_id);
      if (NULL != $team_id) {
         $projectList = Team::getProjectList($team_id);
      }

      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $timetrack = TimeTrackCache::getInstance()->getTimeTrack($row->id);

         // exclude projects not in team list
         // exclude externalTasks & NoStatsProjects
         if (NULL != $projectList) {
            if (!in_array($timetrack->getProjectId(), array_keys($projectList))) {
               continue;
            }
         }

         // exclude Inactivity tasks
         if ($team->isSideTasksProject($timetrack->getProjectId()))
            $workloadPerTaskList["$timetrack->bugId"] += $timetrack->duration;
      }

      return $workloadPerTaskList;
   }

   /**
    * @return array the teams i'm leader of.
    */
   public function getLeadedTeamList() {
      if(NULL == $this->leadedTeams) {
         $this->leadedTeams = array();

         $query = "SELECT DISTINCT id, name FROM `codev_team_table` WHERE leader_id = $this->id  ORDER BY name";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->leadedTeams[$row->id] = $row->name;
            #echo "getLeadedTeamList FOUND $row->id - $row->name<br/>";
         }
      }
      return $this->leadedTeams;
   }

   /**
    * @return array the teams i'm member of.
    */
   public function getDevTeamList() {
      if(NULL == $this->devTeamList) {
         $this->devTeamList = $this->getTeamList(Team::accessLevel_dev);
      }
      return $this->devTeamList;
   }

   /**
    * @return array the teams i'm observer of.
    */
   public function getObservedTeamList() {
      if(NULL == $this->observedTeamList) {
         $this->observedTeamList = $this->getTeamList(Team::accessLevel_observer);
      }
      return $this->observedTeamList;
   }

   /**
    * @return array the teams i'm Manager of.
    */
   public function getManagedTeamList() {
      if(NULL == $this->managedTeamList) {
         $this->managedTeamList = $this->getTeamList(Team::accessLevel_manager);
      }
      return $this->managedTeamList;
   }

   /**
    * returns teams, the user is involved in.
    * @param int $accessLevel if NULL return all teams including observed teams.
    * @return array string[int] name[id]
    */
   public function getTeamList($accessLevel = NULL) {
      $teamList = array();

      if($accessLevel == NULL && $this->allTeamList != NULL) {
         return $this->allTeamList;
      }
      $query = "SELECT codev_team_table.id, codev_team_table.name " .
               "FROM `codev_team_user_table`, `codev_team_table` " .
               "WHERE codev_team_user_table.user_id = $this->id " .
               "AND   codev_team_user_table.team_id = codev_team_table.id ";
      if (NULL != $accessLevel) {
         $query .= "AND   codev_team_user_table.access_level = $accessLevel ";
      }
      $query .= "ORDER BY codev_team_table.name";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $teamList[$row->id] = $row->name;
         #echo "getTeamList(".Team::$accessLevelNames[$accessLevel].") FOUND $row->id - $row->name<br/>";
      }

      if($accessLevel == NULL) {
         $this->allTeamList = $teamList;
      }

      return $teamList;
   }

   /**
    * returns an array of project_id=>project_name that the user is involved in,
    * depending on the teams the user belongs to.
    *
    * @param array teamList       if NULL then return projects from all the teams the user belongs to
    * @param unknown_type noStatsProject if false, the noStatsProject will not be returned in the list
    *
    * @return array [id => project_name]
    */
   public function getProjectList(array $teamList = NULL, $noStatsProject = true) {
      $projList = array();

      if (NULL == $teamList) {
         // if not specified, get projects from the teams I'm member of.
         $teamList = $this->getTeamList();
      }
      if (0 != count($teamList)) {
         $formatedTeamList = implode(', ', array_keys($teamList));
         $query = "SELECT DISTINCT codev_team_project_table.project_id, mantis_project_table.name " .
                 "FROM `codev_team_project_table`, `mantis_project_table`" .
                 "WHERE codev_team_project_table.team_id IN ($formatedTeamList) " .
                 "AND codev_team_project_table.project_id = mantis_project_table.id ";

         if (!$noStatsProject) {
            $query .= "AND codev_team_project_table.type <> " . Project::type_noStatsProject . " ";
         }

         $query .= "ORDER BY mantis_project_table.name";

         if (isset($_GET['debug_sql'])) {
            echo "User.getProjectList(): query = $query<br/>";
         }

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $projList[$row->project_id] = $row->name;
         }
      } else {
         // this happens if User is not a Developper (Manager or Observer)
         //self::$logger->debug("ERROR: User $this->id is not member of any team !");
      }

      return $projList;
   }

   /**
    * sum the RAF (or mgrEffortEstim if no RAF defined) of all the opened Issues assigned to me.
    * @param array $projList
    * @return int 
    */
   public function getForecastWorkload(array $projList = NULL) {
      $totalRemaining = 0;

      if (NULL == $projList) {
         $teamList = $this->getDevTeamList();
         $projList = $this->getProjectList($teamList);
      }

      if (0 == count($projList)) {
         // this happens if User is not a Developper (Manager or Observer)
         //echo "<div style='color:red'>ERROR: no project associated to this team !</div><br>";
         return $totalRemaining;
      }

      $formatedProjList = implode(', ', array_keys($projList));

      // find all issues i'm working on
      $query = "SELECT * FROM `mantis_bug_table` " .
              "WHERE project_id IN ($formatedProjList) " .
              "AND handler_id = $this->id " .
              "AND status < get_project_resolved_status_threshold(project_id) " .
              "ORDER BY id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);

         $totalRemaining += $issue->getDurationMgr();
      }

      return $totalRemaining;
   }

   /**
    * Returns the Issues assigned to me.
    * the issue list is ordered by priority.
    *
    * priority criteria are:
    * - opened
    * - deadLine
    * - priority
    *
    * @param array $projList
    * @param bool $withResolved
    * @return array[bugId] = Issue
    */
   public function getAssignedIssues(array $projList = NULL, $withResolved = false) {
      $issueList = array();

      if (NULL == $projList) {
         // get all teams except those where i'm Observer
         $dTeamList = $this->getDevTeamList();
         $mTeamList = $this->getManagedTeamList();
         $teamList = $dTeamList + $mTeamList;           // array_merge does not work ?!
         $projList = $this->getProjectList($teamList);
      }

      if (0 == count($projList)) {
         self::$logger->warn("getAssignedIssues: no projects defined for user $this->id (" . $this->getRealname() . ")");
         return $issueList;
      }

      $formatedProjList = implode(', ', array_keys($projList));


      $query = "SELECT * " .
              "FROM `mantis_bug_table` " .
              "WHERE project_id IN ($formatedProjList) " .
              "AND handler_id = $this->id ";

      if (!$withResolved) {
         $query .= "AND status < get_project_resolved_status_threshold(project_id) ";
      }
      $query .= "ORDER BY id DESC";


      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issueList[] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      if (self::$logger->isDebugEnabled()) {
         $formatedList = implode(', ', array_keys($issueList));
         self::$logger->debug("getAssignedIssues: List BEFORE sort = " . $formatedList);
      }

      // quickSort the list
      $sortedList = Tools::qsort($issueList);

      if (self::$logger->isDebugEnabled()) {
         $formatedList = implode(', ', array_keys($sortedList));
         self::$logger->debug("getAssignedIssues: List AFTER Sort = " . $formatedList);
      }

      return $sortedList;
   }

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
   public function getMonitoredIssues() {
      if(NULL == $this->monitoredIssues) {
         $query = "SELECT DISTINCT bug_id " .
                  "FROM `mantis_bug_monitor_table` " .
                  "WHERE user_id = $this->id " .
                  "ORDER BY bug_id DESC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->monitoredIssues = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->bug_id);
            if ($issue->currentStatus < $issue->getBugResolvedStatusThreshold()) {
               $this->monitoredIssues[] = $issue;
            }
         }
         // quickSort the list
         $this->monitoredIssues = Tools::qsort($this->monitoredIssues);
      }

      return $this->monitoredIssues;
   }

   /**
    * check Timetracks & fixed holidays
    * and returns how many time is available for work on this day.
    * @param unknown_type $timestamp
    * @return int 
    */
   public function getAvailableTime($timestamp) {
      // we need to be absolutely sure that time is 00:00:00
      $timestamp = mktime(0, 0, 0, date("m", $timestamp), date("d", $timestamp), date("Y", $timestamp));

      // if it's a holiday or a WE, then no work possible
      if (NULL != Holidays::getInstance()->isHoliday($timestamp)) {
         //echo "DEBUG getAvailableTime:".date('Y-m-d', $timestamp)." isHoliday<br/>\n";
         return 0;
      }

      // now check for Timetracks, the time left is free time to work
      $query = "SELECT SUM(duration) " .
              "FROM `codev_timetracking_table` " .
              "WHERE userid = $this->id " .
              "AND date = $timestamp";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $data = SqlWrapper::getInstance()->sql_fetch_array($result);
      $sum = round($data[0], 2);

      $availTime = (1 - $sum <= 0) ? 0 : (1 - $sum);

      //echo "DEBUG getAvailableTime ".date('Y-m-d', $timestamp).": TotalDurations=".$sum."  availTime=$availTime<br/>\n";
      return $availTime;
   }

   /**
    *
    * checks user filter settings in codev_config_table
    * if not found, use defaults settings defined in constants.php
    *
    * id = 'id_timetrackingFilters'
    * type = keyValue  "onlyAssignedTo:0,hideResolved:1,hideDevProjects:0"
    *
    * @param unknown_type $filterName 'onlyAssignedTo'
    * @return unknown_type returns filterValue
    */
   function getTimetrackingFilter($filterName) {
      global $default_timetrackingFilters;

      if ((NULL == $this->timetrackingFilters) ||
              ('' == $this->timetrackingFilters)) {

         // TODO Config class cannot handle multiple lines for same id
         $query = "SELECT value FROM `codev_config_table` " .
                 "WHERE config_id = '" . Config::id_timetrackingFilters . "' " .
                 "AND user_id = $this->id";
         self::$logger->debug("query = " . $query);

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // get default filters if not found
         $keyvalue = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : $default_timetrackingFilters;

         self::$logger->debug("user $this->id timeTrackingFilters = <$keyvalue>");
         $this->timetrackingFilters = Tools::doubleExplode(':', ',', $keyvalue);
      }
      // get value
      $value = $this->timetrackingFilters[$filterName];

      self::$logger->debug("user $this->id timeTrackingFilter $filterName = <$value>");

      return $value;
   }

   /**
    * @param string $filterName
    * @param unknown_type $value 
    */
   function setTimetrackingFilter($filterName, $value) {
      // init timetrackingFilters
      if ((NULL == $this->timetrackingFilters) ||
              ('' == $this->timetrackingFilters)) {
         $this->getTimetrackingFilter('onlyAssignedTo');
      }

      $this->timetrackingFilters[$filterName] = $value;

      $keyvalue = Tools::doubleImplode(':', ',', $this->timetrackingFilters);
      self::$logger->debug("Write filters : $keyvalue");

      // save new settings
      Config::setValue(Config::id_timetrackingFilters, $keyvalue, Config::configType_keyValue, "filter for timetracking page", 0, $this->id);
   }

   /**
    * Get users
    * @return array string[int] : username[id]
    */
   public static function getUsers() {
      if(NULL == self::$users) {
         $query = "SELECT id, username FROM `mantis_user_table` ORDER BY username";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            return NULL;
         }

         self::$users = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            self::$users[$row->id] = $row->username;
         }
      }
      return self::$users;
   }

}

?>
