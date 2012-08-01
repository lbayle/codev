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

require_once('Logger.php');

include_once('classes/indicator_plugin.interface.php');

require_once ('user_cache.class.php');
require_once ('issue_cache.class.php');
require_once ('issue_selection.class.php');
require_once ('jobs.class.php');
require_once ('team.class.php');


/**
 * Description of RemainingHistoryIndicator
 *
 * @author lob
 */
class RemainingHistoryIndicator {

   private $logger;

   protected $remainingHistory;


   public function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->initialize();
   }

   public function initialize() {

      // get info from DB
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
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params {teamid, startTimestamp, endTimestamp}
    *
    * @exception on missing parameters or other error
    * @return float[] workingDaysPerJob[jobid] = duration
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      if (NULL == $params) {
         throw new Exception("Missing parameters: startTimestamp, endTimestamp, interval");
      }

      $this->logger->debug("execute() ISel=".$inputIssueSel->name.' interval='.$params['interval'].' startTimestamp='.$params['startTimestamp'].' endTimestamp='.$params['endTimestamp']);

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

      $this->remainingHistory = array();

      foreach ($timestampList as $timestamp) {
         $remaining = 0;
         foreach ($inputIssueSel->getIssueList() as $id => $issue) {
            $issueRem = $issue->getRemaining($timestamp);
            if (NULL != $issueRem) {
               $remaining += $issueRem;
            } else {
               // if not fount in history, take the MgrEffortEstim (or EffortEstim ??)
               $remaining += $issue->mgrEffortEstim;
            }
         }
         $this->remainingHistory[$timestamp] = $remaining;
      }

      return $this->remainingHistory;
   }

   /**
    *
    * $smartyHelper->assign('daysPerJobIndicator', $myIndic->getSmartyObject());
    *
    * @return array
    */
   public function getSmartyObject() {

      if (NULL != $this->remainingHistory) {

         $smartyData = array();

         $remainingList = array();
         $bottomLabel = array();
         foreach ($this->remainingHistory as $timestamp => $remaining) {

            $remainingList[] = (NULL == $remaining) ? 0 : $remaining; // TODO
            $bottomLabel[] = Tools::formatDate("%d %b", $timestamp);
         }

         $strVal1 = implode(':', array_values($remainingList));

         #echo "strVal1 $strVal1<br>";
         $strBottomLabel = implode(':', $bottomLabel);

         $smartyData = Tools::SmartUrlEncode('title='.T_('Remaining history').'&bottomLabel='.$strBottomLabel.'&leg1='.T_('Remaining').'&x1='.$strVal1);

      } else {
         throw new Exception("the execute() method must be called before assignInSmarty().");
      }
      return $smartyData;
   }
}

?>
