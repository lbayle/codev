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
 * MANTIS CoDev User Authorization Management
 * @author LoB
 * @date 23 Jun 2010
 */
class User extends Model {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * @var array string[int] name[id] All users
    */
   private static $users;

   /**
    *
    * @var array planning_report options
    */
   public static $defaultPlanningOptions;
   public static $defaultPlanningOptionsDesc;

   /**
    * @var int The id
    */
   private $id;

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
    * @var string email
    */
   private $email;

   private $enabled;

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
    * @var int the default Team on login
    */
   private $defaultTeam;

   /**
    * @var int the default Project on login
    */
   private $defaultProject;

   /**
    * @var int the default Language on login
    */
   private $defaultLanguage;

   /**
    *
    * @var string
    */
   private $cmdStateFiltersCache;

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

   /**
    * @var TimeTrack[]
    */
   private $timeTracksCache;

   private $devTeamList;
   private $observedTeamList;
   private $managedTeamList;
   private $allTeamList;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$defaultPlanningOptions = array(
          'displayExtRef' => 0,
         );

      self::$defaultPlanningOptionsDesc = array(
          'displayExtRef'         => 'Display ExtRef instead of mantis_id',
         );
   }

   /**
    * @param int $user_id The user id
    * @param resource $details The user details
    */
   public function __construct($user_id, $details = NULL) {
      $this->id = $user_id;
      $this->initialize($details);
   }

   /**
    * Initialize the user
    * @param resource $row The user details
    */
   private function initialize($row) {
      if(NULL == $row) {
         $sql = AdodbWrapper::getInstance();
         $query = 'SELECT username, realname, enabled FROM {user} WHERE id = '. $sql->db_param();

         $result = $sql->sql_query($query, array($this->id));

         if($sql->getNumRows($result)) {
            $row = $sql->fetchObject($result);
         }
      }

      if(NULL != $row) {
         $this->name = $row->username;
         $this->realName = $row->realname;
         $this->enabled = (1 == $row->enabled);
      } else {
         $this->name = "(unknown $this->id)";
      }
   }

   /**
    * Get the user id from a name
    * @param string $name The username
    * @return int The userid (or NULL if not found)
    */
   public static function getUserId($name) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id FROM {user} WHERE username=".$sql->db_param();
      $q_params[]=$name;
      $result = $sql->sql_query($query, $q_params);

      $userid = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : NULL;
      return $userid;

   }

   /**
    * Get the name
    * @return string The name
    */
   public function getName() {
      return $this->name;
   }

   /**
    * Get the first name
    * @return string The first name
    */
   public function getFirstname() {
      if (NULL == $this->firstName) {
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
      return $this->realName;
   }

   public function getEmail() {
      if (NULL == $this->email) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT email FROM {user} WHERE id=".$sql->db_param();
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);
         $this->email = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : NULL;
      }
      return $this->email;
   }

   /**
    * @return bool isEnabled
    */
   public function isEnabled() {
      return $this->enabled;
   }

   /**
    * @param int $team_id
    * @return bool
    */
   public function isTeamLeader($team_id) {
      $team = TeamCache::getInstance()->getTeam($team_id);
      $adminList = $team->getAdminList();
      return in_array($this->id, $adminList) ;
   }

   /**
    * @param int $team_id
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return bool
    */
   public function isTeamDeveloper($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_dev, $startTimestamp, $endTimestamp);
   }

   /**
    * REM isTeamObserver not used for now
    * @param int $team_id
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return bool
    */
   public function isTeamObserver($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_observer, $startTimestamp, $endTimestamp);
   }

   /**
    * @param int $team_id
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return bool
    */
   public function isTeamManager($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_manager, $startTimestamp, $endTimestamp);
   }

   /**
    * @param int $team_id
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return bool
    */
   public function isTeamCustomer($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      return $this->isTeamMember($team_id, Team::accessLevel_customer, $startTimestamp, $endTimestamp);
   }

   /**
    * @param int $team_id
    * @param int $accessLevel
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return bool
    */
   public function isTeamMember($team_id, $accessLevel = NULL, $startTimestamp = NULL, $endTimestamp = NULL) {
      if (NULL == $this->teamMemberCache) {
         $this->teamMemberCache = array();
      }
      $sql = AdodbWrapper::getInstance();

      $key = $team_id . '_' . $accessLevel . ' ' . $startTimestamp . ' ' . $endTimestamp;

      if (!array_key_exists($key, $this->teamMemberCache)) {
         $query = "SELECT COUNT(id) FROM codev_team_user_table " .
                  " WHERE team_id = " . $sql->db_param().
                  " AND user_id = ". $sql->db_param();
         $q_params = array($team_id, $this->id);

         if (NULL != $accessLevel) {
            $query .= " AND access_level = ". $sql->db_param();
            $q_params[] = $accessLevel;
         }

         if ((NULL != $startTimestamp) && (NULL != $endTimestamp)) {
            $query .= " AND arrival_date <= ". $sql->db_param() .
                      " AND (departure_date >= " .$sql->db_param() .
                      " OR departure_date = 0)";
            $q_params[] = $endTimestamp;
            $q_params[] = $startTimestamp;
            // REM: if departure_date = 0, then user stays until the end of the world.
         }

         $result = $sql->sql_query($query, $q_params);
         $nbTuples = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;

         $this->teamMemberCache[$key] = (0 != $nbTuples);
      }
      return $this->teamMemberCache[$key];
   }

   /**
    * Check if user already exist in Mantis database
    * @param string $username
    * @return boolean : true if user already exist, false if not
    */
   public static function exists($username)
   {
       if($username != null) {
          $sql = AdodbWrapper::getInstance();
            $query = "SELECT count(*) as count FROM {user} WHERE username = ".$sql->db_param();
            $q_params[]=$username;
            $result = $sql->sql_query($query, $q_params);

            while($row = $sql->fetchObject($result)) {
                $count = $row->count;
            }

            if($count != 0)
            {
                return true;
            }
       }
       return false;
   }

   /**
    * Check if user already exist in Mantis database
    * @param string $username
    * @return boolean : true if user already exist, false if not
    */
   public static function existsId($id)
   {
       if($id != null) {
          $sql = AdodbWrapper::getInstance();
            $query = "SELECT count(*) as count FROM {user} WHERE id = ".$sql->db_param();
            $q_params[]=$id;
            $result = $sql->sql_query($query, $q_params);

            while($row = $sql->fetchObject($result)) {
                $count = $row->count;
            }

            if($count != 0)
            {
                return true;
            }
       }
       return false;
   }

   /**
    * if no team specified, choose the oldest arrival date
    * @param int $team_id
    * @return int
    */
   public function getArrivalDate($team_id = NULL) {
      if (NULL == $this->arrivalDateCache) {
         $this->arrivalDateCache = array();
      }

      $key = 't'.$team_id;
      if (!array_key_exists($key, $this->arrivalDateCache)) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT MIN(arrival_date) FROM codev_team_user_table " .
                  "WHERE user_id = ".$sql->db_param();
         $q_params[]=$this->id;
         if (isset($team_id)) {
            $query .= " AND team_id = ".$sql->db_param();
            $q_params[]=$team_id;
         }
         $result = $sql->sql_query($query, $q_params);

         if (0 != $sql->getNumRows($result)) {
             $arrival_date = $sql->sql_result($result, 0);
         } else {
            $arrival_date = time();
            self::$logger->warn("user".$this->id.".getArrivalDate($team_id): no arrival_date found !");
         }
         $this->arrivalDateCache[$key] = $arrival_date;

         //echo "DEBUG user $this->id team $team_id arrivalDate = ".date('Y-m-d', $arrival_date)." ($arrival_date)<br>";
      }

      return $this->arrivalDateCache[$key];
   }

   /**
    * if no team specified, choose the most future departureDate
    * or '0' if still active in a team
    * @param int $team_id
    * @return int
    */
   public function getDepartureDate($team_id = NULL) {
      if (NULL == $this->departureDateCache) {
         $this->departureDateCache = array();
      }

      $key = 't'.$team_id;
      if (!array_key_exists($key, $this->departureDateCache)) {
         $departureDate = 0;
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT departure_date FROM codev_team_user_table " .
                  "WHERE user_id = ".$sql->db_param();
         $q_params[]=$this->id;
         if (isset($team_id)) {
            $query .= " AND team_id = ".$sql->db_param();
            $q_params[]=$team_id;
         }
         $result = $sql->sql_query($query, $q_params);

         while ($row = $sql->fetchObject($result)) {
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
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return TimeTrack[]
    */
   public function getTimeTracks($startTimestamp, $endTimestamp) {
      if(NULL == $this->timeTracksCache) {
         $this->timeTracksCache = array();
      }

      $key = $startTimestamp.'-'.$endTimestamp;

      if(!array_key_exists($key, $this->timeTracksCache)) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_timetracking_table " .
                  "WHERE date >= ".$sql->db_param().
                  " AND date <=  ".$sql->db_param().
                  " AND userid = ".$sql->db_param();
         $q_params[]=$startTimestamp;
         $q_params[]=$endTimestamp;
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);

         $timeTracks = array();
         while ($row = $sql->fetchObject($result)) {
            $timeTracks[] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
         }
         $this->timeTracksCache[$key] = $timeTracks;
      }
      return $this->timeTracksCache[$key];
   }

   /**
    * returns an array $daysOf[date] = $row->duration;
    * @param TimeTrack[] $timeTracks
    * @param int[] $issueIds
    * @return mixed[][]   array(date => array('duration','type','title'))
    */
   public function getDaysOfInPeriod($timeTracks, $issueIds, $teamid) {
      $daysOf = array();  // day => duration
      if(count($issueIds) > 0) {
         $issues = Issue::getIssues($issueIds);

         foreach ($timeTracks as $timeTrack) {
            try {
               $issue = $issues[$timeTrack->getIssueId()];

               if (NULL == $issue) {
                  self::$logger->error("getDaysOfInPeriod(): ".$timeTrack->getIssueId()." not found in ".implode(',', $issueIds));
                  $issue = IssueCache::getInstance()->getIssue($timeTrack->getIssueId());
               }

               if ($issue->isVacation($teamid)) {
                  if (isset($daysOf[$timeTrack->getDate()])) {
                     $daysOf[$timeTrack->getDate()]['duration'] += $timeTrack->getDuration();
                  } else {
                     $daysOf[$timeTrack->getDate()] = array( 'duration' => $timeTrack->getDuration(),
                        'type' => 'Inactivity',  // TODO
                        'color' => 'A8FFBD',  // TODO (light green)
                        'title' => $issue->getSummary()
                     );
                  }
                  #echo "DEBUG user $this->userid daysOf[".date("j", $timeTrack->date)."] = ".$daysOf[date("j", $timeTrack->date)]." (+$timeTrack->duration)<br/>";
               }
            } catch (Exception $e) {
               self::$logger->error("getDaysOfInPeriod(): user $this->id issue ".$timeTrack->getIssueId()." tt_date=".date("Y-m-d", $timeTrack->getDate()).": " . $e->getMessage());
               self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            }
         }
      }

      return $daysOf;
   }

   /**
    * @param TimeTrack[] $timeTracks
    * @param int[] $issueIds
    * @return mixed[]
    */
   public function getOnDutyTaskInMonth($teamid, $timeTracks, $issueIds) {
      $astreintes = array();  // day => duration
      if(count($issueIds) > 0) {
         $issues = Issue::getIssues($issueIds);

         foreach ($timeTracks as $timeTrack) {
            try {
               if (!array_key_exists($timeTrack->getIssueId(), $issues)) {
                  self::$logger->error("getAstreintesInMonth(): issue ".$timeTrack->getIssueId().": not found in issueList");
                  continue;
               }

               $issue = $issues[$timeTrack->getIssueId()];

               if ($issue->isOnDutyTask($teamid)) {
                  if (isset($astreintes[$timeTrack->getDate()])) {
                     $astreintes[$timeTrack->getDate()]['duration'] += $timeTrack->getDuration();
                  } else {
                     $astreintes[$timeTrack->getDate()] = array(
                        'duration' => $timeTrack->getDuration(),
                        'type' => 'onDuty', // TODO
                        'color' => 'F8FFA8', // TODO (yellow)
                        'title' => $issue->getSummary()
                     );
                  }
                  //echo "DEBUG user $this->userid astreintes[".date("j", $timeTrack->date)."] = ".$astreintes[date("j", $timeTrack->date)]." (+$timeTrack->duration)<br/>";
               }
            } catch (Exception $e) {
               self::$logger->error("getAstreintesInMonth(): issue ".$timeTrack->getIssueId().": " . $e->getMessage());
            }
         }
      }

      return $astreintes;
   }

   /**
    * concat durations of all ExternalTasksProject issues.
    * @param TimeTrack[] $timeTracks
    * @param int[] $issueIds
    * @return mixed[] $extTasks[timestamp] = $row->duration;
    */
   public function getExternalTasksInPeriod(array $timeTracks, array $issueIds) {
      $extTasks = array();  // timestamp => duration
      if(count($issueIds) > 0) {
         $issues = Issue::getIssues($issueIds);

         $extTasksProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
         $extTasksCatLeave = Config::getInstance()->getValue(Config::id_externalTasksCat_leave);
         #echo "extTasksCatLeave $extTasksCatLeave<br>";

         foreach ($timeTracks as $timeTrack) {
            try {
               $issue = $issues[$timeTrack->getIssueId()];
               if (is_null($issue)) {
                  self::$logger->error("getExternalTasksInPeriod(): issue ".$timeTrack->getIssueId().": not found in MantisDB");
                  continue;
               }
               if ($issue->getProjectId() == $extTasksProjId) {
                  if (isset($extTasks[$timeTrack->getDate()])) {
                     $extTasks[$timeTrack->getDate()]['duration'] += $timeTrack->getDuration();
                  } else {

                     if ($extTasksCatLeave == $issue->getCategoryId()) {
                        $color = 'A8FFBD';  // TODO (light green)
                        $type = 'Inactivity';
                     } else {
                        $color = '75FFDA';  // TODO (green2)
                        $type = 'ExternalTask';
                     }

                     $extTasks[$timeTrack->getDate()] = array(
                        'duration' => $timeTrack->getDuration(),
                        'type' => $type,  // TODO
                        'color' => $color,  // TODO (green2)
                        'title' => $issue->getSummary()
                     );
                  }
               }
            } catch (Exception $e) {
               self::$logger->warn("getExternalTasksInPeriod: " . $e->getMessage());
            }
         }
      }
      return $extTasks;
   }

   /**
    * Nb working days in the period (no holidays, no external tasks)
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param int $team_id
    * @return int
    */
   public function getAvailableWorkforce($startTimestamp, $endTimestamp, $team_id) {
      $holidays = Holidays::getInstance();

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

      if ($departureDate < $startTimestamp) {
         return 0;
      }

      // restrict timestamp to the period where the user is working on the project
      $startT = ($arrivalDate > $startTimestamp) ? $arrivalDate : $startTimestamp;
      $endT = ($departureDate < $endTimestamp) ? $departureDate : $endTimestamp;

      $timeTracks = $this->getTimeTracks($startT, $endT);
      $issueIds = array();
      foreach ($timeTracks as $timeTrack) {
         $issueIds[] = $timeTrack->getIssueId();
      }
      $daysOf   = $this->getDaysOfInPeriod($timeTracks, $issueIds, $team_id);
      $extTasks = $this->getExternalTasksInPeriod($timeTracks, $issueIds);

      $prodDaysForecast = 0;
      for ($i = $startT; $i <= $endT; $i=strtotime("+1 day",$i)) {

         // workforce = 1
         // remove external task
         // remove dayOf
         // if (workforce == 1) && isHoliday then day=0

         $dayWorkforce = 1;
         if (isset($extTasks[$i])) { $dayWorkforce -= $extTasks[$i]["duration"]; }
         if (isset($daysOf[$i])) { $dayWorkforce -= $daysOf[$i]["duration"]; }
         if ((1 == $dayWorkforce) && (NULL != $holidays->isHoliday($i))) {
            $dayWorkforce = 0;
         }
         $prodDaysForecast += $dayWorkforce;
      }
      return $prodDaysForecast;
   }

   /**
    * Nb days spent on tasks in the period (no holidays, no external tasks)
    *
    * Note: including non-inactivity sideTasks (cat_management, cat_tools, cat_workshop)
    *
    * (consommé sur la periode)
    *
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param int $team_id
    * @return number[] : array[bug_id] = duration
    */
   public function getWorkloadPerTask($startTimestamp, $endTimestamp, $team_id = NULL) {
      $workloadPerTaskList = array();

      $timeTracks = $this->getTimeTracks($startTimestamp, $endTimestamp);

      $team = TeamCache::getInstance()->getTeam($team_id);
      $projectList = $team->getProjects();

      foreach($timeTracks as $timetrack) {
         // exclude projects not in team list
         // exclude externalTasks & NoStatsProjects
         if (NULL != $projectList) {
            if (!array_key_exists($timetrack->getProjectId(), $projectList)) {
               continue;
            }
         }

         // exclude Inactivity tasks
         if ($team->isSideTasksProject($timetrack->getProjectId()))
            $workloadPerTaskList[$timetrack->getIssueId()] += $timetrack->getDuration();
      }

      return $workloadPerTaskList;
   }

   /**
    * @return string[] the teams i'm administrator of.
    */
   public function getAdministratedTeamList($withDisabled=false) {
      if(NULL == $this->leadedTeams) {
         $this->leadedTeams = array();
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT DISTINCT id, name FROM codev_team_table WHERE ".
                  "administrators = ".$this->id." OR ".           // 333
                  "administrators LIKE '%,".$this->id."' OR ".    // %,333
                  "administrators LIKE '%,".$this->id.",%' OR ".  // %,333,%
                  "administrators LIKE '".$this->id.",%'";        // 333,%

         if (!$withDisabled) {
            $query .= " AND enabled = 1 ";
         }
         $query .= " ORDER BY name";

         $result = $sql->sql_query($query);

         while ($row = $sql->fetchObject($result)) {
            $this->leadedTeams[$row->id] = $row->name;
            #echo "getAdministratedTeamList FOUND $row->id - $row->name<br/>";
         }
      }
      return $this->leadedTeams;
   }

   /**
    * @return string[] the teams i'm member of.
    */
   public function getDevTeamList() {
      if(NULL == $this->devTeamList) {
         $this->devTeamList = $this->getTeamList(Team::accessLevel_dev);
      }
      return $this->devTeamList;
   }

   /**
    * @return string[] the teams i'm observer of.
    */
   public function getObservedTeamList() {
      if(NULL == $this->observedTeamList) {
         $this->observedTeamList = $this->getTeamList(Team::accessLevel_observer);
      }
      return $this->observedTeamList;
   }

   /**
    * @return string[] the teams i'm Manager of.
    */
   public function getManagedTeamList() {
      if(NULL == $this->managedTeamList) {
         $this->managedTeamList = $this->getTeamList(Team::accessLevel_manager);
      }
      return $this->managedTeamList;
   }

   /**
    * @return string[] the teams i'm Manager of.
    */
   public function getCustomerTeamList() {
      if(NULL == $this->customerTeamList) {
         $this->customerTeamList = $this->getTeamList(Team::accessLevel_customer);
      }
      return $this->customerTeamList;
   }

   /**
    * returns teams, the user is involved in.
    * @param int $accessLevel if NULL return all teams including observed teams.
    * @param type $withDisabled teams disabled in mantis
    * @param type $withVacatedTeams teams where I have a departure date <= today
    * @return type
    */
   public function getTeamList($accessLevel = NULL, $withDisabled=false, $withVacatedTeams=false) {
      $teamList = array();

      if($accessLevel == NULL && $this->allTeamList != NULL) {
         return $this->allTeamList;
      }
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT team.id, team.name " .
               "FROM codev_team_table as team " .
               "JOIN codev_team_user_table as team_user ON team.id = team_user.team_id ".
               "WHERE   team_user.user_id = " . $sql->db_param();
      $query_params = array($this->id);

      if (!is_null($accessLevel)) {
         $query .= " AND team_user.access_level = ". $sql->db_param();
         $query_params[] = $accessLevel;
      }
      if (!$withDisabled) {
         $query .= " AND team.enabled = 1 ";
      }

      if (!$withVacatedTeams) {
         $now = time();
         $midnightTimestamp = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
         $query .= ' AND (team_user.departure_date >= '.$sql->db_param().' OR team_user.departure_date = 0) ';
         $query_params[] = $midnightTimestamp;
      }

      $query .= " ORDER BY team.name;";
      $result = $sql->sql_query($query, $query_params);

      while ($row = $sql->fetchObject($result)) {
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
    * @param string[] $teamList       if NULL then return projects from all the teams the user belongs to
    * @param bool $noStatsProject if false, the noStatsProject will not be returned in the list
    *
    * @return string[] : array [id => project_name]
    */
   public function getProjectList(array $teamList = NULL, $noStatsProject = true, $withDisabledProjects = true) {
      $projList = array();

      if (NULL == $teamList) {
         // if not specified, get projects from the teams I'm member of.
         $teamList = $this->getTeamList();
      }
      if (0 != count($teamList)) {
         $sql = AdodbWrapper::getInstance();
         $formatedTeamList = implode(', ', array_keys($teamList));
         $query = "SELECT DISTINCT project.id, project.name " .
                  "FROM {project} as project " .
                  "JOIN codev_team_project_table as team_project ON project.id = team_project.project_id ".
                  "WHERE team_project.team_id IN (".$formatedTeamList.") ";

         if (!$noStatsProject) {
            $query .= " AND team_project.type <> " . $sql->db_param();
            $q_params[]=Project::type_noStatsProject;
         }
         if (!$withDisabledProjects) {
            $query .= " AND project.enabled = 1 ";
         }

         $query .= " ORDER BY project.name;";
         $result = $sql->sql_query($query, $q_params);

         while ($row = $sql->fetchObject($result)) {
            $projList[$row->id] = $row->name;
         }
      } 

      return $projList;
   }

   /**
    * sum the RAF (or mgrEffortEstim if no RAF defined) of all the opened Issues assigned to me.
    * @param string[] $projList
    * @return int
    */
   public function getForecastWorkload(array $projList = NULL) {
      $totalBacklog = 0;

      if (NULL == $projList) {
         $managedTeamList = $this->getManagedTeamList();
         $devTeamList = $this->getDevTeamList();
         $custoTeamList = $this->getDevTeamList();
         $teamList = $devTeamList + $managedTeamList + $custoTeamList;

         $projList = $this->getProjectList($teamList);
      }

      if (0 == count($projList)) {
         // this happens if User is not a Developper (Manager or Observer)
         //echo "<div style='color:red'>ERROR: no project associated to this team !</div><br>";
         return $totalBacklog;
      }

      $sql = AdodbWrapper::getInstance();
      $formatedProjList = implode(', ', array_keys($projList));

      // find all issues i'm working on
      $query = "SELECT * FROM {bug} " .
               " WHERE project_id IN (".$formatedProjList. ") " .
               " AND handler_id =  ".$sql->db_param() .
               " AND status < get_project_resolved_status_threshold(project_id) " .
               " ORDER BY id DESC;";
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params);

      while ($row = $sql->fetchObject($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $totalBacklog += (float) $issue->getDuration();
      }
      return $totalBacklog;
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
    * @return Issue[] : array[bugId] = Issue
    */
   public function getAssignedIssues(array $projList = NULL, $withResolved = false) {
      $issueList = array();

      if (NULL == $projList) {
         // get all teams except those where i'm Observer
         $dTeamList = $this->getDevTeamList();
         $mTeamList = $this->getManagedTeamList();
         $cTeamList = $this->getCustomerTeamList();
         $teamList = $dTeamList + $mTeamList + $cTeamList;   // array_merge does not work ?!
         $projList = $this->getProjectList($teamList);
      }

      if (0 == count($projList)) {
         self::$logger->warn("getAssignedIssues: no projects defined for user $this->id (" . $this->getRealname() . ")");
         return $issueList;
      }

      $formatedProjList = implode(', ', array_keys($projList));
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT * FROM {bug} " .
               "WHERE project_id IN (".$formatedProjList . ") " .
               " AND handler_id = ".$sql->db_param();
      $q_params[]=$this->id;

      if (!$withResolved) {
         $query .= " AND status < get_project_resolved_status_threshold(project_id) ";
      }
      $query .= " ORDER BY id DESC;";

      $result = $sql->sql_query($query, $q_params);

      while ($row = $sql->fetchObject($result)) {
         $issueList[] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      // quickSort the list
      Tools::usort($issueList);
      return $issueList;
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
    * @return Issue[]
    */
   public function getMonitoredIssues() {
      if(NULL == $this->monitoredIssues) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT DISTINCT bug.* " .
                  "FROM {bug} as bug ".
                  "JOIN {bug_monitor} as monitor ON bug.id = monitor.bug_id " .
                  "WHERE monitor.user_id = ".$sql->db_param() .
                  " ORDER BY bug.id DESC;";
         $q_params[]=$this->id;

         $result = $sql->sql_query($query, $q_params);

         $this->monitoredIssues = array();
         while ($row = $sql->fetchObject($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->id, $row);
            if ($issue->getCurrentStatus() < $issue->getBugResolvedStatusThreshold()) {
               $this->monitoredIssues[] = $issue;
            }
         }
         // quickSort the list
         Tools::usort($this->monitoredIssues);
      }
      return $this->monitoredIssues;
   }

   /**
    * check Timetracks & fixed holidays
    * and returns how many time is available for work on this day.
    * @param int $timestamp
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
      $sql = AdodbWrapper::getInstance();

      // now check for Timetracks, the time left is free time to work
      $query = "SELECT SUM(duration) FROM codev_timetracking_table " .
               "WHERE userid = ".$sql->db_param() .
               " AND date = ".$sql->db_param();
      $q_params[]=$this->id;
      $q_params[]=$timestamp;
      $result = $sql->sql_query($query, $q_params);

      $sum = round($sql->sql_result($result), 2);
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
    * type = keyValue  "onlyAssignedTo:0,hideResolved:1,hideForbidenStatus:0"
    *
    * @param string $filterName 'onlyAssignedTo'
    * @return unknown_type returns filterValue
    */
   function getTimetrackingFilter($filterName) {
      if ((NULL == $this->timetrackingFilters) ||
         ('' == $this->timetrackingFilters)) {

         $this->timetrackingFilters = Config::getValue(Config::id_timetrackingFilters, array($this->id, 0, 0, 0, 0, 0), true);
         if ($this->timetrackingFilters == NULL) {
         	$this->timetrackingFilters = Tools::doubleExplode(':', ',', Config::default_timetrackingFilters);
         }
      }
      if (!array_key_exists($filterName, $this->timetrackingFilters)) {
         // this is a new key
         $defaultFilters = Tools::doubleExplode(':', ',', Config::default_timetrackingFilters);
         $this->timetrackingFilters[$filterName] = $defaultFilters[$filterName];
      }
      // get value
      $value = $this->timetrackingFilters[$filterName];
      //self::$logger->error("user $this->id timeTrackingFilter $filterName = <$value>");

      return $value;
   }

   /**
    * @param string $filterName
    * @param string $value
    */
   function setTimetrackingFilter($filterName, $value) {
      // init timetrackingFilters
      if ((NULL == $this->timetrackingFilters) ||
         ('' == $this->timetrackingFilters)) {
         $this->getTimetrackingFilter('onlyAssignedTo');
      }
      $this->timetrackingFilters[$filterName] = $value;
      $keyvalue = Tools::doubleImplode(':', ',', $this->timetrackingFilters);

      // save new settings
      Config::setValue(Config::id_timetrackingFilters, $keyvalue, Config::configType_keyValue, "filter for timetracking page", 0, $this->id);
   }

   /**
    * set the Team to set on login
    * @param int $teamid
    */
   public function setDefaultTeam($teamid) {

      // save new settings
      Config::setValue(Config::id_defaultTeamId, $teamid, Config::configType_int, "prefered team on login", 0, $this->id);

      $this->defaultTeam = $teamid;
   }

   /**
    * get the default team on login
    *
    * @return string
    */
   public function getDefaultTeam() {
      if (NULL == $this->defaultTeam) {
         $this->defaultTeam = Config::getValue(Config::id_defaultTeamId, array($this->id, 0, 0, 0, 0, 0), true);
         if ($this->defaultTeam == NULL) {
         	$this->defaultTeam = 0;
         }

         $now = time();
         $midnightTimestamp = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));

         if ((0 != $this->defaultTeam) &&
            (!$this->isTeamMember($this->defaultTeam, NULL, $midnightTimestamp, $midnightTimestamp))) {
            // SECURITY CHECK: User used to belong to a team (config is still in DB) but he no longer belongs to it !
            self::$logger->error("user $this->id no longer belong to team $this->defaultTeam. defaultTeam is now set to 0");
            $this->defaultTeam = 0;
            $this->setDefaultTeam(0);
         }
      }
      return $this->defaultTeam;
   }

   /**
    * set the Team to set on login
    * @param int $teamid
    */
   public function setDefaultProject($projectid) {

      // save new settings
      Config::setValue(Config::id_defaultProjectId, $projectid, Config::configType_int, "prefered project on login", 0, $this->id);

      $this->defaultProject = $projectid;
   }

   /**
    * get the default team on login
    *
    * @return string
    */
   public function getDefaultProject() {
      if (NULL == $this->defaultProject) {
         $this->defaultProject = Config::getValue(Config::id_defaultProjectId, array($this->id, 0, 0, 0, 0, 0), true);
         if ($this->defaultProject == NULL) {
         	$this->defaultProject = 0;
         }

      }
      return $this->defaultProject;
   }

   /**
    * set the Command State filters
    * used to display less commands in the cmd selection combobox
    *
    * @param string $filterStr (key:value,key2:value2)
    * @param int $teamid
    */
   public function setCmdStateFilters($filterStr, $teamid=0) {
      if (is_null($this->cmdStateFiltersCache)) {
         $this->cmdStateFiltersCache = array();
      }
      if (array_key_exists($teamid, $this->cmdStateFiltersCache)) {
         $prevFilters = $this->cmdStateFiltersCache["$teamid"];
      } else {
         $prevFilters = NULL;
      }

      // Note: check type with !== is mandatory
      if ($filterStr !== $prevFilters) {
         // stored as configType_string because we need a string at extraction (perf)
         Config::setValue(Config::id_cmdStateFilters, $filterStr, Config::configType_string, NULL, 0, $this->id, $teamid);
      }
      $this->cmdStateFiltersCache["$teamid"] = $filterStr;
   }

   /**
    * get the Command State filters
    * used to display less commands in the cmd selection combobox
    *
    * @return string or "" if not found
    */
   public function getCmdStateFilters($teamid=0) {

      if (is_null($this->cmdStateFiltersCache)) {
         $this->cmdStateFiltersCache = array();
      }

      if (!array_key_exists($teamid, $this->cmdStateFiltersCache)) {
         $filters = Config::getValue(Config::id_cmdStateFilters, array($this->id, 0, $teamid, 0, 0, 0), true);
         if ($filters == NULL) {
            $filters = "";
         }
         $this->cmdStateFiltersCache["$teamid"] = $filters;
      }
      return $filters;
   }

   /**
    * set the Command State filters
    * used to display less commands in the cmd selection combobox
    *
    * @param string $key
    * @param any $value
    * @param int $teamid
    */
   public function setIssueInfoFilter($key, $value, $teamid=0) {

      $filters = $this->getIssueInfoFilters($teamid);
      $filters[$key] = $value;

      $filterStr = json_encode($filters);
      Config::setValue(Config::id_issueInfoFilters, $filterStr, Config::configType_string, NULL, 0, $this->id, $teamid);
   }


   /**
    * get the Filters used to display the issue_info.php page
    * used to display less issues in the issue combobox
    *
    * @return array
    */
   public function getIssueInfoFilters($teamid=0) {

      // default filter values
      $filters = array(
         //'isOnlyAssignedToMe' => false,
         //'isHideResolved' => false,
         //'isHideClosed' => true,
         'isHideObservedTeams' => true,
      );
      // override with user settings if exist
      $filterStr = Config::getValue(Config::id_issueInfoFilters, array($this->id, 0, $teamid, 0, 0, 0), true);
      if (NULL != $filterStr) {
         $curFilters = json_decode($filterStr);
         foreach ($curFilters as $key => $value) {
            $filters[$key] = $value;
         }
      }
      return $filters;
   }

   /**
    * set the language to set on login
    * @param int $lang
    */
   public function setDefaultLanguage($lang) {

      // save new settings
      Config::setValue(Config::id_defaultLanguage, $lang, Config::configType_int, "prefered language on login", 0, $this->id);

      $this->defaultLanguage = $lang;
   }

   /**
    * get the default llanguage on login (or NULL if not found in DB)
    *
    * @return string
    */
   public function getDefaultLanguage() {
      if (NULL == $this->defaultLanguage) {
         $this->defaultLanguage = Config::getValue(Config::id_defaultLanguage, array($this->id, 0, 0, 0, 0, 0), true);
      }
      return $this->defaultLanguage;
   }


   /**
    * Get users
    * @return string[] : username[id] => name
    */
   public static function getUsers($userRealName=FALSE) {
      //if(NULL == self::$users) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT id, username, realname FROM {user} ";
         if ($userRealName) {
            $query .= " ORDER BY realname";
         } else {
            $query .= " ORDER BY username";
         }
         try {
            $result = $sql->sql_query($query);
         } catch (Exception $e) {
            return NULL;
         }

         self::$users = array();
         while ($row = $sql->fetchObject($result)) {
            if ($userRealName && !empty($row->realname)) {
               self::$users[$row->id] = $row->realname;
            } else {
               self::$users[$row->id] = $row->username;
            }
         }
      //}
      return self::$users;
   }

   /**
    * Set project access_level for the given (private) project
    *
    * manager = 70
    *
    *
    * @param int $project_id
    * @param int $access_level
    */
   public function setProjectAccessLevel($project_id, $access_level) {
      $sql = AdodbWrapper::getInstance();

      // check if access rights already defined
      $query = "SELECT access_level FROM {project_user_list} ".
               " WHERE user_id = ".$sql->db_param().
               " AND project_id = ".$sql->db_param();
      $q_params[]=$this->id;
      $q_params[]=$project_id;

      $result = $sql->sql_query($query, $q_params);
      if (0 == $sql->getNumRows($result)) {

         $query2 = "INSERT INTO {project_user_list} (user_id, project_id, access_level) ".
          ' VALUES ( ' . $sql->db_param() . ','
              . $sql->db_param() . ','
              . $sql->db_param() . ')';

         $q_params2[]=$this->id;
         $q_params2[]=$project_id;
         $q_params2[]=$access_level;
      } else {
         $query2 = "UPDATE {project_user_list} ".
                   " SET access_level = ".$sql->db_param() .
                   " WHERE user_id = ".$sql->db_param()
                 . " AND project_id = ".$sql->db_param();
         $q_params2[]=$access_level;
         $q_params2[]=$this->id;
         $q_params2[]=$project_id;
      }
      $sql->sql_query($query2, $q_params2);
   }

   /**
    * @return int
    */
   public function getId() {
      return $this->id;
   }


   /**
    * return the $limit most recently used issues
    *
    * @param type $limit
    * @return array[$bugid]
    */
   public function getRecentlyUsedIssues($limit = 5, $bugidList = NULL) {

      $now = time();
      $sql = AdodbWrapper::getInstance();

      $query = 'SELECT DISTINCT bugid FROM codev_timetracking_table '.
              " WHERE userid = ".$sql->db_param() .
              " AND date <=  ".$sql->db_param();
      $q_params[]=$this->id;
      $q_params[]=$now;

      if ((NULL != $bugidList) && (count($bugidList) > 0)) {
         $formattedList = implode(", ",$bugidList);
         $query .= " AND bugid IN (".$formattedList.") ";
      }

      $query .= " ORDER BY date DESC";

      $result = $sql->sql_query($query, $q_params, TRUE, $limit);

      $recentIssues = array();
      while ($row = $sql->fetchObject($result)) {
         $recentIssues[] = $row->bugid;
      }
      return $recentIssues;
   }

   /**
    * Returns an array of (date => duration) containing all days where duration != 1
    *
    * Note: this is a replacement for Timetracking::checkIncompleteDays()
    *
    * @param int $userid
    * @param bool $isStrictlyTimestamp
    * @return number[]
    */
   public function checkIncompleteDays($startTimestamp = NULL, $endTimestamp = NULL) {
      $sql = AdodbWrapper::getInstance();

      // Get all dates that must be checked
      $query = "SELECT date, SUM(duration) as count FROM codev_timetracking_table ".
               "WHERE userid = ".$sql->db_param();
      $q_params[]= $this->id;
      if (NULL != $startTimestamp) {
         $query .= " AND date >=  ".$sql->db_param();
         $q_params[]=$startTimestamp;
      }
      if (NULL != $endTimestamp) {
         $query .= " AND date <  ".$sql->db_param();
         $q_params[]=$endTimestamp;
      }
      $query .= " GROUP BY date ORDER BY date;";

      $result = $sql->sql_query($query, $q_params);

      $incompleteDays = array(); // unique date => sum durations
      while($row = $sql->fetchObject($result)) {
         $value = round($row->count, 3);
         if ($value != 1) {
            $incompleteDays[$row->date] = $value;
         }
      }

      return $incompleteDays;
   }

   /**
    * Find days which are not 'sat' or 'sun' or FixedHoliday and that have no timeTrack entry.
    *
    * Note: this is a replacement for Timetracking::checkMissingDays()
    *
    * @param int $userid
    * @return number[]
    */
   public function checkMissingDays($team_id, $startTimestamp = NULL, $endTimestamp = NULL) {
      $holidays = Holidays::getInstance();
      $missingDays = array();

      if (NULL == $endTimestamp) {
         $endTimestamp= mktime(0, 0, 0, date("m"), date("d"), date("Y"));
      }

      if ((!$this->isTeamDeveloper($team_id, $startTimestamp, $endTimestamp)) &&
         (!$this->isTeamManager($team_id, $startTimestamp, $endTimestamp))) {
         // User was not yet present
         return $missingDays;
      }
      $arrivalTimestamp = $this->getArrivalDate($team_id);
      $departureTimestamp = $this->getDepartureDate($team_id);

      // reduce timestamp if needed
      $startT = ($arrivalTimestamp > $startTimestamp) ? $arrivalTimestamp : $startTimestamp;

      $endT = $endTimestamp;
      if ((0 != $departureTimestamp) && ($departureTimestamp < $endTimestamp)) {
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
                  " WHERE userid = ".$sql->db_param() .
                  " AND date IN (".implode(', ', $weekTimestamps).")";
         $q_params[]=$this->id;

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
    * send an email with the list of all incomplete/missing days in the period
    */
   public function sendTimesheetEmail($team_id = NULL, $startTimestamp=NULL, $endTimestamp=NULL) {

      $emailAddress = $this->getEmail();
      if (NULL == $emailAddress) {
         self::$logger->error("sendTimetrackEmails: user $this->id (".$this->getRealname().") has no email address.");
         return FALSE;
      }

      // send email in user's language
      $locale = $this->getDefaultLanguage();
      if (NULL != $locale) { $_SESSION['locale'] = $locale; }


      if (NULL != $team_id) {
         $team = TeamCache::getInstance()->getTeam($team_id);
         $teamString = ' '.T_('for team').' '.$team->getName();
      }
      $emailSubject=T_('[CodevTT] Timesheet reminder !');

      $emailBody=T_('Dear ').$this->getRealname().",\n\n".
         T_('Please fill your CodevTT timesheet').$teamString." :\n\n".
         Constants::$codevURL."\n\n";

      try {
         $incompleteDays = $this->checkIncompleteDays($startTimestamp, $endTimestamp);
         $missingDays = $this->checkMissingDays($team_id, $startTimestamp, $endTimestamp);

         $nbDays = count($incompleteDays) + count($missingDays);
         echo "User $this->id \t".$this->getName()."    ($nbDays days)\n";
         if ($nbDays > 0) {
            foreach($incompleteDays as $date => $duration) {
               $emailBody .= date("Y-m-d", $date).' '.T_("incomplete (missing ").(1 - $duration).' '.T_('day').")\n";
            }
            foreach($missingDays as $date) {
               $emailBody .= date("Y-m-d", $date).' '.T_("not defined.")."\n";
            }

            #self::$logger->debug($emailBody);

            #$now = time();
            #$emailDate = mktime(0, 0, 0, date('m', $now), date('d',$now), date('Y', $now));
            #SELECT count(*) FROM mantis_email_table WHERE subject LIKE '%CodevTT%' AND email = '$emailAddress' AND submitted= '$emailDate'

            Email::getInstance()->sendEmail( $emailAddress, $emailSubject, $emailBody );
         }
      } catch (Exception $e) {
         self::$logger->error("sendTimetrackEmails: Could not send email to user $this->id (".$this->getRealname().") team $team_id");
         self::$logger->error("sendTimetrackEmails: ".$e->getTraceAsString());
         return FALSE;
      }
      return TRUE;
   }

   /**
    *
    * @param int $team_id
    * @param array $planningOptions as key:value
    */
   public function setPlanningOptions($team_id, $planningOptions) {
      $this->planningOptions = $planningOptions;

      $keyvalue = Tools::doubleImplode(':', ',', $this->planningOptions);

      // save new settings
      Config::setValue(Config::id_planningOptions, $keyvalue, Config::configType_keyValue, NULL, 0, $this->id, $team_id );
   }

   /**
    *
    * @return array ('optionName' => [0,1] isEnabled)
    */
   public function getPlanningOptions($team_id) {

      if (empty($this->planningOptions)) {

         $checkList = Config::getValue(Config::id_planningOptions, array($this->id, 0, $team_id, 0, 0, 0), true);

         // get default checkList if not found
         $this->planningOptions = User::$defaultPlanningOptions;

         // update with user specific items
         if ($checkList != NULL && is_array($checkList)) {
            foreach ($checkList as $name => $enabled) {

               if (!array_key_exists($name, $this->planningOptions)) {
                  self::$logger->warn("user $this->id team $team_id: remove unknown/deprecated planningOption: $name");
               } else {
                  $this->planningOptions["$name"] = $enabled;
               }
            }
         }
      }

      return $this->planningOptions;
   }

   public function getPlanningOption($team_id, $optionKey) {
      $options = $this->getPlanningOptions($team_id);
      return $options["$optionKey"];
   }

   /**
    * Create user in Mantis database
    * @param string $username
    * @param string $realName
    * @param string $email
    * @param integer $mantisAccessLevel
    * @param timestamp $entryDate
    * @throws Exception
    */
    public static function createUserInMantisDB($username, $realName, $email, $password, $mantisAccessLevel = 25, $entryDate = null)
    {
        if($entryDate == null)
        {
            $entryDate = time();
        }

        if(null != $username && null != $realName && null != $email)
        {
            if(self::exists($username))
            {
                throw new Exception("User already exist : ".$username);
            }
            else
            {
               $sql = AdodbWrapper::getInstance();
                $crypto = new Crypto();
                $cookieString = $crypto->auth_generate_unique_cookie_string();
                $lastVisit = time();
                $cryptedPassword = $crypto->auth_process_plain_password($password);
                // Insert user in mantis user table
                $query = "INSERT INTO {user} (username, realname, email, password, enabled, access_level, cookie_string, last_visit, date_created) "
                        . ' VALUES ( ' . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ')';

                $q_params[]=$username;
                $q_params[]=$realName;
                $q_params[]=$email;
                $q_params[]=$cryptedPassword;
                $q_params[]=1; // enabled
                $q_params[]=$mantisAccessLevel;
                $q_params[]=$cookieString;
                $q_params[]=$lastVisit;
                $q_params[]=$entryDate;
                $sql->sql_query($query, $q_params);
            }
        }
    }

    /**
     * Affect user to a project according project access level
     * @param integer $projectId
     * @param integer $projectAccessLevelId : default = 55 (developer)
     * @return boolean : true if user has been affected to project, false if he was already affected
     */
    public function affectToProject($projectId, $projectAccessLevelId = 55)
    {
        $project = new Project($projectId);

        if(!$project->hasMember($this->id))
        {
           $sql = AdodbWrapper::getInstance();
            $query = "INSERT INTO {project_user_list} (project_id, user_id, access_level)"
               . ' VALUES ( ' . $sql->db_param() . ','
                              . $sql->db_param() . ','
                              . $sql->db_param() . ')';

            $q_params[]=$projectId;
            $q_params[]=$this->id;
            $q_params[]=$projectAccessLevelId;
            $sql->sql_query($query, $q_params);
            return true;
        }
        return false;
    }

   /**
    * returns an IssueSelection of all the tasks on which the user has worked on (timetracks found)
    * @param type $teamId
    * @param type $startTimestamp
    * @param type $endTimestamp
    * @param type $noStatsProject
    * @param type $withDisabled
    * @param type $sideTasksProjects
    * @return \IssueSelection
    */
   public function getInvolvedTasks($teamId, $startTimestamp, $endTimestamp, $noStatsProject = true, $withDisabled = true, $sideTasksProjects=true) {

      $team = TeamCache::getInstance()->getTeam($teamId);
      $projectList = $team->getProjects($noStatsProject, $withDisabled, $sideTasksProjects);
      $formatedProjects = implode( ', ', array_keys($projectList));

      // timetracks in period on team issues
      // on which user was involved (timetracks found or assignedTo)
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT DISTINCT tt.bugid ".
               " FROM codev_timetracking_table AS tt".
               " JOIN {bug} as bug ON bug.id = tt.bugid ".
               " WHERE tt.userid = ".$sql->db_param().
               " AND tt.date >= ".$sql->db_param().
               " AND tt.date <= ".$sql->db_param().
               " AND bug.project_id IN (".$formatedProjects.")";
      $q_params[]=$this->id;
      $q_params[]=$startTimestamp;
      $q_params[]=$endTimestamp;
      $result = $sql->sql_query($query, $q_params);

      $userIssueSelection = new IssueSelection('User'.$this->session_userid.'ISel');
      while($row = $sql->fetchObject($result)) {
         $userIssueSelection->addIssue($row->bugid);
      }
      return $userIssueSelection;
   }



}

// Initialize complex static variables
User::staticInit();


