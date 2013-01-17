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
class ReopenedRateIndicator implements IndicatorPlugin {


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
      return "plugin/budgetDriftHistoryIndicator.html";
   }

   /**
    *  check input params
    *
    * @param Command $cmd
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
         self::$logger->debug("execute() ISel=".$cmd->getName().' interval='.$params['interval'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
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
    *
    * CmdTotalDrift = Reestimated - (MEE + Provisions)
    *
    *
    * @param Command $cmd
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      // -------- elapsed in the period
      $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d', $this->endTimestamp), date('Y', $this->endTimestamp));

      $timestampList = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);

      // ------ compute

      $reopenedList = array();
      $nbReopenedList = array();

      $formattedBugidList = implode(', ', array_keys($inputIssueSel->getIssueList()));

      for($i = 1, $size = count($timestampList); $i < $size; ++$i) {
         $start = $timestampList[$i-1];
         //$start = mktime(0, 0, 0, date('m', $timestampList[$i-1]), date('d',$timestampList[$i-1]), date('Y', $timestampList[$i-1]));

         $lastDay = ($i + 1 < $size) ? strtotime("-1 day",$timestampList[$i]) : $timestampList[$i];
         $end   = mktime(23, 59, 59, date('m', $lastDay), date('d',$lastDay), date('Y', $lastDay));

         // 1) get all reopened bugs within the timestamp
         $query = "SELECT bug.*" .
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
            $bugidList[] = $row->id;
         }

         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestampList[$i]), date('d', $timestampList[$i]), date('Y', $timestampList[$i]));
         $key = Tools::formatDate('%Y-%m-%d', $midnight_timestamp);

         $reopenedList[$key] = $bugidList;
         $nbReopenedList[$key] = count($bugidList);
         $tableData[$key] = array('reopenedDays' => $nbReopenedList[$key],
                                  'reopenedPercent' => 0); // TODO

         #echo "reopened[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".count($bugidList)." reopened : ".implode(', ', $bugidList).'<br>';
      }

      $this->execData = array();
      $this->execData['nbReopened'] = $nbReopenedList;
      $this->execData['reopenedIssues'] = $reopenedList;
      $this->execData['tableData'] = $tableData;

      return $this->execData;
   }

   public function getSmartyObject() {

      $smartyVariables = array();

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $smartyVariables['reopenedRate_plotMinDate'] = Tools::formatDate("%Y-%m-01", $startTimestamp);
      $smartyVariables['reopenedRate_plotMaxDate'] = Tools::formatDate("%Y-%m-01", $endTimestamp);
      $smartyVariables['reopenedRate_interval']    = ceil($this->interval/20);
      $smartyVariables['reopenedRate_jqplotDaysData']  = '['.Tools::array2plot($this->execData['nbReopened']).']';
      $smartyVariables['reopenedRate_reopenedIssues']   = $this->execData['reopenedIssues'];
      $smartyVariables['reopenedRate_tableData']   = $this->execData['tableData'];

      return $smartyVariables;
   }

}

// Initialize complex static variables
ReopenedRateIndicator::staticInit();
?>
