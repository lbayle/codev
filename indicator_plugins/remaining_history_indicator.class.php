<?php
/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Description of BacklogHistoryIndicator
 *
 * @author lob
 */
class BacklogHistoryIndicator implements IndicatorPlugin {

   protected $execData;

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

   public function getName() {
      return __CLASS__;
   }

   public function getSmartyFilename() {
      return 'backlog_history_indicator.html';
   }

   public function getDesc() {
      return "";
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

      $startTimestamp   = mktime(23, 59, 59, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));
      $timestampList = Tools::createTimestampList($startTimestamp, $endTimestamp, $interval);

      $this->execData = array();

      foreach ($timestampList as $timestamp) {
         $backlog = 0;
         foreach ($inputIssueSel->getIssueList() as $issue) {
            $issueRem = $issue->getBacklog($timestamp);
            if (NULL != $issueRem) {
               $backlog += $issueRem;
            } else {
               // if not fount in history, take the MgrEffortEstim (or EffortEstim ??)
               $backlog += $issue->getMgrEffortEstim();
            }
         }
         $this->execData[$timestamp] = $backlog;
      }

      return $this->execData;
   }

   /**
    * $smartyHelper->assign('daysPerJobIndicator', $myIndic->getSmartyObject());
    *
    * @return string
    * @throws Exception
    */
   public function getSmartyObject() {
      if (NULL != $this->execData) {
         $backlogList = array();
         $bottomLabel = array();
         foreach ($this->execData as $timestamp => $backlog) {
            $backlogList[] = (NULL == $backlog) ? 0 : $backlog; // TODO
            $bottomLabel[] = Tools::formatDate("%d %b", $timestamp);
         }

         $strVal1 = implode(':', array_values($backlogList));

         #echo "strVal1 $strVal1<br>";
         $strBottomLabel = implode(':', $bottomLabel);

         $smartyData = Tools::SmartUrlEncode('title='.T_('Backlog history').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('Backlog').'&x1='.$strVal1);

      } else {
         throw new Exception("the execute() method must be called before assignInSmarty().");
      }
      return $smartyData;
   }
}

// Initialize complex static variables
BacklogHistoryIndicator::staticInit();

?>
