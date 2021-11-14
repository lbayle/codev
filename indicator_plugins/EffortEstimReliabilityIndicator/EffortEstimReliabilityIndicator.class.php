<?php

/**
 * Description of EffortEstimReliability
 *
 */
class EffortEstimReliabilityIndicator implements IndicatorPlugin {
   //put your code here
   /**
    * @var Logger The logger
    */
   private static $logger;
   private $id;

   protected $execData;

   protected $teamid;
   protected $timeTrackingTable; // table of TimeTracking class instances

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
      return "Display EffortEstimReliability history";
   }
   public function getName() {
      return __CLASS__;
   }
   public static function getSmartyFilename() {
      return Constants::$codevRootDir.DIRECTORY_SEPARATOR.self::indicatorPluginsDir.DIRECTORY_SEPARATOR.__CLASS__.DIRECTORY_SEPARATOR.__CLASS__.".html";
   }

  // ----------------------------------------------
  /**
      Compares the EffortEstim to the elapsed time.


   REM: an issue that has been reopened before endTimestamp will NOT be recorded.
   (For the bugs that where re-opened, the EffortEstim may not have been re-estimated,
   and thus the result is not reliable.)

   EffortEstimReliabilityRate = nbResolvedIssues * EffortEstim / elapsed

   @param projects: $prodProjectList or your own selection.
   */
   public function getEffortEstimReliabilityRate($projects, $startTimestamp, $endTimestamp) {

      $resolvedList = array();
      $EEReliability = array(); // {'MEE', 'EE'}
      $EEReliability['MEE'] = 0;
      $EEReliability['EE']  = 0;

      $totalElapsed = 0;

      $bugResolvedStatusThreshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);

      // --------
      $formatedProjList = implode(', ', $projects);
      if ("" == $formatedProjList) {
         // TODO throw exception
         echo "<div style='color:red'>ERROR getEffortEstimReliabilityRate: no project defined for this team !<br/></div>";
         return 0;
      }

      // all bugs which status changed to 'resolved' whthin the timestamp
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT {bug}.id, ".
              "{bug_history}.new_value, ".
              "{bug_history}.old_value, ".
              "{bug_history}.date_modified ".
              "FROM {bug}, {bug_history} " .
              "WHERE {bug}.id = {bug_history}.bug_id " .
              " AND {bug}.project_id IN (".$formatedProjList.") " .
              " AND {bug_history}.field_name='status' " .
              " AND {bug_history}.date_modified >= ".$sql->db_param() .
              " AND {bug_history}.date_modified < ".$sql->db_param() .
              " AND {bug_history}.new_value = ".$sql->db_param() .
              " ORDER BY {bug}.id DESC";

      $result = $sql->sql_query($query, array($startTimestamp, $endTimestamp, $bugResolvedStatusThreshold));

      while ($row = $sql->fetchObject($result)) {

         // check if the bug has been reopened before endTimestamp
         $issue = IssueCache::getInstance()->getIssue($row->id);
         $latestStatus = $issue->getStatus($this->endTimestamp);

         if ($latestStatus >= $bugResolvedStatusThreshold) {

            // remove doubloons
            if (!in_array($row->id, $resolvedList)) {
               $resolvedList[] = $row->id;

               $totalElapsed += (float)$issue->getElapsed();

               $EEReliability['MEE'] += (float)$issue->getMgrEffortEstim();
               $EEReliability['EE'] += (float)$issue->getEffortEstim();
            }
         } else {
            $statusName = Constants::$statusNames[$latestStatus];
         }
      }

      // -------

      if (0 != $totalElapsed) {
         $EEReliability['MEE'] /= $totalElapsed;
         $EEReliability['EE'] /= $totalElapsed;
      } else {
         $EEReliability['MEE'] = 0;
         $EEReliability['EE'] = 0;
      }

      return $EEReliability;
   }

   /**
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    * @throws Exception
    */
   private function checkParams(IssueSelection $inputIssueSel, array $params = NULL) {

      if (array_key_exists('timeTrackingTable', $params)) {

         if ((!is_array($params['timeTrackingTable'])) ||
             (empty($params['timeTrackingTable']))) {
            throw new Exception("Parameter 'timeTrackingTable' must be an array of TimeTracking class instances");
         }
         $this->timeTrackingTable = $params['timeTrackingTable'];

      } else {
         throw new Exception("Missing parameter: timeTrackingTable");
      }

      if (array_key_exists('teamid', $params)) {
         $this->teamid = $params['teamid'];
      } else {
         throw new Exception("Missing parameter: teamid");
      }

   }



   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->checkParams($inputIssueSel, $params);

      $team = TeamCache::getInstance()->getTeam($this->teamid);

      $projects = array_keys($team->getProjects(FALSE, TRUE, FALSE)); // TODO

      $reliabilityTableMEE = array();
      $reliabilityTableEE = array();
      foreach ($this->timeTrackingTable as $date => $timeTracking) {
         $prodRate = $this->getEffortEstimReliabilityRate($projects, $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp());

         $timestamp = Tools::formatDate("%Y-%m-01", $timeTracking->getStartTimestamp());
         $reliabilityTableMEE[$timestamp] = $prodRate['MEE'];
         $reliabilityTableEE[$timestamp] = $prodRate['EE'];
      }
      $this->execData = array();
      $this->execData['MEE'] = $reliabilityTableMEE;
      $this->execData['EE'] = $reliabilityTableEE;
   }

   public function getSmartyObject() {
      $timestamp = Tools::getStartEndKeys($this->execData['MEE']);
      $start = Tools::formatDate("%Y-%m-01", Tools::date2timestamp($timestamp[0]));
      $end = Tools::formatDate("%Y-%m-01", strtotime($timestamp[1]." +1 month"));

      $jsonMEE = Tools::array2plot($this->execData['MEE']);
      $jsonEE  = Tools::array2plot($this->execData['EE']);

      $graphData = "[$jsonMEE,$jsonEE]";

      #$graphDataColors = '["#fcbdbd", "#c2dfff"]';

      $labels = '["MgrEffortEstim ReliabilityRate", "EffortEstim ReliabilityRate"]';

      $tableData = array();
      foreach ($this->execData['MEE'] as $date => $prodRateMEE) {
         $prodRateEE = $this->execData['EE'][$date];

         $timestamp = Tools::date2timestamp($date);
         $formattedDate = Tools::formatDate("%B %Y", $timestamp);

         $tableData[$formattedDate] = array(
             'prodRateMEE' => round($prodRateMEE, 2),
             'prodRateEE' => round($prodRateEE, 2)
         );
      }


      return array(
         'prodRate_history_data'       => $graphData,
         #'prodRate_history_dataColors' => $graphDataColors,
         'prodRate_history_dataLabels' => $labels,
         'prodRate_history_plotMinDate'      => $start,
         'prodRate_history_plotMaxDate'      => $end,
         'prodRate_tableData' => $tableData
      );

   }
}

// Initialize complex static variables
EffortEstimReliabilityIndicator::staticInit();

?>
