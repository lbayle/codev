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

class ConsistencyError2 implements Comparable {

   const severity_error = 3;
   const severity_warn = 2;
   const severity_info = 1;

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

   public $bugId;
   public $userId;
   public $teamId;
   public $desc;
   public $timestamp;
   public $status;
   public $severity;

   public function __construct($bugId, $userId, $status, $timestamp, $desc, $severity = self::severity_error, $rawValue = null) {
      $this->bugId = $bugId;
      $this->userId = $userId;
      $this->status = $status;
      $this->timestamp = $timestamp;
      $this->desc = $desc;

      $this->severity = $severity;
      $this->rawValue = $rawValue;
   }

   /**
    * @return string
    */
   public function getLiteralSeverity() {
      switch ($this->severity) {
         case self::severity_error:
            return T_("Error");
         case self::severity_warn:
            return T_("Warning");
         case self::severity_info:
            return T_("Info");
         default:
            return T_("unknown");
      }
   }

   /**
    * @return string
    */
   public function getSeverityColor() {
      switch ($this->severity) {
         case self::severity_error:
            return "color:red";
         case self::severity_warn:
            return "color:orange";
         case self::severity_info:
            return "color:black";
         default:
            return "color:black";
      }
   }

   /**
    * QuickSort compare method.
    * returns true if $this has higher severity than $cerrB
    *
    * @param ConsistencyError2 $cerrB the object to compare to
    * @return bool
    */
   function compareTo($cerrB) {
      if ($this->severity < $cerrB->severity) {
         return false;
      }
      if ($this->severity > $cerrB->severity) {
         return true;
      }

      if ($this->bugId > $cerrB->bugId) {
         return false;
      } else {
         return true;
      }
   }

   /**
    * uSort compare method
    *
    *
    * @param Comparable $activityA
    * @param Comparable $activityB
    *
    * @return '1' if $activityB > $activityA, -1 if $activityB is lower, 0 if equals
    */
   public static function compare(Comparable $cerrA, Comparable $cerrB) {
      if ($cerrA->severity < $cerrB->severity) {
         return 1;
      } else if ($cerrA->severity > $cerrB->severity) {
         return -1;
      }
      if (0 == $cerrA->userId) {
         return 1;
      } else if (0 == $cerrB->userId) {
         return -1;
      }


      if ($cerrA->bugId > $cerrB->bugId) {
         return 1;
      } else {
         return -1;
      }
      return 0;
   }

}

ConsistencyError2::staticInit();

class ConsistencyCheck2 {

   /**
    * @var Logger The logger
    */
   private static $logger;

   public static $defaultCheckList;
   public static $checkDescriptionList;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$defaultCheckList = array(
          'checkBacklogOnResolved' => 1,
          'checkBadBacklog' => 1,
          'checkEffortEstim' => 1,
          'checkTimeTracksOnNewIssues' => 1,
          'checkIssuesNotInCommand' => 0,
          'checkIssuesNotInMultipleCommands' => 1,
          'checkCommandsNotInCommandset' => 0,
          'checkCommandSetNotInServiceContract' => 0,
          'checkUnassignedTasks' => 0,
          'checkTimetracksOnRemovedIssues' => 0,
          #'checkDeliveryDate' => 0,
          'checkIssuesNotInTeamProjects' => 1,
         );

      self::$checkDescriptionList = array(
          'checkBacklogOnResolved' => T_('Backlog on resolved issues should be 0'),
          'checkBadBacklog' => T_('Backlog on unresolved issues should not be 0'),
          'checkEffortEstim' => T_('EffortEstim should not be 0'),
          'checkTimeTracksOnNewIssues' => T_('There should be no timetracks on "new" issues '),
          'checkIssuesNotInCommand' => T_('Issues should be referenced in a Command'),
          'checkIssuesNotInMultipleCommands' => T_('Issues should not be referenced in multiple commands'),
          'checkCommandsNotInCommandset' => T_('Commands should be referenced in a CommandSet'),
          'checkCommandSetNotInServiceContract' => T_('CommandSets should be referenced in a ServiceContract'),
          'checkUnassignedTasks' => T_('Issues should be assigned to someone'),
          'checkTimetracksOnRemovedIssues' => T_('Check timetracks on removed issues (not needed if Mantis plugin is enabled)'), // for all timetracks of the team, check that the Mantis issue exist.
          'checkIssuesNotInTeamProjects' => T_("Command issues should belong to the team's project-list"),
         );



