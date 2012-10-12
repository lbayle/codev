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
 * Description of StatusHistoryIndicator
 *
 */
class StatusHistoryIndicator implements IndicatorPlugin {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   private $statusData;

   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct() {

   }

   public function getDesc() {
      return "Display Issue Status history";
   }
   public function getName() {
      return __CLASS__;
   }
   public static function getSmartyFilename() {
      return "plugin/status_history_indicator.html";
   }


   /**
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    * @throws Exception
    */
   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {

      if (is_null($inputIssueSel)) {
         throw new Exception("Missing IssueSelection");
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
   private function getStatusData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->statusData = array();

      $historyStatusNew = array();  // timestamp => nbIssues
      $historyStatusFeedback = array();  // timestamp => nbIssues
      $historyStatusOngoing = array();  // timestamp => nbIssues
      $historyStatusResolved = array();  // timestamp => nbIssues
      $historyStatusTotal = array();  // timestamp => nbIssues


      // get a snapshot of the Status at each timestamp
      $issues = $inputIssueSel->getIssueList();
      foreach ($timestampList as $timestamp) {

         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

        // all timestamps must be defined, even if empty
         $historyStatusNew["$midnight_timestamp"] = 0;
         $historyStatusFeedback["$midnight_timestamp"] = 0;
         $historyStatusOngoing["$midnight_timestamp"] = 0;
         $historyStatusResolved["$midnight_timestamp"] = 0;
         $historyStatusTotal["$midnight_timestamp"] = 0;

         foreach ($issues as $issue) {
            $issueStatus = $issue->getStatus($timestamp);

            // if issue exists at this date
            if ( (-1) != $issueStatus) {

               if ($issueStatus >= $issue->getBugResolvedStatusThreshold()) {
                  $historyStatusResolved["$midnight_timestamp"] += 1;
               } else if ($issueStatus == Constants::$status_feedback) {
                  $historyStatusFeedback["$midnight_timestamp"] += 1;
               } else if ($issueStatus == Constants::$status_new) {
                  $historyStatusNew["$midnight_timestamp"] += 1;
               } else {
                  $historyStatusOngoing["$midnight_timestamp"] += 1;
               }
               $historyStatusTotal["$midnight_timestamp"] += 1;
               #echo date('Y-m-d', $timestamp)." issue ".$issue->getId()." status ".$issueStatus."<br>";
            }
         }
         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug('Y-m-d', $midnight_timestamp).
                    ' new '.$historyStatusNew["$midnight_timestamp"].
                    'feedback '.$historyStatusFeedback["$midnight_timestamp"].
                    'ongoing '.$historyStatusOngoing["$midnight_timestamp"].
                    'resolved '.$historyStatusResolved["$midnight_timestamp"].
                    'total '.$historyStatusTotal["$midnight_timestamp"];
         }
      }

      $this->statusData = array(
          'new'      => $historyStatusNew,
          'feedback' => $historyStatusFeedback,
          'ongoing'  => $historyStatusOngoing,
          'resolved' => $historyStatusResolved,
          'total'    => $historyStatusTotal);


      return $this->statusData;
   }


   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      $startTimestamp = mktime(23, 59, 59, date('m', $params['startTimestamp']), date('d', $params['startTimestamp']), date('Y', $params['startTimestamp']));
      $endTimestamp   = mktime(23, 59, 59, date('m', $params['endTimestamp']), date('d',$params['endTimestamp']), date('Y', $params['endTimestamp']));

      //echo "StatusHistoryIndicator start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$params['interval']."<br>";
      $timestampList  = Tools::createTimestampList($startTimestamp, $endTimestamp, $params['interval']);

      $this->getStatusData($inputIssueSel, $timestampList);



   }



   public function getSmartyObject() {

      $historyStatusNew      = $this->statusData['new'];  // timestamp => nbIssues
      $historyStatusFeedback = $this->statusData['feedback'];
      $historyStatusOngoing  = $this->statusData['ongoing'];
      $historyStatusResolved = $this->statusData['resolved'];
      $historyStatusTotal    = $this->statusData['total'];


      $xaxis = array();
      foreach(array_keys($historyStatusNew) as $timestamp) {
         $xaxis[] = '"'.date(T_('Y-m-d'), $timestamp).'"';
      }
      $json_xaxis = Tools::array2plot($xaxis);

#echo $json_xaxis.'<br>';


      $jsonNew      = Tools::array2plot(array_values($historyStatusNew));
      $jsonFeedback = Tools::array2plot(array_values($historyStatusFeedback));
      $jsonOngoing  = Tools::array2plot(array_values($historyStatusOngoing));
      $jsonResolved = Tools::array2plot(array_values($historyStatusResolved));
      $jsonTotal    = Tools::array2plot(array_values($historyStatusTotal));


      $graphData = "[$jsonNew,$jsonFeedback,$jsonOngoing,$jsonResolved]";
#echo $graphData.'<br>';

      $graphDataColors = '["#fcbdbd", "#e3b7eb", "#c2dfff", "#d2f5b0"]';

      $labels1 = '["new", "feedback", "ongoing", "resolved"]';

      return array(
         'status_history_xaxis'  => $json_xaxis,
         'status_history_data1'   => $graphData,
         'status_history_data1Colors' => $graphDataColors,
         'status_history_data1Labels'   => $labels1,
         'status_history_data2'   => $jsonTotal,
      );
   }

}

// Initialize complex static variables
StatusHistoryIndicator::staticInit();
?>
