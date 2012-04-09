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

include_once "issue.class.php";
include_once "user.class.php";
include_once "project.class.php";

class ConsistencyError2 {

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
   }

   // ----------------------------------------------
   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $activityB
    *
    * @param GanttActivity $activityB the object to compare to
    */
   function compareTo($cerrB) {

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

   protected $logger;
   protected $issueList;

   function __construct($issueList) {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->issueList = $issueList;
   }

   // ----------------------------------------------
   /**
    * perform all consistency checks
    */
   public function check() {

      #$this->logger->debug("checkResolved");
      $cerrList2 = $this->checkResolved();

      #$cerrList3 = $this->checkDeliveryDate();

      #$this->logger->debug("checkBadRemaining");
      $cerrList4 = $this->checkBadRemaining();

      #$this->logger->debug("checkMgrEffortEstim");
      $cerrList5 = $this->checkMgrEffortEstim();

      #$this->logger->debug("checkTimeTracksOnNewIssues");
      $cerrList6 = $this->checkTimeTracksOnNewIssues();

      #$this->logger->debug("done.");

      #$cerrList = array_merge($cerrList2, $cerrList3, $cerrList4, $cerrList5);
      $cerrList = array_merge($cerrList2, $cerrList4, $cerrList5, $cerrList6);

      // PHP Fatal error:  Maximum function nesting level of '100' reached, aborting!
      ini_set('xdebug.max_nesting_level', 300);

      $sortedCerrList = qsort($cerrList);

      return $sortedCerrList;
   }

   // ----------------------------------------------
   /**
    * fiches resolved dont le RAE != 0
    */
   public function checkResolved() {

      $cerrList = array();


      foreach ($this->issueList as $bugid => $issue) {

         if (!$issue->isResolved()) { continue; }

         if (0 != $issue->remaining) {
            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
               T_("Remaining should be 0 (not $issue->remaining)."));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }

   // ----------------------------------------------
   /**
    * tasks NOT resolved with RAE == 0
    */
   public function checkBadRemaining() {
      global $status_new;

      $cerrList = array();

      foreach ($this->issueList as $bugid => $issue) {

         if ((!$issue->isResolved()) &&
             ($issue->currentStatus > $status_new) &&
             ($issue->remaining <= 0)) {

            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
               T_("Remaining == 0: Remaining may not be up to date."));
            $cerr->severity = T_("Warning");
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }

   // ----------------------------------------------
   /**
    * a mgrEffortEstim should be defined when creating an Issue.
    *
    */
   public function checkMgrEffortEstim() {

      $cerrList = array();

      foreach ($this->issueList as $bugid => $issue) {

        if ($issue->isResolved()) { continue; }

         // exclude SideTasks (effortEstimation is not relevant)
         $project = ProjectCache::getInstance()->getProject($issue->projectId);
         if ($project->isSideTasksProject()) { continue; }

         if ((NULL   == $issue->mgrEffortEstim) ||
               ('' == $issue->mgrEffortEstim)     ||
               ('0' == $issue->mgrEffortEstim)) {

            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
               T_("MgrEffortEstim not set."));
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }
      return $cerrList;
   }


   /**
    * if you spend some time on a task,
    * then it's status is probably 'ack' or 'open' but certainly not 'new'
    */
   function checkTimeTracksOnNewIssues() {

      global $status_new;
      global $statusNames;

      $cerrList = array();

      foreach ($this->issueList as $bugid => $issue) {

         // select all issues which current status is 'new'
         if ($issue->currentStatus != $status_new) { continue; }

         $elapsed = $issue->getElapsed();

         if (0 != $elapsed) {

            $cerr = new ConsistencyError2($issue->bugId,
               $issue->handlerId,
               $issue->currentStatus,
               $issue->last_updated,
               T_("Status should not be")." '".$statusNames[$status_new]."' (".T_("elapsed")." = ".$elapsed.")");
            $cerr->severity = T_("Error");
            $cerrList[] = $cerr;
         }
      }

      return $cerrList;
   }


}

?>