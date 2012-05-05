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

include_once "team_cache.class.php";

include_once 'jobs.class.php';
include_once 'project.class.php';

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
      Logger::configure(dirname(__FILE__).'/../log4php.xml');
      $logger = Logger::getLogger("default");
      $logger->info("LOG activated !");
}

class Team {

   private $logger;
  // ---
  // il peut y avoir plusieurs observer
  // il n'y a qu'un seul teamLeader
  // il peut y avoir plusieurs managers, mais ils ne peuvent imputer que sur des SideTasks
  // un observer ne fait jamais partie de l'equipe, il n'a acces qu'a des donnees impersonnelles
    const accessLevel_dev      = 10;    // in table codev_team_user_table
    const accessLevel_observer = 20;    // in table codev_team_user_table
    const accessLevel_manager  = 30;    // in table codev_team_user_table

    public static $accessLevelNames = array(Team::accessLevel_dev      => "Developer", // can modify, can NOT view stats
                              Team::accessLevel_observer => "Observer",  // can NOT modify, can view stats
                              //$accessLevel_teamleader => "TeamLeader",  // REM: NOT USED FOR NOW !! can modify, can view stats, can work on projects ? , included in stats ?
                              Team::accessLevel_manager  => "Manager");  // can modify, can view stats, can only work on sideTasksProjects, resource NOT in statistics

   public $id;
   public $name;
   public $description;
   public $leader_id;
   public $date;

   private $projTypeList;


   // -------------------------------------------------------
   /**
    *
    * @param unknown_type $teamid
    */
   public function Team($teamid) {
       $this->id = $teamid;
       $this->logger = Logger::getLogger(__CLASS__);

       $this->initialize();
   }

