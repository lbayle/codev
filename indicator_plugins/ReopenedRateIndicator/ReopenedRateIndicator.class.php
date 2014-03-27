<?php

/*
  This file is part of CodevTT

  CodevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CodevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('lib/log4php/Logger.php');

/* INSERT INCLUDES HERE */

/**
 * Description of ReopenedRateIndicator
 *
 */
class ReopenedRateIndicator extends Plugin implements IndicatorPlugin {


   /**
    * @var Logger The logger
    */
   private static $logger;

   private $startTimestamp;
   private $endTimestamp;
   private $interval;
   private $provisionDays;

   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {
      $this->startTimestamp     = NULL;
      $this->endTimestamp       = NULL;
      $this->interval           = NULL;
      $this->provisionDays      = NULL;
   }

   public function getDesc() {
      return "";
   }

   public function getName() {
      return __CLASS__;
   }

   public static function getSmartyFilename() {
      return Constants::$codevRootDir.DS.self::indicatorPluginsDir.DS.__CLASS__.DS.__CLASS__.".html";
   }

   /**
    *  check input params
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    * @throws Exception
    */
   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $inputIssueSel) {
         throw new Exception("Missing IssueSelection");
      }
      if (NULL == $params) {
         throw new Exception("Missing parameters: startTimestamp, endTimestamp, interval");
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("execute() ISel=".$inputIssueSel->name.' interval='.$params['interval'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
      }

      if (array_key_exists('startTimestamp', $params)) {
         $this->startTimestamp = $params['startTimestamp'];
      } else {
         throw new Exception("Missing parameter: startTimestamp");
      }

      if (array_key_exists('endTimestamp', $params)) {
         $this->endTimestamp = $params['endTimestamp'];
      } else {
         throw new Exception("Missing parameter: endTimestamp");
      }

      if (array_key_exists('interval', $params)) {
         $this->interval = $params['interval'];
      } else {
         throw new Exception("Missing parameter: interval");
      }
   }


   /**
    * all bugs changing status from 'resolved' (or higher) status to a lower status
    *
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getReopened($formattedBugidList, $start, $end) {

      // 1) get all reopened bugs within the timestamp
      $query = "SELECT bug.* " .
               "FROM `mantis_bug_table` as bug ".
               "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id " .
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND history.field_name='status' " .
               "AND history.date_modified >= $start AND history.date_modified < $end " .
               "AND history.old_value >= get_project_resolved_status_threshold(bug.project_id) " .
               "AND history.new_value <  get_project_resolved_status_threshold(bug.project_id) " .
               "GROUP BY bug.id ;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // 2) count reopened and (still) fixed issues at the end of the period
      $bugidList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check that it is still in a 'non-resolved' state.
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $latestStatus = $issue->getStatus($end);
         if ($latestStatus < $issue->getBugResolvedStatusThreshold()) {
            $bugidList[] = $row->id;
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("reopened but then resolved: $row->id");
            }
         }

      }

      // remove duplicated values
      $bugidList = array_unique($bugidList);
      return $bugidList;
   }

   /**
    * all bugs changing status from 'resolved' to a higher status (validated, closed)
    * and that it is still validated at the end of the period.
    *
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getValidated($formattedBugidList, $start, $end) {

      $query = "SELECT bug.* ".
               "FROM `mantis_bug_table` as bug ".
               "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id " .
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND history.field_name='status' " .
               "AND history.date_modified >= $start AND history.date_modified < $end " .
               "AND history.old_value <= get_project_resolved_status_threshold(bug.project_id) " .
               "AND history.new_value >  get_project_resolved_status_threshold(bug.project_id) " .
               "GROUP BY bug.id ;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $bugidList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check that it is still in a 'validated' state.
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $latestStatus = $issue->getStatus($end);
         if ($latestStatus > $issue->getBugResolvedStatusThreshold()) {
            $bugidList[] = $row->id;
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug( "validated but then reopened: $row->id");
            }
         }
      }

      // remove duplicated values
      $bugidList = array_unique($bugidList);
      return $bugidList;
   }

   /**
    * Returns all Issues resolved in the period, including re-opened and validated.
    * 
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getResolved($formattedBugidList, $start, $end) {

      // all bugs which status changed to 'resolved' whthin the timestamp
      $query = "SELECT bug.id ".
               "FROM `mantis_bug_table` as bug, `mantis_bug_history_table` as history ".
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND bug.id = history.bug_id ".
               "AND history.field_name='status' ".
               "AND history.date_modified >= $start AND history.date_modified < $end ".
               "AND history.new_value = get_project_resolved_status_threshold(project_id) ";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $resolvedList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         $resolvedList[] = $row->id;
      }

      // remove duplicated values
      $resolvedList = array_unique($resolvedList);
      return $resolvedList;
   }


   /**
    *
    * 
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);
      $this->execData = array();

      // -------- elapsed in the period
      $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d', $this->endTimestamp), date('Y', $this->endTimestamp));

      $timestampList = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);

      // ------ compute

      #$resolvedList = array();
      #$nbResolvedList = array();
      $reopenedList = array();
      $nbReopenedList = array();
      $validatedList = array();
      $nbValidatedList = array();
      $reopenedPercentList = array();
      $validatedPercentList = array();

      $formattedBugidList = implode(', ', array_keys($inputIssueSel->getIssueList()));

      for($i = 1, $size = count($timestampList); $i < $size; ++$i) {

         $start = $timestampList[$i-1];

         $lastDay = ($i + 1 < $size) ? strtotime("-1 day",$timestampList[$i]) : $timestampList[$i];
         $end   = mktime(23, 59, 59, date('m', $lastDay), date('d',$lastDay), date('Y', $lastDay));

         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestampList[$i]), date('d', $timestampList[$i]), date('Y', $timestampList[$i]));
         $key = Tools::formatDate('%Y-%m-%d', $midnight_timestamp);

         // -------

         $reopenedBugidList = $this->getReopened($formattedBugidList, $start, $end);
         $validatedBugidList = $this->getValidated($formattedBugidList, $start, $end);

         // WARN: the tiestamp list may return something like this:
         // timestamp = 2013-01-14 00:00:00
         // timestamp = 2013-01-21 00:00:00
         // timestamp = 2013-01-21 23:59:59
         if (array_key_exists($key, $reopenedList)) {
            $reopenedBugidList = array_merge($reopenedBugidList, $reopenedList[$key]);
            $validatedBugidList = array_merge($validatedBugidList, $validatedList[$key]);
         }

         // PHP.net: It's often faster to use foreach and array_keys than array_unique
         $tmpBugidList = array_merge($reopenedBugidList, $validatedBugidList);
         $allBugidList = array();
         foreach($tmpBugidList as $val) {
            $allBugidList[$val] = true;
         }
         $allBugidList = array_keys($allBugidList);

         // --------
         $nbResolved = count($allBugidList);
         $nbReopened = count($reopenedBugidList);
         $nbValidated = count($validatedBugidList);

         if (0 != $nbResolved) {
            $pcentReopened = round((100 * $nbReopened / $nbResolved), 2);
            $pcentValidated = round((100 * $nbValidated / $nbResolved), 2);
         } else {
            $pcentReopened = 0;
            $pcentValidated = 0;
         }
         // ---------

         $reopenedList[$key] = $reopenedBugidList;
         $validatedList[$key] = $validatedBugidList;

         $nbReopenedList[$key] = $nbReopened;
         $nbValidatedList[$key] = $nbValidated;

         $reopenedPercentList[$key] = $pcentReopened;
         $validatedPercentList[$key] = $pcentValidated;

         $tableData[$key] = array(#'nbResolved' => $nbResolved,
                                  'nbReopened' => $nbReopened,
                                  'reopenedPercent' => $pcentReopened,
                                  'nbValidated' => $nbValidated,
                                  'validatedPercent' => $pcentValidated,
                                  'dateTooltip' => date('d M H:i:s', $start).' - '.date('d M H:i:s', $end)
                                 );

         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("reopened [".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$nbReopened." reopened  : ".implode(', ', $reopenedBugidList));
            self::$logger->debug("validated[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$nbValidated." validated : ".implode(', ', $validatedBugidList));
            #echo '---<br>';
         }
      }

      $this->execData['nbReopened'] = $nbReopenedList;
      $this->execData['reopenedIssues'] = $reopenedList;
      $this->execData['reopenedPercent'] = $reopenedPercentList;

      $this->execData['nbValidated'] = $nbValidatedList;
      $this->execData['validatedIssues'] = $validatedList;
      $this->execData['validatedPercent'] = $validatedPercentList;

      $this->execData['tableData'] = $tableData;

      return $this->execData;
   }

   /**
    *
    * @return array $smartyVariables
    */
   public function getSmartyObject() {

      $smartyVariables = array();

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $smartyVariables['reopenedRate_plotMinDate'] = Tools::formatDate("%Y-%m-01", $startTimestamp);
      $smartyVariables['reopenedRate_plotMaxDate'] = Tools::formatDate("%Y-%m-01", $endTimestamp);
      $smartyVariables['reopenedRate_interval']    = ceil($this->interval/20);

      #$smartyVariables['reopenedRate_jqplotData']  = "[".Tools::array2plot($this->execData['nbValidated']).','.Tools::array2plot($this->execData['nbReopened'])."]";
      $smartyVariables['reopenedRate_jqplotData']  = "[".Tools::array2plot($this->execData['validatedPercent']).','.Tools::array2plot($this->execData['reopenedPercent'])."]";

      #$smartyVariables['reopenedRate_reopenedIssues']   = $this->execData['reopenedIssues'];
      #$smartyVariables['reopenedRate_validatedIssues']   = $this->execData['validatedIssues'];

      $smartyVariables['reopenedRate_tableData']   = $this->execData['tableData'];

      return $smartyVariables;
   }

}

// Initialize complex static variables
ReopenedRateIndicator::staticInit();
?>
