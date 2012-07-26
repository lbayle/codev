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

include_once "constants.php";

require_once('Logger.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "project.class.php";
include_once "project_cache.class.php";

class ConsistencyError2 {

   const  severity_error = 3;
   const  severity_warn  = 2;
   const  severity_info  = 1;

   /**
    * @var Logger The logger
    */
   private $logger; // TODO static

   public $bugId;
   public $userId;
   public $teamId;
   public $desc;
   public $timestamp;
   public $status;

   public $severity; // unused

   public function __construct($bugId, $userId, $status, $timestamp, $desc) {
      $this->logger = Logger::getLogger(__CLASS__); // TODO static

      $this->bugId     = $bugId;
      $this->userId    = $userId;
      $this->status    = $status;
      $this->timestamp = $timestamp;
      $this->desc      = $desc;

      $this->severity = ConsistencyError2::severity_error;
   }

   /**
    * @return string
    */
   public function getLiteralSeverity() {
      switch ($this->severity) {
         case ConsistencyError2::severity_error:
            return T_("Error");
         case ConsistencyError2::severity_warn:
            return T_("Warning");
         case ConsistencyError2::severity_info:
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
         case ConsistencyError2::severity_error:
            return "color:red";
         case ConsistencyError2::severity_warn:
            return "color:orange";
         case ConsistencyError2::severity_info:
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
         $this->logger->debug("activity.compareTo FALSE (".$this->bugId.'-'.$this->getLiteralSeverity()." <  ".$cerrB->bugId.'-'.$cerrB->getLiteralSeverity().")");
         return false;
      }
      if ($this->severity > $cerrB->severity) {
         $this->logger->debug("activity.compareTo TRUE (".$this->bugId.'-'.$this->getLiteralSeverity()." >  ".$cerrB->bugId.'-'.$cerrB->getLiteralSeverity().")");
         return true;
      }

      if ($this->bugId > $cerrB->bugId) {
         $this->logger->debug("activity.compareTo FALSE (".$this->bugId." >  ".$cerrB->bugId.")");
         return false;
      } else {
         $this->logger->debug("activity.compareTo TRUE  (".$this->bugId." <= ".$cerrB->bugId.")");
         return true;
      }
   }

}

class ConsistencyCheck2 {

   /**
    * @var Logger The logger
    */
   protected $logger;

   /**
    * @var Issue[] The issues list
    */
   protected $issueList;

   /**
    * @var int The team id
    */
   protected $teamId;

   function __construct(array $issueList, $teamId=NULL) {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->issueList = $issueList;
      $this->teamId    = $teamId;
   }

   /**
    * perform all consistency checks
    * @return ConsistencyError2[]
    */
   public function check() {
      #$this->logger->debug("checkResolved");
      $cerrList2 = $this->checkResolved();

      #$cerrList3 = $this->checkDeliveryDate();

      #$this->logger->debug("checkBadRemaining");
      $cerrList4 = $this->checkBadRemaining();

      /*
       * It is now allowed to have MgrEE = 0
       *   tasks having MgrEE > 0 are tasks that have been initialy defined at the Command's creation.
       *   tasks having MgrEE = 0 are internal_tasks
       *

            #$this->logger->debug("checkMgrEffortEstim");
            $cerrList5 = $this->checkMgrEffortEstim();
      */

      #$this->logger->debug("checkEffortEstim");
      $cerrList5 = $this->checkEffortEstim();

      #$this->logger->debug("checkTimeTracksOnNewIssues");
      $cerrList6 = $this->checkTimeTracksOnNewIssues();

      $cerrList7 = $this->checkUnassignedTasks();

      $cerrList8 = $this->checkIssuesNotInCommand();


      #$this->logger->debug("done.");

      #$cerrList = array_merge($cerrList2, $cerrList4, $cerrList5, $cerrList6);
      $cerrList = array_merge($cerrList2, $cerrList4, $cerrList5, $cerrList6, $cerrList7, $cerrList8);

      // PHP Fatal error:  Maximum function nesting level of '100' reached, aborting!
      ini_set('xdebug.max_nesting_level', 300);

      $sortedCerrList = qsort($cerrList);

      return $sortedCerrList;
   }

   /**
    * fiches resolved dont le RAE != 0
    * @return ConsistencyError2[]
    */
   public function checkResolved() {
      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if (!$issue->isResolved()) { continue; }

         if (0 != $issue->remaining) {
            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
               T_("Remaining should be 0 (not $issue->remaining)."));
            $cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

   /**
    * tasks NOT resolved with RAE == 0
    * @return ConsistencyError2[]
    */
   public function checkBadRemaining() {
      global $status_new;

      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if ((!$issue->isResolved()) &&
            ($issue->currentStatus > $status_new) &&
            ((NULL == $issue->remaining) || ($issue->remaining <= 0))) {
            if (NULL == $issue->remaining) {
               $msg = T_("Remaining must be defined !");
            } else {
               $msg = T_("Remaining == 0: Remaining may not be up to date.");
            }

            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
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
         if ($issue->isResolved()) { continue; }

         // exclude SideTasks (effortEstimation is not relevant)
         $project = ProjectCache::getInstance()->getProject($issue->projectId);
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);
         try {
            if ($project->isSideTasksProject($teamList)) { continue; }
         } catch (Exception $e) {
            $this->logger->error("checkMgrEffortEstim(): issue $issue->bugId not checked : ".$e->getMessage());
            continue;
         }

         if ((NULL   == $issue->mgrEffortEstim) ||
            ('' == $issue->mgrEffortEstim)     ||
            ('0' == $issue->mgrEffortEstim)) {

            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
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
      global $status_new;

      $cerrList = array();

      foreach ($this->issueList as $issue) {
         if ($issue->isResolved()) { continue; }
         if ($issue->currentStatus == $status_new) { continue; }

         // exclude SideTasks (effortEstimation is not relevant)
         $project = ProjectCache::getInstance()->getProject($issue->projectId);
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);
         try {
            if ($project->isSideTasksProject($teamList)) { continue; }
         } catch (Exception $e) {
            $this->logger->error("checkEffortEstim(): issue $issue->bugId not checked : ".$e->getMessage());
            continue;
         }

         if ((NULL == $issue->effortEstim) || ('' == $issue->effortEstim) || ('0' == $issue->effortEstim)) {
            $cerr = new ConsistencyError2($issue->bugId, $issue->handlerId, $issue->currentStatus,
               $issue->last_updated, T_("EffortEstim not set."));
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
      global $status_new;
      global $statusNames;

      $cerrList = array();

      foreach ($this->issueList as $issue) {
         // select all issues which current status is 'new'
         if ($issue->currentStatus != $status_new) { continue; }

         $elapsed = $issue->getElapsed();

         if (0 != $elapsed) {
            $cerr = new ConsistencyError2($issue->bugId, $issue->handlerId, $issue->currentStatus,
               $issue->last_updated,
               T_("Status should not be")." '".$statusNames[$status_new]."' (".T_("elapsed")." = ".$elapsed.")");
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
         $project = ProjectCache::getInstance()->getProject($issue->projectId);
         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);

         try {
            if (($project->isSideTasksProject($teamList)) || ($project->isNoStatsProject($teamList))) {
               continue;
            }
         } catch (Exception $e) {
            $this->logger->error("checkUnassignedTasks(): issue $issue->bugId not checked : ".$e->getMessage());
            continue;
         }

         // if resolved, then it's not so important
         if ($issue->isResolved()) {
            continue;
         }

         if ((NULL == $issue->handlerId) || (0 == $issue->handlerId)) {
            $cerr = new ConsistencyError2($issue->bugId, $issue->handlerId, $issue->currentStatus,
               $issue->last_updated, T_("The task is not assigned to anybody."));
            $cerr->severity = ConsistencyError2::severity_warn;
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }


   /**
    * Check issues that are not referenced in a Command (error)
    * Check issues referenced in more than one Command (warning)
    * 
    * Note: SideTasks not checked (they are directly added into the ServiceContract)
    *
    *  @return ConsistencyError2[]
    */
   public function checkIssuesNotInCommand() {

      $cerrList = array();

      foreach ($this->issueList as $issue) {
         $project = ProjectCache::getInstance()->getProject($issue->projectId);

         $teamList = (NULL == $this->teamId) ? NULL: array($this->teamId);

         try {
            if (($project->isSideTasksProject($teamList)) || ($project->isNoStatsProject($teamList))) {
               // exclude SideTasks: they are not referenced in a command,
               // they are directly added into the ServiceContract
               continue;
            }
         } catch (Exception $e) {
            $this->logger->error("checkIssuesNotInCommand(): issue $issue->bugId not checked : ".$e->getMessage());
            continue;
         }

         $query  = "SELECT COUNT(command_id) FROM `codev_command_bug_table` WHERE bug_id=$issue->bugId ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
        $nbTuples  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;

        if (0 == $nbTuples) {
            $cerr = new ConsistencyError2($issue->bugId, $issue->handlerId, $issue->currentStatus,
               $issue->last_updated, T_("The task is not referenced in any Command."));
            $cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
        } else if ($nbTuples > 1) {
            $cerr = new ConsistencyError2($issue->bugId, $issue->handlerId, $issue->currentStatus,
               $issue->last_updated, T_("The task is referenced in $nbTuples Commands."));
            $cerr->severity = ConsistencyError2::severity_warn;
            $cerrList[] = $cerr;
        }
        $this->logger->debug("checkIssuesNotInCommand(): issue $issue->bugId referenced in $nbTuples Commands.");
      }
      return $cerrList;
   }

   /**
    * Find Commands that are not referenced in any CommandSet
    * 
    * @return array 
    */
   public function checkCommandsNotInCommandset() {

      $cerrList = array();

         $query  = "SELECT id, name, reference FROM `codev_command_table` ".
                   "WHERE team_id = $this->teamId ".
                   "AND id NOT IN (SELECT command_id FROM `codev_commandset_cmd_table`) ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

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
    * @return array
    */
   public function checkCommandSetNotInServiceContract() {

      $cerrList = array();

         $query  = "SELECT id, name, reference FROM `codev_commandset_table` ".
                   "WHERE team_id = $this->teamId ".
                   "AND id NOT IN (SELECT commandset_id FROM `codev_servicecontract_cmdset_table`) ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

               $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL,
                       T_("CommandSet")." \"$row->reference $row->name\" ".T_("is not referenced in any ServiceContract"));
               $cerr->severity = ConsistencyError2::severity_info;
               $cerrList[] = $cerr;
         }

      return $cerrList;
   }


   /**
    * for all timetracks of the team, check that the Mantis issue exist.
    */
   public function checkTeamTimetracks() {

      $cerrList = array();

      if (NULL != $this->teamId) {
         $team = TeamCache::getInstance()->getTeam($this->teamId);

         $userList = $team->getMembers();
         $formatedUsers = implode( ', ', array_keys($userList));

         $query     = "SELECT * ".
            "FROM  `codev_timetracking_table` ".
            "WHERE  codev_timetracking_table.date >= $team->date ".
            "AND    codev_timetracking_table.userid IN ($formatedUsers) ";
            #"AND    0 = (SELECT COUNT(id) FROM `mantis_bug_table` WHERE id='codev_timetracking_table.bugid' ) ";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

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
   public static function checkIncompleteDays(TimeTracking $timeTracking) {

      $cerrList = array();
      $now = time();

      $mList = Team::getActiveMemberList($timeTracking->team_id);

      foreach($mList as $userid => $username) {

         $incompleteDays = $timeTracking->checkCompleteDays($userid, TRUE);
         foreach ($incompleteDays as $date => $value) {

            if ($date > $now) { continue; } // skip dates in the future

            $label = NULL;
            if ($value < 1) {
               $label = T_("incomplete (missing ").(1-$value).T_(" days").")";
            } else {
               $label = T_("inconsistent")." (".($value)." ".T_("days").")";
            }

            $cerr = new ConsistencyError2(NULL, $userid, NULL, $date, $label);
            $cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }

         $missingDays = $timeTracking->checkMissingDays($userid);
         foreach ($missingDays as $date) {

            if ($date > $now) { continue; } // skip dates in the future

            $cerr = new ConsistencyError2(0, $userid, NULL, $date, T_("not defined."));
            $cerr->severity = ConsistencyError2::severity_error;
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

}

?>