   // -------------------------------------------------------
   /**
    *
    */
   public function initialize() {

      $query = "SELECT * FROM `codev_team_table` WHERE id = $this->id";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $row = mysql_fetch_object($result);

      $this->name           = $row->name;
      $this->description    = $row->description;
      $this->leader_id      = $row->leader_id;
      $this->date           = $row->date;


      // -------
      $this->projTypeList = array();
      $query = "SELECT * FROM `codev_team_project_table` WHERE team_id = $this->id ";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $this->logger->debug("initialize: team $this->id proj $row->project_id type $row->type");
         $this->projTypeList[$row->project_id] = $row->type;
      }


   }

   // -------------------------------------------------------
   /**
    * STATIC insert new team in DB
    *
    * Team::create($name, $description, $leader_id, $date);
    *
    * @return the team id or -1 if not found
    */
   public static function create($name, $description, $leader_id, $date) {

      global $logger;

      // check if Team name exists !
      $teamid = Team::getIdFromName($name);

      if ($teamid < 0) {
         // create team
         $formattedName = mysql_real_escape_string($name);
         $formattedDesc = mysql_real_escape_string($description);
         $query = "INSERT INTO `codev_team_table`  (`name`, `description`, `leader_id`, `date`) VALUES ('$formattedName','$formattedDesc','$leader_id', '$date');";
         $result = mysql_query($query);
         if (!$result) {
             $logger->error("Query FAILED: $query");
             $logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
         }
         $teamid = mysql_insert_id();
      } else {
         echo "<span style='color:red'>ERROR: Team name '$name' already exists !</span>";
         $teamid = -1;
      }
      return $teamid;
   }

   // -------------------------------------------------------
   /**
    * @param unknown_type $name
    * @return the team id or -1 if not found
    */
   public static function getIdFromName($name) {
      global $logger;

      $formattedName = mysql_real_escape_string($name);
      $query = "SELECT id FROM `codev_team_table` WHERE name = '$formattedName';";
      $result = mysql_query($query);
      if (!$result) {
             $logger->error("Query FAILED: $query");
             $logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $teamid = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : (-1);

      return $teamid;
   }

   // -------------------------------------------------------
   public static function getLeaderId($teamid) {
      global $logger;

      $query = "SELECT leader_id FROM `codev_team_table` WHERE id = $teamid";
      $result = mysql_query($query);
      if (!$result) {
             $logger->error("Query FAILED: $query");
             $logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      $leaderid  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : 0;

      return $leaderid;
   }


   // -------------------------------------------------------
   public static function getProjectList($teamid, $noStatsProject = true) {
      global $logger;

      $projList = array();

      $query = "SELECT codev_team_project_table.project_id, mantis_project_table.name ".
               "FROM `codev_team_project_table`, `mantis_project_table` ".
               "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
               "AND codev_team_project_table.team_id=$teamid ";

     if (!$noStatsProject) {
        $query .= "AND codev_team_project_table.type <> ".Project::type_noStatsProject." ";
     }
      $query .= "ORDER BY mantis_project_table.name";

      if (isset($_GET['debug_sql'])) { echo "Team.getProjectList(): query = $query<br/>"; }

      $result    = mysql_query($query);
      if (!$result) {
             $logger->error("Query FAILED: $query");
             $logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $projList[$row->project_id] = $row->name;
      }

      return $projList;
   }

   // -------------------------------------------------------
   public static function getMemberList($teamid) {

      global $logger;
      $mList = array();

      $query  = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
                "FROM `codev_team_user_table`, `mantis_user_table` ".
                "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
                "AND codev_team_user_table.team_id=$teamid ".
                "ORDER BY mantis_user_table.username";
      $result    = mysql_query($query);
      if (!$result) {
             $logger->error("Query FAILED: $query");
             $logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $mList[$row->user_id] = $row->username;
      }

      return $mList;
   }

   // -------------------------------------------------------
   /**
    *
    * get all issues managed by the team's users on the team's projects.
    *
    * @param teamid the team
    * @param addUnassignedIssues if true, include issues on team's projects that are assigned to nobody
    *
    * @return issueList
    *
    */
   public static function getTeamIssues($teamid, $addUnassignedIssues = false) {

      global $logger;

      $projectList = Team::getProjectList($teamid);
      $memberList = Team::getMemberList($teamid);


      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', array_keys($memberList));

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $formatedMembers .= ',0';
      }

      $logger->debug("getTeamIssues(teamid=$teamid) projects=$formatedProjects members=$formatedMembers");

      $query = "SELECT id AS bug_id, status, handler_id, last_updated ".
         "FROM `mantis_bug_table` ".
         "WHERE project_id IN ($formatedProjects) ".
         "AND   handler_id IN ($formatedMembers) ";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $issueList = array();
      while($row = mysql_fetch_object($result))
      {
         $issue = IssueCache::getInstance()->getIssue($row->bug_id);
         $issueList[$row->bug_id] = $issue;
      }

      $logger->debug("getTeamIssues(teamid=$teamid) nbIssues=".count($issueList));
      return $issueList;
   }


   // -------------------------------------------------------
   /**
    * get all current issues managed by the team's users on the team's projects.
    *
    * @param int $teamid
    * @param boolean $addUnassignedIssues
    * @param boolean $addNewIssues
    *
    * @return array issueList
    */
   public static function getCurrentIssues($teamid, $addUnassignedIssues = false, $addNewIssues = false) {

      global $logger;
      global $status_new;

      $projectList = Team::getProjectList($teamid);
      $memberList = Team::getMemberList($teamid);


      $formatedProjects = implode( ', ', array_keys($projectList));
      $formatedMembers = implode( ', ', array_keys($memberList));

      // add unassigned tasks
      if ($addUnassignedIssues) {
         $formatedMembers .= ', 0';
      }

      $logger->debug("Team::getCurrentIssues(teamid=$teamid) projects=$formatedProjects members=$formatedMembers");

      // ---- get Issues that are not Resolved/Closed
      $query = "SELECT DISTINCT id ".
            "FROM `mantis_bug_table` ".
            "WHERE status < get_project_resolved_status_threshold(project_id) ".
            "AND project_id IN ($formatedProjects) ".
            "AND handler_id IN ($formatedMembers) ";

      if (false == $addNewIssues) {
         $query .= "AND status > $status_new ";
      }

      $query .= "ORDER BY id DESC";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         return;
      }

      $issueList = array();
      while($row = mysql_fetch_object($result))
      {
         $issue = IssueCache::getInstance()->getIssue($row->id);
         $issueList[$row->id] = $issue;
      }

      $logger->debug("Team::getCurrentIssues(teamid=$teamid) nbIssues=".count($issueList));
      return $issueList;
   }



   // -------------------------------------------------------
   /**
    *
    * @param unknown_type $memberid
    * @param unknown_type $arrivalTimestamp
    * @param unknown_type $memberAccess
    */
   public function addMember($memberid, $arrivalTimestamp, $memberAccess) {
      $query = "INSERT INTO `codev_team_user_table`  (`user_id`, `team_id`, `arrival_date`, `departure_date`, `access_level`) ".
               "VALUES ('$memberid','$this->id','$arrivalTimestamp', '0', '$memberAccess');";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }


   // -------------------------------------------------------
   public function setMemberDepartureDate($memberid, $departureTimestamp) {
     $query = "UPDATE `codev_team_user_table` SET departure_date = $departureTimestamp WHERE user_id = $memberid AND team_id = $this->id;";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }


   // -------------------------------------------------------
   /**
    * add all members declared in Team $src_teamid (same dates, same access)
    * users already declared are omitted
    *
    * @param unknown_type $src_teamid
    */
   public function addMembersFrom($src_teamid) {

      $query = "SELECT * from `codev_team_user_table` WHERE team_id = $src_teamid ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $user = UserCache::getInstance()->getUser($row->user_id);
         if (! $user->isTeamMember($this->id)) {
            $this->addMember($row->user_id,$row->arrival_date, $row->access_level);

            if (NULL != $row->departure_date) {
               $this->setMemberDepartureDate($row->user_id, $row->departure_date);
            }
         }
      }

   }

   // -------------------------------------------------------
   /**
    *
    * @param unknown_type $projectid
    * @param unknown_type $projecttype
    */
   public function addProject($projectid, $projecttype) {
      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$projectid','$this->id','$projecttype');";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   // -------------------------------------------------------
   /**
    *
    */
   public function addExternalTasksProject() {

      $extTasksProjectType = Project::type_noStatsProject;

      $externalTasksProject = Config::getInstance()->getValue(Config::id_externalTasksProject);

      // TODO check if ExternalTasksProject not already in table !

      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$externalTasksProject','$this->id','$extTasksProjectType');";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   // -------------------------------------------------------
   /**
    *
    * @param unknown_type $projectName
    * @return unknown_type $projectId
    */
   public function createSideTaskProject($projectName) {

      $sideTaskProjectType = Project::type_sideTaskProject;

      $projectDesc = "CoDev SideTaskProject for team $this->name";

      $projectid = Project::createSideTaskProject($projectName);

      if (-1 != $projectid) {

         // add new SideTaskProj to the team
         $query = "INSERT INTO `codev_team_project_table` (`project_id`, `team_id`, `type`) ".
                  "VALUES ('$projectid','$this->id','$sideTaskProjectType');";
         $result = mysql_query($query);
         if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
         }

      } else {
        $this->logger->error("team $name createSideTaskProject !!!");
        echo "<span style='color:red'>ERROR: team $name createSideTaskProject !!!</span>";
        exit;
      }

      // --- assign SideTaskProject specific Job
      #REM: 'N/A' job_id = 1, created by SQL file
      Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);

      return $projectid;
   }

   // -------------------------------------------------------
   /**
    *
    * @param unknown_type $date_create
    */
   public function setCreationDate($date) {

      $query = "UPDATE `codev_team_table` SET date = $date WHERE id = $this->id;";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }


   // -----------------------------------------------
   public function getProjectType($projectid) {
      return  $this->projTypeList[$projectid];
   }

   // -----------------------------------------------
   public function isSideTasksProject($projectid) {
      $this->logger->debug("isSideTasksProject:  team $this->id proj $projectid type ".$this->projTypeList[$projectid]);
      return (Project::type_sideTaskProject == $this->projTypeList[$projectid]);
   }

   // -----------------------------------------------
   public function isNoStatsProject($projectid) {
      return (Project::type_noStatsProject == $this->projTypeList[$projectid]);
   }



}

?>
