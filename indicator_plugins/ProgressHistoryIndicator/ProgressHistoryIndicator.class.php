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
 * Description of ProgressHistoryIndicator
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

   public static function getSmartyFilename() {
      return Constants::$codevIndicatorPluginsDir.DS.__CLASS__.DS.__CLASS__.".html";
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

   /**
    * @param IssueSelection $inputIssueSel
    * @param int[] $timestampList
    */
   private function getBacklogData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->backlogData = array();

      $mgrEffortEstimCache = array();

      // get a snapshot of the Backlog at each timestamp
      $issues = $inputIssueSel->getIssueList();
      krsort($timestampList);
      foreach ($timestampList as $timestamp) {
         $backlog = 0;
         #echo "=========getBacklogData ".date('Y-m-d H:i:s', $timestamp)."<br>";
         foreach ($issues as $issue) {
            if(!array_key_exists($issue->getId(),$mgrEffortEstimCache)) {
               if($timestamp >= $issue->getDateSubmission()) {

                  if ($issue->isResolved($timestamp)) {
                     #echo "issue ".$issue->getId()." isResolved at ".date('Y-m-d H:i:s', $timestamp)."<br>";
                     $issueBacklog = 0;
                  } else {
                     $issueBL = $issue->getBacklog($timestamp);
                     if ((!is_null($issueBL) && ('' != $issueBL))) {
                        $issueBacklog = $issueBL;
                     } else {
                        // if not fount in history, take max(MgrEffortEstim, EffortEstim)
                        $issueEE    = $issue->getEffortEstim() + $issue->getEffortAdd();
                        $issueEEMgr = $issue->getMgrEffortEstim();
                        $issueBacklog = max(array($issueEE, $issueEEMgr));

                        $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
                     }

                  }

               } else {
                  // issue does not exist at this date, take max(MgrEffortEstim, EffortEstim)
                  // Note: getDuration() would return 0 which in this case is wrong
                  $issueEE    = $issue->getEffortEstim() + $issue->getEffortAdd();
                  $issueEEMgr = $issue->getMgrEffortEstim();
                  $issueBacklog = max(array($issueEE, $issueEEMgr));
                  $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
               }
            } else {
               $issueBacklog = $mgrEffortEstimCache[$issue->getId()];
            }
            #echo "issue ".$issue->getId()." issueBacklog = $issueBacklog  getBacklog = $issueBL<br>";
            $backlog += $issueBacklog;
         }

         #echo "backlog(".date('Y-m-d H:i:s', $timestamp).") = ".$backlog.'<br>';
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
         $this->backlogData[$midnight_timestamp] = $backlog;
      }
      // No need to sort, values are get by the index
      //ksort($this->backlogData);
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
    * Deux courbes:
    *
    * RAF Theorique = charge initiale - cumul consomé
    *
    * RAF Reel      = cumul consomé / (cumul consomé +  RAF)
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {
      $this->checkParams($inputIssueSel, $params);

      $startTimestamp = mktime(23, 59, 59, date('m', $params['startTimestamp']), date('d', $params['startTimestamp']), date('Y', $params['startTimestamp']));
      $endTimestamp   = mktime(23, 59, 59, date('m', $params['endTimestamp']), date('d',$params['endTimestamp']), date('Y', $params['endTimestamp']));

      #echo "Backlog start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$params['interval']."<br>";
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
      $iselMaxEE = max(array($inputIssueSel->mgrEffortEstim, ($inputIssueSel->effortEstim + $inputIssueSel->effortAdd)));
      $sumElapsed = 0;
      $nbZeroDivErrors1 = 0;
      $nbZeroDivErrors2 = 0;
      foreach ($timestampList as $timestamp) {
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

         // ========= RAF theorique
         // Indicateur = charge initiale - cumul consomé
         if(array_key_exists($midnight_timestamp,$this->elapsedData)) {
            #echo "sumElapsed += ".$this->elapsedData[$midnight_timestamp]." from ".date('Y-m-d H:i:s', $midnight_timestamp)."<br>";
            $sumElapsed += $this->elapsedData[$midnight_timestamp];
         }
         if (0 != $iselMaxEE) {
            $val1 = $sumElapsed / $iselMaxEE;

         } else {
            // TODO
            $val1 = 0;
            $nbZeroDivErrors1 += 1;
            //self::$logger->error("Division by zero ! (mgrEffortEstim)");
         }
         if ($val1 > 1) {$val1 = 1;}
         $theoBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val1 * 100, 2);

         // =========  RAF reel
        // Indicateur = Conso. Cumulé / (Conso. Cumulé +  RAF)

         $tmp = ($sumElapsed + $this->backlogData[$midnight_timestamp]);
         if (0 != $tmp) {
            $val2 = $sumElapsed / $tmp;
         } else {
            // TODO
            $val2 = 0;
            $nbZeroDivErrors2 += 1;
            //self::$logger->error("Division by zero ! (elapsed + realBacklog)");
         }
         $realBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val2 * 100, 2);

         #echo "(".date('Y-m-d', $midnight_timestamp).") sumElapsed = $sumElapsed BacklogData = ".$this->backlogData[$midnight_timestamp]." MaxEE = ".$iselMaxEE.'<br>';
         #echo "(".date('Y-m-d', $midnight_timestamp).") theoBacklog = ".$theoBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)]." realBacklog = ".$realBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)].'<br>';


      } // foreach timestamp

      // PERF logging is slow, factorize errors
      if ($nbZeroDivErrors1 > 0) {
         self::$logger->error("$nbZeroDivErrors1 Division by zero ! (mgrEffortEstim)");
      }
      if ($nbZeroDivErrors2 > 0) {
         self::$logger->error("$nbZeroDivErrors2 Division by zero ! (elapsed + realBacklog)");
      }

      $this->execData = array();
      $this->execData['theo'] = $theoBacklog;
      $this->execData['real'] = $realBacklog;
   }

   public function getSmartyObject() {

      $theoBacklog = $this->execData['theo'];
      $realBacklog = $this->execData['real'];

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $smartyVariables = array();
      
      if ($startTimestamp <= $endTimestamp) {
	      $smartyVariables['progress_history_plotMinDate'] = Tools::formatDate("%Y-%m-01", $startTimestamp);
	      $smartyVariables['progress_history_plotMaxDate'] = Tools::formatDate("%Y-%m-01", $endTimestamp);
	      $smartyVariables['progress_history_interval'] = ceil($this->interval/20);
	      $smartyVariables['progress_history_data'] = "[".Tools::array2plot($theoBacklog).','.Tools::array2plot($realBacklog)."]";
      }

      return $smartyVariables;
   }

}

// Initialize complex static variables
ProgressHistoryIndicator::staticInit();

?>
