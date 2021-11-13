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
   public $mgrEffortEstim;
   public $effortEstim;

   /**
    * provision can be added, it is an 'extra' mgrEffortEstim
    * that will be included on Drift computing.
    *
    * @var int nb days
    */
   private $provision;

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
      $this->mgrEffortEstim = 0;
      $this->effortEstim = 0;
      $this->provision = 0;

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
            $this->addIssue($issue->getId());
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
      if (!array_key_exists($bugid, $this->issueList)) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $this->issueList[$bugid] = $issue;
         $this->elapsed += (float)$issue->getElapsed();
         $this->duration += (float)$issue->getDuration();
         $this->mgrEffortEstim += (float)$issue->getMgrEffortEstim();
         $this->effortEstim += (float)$issue->getEffortEstim();
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
      }
   }

   public function getProvision() {
      return $this->provision;
   }

   public function setProvision($value) {
      $this->provision = $value;
   }

   public function addProvision($value) {
      $this->provision += $value;
      return $this->provision;
   }

   public function getProgress() {
      if (NULL == $this->progress) {
         // compute total progress

         if (0 == $this->elapsed) {

            if (0 === count($this->issueList)) {
               // if no issue, 0% seems more logical than 100%
               $this->progress = 0;
            } else {
               $hasUnresolved = false;
               foreach ($this->issueList as $issue) {
                  if (!$issue->isResolved()) {
                     // at least one open task, consider no work is done (0%)
                     $this->progress = 0;
                     $hasUnresolved = true;
                  }
               }
               if (false == $hasUnresolved) {
                  // no time spent, but all tasks resolved => 100% done
                  $this->progress = 1;
               }
            }
         } elseif (0 == $this->duration) {
            $this->progress = 1;  // if no duration, then Project is 100% done.
         } else {
            $this->progress = $this->elapsed / $this->getReestimated();
         }
      }

      return $this->progress;
   }

   /**
    *
    * @param type $timestamp
    */
   public function getMgrEffortEstim($timestamp = NULL) {

      if (is_null($timestamp)) {
         return $this->mgrEffortEstim;
      }

      $mgrEffortEstim = 0;
      foreach ($this->issueList as $issue) {
         $submission = $issue->getDateSubmission();

         if ($submission <= $timestamp) {
            $mgrEffortEstim += (float)$issue->getMgrEffortEstim();
         } else {
            #echo "issue ".$issue->getId()." does not yet exist<br>";
         }
      }
      return $mgrEffortEstim;
   }

   public function getEffortEstim() {
      return $this->effortEstim;
   }

   /**
    * reestimated = elapsed + duration
    * @return int
    */
   public function getReestimated($timestamp = NULL) {

      if (is_null($timestamp)) {
         return ($this->elapsed + $this->duration);

      } else {

         $duration = 0;
         foreach ($this->issueList as $issue) {
            $submission = $issue->getDateSubmission();

            if ($submission <= $timestamp) {
               $duration += (float)$issue->getDuration($timestamp);
            } else {
               #echo "issue ".$issue->getId()." does not yet exist<br>";
            }

         }
         $elapsed = $this->getElapsed(NULL, $timestamp);
         $reest = $elapsed + $duration;
         #echo "   reest =  $elapsed + $duration = $reest<br>";
         return $reest;
      }
   }

   /**
    * the nb of days needed to finish the issue.
    *
    * for each task:
    * if status >= resolved, return 0.
    * if the 'backlog' (BL) field is not defined, return max(effortEstim+effortAdd, mgrEffortEstim)
    *
    * @return int the nb of days needed to finish the issueSel or NULL if not found (rare).
    */
   public function getDuration($timestamp = NULL) {

      $duration = 0;
      foreach ($this->issueList as $issue) {
         $submission = $issue->getDateSubmission();

         if ($submission <= $timestamp) {
            $duration += (float)$issue->getDuration($timestamp);
         } else {
            #echo "issue ".$issue->getId()." does not yet exist<br>";
         }
      }
      return $duration;
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
   public function getElapsed($startTimestamp = NULL, $endTimestamp = NULL, $useridList = NULL) {
      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();

         // for each issue, sum all its timetracks within period
         $formattedList=implode(', ', array_keys($this->issueList));;
         $query = "SELECT SUM(duration) FROM codev_timetracking_table ".
                  " WHERE bugid IN (".$formattedList.") ";

         if (NULL != $useridList) {
            $query .= ' AND userid IN ('.implode( ', ', $useridList).') ';
         }
         if (isset($startTimestamp)) {
            $query .= " AND date >=  ".$sql->db_param();
            $q_params[]=$startTimestamp;
         }
         if (isset($endTimestamp)) {
            $query .= " AND date <=  ".$sql->db_param();
            $q_params[]=$endTimestamp;
         }

         $result = $sql->sql_query($query, $q_params);

         return round($sql->sql_result($result),2);
      } else {
         return 0;
      }
   }

   /**
    * return list of issues having timetracks in the period
    *
    * @param type $startTimestamp
    * @param type $endTimestamp
    * @return array[bugid] = issue
    */
   public function getIssuesWithElapsed($startTimestamp = NULL, $endTimestamp = NULL) {

      $issues = array();
      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();

         // for each issue, sum all its timetracks within period
         $formattedList=implode(', ', array_keys($this->issueList));
         $query = "SELECT bugid FROM codev_timetracking_table ".
                  " WHERE bugid IN (".$formattedList.") ";

         if (isset($startTimestamp)) {
            $query .= " AND date >=  ".$sql->db_param();
            $q_params[]=$startTimestamp;
         }
         if (isset($endTimestamp)) {
            $query .= " AND date <=  ".$sql->db_param();
            $q_params[]=$endTimestamp;
         }
         $query .= " GROUP BY bugid";

         $result = $sql->sql_query($query, $q_params);

         while ($row = $sql->fetchObject($result)) {
            $issue = IssueCache::getInstance()->getIssue($row->bugid);
            $issues[$row->bugid] = $issue;
         }
      }
      return $issues;
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

         $titleAttr = array(
               T_('Project') => $issue->getProjectName(),
               T_('Summary') => $issue->getSummary(),
         );
         $extRef = $issue->getTcId();
         if ((!is_null($extRef)) && ('' != $extRef)) {
            $titleAttr[T_('ExtRef')] = $extRef;
         }
         $formattedList .= Tools::issueInfoURL($bugid, $titleAttr);
      }
      return $formattedList;
   }

   /**
    * sum(issue->driftMgr)
    *
    * percent = nbDaysDrift / (mgrEffortEstim + provision)
    *
    * @return number[] array(nbDays, percent)
    */
   public function getDriftMgr() {
      $nbDaysDrift = 0;
      $myEstim = 0;

      foreach ($this->issueList as $issue) {
         $nbDaysDrift += (float)$issue->getDriftMgr();
         $myEstim += (float)$issue->getMgrEffortEstim();
      }
      $myEstim += $this->provision;
      $nbDaysDrift -= $this->provision;

      if (0 == $myEstim) {
         $percent = 0;
         self::$logger->warn("IssueSelection [$this->name] :  getDriftMgr() could not compute drift percent because mgrEffortEstim==0");
      } else {
         $percent = ($nbDaysDrift / $myEstim);
      }

      $values = array();
      $values['nbDays']  = round($nbDaysDrift,3);
      $values['percent'] = $percent;

      return $values;
   }

   /**
    * sum(issue->drift)
    *
    * percent = nbDaysDrift / (effortEstim + provision)
    *
    * @return number[] array(nbDays, percent)
    */
   public function getDrift() {
      $nbDaysDrift = 0;
      $myEstim = 0;

      foreach ($this->issueList as $issue) {
         $nbDaysDrift += (float)$issue->getDrift();
         $myEstim += (float)$issue->getEffortEstim();
      }
      $myEstim += $this->provision;
      $nbDaysDrift -= $this->provision;

      if (0 == $myEstim) {
         $percent = 0;
         self::$logger->warn("IssueSelection [$this->name] :  getDrift() could not compute drift percent because effortEstim==0");
      } else {
         $percent = ($nbDaysDrift / $myEstim);
      }

      $values = array();
      $values['nbDays'] = round($nbDaysDrift,3);
      $values['percent'] = $percent;

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
      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();
         $formattedList=implode(', ',array_keys($this->issueList));
         $query = "SELECT * from codev_timetracking_table ".
            "WHERE bugid IN (".$formattedList.") ".
            " ORDER BY date ASC";
         $result = $sql->sql_query($query, null, TRUE, 1); // LIMIT 1

         $timeTrack = NULL;
         if (0 != $sql->getNumRows($result)) {
            $row = $sql->fetchObject($result);
            $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
         }

         return $timeTrack;
      } else {
         return 0;
      }
   }

   /**
    * @return TimeTrack
    */
   public function getLatestTimetrack() {
      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();
         $formattedList=implode(', ',array_keys($this->issueList));
         $query = "SELECT * from codev_timetracking_table ".
            "WHERE bugid IN (".$formattedList.") ".
            " ORDER BY date DESC";
         $result = $sql->sql_query($query, null, TRUE, 1); // LIMIT 1

         $timeTrack = NULL;
         if (0 != $sql->getNumRows($result)) {
            $row = $sql->fetchObject($result);
            $timeTrack = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
         }
         return $timeTrack;
      } else {
         return NULL;
      }
   }

   /**
    * get the date of the latest update of the issueList
    * @return timestamp
    */
   public function getLastUpdated() {
      $lastUpdated = 0;

      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();
         $formattedList=implode(', ',array_keys($this->issueList));
         $query = "SELECT last_updated from {bug} ".
            "WHERE id IN (".$formattedList.") ".
            " ORDER BY last_updated DESC";
         $result = $sql->sql_query($query, null, TRUE, 1); // LIMIT 1

         if (0 != $sql->getNumRows($result)) {
            $row = $sql->fetchObject($result);
            $lastUpdated = $row->last_updated;
         }
      }
      return $lastUpdated;
   }

   /**
    * get the date of the $max latest updated issues of the issueList
    * @return array bugid => timestamp
    */
   public function getLastUpdatedList($max = 1) {
   	$lastUpdatedList = array();
      if(count($this->issueList) > 0) {
         $sql = AdodbWrapper::getInstance();
         $formattedList=implode(', ',array_keys($this->issueList));
         $query = "SELECT id, last_updated from {bug} ".
            "WHERE id IN (".$formattedList.") ".
            " ORDER BY last_updated DESC";
         $result = $sql->sql_query($query, null, TRUE, $max); // LIMIT max

         while ($row = $sql->fetchObject($result)) {
            $lastUpdatedList["$row->id"] = $row->last_updated;
         }
      }
      return $lastUpdatedList;
   }

   /**
    * get timetracks for each Issue
    *
    * @param array $useridList
    * @param type $startTimestamp
    * @param type $endTimestamp
    * @return array of TimeTrack
    */
   public function getTimetracks($useridList = NULL, $startTimestamp = NULL, $endTimestamp = NULL) {

      if (empty($this->issueList)) {
         return array();
      }

      // TODO cache results !

      $sql = AdodbWrapper::getInstance();
      $formattedList=implode( ', ', array_keys($this->issueList));
      $query = "SELECT * FROM codev_timetracking_table ".
               "WHERE bugid IN (".$formattedList.") ";

      if (NULL != $useridList) {
         $query .= ' AND userid IN ('.implode( ', ', $useridList).') ';
      }
      if (NULL != $startTimestamp) { $query .= " AND date >=  ".$sql->db_param(); $q_params[]=$startTimestamp; }
      if (NULL != $endTimestamp)   { $query .= " AND date <=  ".$sql->db_param(); $q_params[]=$endTimestamp; }
      $query .= ' ORDER BY bugid';

      $result = $sql->sql_query($query, $q_params);

      $timeTracks = array();
      while($row = $sql->fetchObject($result)) {
         $timeTracks[$row->id] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
      }
      return $timeTracks;
   }

   /**
    * sum the costs of all timetracks
    * @param type $targetCurrency
    * @param type $teamid
    * @return array the 6 basic indicators
    * @throws Exception
    */
   public function getCostStruct($targetCurrency, $teamid) {

      foreach ($this->issueList as $issue) {
         $issueCosts = $issue->getCostStruct($targetCurrency, $teamid);

         foreach($issueCosts as $key => $value) {
            $issueCostSums[$key] += $value;
         }
      }
      return $issueCostSums;
   }

   /**
    * returns issues that have been submitted in the period
    * @return Issue[] a list of Issue class instances
    */
   public function getSubmittedIssues($startTimestamp = NULL, $endTimestamp = NULL, $extRefOnly = FALSE) {


      if (is_null($this->issueList) || (0 == count($this->issueList))) {
         $e = new Exception('No issue in this IssueSelection ('.$this->name.')');
         self::$logger->error("EXCEPTION getSubmittedIssues: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);
      $sql = AdodbWrapper::getInstance();

      $query = 'SELECT bug.id FROM {bug} as bug ';
      if ($extRefOnly) {
         $query .= ', {custom_field_string} ';
      }
      $query .= ' WHERE bug.date_submitted >= '.$sql->db_param().
                ' AND bug.date_submitted < '.$sql->db_param();
      $q_params[]=$startTimestamp;
      $q_params[]=$endTimestamp;

      $formattedList=implode( ', ', array_keys($this->issueList));
      $query .= ' AND bug.id IN ('.$formattedList.')  ';

      if ($extRefOnly) {
         $query .= ' AND {custom_field_string}.field_id =  '.$sql->db_param();
         $query .= ' AND {custom_field_string}.bug_id = bug.id ';
         $query .= " AND {custom_field_string}.value <> '' ";
         $q_params[]=$extIdField;
      }

      $result = $sql->sql_query($query, $q_params);
      $submittedIssues = array();
      while($row = $sql->fetchObject($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id);
         $submittedIssues[$issue->getId()] = $issue;
      }
      return $submittedIssues;
   }

   /**
    * Returns all Issues resolved in the period and having not been re-opened
    * @return Issue[] a list of Issue class instances
    */
   public function getResolvedIssues($startTimestamp = NULL, $endTimestamp = NULL, $extRefOnly = FALSE, $withReopened=FALSE) {

      if (is_null($this->issueList) || (0 == count($this->issueList))) {
         $e = new Exception('No issue in this IssueSelection ('.$this->name.')');
         self::$logger->error("EXCEPTION getResolvedIssues: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $sql = AdodbWrapper::getInstance();
      $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);

      $formattedList=implode( ', ', array_keys($this->issueList));

      // all bugs which status changed to 'resolved' whthin the timestamp
      $query = "SELECT bug.id ".
               "FROM {bug} as bug ";
      if ($extRefOnly) {
         $query .= ", {custom_field_string} ";
      }

      $query .= ", {bug_history} as history ".
               " WHERE bug.id IN (".$formattedList.") ".
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
               " AND history.new_value = get_project_resolved_status_threshold(project_id) ";
               #" ORDER BY bug.id DESC"; // time consuming
        $q_params[]=$startTimestamp;
        $q_params[]=$endTimestamp;

      $result = $sql->sql_query($query, $q_params);

      $resolvedIssues = array();
      while($row = $sql->fetchObject($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id);

         if (!$withReopened) {
            // skip if the bug has been reopened before endTimestamp
            $latestStatus = $issue->getStatus($endTimestamp);
            if ($latestStatus < $issue->getBugResolvedStatusThreshold()) {
               continue;
            }
         }
         // remove duplicated values
         if (!in_array ($issue->getId(), $resolvedIssues)) {
            $resolvedIssues[$issue->getId()] = $issue;
         }
      }
      return $resolvedIssues;
   }

} // class

IssueSelection::staticInit();


