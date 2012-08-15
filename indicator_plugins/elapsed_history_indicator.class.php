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
 * Description of ElapsedHistoryIndicator
 */
class ElapsedHistoryIndicator implements IndicatorPlugin {

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

   protected $execData;

   public function __construct() {
   }

   public function getName() {
      return __CLASS__;
   }

   public function getSmartyFilename() {
      return 'days_per_job_indicator.html';
   }

   public function getDesc() {
      return T_("Working days per Job");
   }

   /**
    * @param IssueSelection $inputIssueSel
    * @param array $params {teamid, startTimestamp, endTimestamp}
    *
    * @return float[] workingDaysPerJob[jobid] = duration
    * @throws Exception on missing parameters or other error
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {
      if (NULL == $params) {
         throw new Exception("Missing parameters: startTimestamp, endTimestamp, interval");
      }

      self::$logger->debug("execute() ISel=".$inputIssueSel->name.' interval='.$params['interval'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);

      $startTimestamp     = NULL;
      $endTimestamp       = NULL;
      $interval           = NULL;

      if (array_key_exists('startTimestamp', $params)) {
         $startTimestamp = $params['startTimestamp'];
      } else {
         throw new Exception("Missing parameter: startTimestamp");
      }

      if (array_key_exists('endTimestamp', $params)) {
         $endTimestamp = $params['endTimestamp'];
      } else {
         throw new Exception("Missing parameter: endTimestamp");
      }

      if (array_key_exists('interval', $params)) {
         $interval = $params['interval'];
      } else {
         throw new Exception("Missing parameter: interval");
      }
      #echo "BBB start ".date("Y-m-d", $startTimestamp)."end ".date("Y-m-d", $endTimestamp);

      $startTimestamp = mktime(0, 0, 0, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));
      $endTimestamp = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));
      $timestampList = Tools::createTimestampList($startTimestamp, $endTimestamp, $interval);

      $this->execData = array();
      
      // there is no elapsed on first date
      $this->execData[] = 0;

      for($i = 1, $size = count($timestampList); $i < $size; ++$i) {
         $start = $timestampList[$i-1];
         $end = mktime(23, 59, 59, date('m', $timestampList[$i]), date('d',$timestampList[$i]), date('Y', $timestampList[$i]));
         #$elapsed = 0; // cumule / non-cumule

         $elapsed = $inputIssueSel->getElapsed($start, $end);

         #echo "elapsed(".$timestampList[$i].") = ".$elapsed.'<br>';
         $this->execData[$timestampList[$i]] = $elapsed;
      }

      return $this->execData;
   }

   /**
    * $smartyHelper->assign('daysPerJobIndicator', $myIndic->getSmartyObject());
    *
    * @return mixed[]
    */
   public function getSmartyObject() {
      $smartyData = NULL;
      if (NULL != $this->execData) {
         $smartyData = array();
      }
      return $smartyData;
   }
}

// Initialize complex static variables
ElapsedHistoryIndicator::staticInit();

?>
