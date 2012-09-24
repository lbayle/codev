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

/**
 * Description of BacklogVariationIndicator
 */
class ProgressHistoryIndicator implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private $startTimestamp;
   private $endTimestamp;
   private $interval;

   protected $execData;

   protected $backlogData;
   protected $elapsedData;

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
   }

   public function getDesc() {
      return "";
   }

   public function getName() {
      return __CLASS__;
   }

   public function getSmartyFilename() {
      return "";
   }

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

   private function getBacklogData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->backlogData = array();

      // get a snapshot of the Backlog at each timestamp
      foreach ($timestampList as $timestamp) {
         $backlog = 0;
         foreach ($inputIssueSel->getIssueList() as $issue) {

            $issueBL = $issue->getBacklog($timestamp);
            if (NULL != $issueBL) {
               $backlog += $issueBL;
            } else {
               // if not fount in history, take the MgrEffortEstim (or EffortEstim ??)
               $backlog += $issue->getMgrEffortEstim();
            }
         }

         #echo "backlog(".date('Y-m-d', $timestamp).") = ".$backlog.'<br>';
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
         $this->backlogData[$midnight_timestamp] = $backlog;
      }
   }

   private function getElapsedData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->elapsedData = array();

      // there is no elapsed on first date
      $this->elapsedData[] = 0;

      for($i = 1, $size = count($timestampList); $i < $size; ++$i) {
         $start = $timestampList[$i-1];
         $end = mktime(23, 59, 59, date('m', $timestampList[$i]), date('d',$timestampList[$i]), date('Y', $timestampList[$i]));

         #echo "Elapsed interval start ".date('Y-m-d', $start)." end ".date('Y-m-d', $end)."<br>";

         #   echo "nb issues = ".count($inputIssueSel->getIssueList());

         $elapsed = $inputIssueSel->getElapsed($start, $end);

         #echo "elapsed(".date('Y-m-d', $timestampList[$i]).") = ".$elapsed.'<br>';
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestampList[$i]), date('d', $timestampList[$i]), date('Y', $timestampList[$i]));
         $this->elapsedData[$midnight_timestamp] = $elapsed;
      }
   }

   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {
      $this->checkParams($inputIssueSel, $params);

     // Indicateur = Conso. Cumulé / (Conso. Cumulé +  RAF)

      $startTimestamp = mktime(23, 59, 59, date('m', $params['startTimestamp']), date('d', $params['startTimestamp']), date('Y', $params['startTimestamp']));
      $endTimestamp   = mktime(23, 59, 59, date('m', $params['endTimestamp']), date('d',$params['endTimestamp']), date('Y', $params['endTimestamp']));

      //echo "Backlog start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$params['interval']."<br>";
      $timestampList  = Tools::createTimestampList($startTimestamp, $endTimestamp, $params['interval']);


      // -------- elapsed in the period
      $startTimestamp = mktime(0, 0, 0, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));
      $endTimestamp = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));

      //echo "Elapsed start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$params['interval']."<br>";

      $timestampList2 = Tools::createTimestampList($startTimestamp, $endTimestamp, $params['interval']);


      $this->getBacklogData($inputIssueSel, $timestampList);
      $this->getElapsedData($inputIssueSel, $timestampList2);

      // ------ compute
      $theoBacklog = array();
      $realBacklog = array();
      $sumElapsed = 0;
      foreach ($timestampList as $timestamp) {
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

         // RAF theorique = charge initiale - cumul consomé
         if(array_key_exists($midnight_timestamp,$this->elapsedData)) {
            $sumElapsed += $this->elapsedData[$midnight_timestamp];
         }
         if (0 != $inputIssueSel->mgrEffortEstim) {
            $val1 = $sumElapsed / $inputIssueSel->mgrEffortEstim;
         } else {
            // TODO
            $val1 = 0;
            self::$logger->error("Division by zero ! (mgrEffortEstim)");
         }
         if ($val1 > 1) {$val1 = 1;}
         $theoBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val1 * 100, 2);

         // RAF reel
         $tmp = ($sumElapsed + $this->backlogData[$midnight_timestamp]);
         if (0 != $tmp) {
            $val2 = $sumElapsed / $tmp;
         } else {
            // TODO
            $val2 = 0;
            self::$logger->error("Division by zero ! (elapsed + realBacklog)");
         }
         $realBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val2 * 100, 2);

         #echo "(".date('Y-m-d', $midnight_timestamp).")  rafTheo = $rafTheo sumElapsed = $sumElapsed theoBacklog = ".$theoBacklog[$midnight_timestamp]." realBacklog = ".$realBacklog[$midnight_timestamp].'<br>';
      }

      $this->execData = array();
      $this->execData['theo'] = $theoBacklog;
      $this->execData['real'] = $realBacklog;
   }

   public function getSmartyObject() {
      $theoBacklog = $this->execData['theo'];
      $realBacklog = $this->execData['real'];
      return "[".Tools::array2plot($theoBacklog).','.Tools::array2plot($realBacklog)."]";
   }

}

// Initialize complex static variables
ProgressHistoryIndicator::staticInit();

?>