      /*
       * checkMgrEffortEstim
       * It is now allowed to have MgrEE = 0
       *   tasks having MgrEE > 0 are tasks that have been initialy defined at the Command's creation.
       *   tasks having MgrEE = 0 are internal_tasks
       */

   }

   /**
    * @var Issue[] The issues list
    */
   protected $issueList;
   protected $bugidList;
   protected $formattedBugidList; // "123,234,456" (used in SQL requests)
   protected $checkList;

   /**
    * @var int The team id
    */
   protected $teamId;

   function __construct(array $issueList, $teamId=NULL) {
      $this->issueList = $issueList;
      $this->teamId = $teamId;

      $this->bugidList = array();
      foreach ($issueList as $issue) {
         $this->bugidList[] = $issue->getId();
      }
      $this->formattedBugidList = implode(', ', $this->bugidList);

      // teamid is not mandatory !
      if (!is_null($this->teamId)) {
         $team = TeamCache::getInstance()->getTeam($this->teamId);
         $this->checkList = $team->getConsistencyCheckList();
      } else {
         $this->checkList = self::$defaultCheckList;
      }

   }

   /**
    * perform all consistency checks
    *
    * @param array $checkList override team's checkList
    * @return type
    */
   public function check(array $checkList = NULL) {

      $cerrList = array(); // if null, array_merge fails !

      if (is_null($checkList)) { $checkList = $this->checkList; }

      // each key of the checkList is a methodName
      $reflectionObject = new ReflectionObject($this);
      foreach ($checkList as $callback => $isEnabled) {

         if (0 != $isEnabled) {
            try {
               $method = $reflectionObject->getMethod($callback);
               $tmpCerrList = $method->invoke($this);
            } catch (Exception $e) {
               self::$logger->error("unknown method: $callback");
            }
            $cerrList = array_merge($cerrList, $tmpCerrList);
         }
      }

      // PHP Fatal error:  Maximum function nesting level of '100' reached, aborting!
      ini_set('xdebug.max_nesting_level', 1000);

      Tools::usort($cerrList);
      return $cerrList;
   }

   /**
    * fiches resolved dont le RAF != 0
    * @return ConsistencyError2[]
    */
   public function checkBacklogOnResolved() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if (!$issue->isResolved()) {
            continue;
         }

         if (0 != $issue->getBacklog()) {
            $cerr = new ConsistencyError2($issue->getId(),
               $issue->getHandlerId(),
               $issue->getCurrentStatus(),
               $issue->getLastUpdate(),
               T_("Backlog should be 0 (not ".$issue->getBacklog().")."));
            $cerr->severity = ConsistencyError2::severity_warn;

            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

   /**
    * tasks NOT resolved with RAE == 0
    * @return ConsistencyError2[]
    */
   public function checkBadBacklog() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if ((!$issue->isResolved()) &&
            ($issue->getCurrentStatus() > Constants::$status_new) &&
            (is_null($issue->getBacklog()) || ($issue->getBacklog() <= 0))) {
            if (is_null($issue->getBacklog())) {
               $msg = T_("Backlog must be defined !");
            } else {
               $msg = T_("Backlog == 0: Backlog may not be up to date.");
            }

            $cerr = new ConsistencyError2($issue->getId(),
               $issue->getHandlerId(),
               $issue->getCurrentStatus(),
               $issue->getLastUpdate(),
               $msg);
            $cerr->severity = ConsistencyError2::severity_error;

            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   /**
    * a mgrEffortEstim should be defined when creating an Issue.
    * @return ConsistencyError2[]
    */
   public function checkMgrEffortEstim() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if ($issue->isResolved()) {
            continue;
         }

         // exclude SideTasks (effortEstimation is not relevant)
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);
         try {
            if ($project->isSideTasksProject($teamList)) { continue; }
         } catch (Exception $e) {
            self::$logger->error("checkMgrEffortEstim(): issue ".$issue->getId()." not checked : ".$e->getMessage());
            continue;
         }

         if ((NULL   == $issue->getMgrEffortEstim()) ||
            ('' == $issue->getMgrEffortEstim())     ||
            ('0' == $issue->getMgrEffortEstim())) {

            $cerr = new ConsistencyError2($issue->getId(),
               $issue->getHandlerId(),
               $issue->getCurrentStatus(),
               $issue->getLastUpdate(),
               T_("MgrEffortEstim not set."));
            $cerr->severity = ConsistencyError2::severity_error;

            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   /**
    * EffortEstim should be defined when status > new.
    * @return ConsistencyError2[]
    */
   public function checkEffortEstim() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if ($issue->isResolved()) { continue; }
         if ($issue->getCurrentStatus() == Constants::$status_new) { continue; }

         // exclude SideTasks (effortEstimation is not relevant)
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);
         try {
            if ($project->isSideTasksProject($teamList)) { continue; }
         } catch (Exception $e) {
            self::$logger->error("checkEffortEstim(): issue ".$issue->getId()." not checked : ".$e->getMessage());
            continue;
         }

         if ((NULL == $issue->getEffortEstim()) || ('' == $issue->getEffortEstim()) || ('0' == $issue->getEffortEstim())) {
            $cerr = new ConsistencyError2($issue->getId(), $issue->getHandlerId(), $issue->getCurrentStatus(),
               $issue->getLastUpdate(), T_("EffortEstim not set."));
            $cerr->severity = ConsistencyError2::severity_error;

            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   /**
    * if you spend some time on a task,
    * then it's status is probably 'ack' or 'open' but certainly not 'new'
    * @return ConsistencyError2[]
    */
   function checkTimeTracksOnNewIssues() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         // select all issues which current status is 'new'
         if ($issue->getCurrentStatus() != Constants::$status_new) { continue; }

         $elapsed = $issue->getElapsed();

         if (0 != $elapsed) {
            $cerr = new ConsistencyError2($issue->getId(), $issue->getHandlerId(), $issue->getCurrentStatus(),
               $issue->getLastUpdate(),
               T_("Status should not be")." '".Constants::$statusNames[Constants::$status_new]."' (".T_("elapsed")." = ".$elapsed.")");
            $cerr->severity = ConsistencyError2::severity_error;

            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

   /**
    * check if some tasks are not assigned
    * @return ConsistencyError2[]
    */
   public function checkUnassignedTasks() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         // exclude SideTasks (persistant tasks are not assigned)
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);

         try {
            if (($project->isSideTasksProject($teamList)) || ($project->isNoStatsProject($teamList))) {
               continue;
            }
         } catch (Exception $e) {
            self::$logger->error("checkUnassignedTasks(): issue ".$issue->getId()." not checked : ".$e->getMessage());
            continue;
         }

         // if resolved, then it's not so important
         if ($issue->isResolved()) {
            continue;
         }

         if ((NULL == $issue->getHandlerId()) || (0 == $issue->getHandlerId())) {
            $cerr = new ConsistencyError2($issue->getId(), $issue->getHandlerId(), $issue->getCurrentStatus(),
               $issue->getLastUpdate(), T_("The task is not assigned to anybody."));
            $cerr->severity = ConsistencyError2::severity_warn;

            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }


   /**
    * Check issues that are not referenced in a Command (warn)
    *
    * Note: SideTasks not checked (they are directly added into the ServiceContract)
    *
    *  @return ConsistencyError2[]
    */
   public function checkIssuesNotInCommand() {
      $cerrList = array();

      if(count($this->issueList) > 0) {

         $sql = AdodbWrapper::getInstance();
         $query = "SELECT bug_id, COUNT(command_id) as count FROM codev_command_bug_table".
                  " WHERE bug_id IN (".$this->formattedBugidList.") GROUP BY bug_id;";
         $result = $sql->sql_query($query);

         $commandsByIssue = array();
         while($row = $sql->fetchObject($result)) {
            $commandsByIssue[$row->bug_id] = $row->count;
         }

         foreach ($this->issueList as $issue) {
            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());

            $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);

            try {
               if (($project->isSideTasksProject($teamList)) || ($project->isNoStatsProject($teamList))) {
                  // exclude SideTasks: they are not referenced in a command,
                  // they are directly added into the ServiceContract
                  continue;
               }
            } catch (Exception $e) {
               self::$logger->error("checkIssuesNotInCommand(): issue ".$issue->getId()." not checked : ".$e->getMessage());
               continue;
            }

            $nbTuples = 0;
            if(array_key_exists($issue->getId(), $commandsByIssue)) {
               $nbTuples = $commandsByIssue[$issue->getId()];
            }
            if (0 == $nbTuples) {
               $cerr = new ConsistencyError2($issue->getId(), $issue->getHandlerId(), $issue->getCurrentStatus(),
                  $issue->getLastUpdate(), T_("The task is not referenced in any Command."));
               $cerr->severity = ConsistencyError2::severity_warn;
               $cerrList[] = $cerr;
            }
         }
      }

      return $cerrList;
   }

   /**
    * Check issues referenced in more than one Command (warn)
    *
    * Note: SideTasks not checked (they are directly added into the ServiceContract)
    *
    *  @return ConsistencyError2[]
    */
   public function checkIssuesNotInMultipleCommands() {
      $cerrList = array();

      if(count($this->issueList) > 0) {

         $sql = AdodbWrapper::getInstance();
         $query = "SELECT bug_id, COUNT(command_id) as count FROM codev_command_bug_table".
            " WHERE bug_id IN (".$this->formattedBugidList.") GROUP BY bug_id";
         $result = $sql->sql_query($query);

         $commandsByIssue = array();
         while($row = $sql->fetchObject($result)) {
            $commandsByIssue[$row->bug_id] = $row->count;
         }

         foreach ($this->issueList as $issue) {
            $project = ProjectCache::getInstance()->getProject($issue->getProjectId());

            $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);

            try {
               if (($project->isSideTasksProject($teamList)) || ($project->isNoStatsProject($teamList))) {
                  // exclude SideTasks: they are not referenced in a command,
                  // they are directly added into the ServiceContract
                  continue;
               }
            } catch (Exception $e) {
               self::$logger->error("checkIssuesNotInCommand(): issue ".$issue->getId()." not checked : ".$e->getMessage());
               continue;
            }

            $nbTuples = 0;
            if(array_key_exists($issue->getId(), $commandsByIssue)) {
               $nbTuples = $commandsByIssue[$issue->getId()];
            }

            if ($nbTuples > 1) {

               // a task referenced in 2 Commands is not error if in two != teams
               $query = "SELECT team_id FROM codev_command_table, codev_command_bug_table "
                       . "WHERE codev_command_table.id = codev_command_bug_table.command_id "
                       . " AND codev_command_bug_table.bug_id = ".$sql->db_param();
               $result = $sql->sql_query($query, array($issue->getId()));

               $tmpTeamId = 0;
               while($row = $sql->fetchObject($result)) {
                  if (0 == $tmpTeamId) {
                     $tmpTeamId = $row->team_id;
                  } else {
                     if ($tmpTeamId == $row->team_id) {
                        # issue in 2 commands of the same team is a warning
                        $cerr = new ConsistencyError2($issue->getId(), $issue->getHandlerId(), $issue->getCurrentStatus(),
                        $issue->getLastUpdate(), T_("The task is referenced in $nbTuples Commands."));
                        $cerr->severity = ConsistencyError2::severity_warn;
                        $cerrList[] = $cerr;
                        break;
                     }
                  }
               }
            }
         }
      }

      return $cerrList;
   }

   /**
    * Find Commands that are not referenced in any CommandSet
    *
    * @return ConsistencyError2[]
    */
   public function checkCommandsNotInCommandset() {
      $cerrList = array();
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT id, name, reference FROM codev_command_table ".
               "WHERE team_id =  ".$sql->db_param().
               " AND id NOT IN (SELECT command_id FROM codev_commandset_cmd_table) ";
      $result = $sql->sql_query($query, array($this->teamId));

      while($row = $sql->fetchObject($result)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Command")." \"$row->reference $row->name\" ".T_("is not referenced in any CommandSet"));
         $cerr->severity = ConsistencyError2::severity_info;

         $cerrList[] = $cerr;
      }

      return $cerrList;
   }

   /**
    * Find CommandSets that are not referenced in any ServiceContract
    *
    * @return ConsistencyError2[]
    */
   public function checkCommandSetNotInServiceContract() {
      $cerrList = array();
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT id, name, reference FROM codev_commandset_table ".
               "WHERE team_id =  ".$sql->db_param().
               " AND id NOT IN (SELECT commandset_id FROM codev_servicecontract_cmdset_table) ";
      $result = $sql->sql_query($query, array($this->teamId));

      while($row = $sql->fetchObject($result)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("CommandSet")." \"$row->reference $row->name\" ".T_("is not referenced in any ServiceContract"));
         $cerr->severity = ConsistencyError2::severity_info;

         $cerrList[] = $cerr;
      }

      return $cerrList;
   }


   /**
    * for all timetracks of the team, check that the Mantis issue exist.
    * @return ConsistencyError2[]
    */
   public function checkTimetracksOnRemovedIssues() {
      $cerrList = array();
      $sql = AdodbWrapper::getInstance();

      if (NULL != $this->teamId) {
         $team = TeamCache::getInstance()->getTeam($this->teamId);

         $userList = $team->getMembers();
         $formatedUsers = implode( ', ', array_keys($userList));

         $query = "SELECT * ".
                  "FROM codev_timetracking_table ".
                  "WHERE date >= ".$sql->db_param()." ".
                  " AND userid IN (".$formatedUsers.") ";
         #" AND    0 = (SELECT COUNT(id) FROM {bug} WHERE id='codev_timetracking_table.bugid' ) ";

         $result = $sql->sql_query($query, array($team->getDate()));

         while($row = $sql->fetchObject($result)) {
            if (!Issue::exists($row->bugid)) {
               $cerr = new ConsistencyError2($row->bugid, $row->userid, NULL,
                  $row->date, T_("Timetrack found on a task that does not exist in Mantis DB (duration = $row->duration)."));
               $cerr->severity = ConsistencyError2::severity_error;

               $cerrList[] = $cerr;
            }
         }
      }

      return $cerrList;
   }


   /**
    * for all users of the team, return incomplete/missing days in the period
    *
    * @param TimeTracking $timeTracking
    * @return ConsistencyError2[]
    */
   public static function checkIncompleteDays(TimeTracking $timeTracking, $userid = NULL) {
      $cerrList = array();
      $now = time();

      if (is_null($userid)) {
         $mList = TeamCache::getInstance()->getTeam($timeTracking->getTeamid())->getActiveMembers();
         $useridList = array_keys($mList);
      } else {
         $useridList = array($userid);
      }
      foreach($useridList as $userid) {

         $incompleteDays = $timeTracking->checkCompleteDays($userid, TRUE);
         foreach ($incompleteDays as $date => $value) {

            if ($date > $now) { continue; } // skip dates in the future

            $label = NULL;
            $missing = 0;
            if ($value < 1) {
               $missing = (1-$value);
               $label = T_("incomplete (missing ").(1-$value).T_(" days").")";
               $severity=ConsistencyError2::severity_error;
            } else {
               $missing = 0;
               $label = T_("inconsistent")." (".($value)." ".T_("days").")";
               $severity=ConsistencyError2::severity_warn;
            }

            $cerr = new ConsistencyError2(NULL, $userid, NULL, $date, $label, $severity, $missing);
            //$cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }

         $missingDays = $timeTracking->checkMissingDays($userid);
         $missing = 1;
         $label=T_("not defined.");
         foreach ($missingDays as $date) {

            if ($date > $now) { continue; } // skip dates in the future

            $cerr = new ConsistencyError2(0, $userid, NULL, $date, $label, ConsistencyError2::severity_error, $missing);
            //$cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   public static function checkMantisDefaultProjectWorkflow() {
      $cerrList = array();
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT * FROM {config} ".
               "WHERE project_id = 0 ".
               " AND config_id = 'status_enum_workflow' ";

      $result = $sql->sql_query($query);

      if (0 == $sql->getNumRows($result)) {
         if (!is_array(Constants::$status_enum_workflow)) {
            $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL, T_("No default project workflow defined, check config file: ".Constants::$config_file));
            $cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   public static function checkDatabaseVersion() {
      // check DB version
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT * from codev_config_table WHERE config_id = 'database_version' ";
      $result = $result = $sql->sql_query($query);
      $row = $sql->fetchObject($result);
      $currentDatabaseVersion=$row->value;

      $cerrList = array();
      if ($currentDatabaseVersion != Config::databaseVersion) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Database version: $currentDatabaseVersion (expected:".Config::databaseVersion.
               ") Please run <a href='tools/update_codevtt.php'>update_codevtt.php</a>"));
         $cerr->severity = ConsistencyError2::severity_error;
         $cerrList[] = $cerr;
      }
      return $cerrList;
   }

   /**
    * Commands are a set of tasks, there should not be tasks not assigned to the team
    */
   public function checkIssuesNotInTeamProjects() {
      $cerrList = array();
      if (0 != $this->teamId) {
         $team = TeamCache::getInstance()->getTeam($this->teamId);
         $teamProjects = $team->getProjects();

         if(count($this->issueList) > 0) {
            foreach ($this->issueList as $issue) {
               if (!array_key_exists($issue->getProjectId(), $teamProjects)) {
                  $cerr = new ConsistencyError2($issue->getId(), NULL, NULL, NULL, T_("Project not defined in the team's project-list"));
                  $cerr->severity = ConsistencyError2::severity_error;
                  $cerrList[] = $cerr;
               }
            }
         }
      }
      return $cerrList;
   }

   /**
    * since v1.7.0 config files have moved to ./config
    */
   public static function checkConfigFiles() {

      $cerrList = array();
      if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.ini')) {
      //if (file_exists(Constants::$config_file_old)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Please move config file to: ").dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.ini');
         $cerr->severity = ConsistencyError2::severity_error;
         $cerrList[] = $cerr;
      } else if (file_exists(Constants::$config_file_old)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Please remove: ").Constants::$config_file_old);
         $cerr->severity = ConsistencyError2::severity_warn;
         $cerrList[] = $cerr;
      }

      if (!file_exists(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'log4php.xml')) {
      //if (file_exists(Constants::$config_file_old)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Please move log4php file to: ").dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'log4php.xml');
         $cerr->severity = ConsistencyError2::severity_error;
         $cerrList[] = $cerr;
      } else if (file_exists(Constants::$log4php_file_old)) {
         $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
            T_("Please remove: ").Constants::$log4php_file_old);
         $cerr->severity = ConsistencyError2::severity_warn;
         $cerrList[] = $cerr;
      }

      return $cerrList;
   }
}

ConsistencyCheck2::staticInit();

