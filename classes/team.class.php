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

class Team extends Model {

   /**
    * @var Logger The logger
    */
   private static $logger;

   public static $defaultGeneralPrefsList;
   public static $generalPrefsDescriptionList;
   
   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$defaultGeneralPrefsList = array(
          'forbidAddTimetracksOnClosed' => 1,
          'displayCalculatedBacklogInDialogbox' => 0,
          'recreditBacklogOnTimetrackDeletion' => 0,
          'useTrackNote' => 1,
          'isTrackNoteMandatory' => 0,
          'sendTimesheetEmailNotification' => 1,
         );

      self::$generalPrefsDescriptionList = array(
          'forbidAddTimetracksOnClosed'         => 'Forbid adding timetracks on closed issues',
          'displayCalculatedBacklogInDialogbox' => 'Display calculated backlog as default value in the updateBacklog dialogbox',
          'recreditBacklogOnTimetrackDeletion'  => 'Recredit task backlog on timetrack deletion',
          'useTrackNote'                        => 'Add a timetrack note input field in the updateBacklog dialogbox',
          'isTrackNoteMandatory'                => 'The timetrack note in the updateBacklog dialogbox is mandatory',
          'sendTimesheetEmailNotification'      => 'Send timesheet email reminder',
         );

   }

   // ---
   // il n'y a qu'un seul teamLeader
   // il peut y avoir plusieurs observer
   // il peut y avoir plusieurs manager
   // un observer ne peut imputer sur les taches de l'equipe, il a acces en lecture seule aux donnees
   // un customer ne peut imputer, il n'est pas considéré comme ressource, il sert a "stocker" des fiches
   const accessLevel_customer = 5; // in table codev_team_user_table
   const accessLevel_dev = 10; // in table codev_team_user_table
   const accessLevel_observer = 20; // in table codev_team_user_table
   const accessLevel_manager  = 30; // in table codev_team_user_table

   public static $accessLevelNames = array(
      self::accessLevel_dev      => "Developer", // can modify, can NOT view stats
      self::accessLevel_manager  => "Manager", // can modify, can view stats, can only work on sideTasksProjects, resource NOT in statistics
      self::accessLevel_observer => "Observer",  // can NOT modify, can view stats
      self::accessLevel_customer  => "Customer" // can NOT modify, can NOT view stats
      //self::accessLevel_teamleader => "TeamLeader",  // REM: NOT USED FOR NOW !! can modify, can view stats, can work on projects ? , included in stats ?
   );

   private $id;
   private $name;
   private $description;
   private $leader_id;
   private $date;
   private $average_daily_cost;
   private $currency;

   private $enabled;
   private $commands_enabled;
   private $lock_timetracks_date;

   private $projTypeList;
   private $commandList;
   private $commandSetList;
   private $serviceContractList;
   private $onDutyTaskList;


   private $consistencyCheckList;

   private $userDailyCostObj;

   /**
    * @var string[]
    */
   private $members;

   /**
    * int[][]
    */
   private $projectIdsCache;

   /**
    * @param int $teamid
    * @throws Exception
    */
   public function __construct($teamid) {
      if (0 == $teamid) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a Team with id=0 is not allowed.");
         self::$logger->error("EXCEPTION Team constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      if (-1 == $teamid) {
         $e = new Exception("Creating a Team with id=-1 is EVIL.");
         self::$logger->error("EXCEPTION Team constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }

      $this->id = $teamid;
      $this->initialize();
   }

   /**
    * Initialize with DB
    */
   public function initialize() {
      $sql = AdodbWrapper::getInstance();
      $query = 'SELECT * FROM codev_team_table WHERE id = '.$sql->db_param();
      $result = $sql->sql_query($query, array($this->id));

      if($sql->getNumRows($result) == 1) {
         $row = $sql->fetchObject($result);

         $this->name = $row->name;
         $this->description = $row->description;
         $this->leader_id = $row->leader_id;
         $this->enabled = (1 == $row->enabled);
         $this->commands_enabled = (1 == $row->commands_enabled);
         $this->date = $row->date;
         $this->currency = $row->currency;
         $this->lock_timetracks_date = $row->lock_timetracks_date;

         if (NULL !== $row->average_daily_cost) {
            $this->average_daily_cost = floatval($row->average_daily_cost) / 100; // (2 decimals)
         }

      } else {
         self::$logger->error('The team '.$this->id." doesn't exists");
      }
   }

   /**
    * STATIC insert new team in DB
    *
    * Team::create($name, $description, $leader_id, $date);
    *
    * @static
    * @param string $name
    * @param string $description
    * @param int $leader_id
    * @param int $date
    * @return int the team id or -1 if not found
    */
   public static function create($name, $description, $leader_id, $date) {
      // check if Team name exists !
      $teamid = self::getIdFromName($name);

      if ($teamid < 0) {
         // create team
         $sql = AdodbWrapper::getInstance();
         $query = "INSERT INTO codev_team_table  (name, description, leader_id, date)"
            . " VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().", ".$sql->db_param().")";
         $q_params[]=$name;
         $q_params[]=$description;
         $q_params[]=$leader_id;
         $q_params[]=$date;
         $sql->sql_query($query, $q_params);
         $teamid = AdodbWrapper::getInstance()->getInsertId();
      } else {
         echo "<span style='color:red'>ERROR: Team name '$name' already exists !</span>";
         $teamid = -1;
      }
      return $teamid;
   }

   /**
    * delete a team (and all it's ServiceContracts,CommandSets,Commands)
    * @static
    * @param int $teamidToDelete
    * @return bool
    */
   public static function delete($teamidToDelete) {
      try {
         $team = TeamCache::getInstance()->getTeam($teamidToDelete);

         $idlist = array_keys($team->getCommands());
         foreach ($idlist as $id) {
            Command::delete($id);
         }
         $idlist = array_keys($team->getCommandSetList());
         foreach ($idlist as $id) {
            CommandSet::delete($id);
         }
         $idlist = array_keys($team->getServiceContractList());
         foreach ($idlist as $id) {
            ServiceContract::delete($id);
         }

         $sql = AdodbWrapper::getInstance();
         $query = "DELETE FROM codev_team_project_table WHERE team_id = ".$sql->db_param();
         $sql->sql_query($query, array($teamidToDelete));

         $query = "DELETE FROM codev_team_user_table WHERE team_id = ".$sql->db_param();
         $sql->sql_query($query, array($teamidToDelete));

         $query = "DELETE FROM codev_team_table WHERE id = ".$sql->db_param();
         $sql->sql_query($query, array($teamidToDelete));
      } catch (Exception $e) {
         return false;
      }
      return true;
   }

   /**
    * @param string $name
    * @return int the team id or -1 if not found
    */
   public static function getIdFromName($name) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id FROM codev_team_table WHERE name = ".$sql->db_param();
      $q_params[]=$name;
      $result = $sql->sql_query($query, $q_params);
      return (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : (-1);
   }

   /**
    * @return int
    */
   public function getLeaderId() {
      return $this->leader_id;
   }

   /**
    * @return bool isEnabled
    */
   public function isEnabled() {
      return $this->enabled;
   }

   /**
    * @param bool isEnabled
    */
   public function setEnabled($isEnabled) {
      $this->enabled = $isEnabled;
      $val = $isEnabled ? '1' : '0';

      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_table SET enabled = ".$sql->db_param()
         . " WHERE id =".$sql->db_param();
      $q_params[]=$val;
      $q_params[]=$this->id;
      $sql->sql_query($query, $q_params);
      return true;
   }

   /**
    * @return bool isEnabled
    */
   public function isCommandsEnabled() {
      return $this->commands_enabled;
   }

   /**
    * @param bool isEnabled
    */
   public function setCommandsEnabled($isEnabled) {
      $this->commands_enabled = $isEnabled;
      $val = $isEnabled ? '1' : '0';

      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_table SET commands_enabled = ".$sql->db_param()
         . " WHERE id =".$sql->db_param();
      $q_params[]=$val;
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params);
      return true;
   }

   /**
    * add/change timetracks before this date is not allowed
    * @return int timestamp
    */
   public function getLockTimetracksDate() {
      return $this->lock_timetracks_date;
   }

   /**
    * add/change timetracks before this date is not allowed
    * @param int $timestamp
    */
   public function setLockTimetracksDate($timestamp) {
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_table SET lock_timetracks_date = ".$sql->db_param()
         . " WHERE id =".$sql->db_param();
      $q_params[]=$timestamp;
      $q_params[]=$this->id;
      $sql->sql_query($query, $q_params);
      $this->lock_timetracks_date = $timestamp;
   }

   /**
    * team ADR (default value if UDC not defined
    * @return float teamADR or NULL if undefined
    */
   public function getAverageDailyCost() {
      return $this->average_daily_cost;
   }

   /**
    * add/change the team ADC (default value if UDC not defined)
    * @param float $teamADC
    */
   public function setAverageDailyCost($teamADC) {

      // TODO check floatval errors !

      if ($teamADC !== $this->average_daily_cost) {
         // convert float to int (ex: 123.5 => 12350 (2 decimals)
         $newADR = round(floatval($teamADC), 2) * 100;

         $sql = AdodbWrapper::getInstance();
         $query = "UPDATE codev_team_table SET average_daily_cost = ".$sql->db_param()
            . " WHERE id =".$sql->db_param();
         $q_params[]=$newADR;
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);
         $this->average_daily_cost = $teamADC;
      }
   }

   /**

    * @return String currency (EUR, USD, ...)
    */
   public function getTeamCurrency() {
      return $this->currency;
   }

   /**
    * add/change the team ADR (default value if UDC not defined
    * @param float $teamADR
    */
   public function setTeamCurrency($teamCurrency) {

      // TODO check if currency is in codev_currencies_table !

      if ($teamCurrency !== $this->currency) {
         $sql = AdodbWrapper::getInstance();
         $query = "UPDATE codev_team_table SET currency = ".$sql->db_param()
            . " WHERE id =".$sql->db_param();
         $q_params[]=$teamCurrency;
         $q_params[]=$this->id;
         $sql->sql_query($query, $q_params);
         $this->currency = $teamCurrency;
      }
   }

   /**
    * returns an array of project id => name
    *
    * @param bool $noStatsProject
    * @return string[] : array[project_id] = project_name
    */
   public function getProjects($noStatsProject = true, $withDisabled = true, $sideTasksProjects=true) {

      if(NULL == $this->projectIdsCache) {
         $this->projectIdsCache = array();
      }

      $key = 'P';
      $key .= ($noStatsProject) ? '_WithNoStatsProject' : '_WithoutNoStatsProject';
      $key .= ($withDisabled) ? '_withDisabled' : '_NoDisabled';
      $key .= ($sideTasksProjects) ? '_withSTProjects' : '_NoSTProjects';


      if(!array_key_exists($key, $this->projectIdsCache)) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT project.id, project.name ".
                  "FROM {project} as project ".
                  "JOIN codev_team_project_table as team_project ON project.id = team_project.project_id ".
                  "WHERE team_project.team_id =  ".$sql->db_param();
         $q_params[]=$this->id;

         if (!$noStatsProject) {
            $query .= " AND team_project.type <> ".$sql->db_param();
            $q_params[]=Project::type_noStatsProject;
         }
         if (!$sideTasksProjects) {
            $query .= " AND team_project.type <> ".$sql->db_param();
            $q_params[]=Project::type_sideTaskProject;
         }
         if (!$withDisabled) {
            $query .= " AND project.enabled = 1 ";
         }
         $query .= " ORDER BY project.name";

         $result = $sql->sql_query($query, $q_params);

         $projList = array();
         while($row = $sql->fetchObject($result)) {
            $projList[$row->id] = $row->name;
         }
         #self::$logger->error("getProjects $key ".implode(',', array_keys($projList)));
         $this->projectIdsCache[$key] = $projList;
      }

      return $this->projectIdsCache[$key];
   }

   /**
    * returns an array of Project class instances
    *
    * @param bool $noStatsProject
    * @return Project[]
    */
   public function getTrueProjects($noStatsProject = true, $withDisabled = true) {
      if(NULL == $this->projectIdsCache) {
         $this->projectIdsCache = array();
      }

      $key = ''.$noStatsProject;

      if(!array_key_exists($key, $this->projectIdsCache)) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT project.* ".
                  " FROM {project} as project ".
                  " JOIN codev_team_project_table as team_project ON project.id = team_project.project_id ".
                  " WHERE team_project.team_id = ".$sql->db_param();
         $q_params[]=$this->id;

         if (!$noStatsProject) {
            $query .= " AND team_project.type <> ".$sql->db_param();
            $q_params[]=Project::type_noStatsProject;
         }
         if (!$withDisabled) {
            $query .= " AND project.enabled = 1 ";
         }
         $query .= " ORDER BY project.name";

         $result = $sql->sql_query($query, $q_params);

         $projList = array();
         while($row = $sql->fetchObject($result)) {
            ProjectCache::getInstance()->getProject($row->id, $row);
            $projList[$row->id] = $row->name;
         }
         $this->projectIdsCache[$key] = $projList;
      }

      $projects = array();
      foreach($this->projectIdsCache[$key] as $id => $name) {
         $projects[] = ProjectCache::getInstance()->getProject($id);
      }
      return $projects;
   }

   /**
    * @static
    * @param int $teamid
    * @return string[]
    * @deprecated Use TeamCache::getInstance()->getTeam($teamid)->getMembers()
    */
   public static function getMemberList($teamid) {
      return TeamCache::getInstance()->getTeam($teamid)->getMembers();
   }

   /**
    * @return string[]
    */
   public function getMembers() {
      if(NULL == $this->members) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT user.id, user.username ".
                  " FROM {user} as user ".
                  " JOIN codev_team_user_table as team_user ON user.id = team_user.user_id ".
                  " WHERE team_user.team_id= ".$sql->db_param().
                  " ORDER BY user.username";
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);

         $this->members = array();
         while($row = $sql->fetchObject($result)) {
            $this->members[$row->id] = $row->username;
         }
      }

      return $this->members;
   }

   /**
    * Get all users of a team
    * @return User[] The users (User[id])
    */
   public function getUsers() {
      if(NULL == $this->members) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT user.* ".
                  " FROM {user} as user ".
                  " JOIN codev_team_user_table as team_user ON user.id = team_user.user_id ".
                  " WHERE team_user.team_id =  ".$sql->db_param().
                  " ORDER BY user.username;";
         $q_params[]=$this->id;
         try {
            $result = $sql->sql_query($query, $q_params);
         } catch (Exception $e) {
            return NULL;
         }

         $this->members = array();
         while($row = $sql->fetchObject($result)) {
            UserCache::getInstance()->getUser($row->id, $row);
            $this->members[$row->id] = $row->username;
         }
      }

      $users = array();
      foreach($this->members as $id => $name) {
         $users[] = UserCache::getInstance()->getUser($id);
      }

      return $users;
   }

   /**
    * team members (exept Observers) working on this team at $startTimestamp
    * or during the period.
    *
    * @param int $startTimestamp date (if NULL, today)
    * @param int $endTimestamp date (if NULL, $startTimestamp)
    * @return string[]
    */
   public function getActiveMembers($startTimestamp=NULL, $endTimestamp=NULL, $userRealName=FALSE) {
      if (NULL == $startTimestamp) {
         // if $startTimestamp not defined, get current active members
         $startTimestamp = Tools::date2timestamp(date("Y-m-d", time()));
         $endTimestamp = $startTimestamp;
      } else {
         // if $endTimestamp not defined, get members active at $startTimestamp
         if (NULL == $endTimestamp) {
            $endTimestamp = $startTimestamp;
         }
      }

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT user.id, user.username, user.realname ".
               " FROM {user} as user ".
               " JOIN codev_team_user_table as team_user ON user.id = team_user.user_id ".
               " AND team_user.team_id= ".$sql->db_param().
               " AND team_user.access_level IN (".$sql->db_param().', '.$sql->db_param().') '.
               " AND team_user.arrival_date <=  ".$sql->db_param().
               " AND (team_user.departure_date = 0 OR team_user.departure_date >= ".$sql->db_param().") ";
      $query .= ($userRealName) ? " ORDER BY user.realname" : " ORDER BY user.username";
      $q_params[]=$this->id;
      $q_params[]=self::accessLevel_dev;
      $q_params[]=self::accessLevel_manager;
      $q_params[]=$endTimestamp;
      $q_params[]=$startTimestamp;

      $result = $sql->sql_query($query, $q_params);

      $mList = array();
      while($row = $sql->fetchObject($result)) {
         $mList[$row->id] = ($userRealName) ? $row->realname : $row->username;
      }

      return $mList;
   }

   /**
    * get all issues managed by the team's users on the team's projects.
    * @param bool $addUnassignedIssues if true, include issues on team's projects that are assigned to nobody
    * @return Issue[] : issueList
    */
   public function getTeamIssueList($addUnassignedIssues = false, $withDisabledProjects = true) {

      $issueList = array();

      $projectList = $this->getProjects(true, $withDisabledProjects);
      $memberList = $this->getMembers();

      $memberIdList = array_keys($memberList);

      if (0 == count($memberIdList)) {
         self::$logger->error("getTeamIssues(teamid=$this->id) : No members defined in the team !");
      }

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $memberIdList[] = '0';
      }

      if (0 == count($memberIdList)) {
         return $issueList;
      }

      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', $memberIdList);


      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getTeamIssues(teamid=$this->id) projects=$formatedProjects members=$formatedMembers");
      }

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * ".
               "FROM {bug} ".
               "WHERE project_id IN (".$formatedProjects.") ".
               " AND handler_id IN (".$formatedMembers.") ";

      $result = $sql->sql_query($query);

      while($row = $sql->fetchObject($result)) {
         $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getTeamIssues(teamid=$this->id) nbIssues=".count($issueList));
      }
      return $issueList;
   }

   /**
    * get all current issues managed by the team's users on the team's projects.
    * @param bool $addUnassignedIssues
    * @param bool $addNewIssues
    * @return Issue[] issueList
    */
   public function getCurrentIssueList($addUnassignedIssues = false, $addNewIssues = false, $withDisabledProjects = true) {
      $projectList = $this->getProjects(true, $withDisabledProjects);
      $memberList = $this->getMembers();

      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', array_keys($memberList));

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $formatedMembers .= ', 0';
      }

      #if(self::$logger->isDebugEnabled()) {
      #   self::$logger->debug("Team::getCurrentIssues(teamid=$this->id) projects=$formatedProjects members=$formatedMembers");
      #}

      // get Issues that are not Resolved/Closed
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * ".
               "FROM {bug} ".
               "WHERE status < get_project_resolved_status_threshold(project_id) ".
               " AND project_id IN (".$formatedProjects.") ".
               " AND handler_id IN (".$formatedMembers.") ";


      if (!$addNewIssues) {
         $query .= " AND status > ".$sql->db_param();
         $q_params[]=Constants::$status_new;
      }

      $query .= " ORDER BY id DESC;";

      try {
         $result = $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         return NULL;
      }

      $issueList = array();
      while($row = $sql->fetchObject($result)) {
         $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Team::getCurrentIssues(teamid=$this->id) nbIssues=".count($issueList));
      }
      return $issueList;
   }

   /**
    * Commands for this team
    * @return Command[] : array id => Command
    */
   public function getCommands() {
      if (NULL == $this->commandList) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_command_table ".
                  "WHERE team_id =  ".$sql->db_param().
                  " ORDER BY reference, name";
         $q_params[]=$this->id;

         $result = $sql->sql_query($query, $q_params);

         $this->commandList = array();
         while($row = $sql->fetchObject($result)) {
            $this->commandList[$row->id] = CommandCache::getInstance()->getCommand($row->id, $row);
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getCommands(teamid=$this->id) nbEng=".count($this->commandList));
      }
      return $this->commandList;
   }

   /**
    * CommandSets for this team
    * @return CommandSet[] : array id => CommandSet
    */
   public function getCommandSetList() {
      if (NULL == $this->commandSetList) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_commandset_table ".
                  "WHERE team_id =  ".$sql->db_param().
                  " ORDER BY reference, name";
         $q_params[]=$this->id;

         $result = $sql->sql_query($query, $q_params);

         $this->commandSetList = array();
         while($row = $sql->fetchObject($result)) {
            $this->commandSetList[$row->id] = CommandSetCache::getInstance()->getCommandSet($row->id, $row);
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getCommandSetList(teamid=$this->id) nbCommandSet=".count($this->commandSetList));
      }
      return $this->commandSetList;
   }

   /**
    * ServiceContracts for this team
    * @return ServiceContract[] : array id => ServiceContract
    */
   public function getServiceContractList() {
      if (NULL == $this->serviceContractList) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * ".
                  "FROM codev_servicecontract_table ".
                  "WHERE team_id = ".$sql->db_param();
         $q_params[]=$this->id;

         $result = $sql->sql_query($query, $q_params);

         $this->serviceContractList = array();
         while($row = $sql->fetchObject($result)) {
            $this->serviceContractList[$row->id] = ServiceContractCache::getInstance()->getServiceContract($row->id, $row);
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getServiceContractList(teamid=$this->id) nbServiceContracts=".count($this->serviceContractList));
      }
      return $this->serviceContractList;
   }
   

   /**
    * Add a member to the team
    * @param type $memberid
    * @param type $arrivalTimestamp
    * @param type $memberAccess
    * @return boolean : true if user has been added to team, false if team had already user
    */
   public function addMember($memberid, $arrivalTimestamp, $memberAccess) {
       
        if(!UserCache::getInstance()->getUser($memberid)->isTeamMember($this->id))
        {
           $sql = AdodbWrapper::getInstance();
            $query = "INSERT INTO codev_team_user_table  (user_id, team_id, arrival_date, departure_date, access_level) ".
                    "VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().", '0', ".$sql->db_param().")";
            $q_params[]=$memberid;
            $q_params[]=$this->id;
            $q_params[]=$arrivalTimestamp;
            $q_params[]=$memberAccess;
            $sql->sql_query($query, $q_params);
            return true;
        } else {
            return false;
        }
   }

   public function setMemberDepartureDate($memberid, $departureTimestamp) {
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_user_table SET departure_date = ".$sql->db_param()
         . " WHERE user_id = ".$sql->db_param()
         . " AND team_id = ".$sql->db_param();
      $q_params[]=$departureTimestamp;
      $q_params[]=$memberid;
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params);
   }

   /**
    *
    * @param type $memberid
    * @return int timestamp
    */
   public function getMemberArrivalDate($memberid) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT arrival_date FROM codev_team_user_table "
              ." WHERE user_id = ".$sql->db_param()
              ." AND team_id =  ".$sql->db_param()
              ." ORDER BY arrival_date ASC";
      $q_params[]=$memberid;
      $q_params[]=$this->id;
      $result = $sql->sql_query($query, $q_params, TRUE, 1); // LIMIT 1
      $arrivalTimestamp = $sql->sql_result($result);
      return $arrivalTimestamp;
   }

   /**
    * add all members declared in Team $src_teamid (same dates, same access)
    * users already declared are omitted
    *
    * @param int $src_teamid
    */
   public function addMembersFrom($src_teamid) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * from codev_team_user_table WHERE team_id = ".$sql->db_param();
      $q_params[]=$src_teamid;
      $result = $sql->sql_query($query, $q_params);

      while($row = $sql->fetchObject($result)) {
         $user = UserCache::getInstance()->getUser($row->user_id);
         if (! $user->isTeamMember($this->id)) {
            $this->addMember($row->user_id,$row->arrival_date, $row->access_level);

            if (NULL != $row->departure_date) {
               $this->setMemberDepartureDate($row->user_id, $row->departure_date);
            }
         }
      }

   }

   /**
    * Add a project to the team,
    * Add default jobs to the project (if not already defined)
    *
    * @param int $projectid
    * @param int $projecttype
    * @return bool
    */
   public function addProject($projectid, $projecttype) {
      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO codev_team_project_table  (project_id, team_id, type)"
         . " VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
      $q_params[]=$projectid;
      $q_params[]=$this->id;
      $q_params[]=$projecttype;
      try {
         $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         throw new Exception("Couldn't add the project to the team (insert project $projectid)");
      }
      // jobs assignations are not (not yet) specific to a team.
      // check if jobs already defined
      $query2 = "SELECT count(1) as cnt FROM codev_project_job_table WHERE project_id = ".$sql->db_param();
      $q_params2[]=$projectid;
      try {
         $result2 = $sql->sql_query($query2, $q_params2);
      } catch (Exception $e) {
         throw new Exception("Couldn't check existing jobs for project $projectid");
      }
      $count = $sql->sql_result($result2);
      //self::$logger->error("count jobs = $count");
      if (0 == $count) {
         // if no job defined yet, then default jobs (previously: commonJobs) must be assigned.
         $query3 = "INSERT INTO codev_project_job_table(project_id, job_id) ".
                  "SELECT ".$sql->db_param().", job.id FROM codev_job_table job WHERE type = 0";
         $q_params3[]=$projectid;
         try {
            $sql->sql_query($query3, $q_params3);
         } catch (Exception $e) {
            throw new Exception("Couldn't add the project to the team (add default jobs)");
         }
      }

      return true;
   }

   /**
    * removes a project from the team
    * @param int $projectid
    * @return bool
    */
   public function removeProject($projectid) {
      // TODO check if projectid exists in codev_team_project_table
      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_team_project_table WHERE id = ".$sql->db_param();
      $q_params[]=$projectid;
      try {
         $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         self::$logger->error("Could not remove project $projectid from team $this->id");
         return false;
      }
      return true;
   }

   public function addExternalTasksProject() {
      $extTasksProjectType = Project::type_noStatsProject;

      $externalTasksProject = Config::getInstance()->getValue(Config::id_externalTasksProject);

      // TODO check if ExternalTasksProject not already in table !
      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO codev_team_project_table  (project_id, team_id, type)"
         . " VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
      $q_params[]=$externalTasksProject;
      $q_params[]=$this->id;
      $q_params[]=$extTasksProjectType;
      $sql->sql_query($query, $q_params);
   }

   /**
    * @param string $projectName
    * @return int $projectId
    */
   public function createSideTaskProject($projectName) {
      $sideTaskProjectType = Project::type_sideTaskProject;

      $projectid = Project::createSideTaskProject($projectName);

      if (-1 != $projectid) {
         // add new SideTaskProj to the team
         $sql = AdodbWrapper::getInstance();
         $query = "INSERT INTO codev_team_project_table (project_id, team_id, type) ".
                  "VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
         $q_params[]=$projectid;
         $q_params[]=$this->id;
         $q_params[]=$sideTaskProjectType;
         $sql->sql_query($query, $q_params);
      } else {
         self::$logger->error("team $this->name createSideTaskProject !!!");
         echo "<span style='color:red'>ERROR: team $this->name createSideTaskProject !!!</span>";
         exit;
      }

      // assign SideTaskProject specific Job
      #REM: 'N/A' job_id = 1, created by SQL file
      Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);

      return $projectid;
   }

   /**
    * @param int $date
    * @return bool
    */
   public function setCreationDate($date) {
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_table SET date = ".$sql->db_param()." WHERE id = ".$sql->db_param();
      $q_params[]=$date;
      $q_params[]=$this->id;
      try {
         $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         return false;
      }
      $this->date = $date;
      return true;
   }

   /**
    * @param int $leaderid
    * @return bool
    */
   public function setLeader($leaderid) {
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_team_table SET leader_id = ".$sql->db_param()." WHERE id = ".$sql->db_param();
      $q_params[]=$leaderid;
      $q_params[]=$this->id;
      try {
         $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         return false;
      }
      $this->leader_id = $leaderid;
      return true;
   }

   /**
    * @return int[] The type by project
    */
   public function getProjectsType() {
      if($this->projTypeList == NULL) {
         $this->projTypeList = array();
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_team_project_table WHERE team_id =  ".$sql->db_param();
         $q_params[]=$this->id;
         $result = $sql->sql_query($query, $q_params);

         while($row = $sql->fetchObject($result)) {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("initialize: team $this->id proj $row->project_id type $row->type");
            }
            $this->projTypeList[$row->project_id] = $row->type;
         }
      }

      return $this->projTypeList;
   }

   /**
    * @param int $projectid The project id
    * @return int The type
    */
   public function getProjectType($projectid) {
      $projectsType = $this->getProjectsType();
      return $projectsType[$projectid];
   }

   /**
    * @param int $type The project type
    * @return int[] The team's project ids matching the type
    */
   public function getSpecificTypedProjectIds($type) {
      $projectsType = $this->getProjectsType();
      return array_keys($projectsType, $type);
   }

   public function isSideTasksProject($projectid) {
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("isSideTasksProject:  team $this->id proj $projectid type ".$this->getProjectType($projectid));
      }
      return (Project::type_sideTaskProject == $this->getProjectType($projectid));
   }

   public function isNoStatsProject($projectid) {
      return (Project::type_noStatsProject == $this->getProjectType($projectid));
   }

   /**
    * Get the name
    * @return string name
    */
   public function getName() {
      return $this->name;
   }

   /**
    * Get all teams by name
    * @param bool $withDisabled if true, include disabled teams
    * @return string[int] : name[id]
    */
   public static function getTeams($withDisabled=false) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id, name FROM codev_team_table ";
      if (!$withDisabled) {
         $query .= " WHERE enabled = 1 ";
      }
      $query .= " ORDER BY name";
      try {
         $result = $sql->sql_query($query);
      } catch (Exception $e) {
         return NULL;
      }
      $teams = array();
      while ($row = $sql->fetchObject($result)) {
         $teams[$row->id] = $row->name;
      }
      return $teams;
   }

   /**
    * Get projects not yet in the team (candidates)
    * @return string[]
    */
   public function getOtherProjects($withDisabledProjects = true) {

      $projects = $this->getProjects(true, $withDisabledProjects);
      $formatedCurProjList = implode( ', ', array_keys($projects));

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id, name FROM {project}";

      if($projects != NULL && count($projects) != 0) {
         $query .= " WHERE id NOT IN (".$formatedCurProjList.")";
      }

      $query .= " ORDER BY name";

      try {
         $result = $sql->sql_query($query, $q_params);
      } catch (Exception $e) {
         return NULL;
      }

      $teamProjects = array();
      while($row = $sql->fetchObject($result)) {
         $teamProjects[$row->id] = $row->name;
      }

      return $teamProjects;
   }

   /**
    * @return int
    */
   public function getId() {
      return $this->id;
   }

   /**
    * @return int
    */
   public function getDate() {
      return $this->date;
   }

   /**
    * return onDutyTasks for this team, or empty array
    *
    * @return array of bugid
    */
   public function getOnDutyTasks() {

      if (is_null($this->onDutyTaskList)) {

      	 $this->onDutyTaskList = Config::getValue(Config::id_onDutyTaskList, array(0, 0, $this->id, 0, 0, 0), true);
      	 if($this->onDutyTaskList == NULL || empty($this->onDutyTaskList)) {
      	 	return array();
      	 }
      	 
      }
      return $this->onDutyTaskList;
   }

   public function addOnDutyTask($bugid) {

      $onDutyTaskList = $this->getOnDutyTasks();

      if (!in_array($bugid, $onDutyTaskList)) {
         $onDutyTaskList[] = $bugid;
         $imploded = implode(',', $onDutyTaskList);
         Config::setValue(Config::id_onDutyTaskList, $imploded, Config::configType_array, NULL, 0, 0, $this->id);
         $this->onDutyTaskList = $onDutyTaskList;
      }
   }

   public function removeOnDutyTask($bugid) {

      $onDutyTaskList = $this->getOnDutyTasks();

      if (in_array($bugid, $onDutyTaskList)) {
         $key = array_search($bugid, $onDutyTaskList);
         unset($onDutyTaskList[$key]);

         $imploded = (0 == count($onDutyTaskList)) ? "" : implode(',', $onDutyTaskList);
         Config::setValue(Config::id_onDutyTaskList, $imploded, Config::configType_array, NULL, 0, 0, $this->id);
         $this->onDutyTaskList = $onDutyTaskList;
      }

   }

   /**
    * get all unassigned tasks found in team projects
    *
    * Note: sideTasks and nostatsProjects excluded, resolved tasks excluded.
    *
    * @return Issue[] : issueList (id => Issue)
    */
   public function getUnassignedTasks() {


      $issueList = array();
      $sql = AdodbWrapper::getInstance();

      $query_projects = "SELECT project.id ".
               "FROM {project} as project ".
               "JOIN codev_team_project_table as team_project ON project.id = team_project.project_id ".
               "WHERE team_project.team_id = ".$sql->db_param().
               " AND team_project.type NOT IN (".$sql->db_param().', '.$sql->db_param().') ';

      $query = "SELECT * ".
               "FROM {bug} ".
               "WHERE project_id IN (".$query_projects.") ".
               " AND handler_id = '0' ".
               " AND status < get_project_resolved_status_threshold(project_id) ".
               " ORDER BY project_id ASC, id ASC";
      $q_params[]= $this->id;
      $q_params[]=Project::type_noStatsProject;
      $q_params[]=Project::type_sideTaskProject;

      $result = $sql->sql_query($query, $q_params);

      while($row = $sql->fetchObject($result)) {
         $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getUnassignedTasks(teamid=$this->id) nbIssues=".count($issueList));
      }
      return $issueList;
   }

   /**
    *
    * @return array ('checkName' => [0,1] isEnabled)
    */
   public function getConsistencyCheckList() {

      if (empty($this->consistencyCheckList)) {

      	 $checkList = Config::getValue(Config::id_consistencyCheckList, array(0, 0, $this->id, 0, 0, 0), true);
         // get default checkList if not found
         $this->consistencyCheckList = ConsistencyCheck2::$defaultCheckList;

         // update with team specific items
         if ($checkList != NULL && is_array($checkList)) {

            foreach ($checkList as $name => $enabled) {
               if (!array_key_exists($name, $this->consistencyCheckList)) {
                  self::$logger->warn("team $this->id: remove unknown/deprecated consistencyCheck: $name");
               } else {
                  $this->consistencyCheckList["$name"] = $enabled;
               }
            }
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("team $this->id consistencyCheckList = ". Tools::doubleImplode(':', ',',  $this->consistencyCheckList));
      }

      return $this->consistencyCheckList;

   }

   /**
    * @param array $checkList ('checkName' => [0,1] isEnabled)
    */
   function setConsistencyCheckList(array $checkList) {

      $this->consistencyCheckList = $checkList;

      $keyvalue = Tools::doubleImplode(':', ',', $this->consistencyCheckList);
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Write team $this->id consistencyCheckList : $keyvalue");
      }

      // save new settings
      Config::setValue(Config::id_consistencyCheckList, $keyvalue, Config::configType_keyValue, NULL, 0, 0, $this->id);
   }


   /**
    *
    * @return array ('checkName' => [0,1] isEnabled)
    */
   public function getGeneralPrefsList() {

      if (empty($this->generalPrefsList)) {

         $checkList = Config::getValue(Config::id_teamGeneralPreferences, array(0, 0, $this->id, 0, 0, 0), true);
         
         // get default checkList if not found
         $this->generalPrefsList = Team::$defaultGeneralPrefsList;

         // update with team specific items
         if ($checkList != NULL && is_array($checkList)) {
            foreach ($checkList as $name => $enabled) {

               if (!array_key_exists($name, $this->generalPrefsList)) {
                  self::$logger->warn("team $this->id: remove unknown/deprecated generalPref: $name");
               } else {
                  $this->generalPrefsList["$name"] = $enabled;
               }
            }
         }
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("team $this->id generalPrefsList = ". Tools::doubleImplode(':', ',',  $this->generalPrefsList));
      }

      return $this->generalPrefsList;

   }

   public function getGeneralPreference($prefKey) {
      $prefs = $this->getGeneralPrefsList();
      return $prefs["$prefKey"];
   }

   /**
    * @param array $checkList ('checkName' => [0,1] isEnabled)
    */
   function setGeneralPrefsList(array $checkList) {

      $this->generalPrefsList = $checkList;

      $keyvalue = Tools::doubleImplode(':', ',', $this->generalPrefsList);
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Write team $this->id generalPrefsList : $keyvalue");
      }

      // save new settings
      Config::setValue(Config::id_teamGeneralPreferences, $keyvalue, Config::configType_keyValue, NULL, 0, 0, $this->id);
   }

   /**
    * send an email to each member with the list of all incomplete/missing days in the period
    */
   public function sendTimesheetEmails($startTimestamp=NULL, $endTimestamp=NULL) {

      if (($this->isEnabled()) &&
          (1 == Constants::$emailSettings['enable_email_notification']) &&
          (1 == $this->getGeneralPreference('sendTimesheetEmailNotification'))) {

         echo "=== Team $this->id : ".$this->getName()."\n";
         $users = $this->getActiveMembers();
         foreach ($users as $id => $name) {
            try {
               $user = UserCache::getInstance()->getUser($id);
               if ($user->isEnabled()) {
                  $user->sendTimesheetEmail($this->id, $startTimestamp, $endTimestamp);
               }
            } catch (Exception $e) {
               self::$logger->error("sendTimetrackEmails: Could not send mail to $name");
            }
         }
      }
   }

   /**
    *
    * @return UserDailyCost class instance
    */
   public function getUserDailyCostObj() {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj;
   }

   /**
    *
    * @param int $userid
    * @param int $timestamp
    * @param float $userDailyCost
    * @param string $currency if unset, use teamCurrency
    * @param string $description
    * @return id of inserted row or FALSE on error
    */
   public function setUserDailyCost($userid, $timestamp, $userDailyCost, $currency = NULL, $description = NULL) {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj->setUserDailyCost($userid, $timestamp, $userDailyCost, $currency, $description);
   }

   /**
    * delete UDC by id
    * @param type $id
    */
   public function deleteUserDailyCost($id) {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj->deleteUserDailyCost($id);
   }

   /**
    * returns the valid UDC struct for a date
    * if no UDC defined, returns NULL
    *
    * valid UDC is the closest start_date <= $timestamp
    *
    * Note: this method is used in admin page to check if an UDC has effectively been created
    *
    * @param int $userid team member
    * @param int $timestamp
    * @return array { 'id', 'timestamp', 'udr', 'currency' } or NULL if not found
    */
   public function getUserDailyCost($userid, $timestamp) {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj->getUserDailyCost($userid, $timestamp);
   }

   /**
    * strict search for an UDC definition at the given timestamp
    *
    * @param type $userid
    * @param type $timestamp
    * @return struct or FALSE if UDC not defined at this date (strictly)
    */
   public function existsUserDailyCost($userid, $timestamp) {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj->existsUserDailyCost($userid, $timestamp);
   }

   /**
    * returns the valid UDC value for a user at a specific date
    * if no UDC defined for this user, result depends on $isRaw
    *  - $isRay == TRUE  return FALSE
    *  - $isRay == FALSE return teamADR
    *
    * if $isRay == FALSE AND no teamADR defined, throw exception
    *
    * Note: this method is used to compute the cost of a timetrack
    *
    * @param int $userid team member
    * @param int $timestamp date of the timetrack
    * @param type $targetCurrency target currency (default: team currency)
    * @param type $isRaw if TRUE do not look for teamADC
    * @return float UDC in the target currency or FALSE if not found
    * @throws Exception
    */
   public function getUdcValue($userid, $timestamp, $targetCurrency=NULL, $isRaw=FALSE) {
      if (NULL === $this->userDailyCostObj) {
         $this->userDailyCostObj = new UserDailyCost($this->id);
      }
      return $this->userDailyCostObj->getUdcValue($userid, $timestamp, $targetCurrency, $isRaw);
   }

} // class Team
Team::staticInit();


