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
 * Description of BudgetDriftHistoryIndicator
 *
 */
class BudgetDriftHistoryIndicator implements IndicatorPlugin {


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
         throw new Exception("Missing parameters: startTimestamp, endTimestamp, interval, provisionDays");
      }

      //if(self::$logger->isDebugEnabled()) {
      //   self::$logger->debug("execute() ISel=".$cmd->getName().' interval='.$params['interval'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);
      //}

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
      if (array_key_exists('provisionDays', $params)) {
         $this->provisionDays = $params['provisionDays'];
      } else {
         throw new Exception("Missing parameter: provisionDays");
      }
   }


   private function getElapsedData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->elapsedData = array();

      // there is no elapsed on first date
      $this->elapsedData[] = 0;

      for($i = 1, $size = count($timestampList); $i < $size; ++$i) {
         $start = $timestampList[$i-1];
         //$start = mktime(0, 0, 0, date('m', $timestampList[$i-1]), date('d',$timestampList[$i-1]), date('Y', $timestampList[$i-1]));

         $lastDay = ($i + 1 < $size) ? strtotime("-1 day",$timestampList[$i]) : $timestampList[$i];
         $end   = mktime(23, 59, 59, date('m', $lastDay), date('d',$lastDay), date('Y', $lastDay));

         #   echo "nb issues = ".count($inputIssueSel->getIssueList());

         $elapsed = $inputIssueSel->getElapsed($start, $end);

         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestampList[$i]), date('d', $timestampList[$i]), date('Y', $timestampList[$i]));
         $this->elapsedData[$midnight_timestamp] += $elapsed; // Note: += is important
         #echo "elapsed[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$this->elapsedData[$midnight_timestamp].'<br>';
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
      $startTimestamp = mktime(0, 0, 0, date('m', $params['startTimestamp']), date('d', $params['startTimestamp']), date('Y', $params['startTimestamp']));
      $endTimestamp   = mktime(23, 59, 59, date('m', $params['endTimestamp']), date('d',$params['endTimestamp']), date('Y', $params['endTimestamp']));

      $timestampList2 = Tools::createTimestampList($startTimestamp, $endTimestamp, $params['interval']);

      $this->getElapsedData($inputIssueSel, $timestampList2);

      // ------ compute
      // CmdTotalDrift = Reestimated - (MEE + Provisions)

      $driftDaysList = array();
      $driftPercentList = array();
      $tableData = array();
      $nbZeroDivErrors1 = 0;
      foreach ($timestampList2 as $timestamp) {
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

         $cmdProvAndMeeDays = $inputIssueSel->getMgrEffortEstim($timestamp) + $this->provisionDays;
         $reestimated = $inputIssueSel->getReestimated($timestamp);

         $driftDays = $reestimated - $cmdProvAndMeeDays;

         if (0 != $cmdProvAndMeeDays) {
            $driftPercent = ($driftDays * 100 / $cmdProvAndMeeDays);
         } else {
            $nbZeroDivErrors1 += 1;
            $driftPercent = 0;
         }
         #$totalDriftCost = $totalDrift * $cmd->getAverageDailyRate();

         $key = Tools::formatDate('%Y-%m-%d', $midnight_timestamp);
         $driftDaysList[$key] = round($driftDays, 2);
         $driftPercentList[$key] = round($driftPercent, 2);
         $tableData[$key] = array('driftDays' => $driftDaysList[$key], 
                                  'driftPercent' => $driftPercentList[$key],
                                  'provAndMeeDays' => $cmdProvAndMeeDays);

         #echo $key." CmdTotalDrift = $reestimated - $cmdProvAndMeeDays = ".$cmdTotalDrift[$key]."<br>";

      } // foreach timestamp

      // PERF logging is slow, factorize errors
      if ($nbZeroDivErrors1 > 0) {
         self::$logger->error("$nbZeroDivErrors1 Division by zero ! (cmdProvAndMeeDays)");
      }

      $this->execData['budgetDriftDays'] = $driftDaysList;
      $this->execData['budgetDriftPercent'] = $driftPercentList;
      $this->execData['budgetDriftTable'] = $tableData;

      return $this->execData;
   }

   public function getSmartyObject() {

      $smartyVariables = array();

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $smartyVariables['budget_drift_history_plotMinDate'] = Tools::formatDate("%Y-%m-01", $startTimestamp);
      $smartyVariables['budget_drift_history_plotMaxDate'] = Tools::formatDate("%Y-%m-01", $endTimestamp);
      $smartyVariables['budget_drift_history_interval'] = ceil($this->interval/20);
      $smartyVariables['budget_drift_history_driftDaysList'] = '['.Tools::array2plot($this->execData['budgetDriftDays']).']';
      $smartyVariables['budget_drift_history_driftPercentList'] = '['.Tools::array2plot($this->execData['budgetDriftPercent']).']';
      $smartyVariables['budget_drift_history_tableData'] = $this->execData['budgetDriftTable'];

      return $smartyVariables;
   }
}

// Initialize complex static variables
BudgetDriftHistoryIndicator::staticInit();
?>
