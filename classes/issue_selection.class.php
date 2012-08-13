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

include_once('classes/consistency_check2.class.php');
include_once('classes/issue_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class IssueSelection {

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
   
   public $name; // name for this selection
   public $elapsed;
   public $duration;
   public $durationMgr;
   public $mgrEffortEstim;
   public $effortEstim;
   public $effortAdd;

   /**
    * @var Issue[]
    */
   protected $issueList;
   protected $progress = NULL;
   protected $progressMgr = NULL;

   public function __construct($name = "no_name") {
      $this->name = $name;

      $this->elapsed = 0;
      $this->duration = 0;
      $this->durationMgr = 0;
      $this->mgrEffortEstim = 0;
      $this->effortEstim = 0;
      $this->effortAdd = 0;    // BS

      $this->issueList = array();
   }

   /**
    * add an array of Issue instances
    *
    * @param Issue[] $issueList array of Issue
    */
   public function addIssueList(array $issueList) {
      if (NULL != $issueList) {
         foreach ($issueList as $issue) {
            $this->addIssue($issue->bugId);
         }
      }
   }

   /**
    * @param int $bugid
    * @return bool true if added, false if not (already in list)
    * @exception if issue does not exist in mantis DB
    */
   public function addIssue($bugid) {
      $retCode = false;

      // do not add twice the same issue
      if (NULL == $this->issueList[$bugid]) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $this->issueList[$bugid] = $issue;
         $this->elapsed += $issue->getElapsed();
         $this->duration += $issue->getDuration();
         $this->durationMgr += $issue->getDurationMgr();
         $this->mgrEffortEstim += $issue->mgrEffortEstim;
         $this->effortEstim += $issue->effortEstim;
         $this->effortAdd += $issue->effortAdd;

         self::$logger->debug("IssueSelection [$this->name] : addIssue($bugid) version = <".$issue->getTargetVersion()."> MgrEE=".$issue->mgrEffortEstim." BI+BS=".($issue->effortEstim + $issue->effortAdd)." elapsed=".$issue->getElapsed()." RAF=".$issue->getDuration()." RAF_Mgr=".$issue->getDurationMgr()." drift=".$issue->getDrift()." driftMgr=".$issue->getDriftMgr());
         $retCode = true;
      }
      return $retCode;
   }

   /**
    * remove Issue from List.
    * the Issue itself is not deleted.
    *
    * @param int $bugid
    */
   public function removeIssue($bugid) {
      if (NULL != $this->issueList[$bugid]) {
         unset($this->issueList[$bugid]);
      } else {
         self::$logger->debug("IssueSelection [$this->name] : removeIssue($bugid) : Issue not found !");
      }
   }

   /**
    * @return int
    */
   public function getProgress() {
      if (NULL == $this->progress) {
         // compute total progress
         if (0 == $this->elapsed) {
            $this->progress = 0;  // if no time spent, then no work done.
         } elseif (0 == $this->duration) {
            $this->progress = 1;  // if no duration, then Project is 100% done.
         } else {
            $this->progress = $this->elapsed / $this->getReestimated();
         }

         self::$logger->debug("IssueSelection [$this->name] : progress = ".$this->progress." = $this->elapsed / ($this->elapsed + ".$this->duration.")");
      }

      return $this->progress;
   }

   /**
    * @return int
    */
   public function getProgressMgr() {
      if (NULL == $this->progressMgr) {
         // compute total progress

         if (0 == $this->elapsed) {
            $this->progressMgr = 0;  // if no time spent, then no work done.
         } elseif (0 == $this->durationMgr) {
            $this->progressMgr = 1;  // if no duration, then Project is 100% done.
         } else {
            $this->progressMgr = $this->elapsed / $this->getReestimatedMgr();
         }

         self::$logger->debug("IssueSelection [$this->name] : progressMgr = ".$this->progressMgr." = $this->elapsed / ($this->elapsed + ".$this->durationMgr.")");
      }

      return $this->progressMgr;
   }

   /**
    * reestimated = elapsed + duration
    * @return int
    */
   public function getReestimated() {
      return $this->elapsed + $this->duration;
   }

   /**
    * reestimated = elapsed + durationMgr
    * @return int
    */
   public function getReestimatedMgr() {
      return $this->elapsed + $this->durationMgr;
   }

   /**
    * @return Issue[]
    */
   public function getIssueList() {
      return $this->issueList;
   }

   /**
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return float
    */
   public function getElapsed($startTimestamp, $endTimestamp) {
      if(count($this->issueList) > 0) {
         $issueIds = implode(', ', array_keys($this->issueList));

         // for each issue, sum all its timetracks within period
         $query = "SELECT SUM(duration) FROM `codev_timetracking_table` ".
            "WHERE bugid IN (".$issueIds.") ";

         if (isset($startTimestamp)) {
            $query .= "AND date >= $startTimestamp ";
         }
         if (isset($endTimestamp)) {
            $query .= "AND date <= $endTimestamp ";
         }

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         return round(SqlWrapper::getInstance()->sql_result($result),2);
      } else {
         return 0;
      }
   }



   /**
    * @return int
    */
   public function getNbIssues() {
      return count($this->issueList);
   }

   /**
    * return a coma separated list of bugid URLs
    * @return string
    */
   public function getFormattedIssueList() {
      $formattedList = "";

      // make a copy, the initial issueList may be already sorted on different criteria
      $sortedList = $this->issueList; 
      ksort($sortedList, SORT_NUMERIC);

      foreach ($sortedList as $bugid => $issue) {
         
         if ("" != $formattedList) {
            $formattedList .= ', ';
         }

         $formattedList .= Tools::issueInfoURL($bugid, '['.$issue->getProjectName().'] '.$issue->summary);
      }
      return $formattedList;
   }

   /**
    * sum(issue->driftMgr)
    *
    * percent = nbDaysDrift / mgrEffortEstim
    *
    * @return number[] array(nbDays, percent)
    */
   public function getDriftMgr() {
      $nbDaysDrift = 0;
      $myEstim = 0;
      
      foreach ($this->issueList as $issue) {
         $nbDaysDrift += $issue->getDriftMgr();
         $myEstim     += $issue->mgrEffortEstim;
      }

      if (0 == $myEstim) {
         $percent = 0;
         self::$logger->warn("IssueSelection [$this->name] :  getDriftMgr() could not compute drift percent because mgrEffortEstim==0");
      } else {
         $percent = ($nbDaysDrift / $myEstim);
      }

      $values = array();
      $values['nbDays']  = round($nbDaysDrift,3);
      $values['percent'] = $percent;

      self::$logger->debug("IssueSelection [$this->name] :  getDriftMgr nbDays = ".$nbDaysDrift." percent = ".$percent." ($nbDaysDrift/$myEstim)");
      return $values;
   }

   /**
    * sum(issue->drift)
    *
    * percent = nbDaysDrift / (effortEstim + effortAdd)
    *
    * @return number[] array(nbDays, percent)
    */
   public function getDrift() {
      $nbDaysDrift = 0;
      $myEstim = 0;

      foreach ($this->issueList as $issue) {
         $nbDaysDrift += $issue->getDrift();
         $myEstim     += $issue->effortEstim + $issue->effortAdd;
      }

      if (0 == $myEstim) {
         $percent = 0;
         self::$logger->warn("IssueSelection [$this->name] :  getDrift() could not compute drift percent because effortEstim==0");
      } else {
         $percent = ($nbDaysDrift / $myEstim);
      }

      $values = array();
      $values['nbDays'] = round($nbDaysDrift,3);
      $values['percent'] = $percent;

      self::$logger->debug("IssueSelection [$this->name] :  getDrift nbDays = ".$nbDaysDrift." percent = ".$percent." ($nbDaysDrift/$myEstim)");
      return $values;
   }

   /**
    * @static
    * @param number $percent  100% = 1
    * @param number $threshold  5% = 0.05
    * @return string color
    */
   public static function getDriftColor($percent, $threshold = 0.05) {
      if (abs($percent) < $threshold) {
         return NULL; // no drift
      }

      if ($percent > 0) {
         $color = "fcbdbd";
      } else {
         $color = "bdfcbd";
      }
      return $color;
   }

   /**
    * @param bool $isManager
    * @param bool $withSupport
    * @return Issue[]
    */
   public function getIssuesInDrift($isManager=false, $withSupport = true) {
      $issuesInDrift = array();

      foreach ($this->issueList as $bugid => $issue) {
         // if not manager, disable getDriftMgrEE check
         $driftMgrEE = ($isManager) ? $issue->getDriftMgr($withSupport) : 0;
         $driftEE = $issue->getDrift($withSupport);

         if (($driftMgrEE > 0) || ($driftEE > 0)) {
            $issuesInDrift[$bugid] = $issue;
         }
      }

      return $issuesInDrift;
   }

   /**
    * Split selection in 3 selection, sorted on issue drift.
    *
    * @param int $threshold
    * @param bool $withSupport
    *
    * @return IssueSelection[] array of 3 IssueSelection instances ('negative', 'equal', 'positive')
    */
   public function getDeviationGroups($threshold = 1, $withSupport = true) {
      if (0== count($this->issueList)) {
         echo "<div style='color:red'>ERROR getDeviationGroups: Issue List is empty !<br/></div>";
         self::$logger->error("getDeviationGroups(): Issue List is empty !");
         return NULL;
      }

      $negSubList = new IssueSelection("ahead");
      $equalSubList = new IssueSelection("in time");
      $posSubList = new IssueSelection("in drift");

      foreach ($this->issueList as $bugId => $issue) {
         $issueDrift = $issue->getDrift($withSupport);

         // get drift stats. equal is when drif = +-threshold
         if ($issueDrift < -$threshold) {
            $negSubList->addIssue($bugId);

         } elseif ($issueDrift > $threshold){
            $posSubList->addIssue($bugId);
         } else {
            $equalSubList->addIssue($bugId);
         }
      }

      return array(
         "negative" => $negSubList,
         "equal" => $equalSubList,
         "positive" => $posSubList
      );
   }

   /**
    * Split selection in 3 selection, sorted on issue drift.
    *
    * Note: this is a replacement for Timetracking::getIssuesDriftStats()
    *
    * @param int $threshold
    * @param bool $withSupport
    *
    * @return IssueSelection[] array of 3 IssueSelection instances ('negative', 'equal', 'positive')
    */
   public function getDeviationGroupsMgr($threshold = 1, $withSupport = true) {
      if (0 == count($this->issueList)) {
         echo "<div style='color:red'>ERROR getDeviationGroupsMgr: Issue List is empty !<br/></div>";
         self::$logger->error("getDeviationGroupsMgr(): Issue List is empty !");
         return NULL;
      }

      $negSubList = new IssueSelection("ahead Mgr");
      $equalSubList = new IssueSelection("in time Mgr");
      $posSubList = new IssueSelection("in drift Mgr");

      foreach ($this->issueList as $bugId => $issue) {
         $issueDrift = $issue->getDriftMgr($withSupport);

         // get drift stats. equal is when drif = +-threshold
         if ($issueDrift < -$threshold) {
            $negSubList->addIssue($bugId);

         } elseif ($issueDrift > $threshold){
            $posSubList->addIssue($bugId);
         } else {
            $equalSubList->addIssue($bugId);
         }
      }

      return array(
         "negative" => $negSubList,
         "equal" => $equalSubList,
         "positive" => $posSubList
      );
   }

   /**
    * get consistency errors
    * @return ConsistencyError2[]
    */
   public function getConsistencyErrors(){
      $ccheck = new ConsistencyCheck2($this->issueList);
      return $ccheck->check();
   }

   /**
    * @return TimeTrack
    */
   public function getFirstTimetrack() {
      $found = NULL;
      $firstTimestamp = time();
      foreach ($this->issueList as $issue) {
         $tt = $issue->getFirstTimetrack();
         if ((NULL != $tt) && ( $tt->date < $firstTimestamp)) {
            $firstTimestamp = $tt->date;
            $found = $tt;
         }
      }
      #echo "getFirstTimetrack = $firstTimestamp<br>";
      return $found;
   }

   /**
    * @return TimeTrack
    */
   public function getLatestTimetrack() {
      $found = NULL;
      $latestTimestamp = 0;
      foreach ($this->issueList as $issue) {
         $tt = $issue->getLatestTimetrack();
         if ((NULL != $tt) && ($tt->date > $latestTimestamp)) {
            $latestTimestamp = $tt->date;
            $found = $tt;
         }
      }
      #echo "getLatestTimetrack = $latestTimestamp<br>";
      return $found;
   }

}

IssueSelection::staticInit();

?>
