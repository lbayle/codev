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
include_once('classes/team_cache.class.php');

include_once('classes/command.class.php');
include_once('classes/command_cache.class.php');
include_once('classes/commandset.class.php');
include_once('classes/commandset_cache.class.php');
include_once('classes/config.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/jobs.class.php');
include_once('classes/project.class.php');
include_once('classes/servicecontract.class.php');
include_once('classes/servicecontract_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class Team {

   // il n'y a qu'un seul teamLeader
   // il peut y avoir plusieurs observer
   // il peut y avoir plusieurs manager
   // un observer ne peut imputer sur les taches de l'equipe, il a acces en lecture seule aux donnees
   // un noStats ne peut imputer, il n'est pas considéré comme ressource, il sert a "stocker" des fiches

   const accessLevel_nostats  =  5;    // in table codev_team_user_table
   const accessLevel_dev      = 10;    // in table codev_team_user_table
   const accessLevel_observer = 20;    // in table codev_team_user_table
   const accessLevel_manager  = 30;    // in table codev_team_user_table

   public static $accessLevelNames = array(
                              //self::accessLevel_nostats  => "NoStats", // can modify, can NOT view stats
                              self::accessLevel_dev      => "Developer", // can modify, can NOT view stats
                              self::accessLevel_observer => "Observer",  // can NOT modify, can view stats
                              //self::accessLevel_teamleader => "TeamLeader",  // REM: NOT USED FOR NOW !! can modify, can view stats, can work on projects ? , included in stats ?
                              self::accessLevel_manager  => "Manager");  // can modify, can view stats, can only work on sideTasksProjects, resource NOT in statistics
   
   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   public $id;
   public $name;
   public $description;
   public $leader_id;
   public $date;
   private $enabled;
   private $lock_timetracks_date;

   private $projTypeList;
   private $commandList;
   private $commandSetList;
   private $serviceContractList;

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

       $this->id = $teamid;
       $this->initialize();
   }

   /**
    * Initialize with DB
    */
   public function initialize() {
      $query = 'SELECT * FROM `codev_team_table` WHERE id = '.$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);

      $this->name = $row->name;
      $this->description = $row->description;
      $this->leader_id = $row->leader_id;
      $this->enabled = (1 == $row->enabled);
      $this->lock_timetracks_date = $row->lock_timetracks_date;
      $this->date = $row->date;
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
    * @param unknown_type $date
    * @return int the team id or -1 if not found
    */
   public static function create($name, $description, $leader_id, $date) {
      // check if Team name exists !
      $teamid = self::getIdFromName($name);

      if ($teamid < 0) {
         // create team
         $formattedName = SqlWrapper::getInstance()->sql_real_escape_string($name);
         $formattedDesc = SqlWrapper::getInstance()->sql_real_escape_string($description);
         $query = "INSERT INTO `codev_team_table`  (`name`, `description`, `leader_id`, `date`) VALUES ('$formattedName','$formattedDesc','$leader_id', '$date');";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
         }
         $teamid = SqlWrapper::getInstance()->sql_insert_id();
      } else {
         echo "<span style='color:red'>ERROR: Team name '$name' already exists !</span>";
         $teamid = -1;
      }
      return $teamid;
   }

   /**
    * delete a team (and all it's ServiceContracts,CommandSets,Commands)
    * @param int $teamidToDelete
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

         $query = "DELETE FROM `codev_team_project_table` WHERE team_id = $teamidToDelete;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
            exit;
         }

         $query = "DELETE FROM `codev_team_user_table` WHERE team_id = $teamidToDelete;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
            exit;
         }

         $query = "DELETE FROM `codev_team_table` WHERE id = $teamidToDelete;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
            exit;
         }
      } catch (Exception $e) {
         return false;
      }
      return true;
   }

   /**
    * @param string $name
    * @return int the team id or -1 if not found
    */
   private static function getIdFromName($name) {
      $formattedName = SqlWrapper::getInstance()->sql_real_escape_string($name);
      $query = "SELECT id FROM `codev_team_table` WHERE name = '$formattedName';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }

      return (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : (-1);
   }

   /**
    *
    * @return bool isEnabled 
    */
   public function isEnabled() {
      return $this->enabled;
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
    * @return int timestamp
    */
   public function setLockTimetracksDate($timestamp) {
      $query = "UPDATE `codev_team_table` SET lock_timetracks_date = $timestamp WHERE id ='$this->id';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
      }
      $this->lock_timetracks_date = $timestamp;
   }

   /**
    * @param bool $noStatsProject
    * @return string[] : array[project_id] = project_name
    */
   public function getProjects($noStatsProject = true) {
      if(NULL == $this->projectIdsCache) {
         $this->projectIdsCache = array();
      }

      $key = ''.$noStatsProject;

      if(!array_key_exists($key, $this->projectIdsCache)) {
         $query = "SELECT codev_team_project_table.project_id, mantis_project_table.name ".
            "FROM `codev_team_project_table`, `mantis_project_table` ".
            "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
            "AND codev_team_project_table.team_id=$this->id";

         if (!$noStatsProject) {
            $query .= " AND codev_team_project_table.type <> ".Project::type_noStatsProject;
         }
         $query .= " ORDER BY mantis_project_table.name";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $projList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $projList[$row->project_id] = $row->name;
         }
         $this->projectIdsCache[$key] = $projList;
      }

      return $this->projectIdsCache[$key];
   }

   /**
    * @return string[]
    */
   public function getMembers() {
      if(NULL == $this->members) {
         $this->members = array();

         $query  = "SELECT mantis_user_table.id, mantis_user_table.username ".
            "FROM `codev_team_user_table`, `mantis_user_table` ".
            "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
            "AND codev_team_user_table.team_id=$this->id ".
            "ORDER BY mantis_user_table.username";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->members[$row->id] = $row->username;
         }
      }

      return $this->members;
   }

   /**
    * team members (exept Observers) working on this team at $startTimestamp
    * or during the period.
    *
    * @param int $startTimestamp date (if NULL, today)
    * @param int $endTimestamp date (if NULL, $startTimestamp)
    * @return string[]
    */
   public function getActiveMembers($startTimestamp=NULL, $endTimestamp=NULL) {
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

      $mList = array();

      $query  = "SELECT mantis_user_table.id, mantis_user_table.username ".
         "FROM `codev_team_user_table`, `mantis_user_table` ".
         "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
         "AND   codev_team_user_table.team_id=$this->id ".
         "AND   codev_team_user_table.access_level <> ".self::accessLevel_observer.' '.
         "AND   codev_team_user_table.arrival_date <= $endTimestamp ".
         "AND  (codev_team_user_table.departure_date = 0 OR codev_team_user_table.departure_date >= $startTimestamp) ".
         "ORDER BY mantis_user_table.username";

      $result    = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $mList[$row->id] = $row->username;
      }

      return $mList;
   }

   /**
    * get all issues managed by the team's users on the team's projects.
    * @param bool $addUnassignedIssues if true, include issues on team's projects that are assigned to nobody
    * @return Issue[] : issueList
    */
   public function getTeamIssueList($addUnassignedIssues = false) {
      $projectList = $this->getProjects();
      $memberList = $this->getMembers();

      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', array_keys($memberList));

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $formatedMembers .= ',0';
      }

      self::$logger->debug("getTeamIssues(teamid=$this->id) projects=$formatedProjects members=$formatedMembers");

      $query = "SELECT * ".
         "FROM `mantis_bug_table` ".
         "WHERE project_id IN ($formatedProjects) ".
         "AND   handler_id IN ($formatedMembers) ";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $issueList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      self::$logger->debug("getTeamIssues(teamid=$this->id) nbIssues=".count($issueList));
      return $issueList;
   }

   /**
    * get all current issues managed by the team's users on the team's projects.
    * @param bool $addUnassignedIssues
    * @param bool $addNewIssues
    * @return Issue[] issueList
    */
   public function getCurrentIssueList($addUnassignedIssues = false, $addNewIssues = false) {
      global $status_new;

      $projectList = $this->getProjects();
      $memberList = $this->getMembers();

      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', array_keys($memberList));

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $formatedMembers .= ', 0';
      }

      self::$logger->debug("Team::getCurrentIssues(teamid=$this->id) projects=$formatedProjects members=$formatedMembers");

      // get Issues that are not Resolved/Closed
      $query = "SELECT * ".
         "FROM `mantis_bug_table` ".
         "WHERE status < get_project_resolved_status_threshold(project_id) ".
         "AND project_id IN ($formatedProjects) ".
         "AND handler_id IN ($formatedMembers) ";

      if (false == $addNewIssues) {
         $query .= "AND status > $status_new ";
      }

      $query .= "ORDER BY id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return NULL;
      }

      $issueList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issueList[$row->id] = IssueCache::getInstance()->getIssue($row->id, $row);
      }

      self::$logger->debug("Team::getCurrentIssues(teamid=$this->id) nbIssues=".count($issueList));
      return $issueList;
   }

   /**
    * Commands for this team
    * @return Command[] : array id => Command
    */
   public function getCommands() {
      if (NULL == $this->commandList) {
         $query = "SELECT * FROM `codev_command_table` ".
            "WHERE team_id = $this->id ".
            "ORDER BY reference,name";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->commandList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->commandList[$row->id] = CommandCache::getInstance()->getCommand($row->id, $row);
         }
      }

      self::$logger->debug("getCommands(teamid=$this->id) nbEng=".count($this->commandList));
      return $this->commandList;
   }

   /**
    * CommandSets for this team
    * @return CommandSet[] : array id => CommandSet
    */
   public function getCommandSetList() {
      if (NULL == $this->commandSetList) {
         $query = "SELECT * FROM `codev_commandset_table` ".
            "WHERE team_id = $this->id ".
            "ORDER BY reference, name";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->commandSetList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->commandSetList[$row->id] = CommandSetCache::getInstance()->getCommandSet($row->id, $row);
         }
      }

      self::$logger->debug("getCommandSetList(teamid=$this->id) nbCommandSet=".count($this->commandSetList));
      return $this->commandSetList;
   }

   /**
    * ServiceContracts for this team
    * @return ServiceContract[] : array id => ServiceContract
    */
   public function getServiceContractList() {
      if (NULL == $this->serviceContractList) {
         $query = "SELECT * FROM `codev_servicecontract_table` ".
            "WHERE team_id = $this->id ";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $this->serviceContractList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->serviceContractList[$row->id] = ServiceContractCache::getInstance()->getServiceContract($row->id, $row);
         }
      }

      self::$logger->debug("getServiceContractList(teamid=$this->id) nbServiceContracts=".count($this->serviceContractList));
      return $this->serviceContractList;
   }

   /**
    * @param int $memberid
    * @param unknown_type $arrivalTimestamp
    * @param unknown_type $memberAccess
    */
   public function addMember($memberid, $arrivalTimestamp, $memberAccess) {
      $query = "INSERT INTO `codev_team_user_table`  (`user_id`, `team_id`, `arrival_date`, `departure_date`, `access_level`) ".
               "VALUES ('$memberid','$this->id','$arrivalTimestamp', '0', '$memberAccess');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function setMemberDepartureDate($memberid, $departureTimestamp) {
      $query = "UPDATE `codev_team_user_table` SET departure_date = $departureTimestamp WHERE user_id = $memberid AND team_id = $this->id;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * add all members declared in Team $src_teamid (same dates, same access)
    * users already declared are omitted
    *
    * @param int $src_teamid
    */
   public function addMembersFrom($src_teamid) {
      $query = "SELECT * from `codev_team_user_table` WHERE team_id = $src_teamid ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
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
    * Add a project to the team
    * 
    * @param int $projectid
    * @param int $projecttype
    * @return bool
    */
   public function addProject($projectid, $projecttype) {
      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$projectid','$this->id','$projecttype');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      }
      return true;
   }

   /**
    * removes a project from the team
    * @param int $projectid 
    */
   public function removeProject($projectid) {
      // TODO check if projectid exists in codev_team_project_table
      $query = "DELETE FROM `codev_team_project_table` WHERE id = ".$projectid.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         self::$logger->error("Could not remove project $projectid from team $this->id");
         return false;
      }
      return true;
   }
   
   public function addExternalTasksProject() {

      $extTasksProjectType = Project::type_noStatsProject;

      $externalTasksProject = Config::getInstance()->getValue(Config::id_externalTasksProject);

      // TODO check if ExternalTasksProject not already in table !

      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$externalTasksProject','$this->id','$extTasksProjectType');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
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
         $query = "INSERT INTO `codev_team_project_table` (`project_id`, `team_id`, `type`) ".
                  "VALUES ('$projectid','$this->id','$sideTaskProjectType');";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
         }

      } else {
        self::$logger->error("team $this->name createSideTaskProject !!!");
        echo "<span style='color:red'>ERROR: team $this->name createSideTaskProject !!!</span>";
        exit;
      }

      // --- assign SideTaskProject specific Job
      #REM: 'N/A' job_id = 1, created by SQL file
      Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);

      return $projectid;
   }

   /**
    * @param int $date
    * @return bool
    */
   public function setCreationDate($date) {
      $query = "UPDATE `codev_team_table` SET date = ".$date." WHERE id = ".$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
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
      $query = "UPDATE `codev_team_table` SET leader_id = ".$leaderid." WHERE id = ".$this->id.';';
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
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
         $query = "SELECT * FROM `codev_team_project_table` WHERE team_id = $this->id ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            self::$logger->debug("initialize: team $this->id proj $row->project_id type $row->type");
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
      self::$logger->debug("isSideTasksProject:  team $this->id proj $projectid type ".$this->getProjectType($projectid));
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
    * @return string[int] : name[id]
    */
   public static function getTeams() {
      $query = "SELECT id, name FROM `codev_team_table` ORDER BY name";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return NULL;
      }
      $teams = array();
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $teams[$row->id] = $row->name;
      }
      return $teams;
   }

   /**
    * Get all users of a team
    * @return User[] The users (User[id])
    */
   public function getUsers() {
      if(NULL == $this->members) {
         $this->members = array();

         $query = "SELECT mantis_user_table.* ".
            "FROM  `codev_team_user_table`, `mantis_user_table` ".
            "WHERE  codev_team_user_table.team_id = $this->id ".
            "AND    codev_team_user_table.user_id = mantis_user_table.id ".
            "ORDER BY mantis_user_table.username";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            return NULL;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
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
    * Get other projects
    * @return string[]
    */
   public function getOtherProjects() {
      $formatedCurProjList = implode( ', ', array_keys($this->getProjects()));

      $query = "SELECT id, name FROM `mantis_project_table`";
      
      $projects = $this->getProjects();
      if($projects != NULL && count($projects) == 0) {
         $query .= " WHERE id NOT IN ($formatedCurProjList)";
      }
      
      $query .= " ORDER BY name;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return NULL;
      }

      $teamProjects = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $teamProjects[$row->id] = $row->name;
      }

      return $teamProjects;
   }

}

// Initialize complex static variables
Team::staticInit();

?>
