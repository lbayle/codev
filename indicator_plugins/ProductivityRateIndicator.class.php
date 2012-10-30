<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ProductivityRate
 *
 * @author tuzieblo
 */
class ProductivityRateIndicator implements IndicatorPlugin {
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
      return "Display ProductivityRate history";
   }
   public function getName() {
      return __CLASS__;
   }
   public static function getSmartyFilename() {
      return "plugin/productivity_rate_indicator.html";
   }

  // ----------------------------------------------
  /**
      Compares the EffortEstim to the elapsed time.


   REM: an issue that has been reopened before endTimestamp will NOT be recorded.
   (For the bugs that where re-opened, the EffortEstim may not have been re-estimated,
   and thus the result is not reliable.)

   ProductivityRate = nbResolvedIssues * EffortEstim / elapsed

   @param projects: $prodProjectList or your own selection.
   */
   public function getProductivityRate($projects, $startTimestamp, $endTimestamp) {

      $resolvedList = array();
      $productivityRate = array(); // {'MEE', 'EE'}
      $productivityRate['MEE'] = 0;
      $productivityRate['EE']  = 0;

      $totalElapsed = 0;

      $bugResolvedStatusThreshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);

      // --------
      $formatedProjList = implode(', ', $projects);
      if ("" == $formatedProjList) {
         // TODO throw exception
         echo "<div style='color:red'>ERROR getProductivRate: no project defined for this team !<br/></div>";
         return 0;
      }

      // all bugs which status changed to 'resolved' whthin the timestamp
      $query = "SELECT mantis_bug_table.id, ".
              "mantis_bug_history_table.new_value, ".
              "mantis_bug_history_table.old_value, ".
              "mantis_bug_history_table.date_modified ".
              "FROM `mantis_bug_table`, `mantis_bug_history_table` " .
              "WHERE mantis_bug_table.id = mantis_bug_history_table.bug_id " .
              "AND mantis_bug_table.project_id IN ($formatedProjList) " .
              "AND mantis_bug_history_table.field_name='status' " .
              "AND mantis_bug_history_table.date_modified >= $startTimestamp " .
              "AND mantis_bug_history_table.date_modified <  $endTimestamp " .
              "AND mantis_bug_history_table.new_value = $bugResolvedStatusThreshold " .
              " ORDER BY mantis_bug_table.id DESC";

      self::$logger->debug("getProductivRate QUERY = $query");
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check if the bug has been reopened before endTimestamp
         $issue = IssueCache::getInstance()->getIssue($row->id);
         $latestStatus = $issue->getStatus($this->endTimestamp);

         if ($latestStatus >= $bugResolvedStatusThreshold) {

            // remove doubloons
            if (!in_array($row->id, $resolvedList)) {
               self::$logger->debug("getProductivRate() Found : bugid = $row->id, old_status=$row->old_value, new_status=$row->new_value, mgrEE=" . $issue->getMgrEffortEstim() . " date_modified=" . date("d F Y", $row->date_modified) . ", effortEstim=" . $issue->getEffortEstim() . ", BS=" . $issue->getEffortAdd() . ", elapsed = " . $issue->getElapsed());

               $resolvedList[] = $row->id;

               $totalElapsed += $issue->getElapsed();

               self::$logger->debug("getProductivRate(MEE) : ".$productivityRate['MEE']." + " . $issue->getMgrEffortEstim() . " = " . ($productivityRate['MEE'] + $issue->getMgrEffortEstim()));
               $productivityRate['MEE'] += $issue->getMgrEffortEstim();
               self::$logger->debug("getProductivRate(EE) : ".$productivityRate['EE']." + (" . $issue->getEffortEstim() . " + " . $issue->getEffortAdd() . ") = " . ($productivityRate['EE'] + $issue->getEffortEstim() + $issue->getEffortAdd()));
               $productivityRate['EE'] += $issue->getEffortEstim() + $issue->getEffortAdd();
            }
         } else {
            $statusName = Constants::$statusNames[$latestStatus];
            self::$logger->debug("getProductivRate REOPENED : bugid = $row->id status = " . $statusName);
         }
      }

      // -------
      self::$logger->debug("getProductivRate: productivityRate (MEE) = " . $productivityRate['MEE'] . " / $totalElapsed, nbBugs=" . count($resolvedList));
      self::$logger->debug("getProductivRate: productivityRate (EE) = " . $productivityRate['EE'] . " / $totalElapsed, nbBugs=" . count($resolvedList));

      if (0 != $totalElapsed) {
         $productivityRate['MEE'] /= $totalElapsed;
         $productivityRate['EE'] /= $totalElapsed;
      } else {
         $productivityRate['MEE'] = 0;
         $productivityRate['EE'] = 0;
      }

      return $productivityRate;
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

      $projects = array_keys($team->getProjects(FALSE, FALSE, FALSE)); // TODO

      $prodRateTableMEE = array();
      $prodRateTableEE = array();
      foreach ($this->timeTrackingTable as $date => $timeTracking) {
         $prodRate = $this->getProductivityRate(
                 $projects,
                 $timeTracking->getStartTimestamp(),
                 $timeTracking->getEndTimestamp());

         $prodRateTableMEE[$timeTracking->getStartTimestamp()] = $prodRate['MEE'];
         $prodRateTableEE[$timeTracking->getStartTimestamp()] = $prodRate['EE'];
      }
      $this->execData = array();
      $this->execData['MEE'] = $prodRateTableMEE;
      $this->execData['EE'] = $prodRateTableEE;

   }



   public function getSmartyObject() {


      $xaxis = array();
      foreach(array_keys($this->execData['MEE']) as $timestamp) {
         $xaxis[] = '"'.date(T_('Y-m'), $timestamp).'"';
      }
      $json_xaxis = Tools::array2plot($xaxis);

      $jsonMEE = Tools::array2plot(array_values($this->execData['MEE']));
      $jsonEE  = Tools::array2plot(array_values($this->execData['EE']));

      $graphData = "[$jsonMEE,$jsonEE]";

      $graphDataColors = '["#fcbdbd", "#c2dfff"]';

      $labels = '["ProductivityRate Mgr", "ProductivityRate"]';

      return array(
         'prodRate_history_xaxis'       => $json_xaxis,
         'prodRate_history_data'       => $graphData,
         'prodRate_history_dataColors' => $graphDataColors,
         'prodRate_history_dataLabels' => $labels,
      );

   }
}

// Initialize complex static variables
ProductivityRateIndicator::staticInit();

?>
