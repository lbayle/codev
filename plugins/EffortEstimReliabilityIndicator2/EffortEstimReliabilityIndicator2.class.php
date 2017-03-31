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
 * Description of EffortEstimReliability
 *
 */
class EffortEstimReliabilityIndicator2 extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval';

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $interval;

   // internal
   private $formatedBugidList;
   private $bugResolvedStatusThreshold;
   protected $execData;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_QUALITY
      );
   }

   public static function getName() {
      return T_('Effort estimation reliability rate');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Display the EffortEstim reliability rate history')."<br>".
         T_('rate = EffortEstim / elapsed (on resolved tasks only)');
      if (!$isShortDesc) {
         $desc .= "<br><br>".
            T_('REM: An issue that has been reopened before endTimestamp will NOT be recorded.')."<br>"
            .T_('(For the bugs that where re-opened, the EffortEstim may not have been re-estimated, and thus the result is not reliable.)');
      }
      return $desc;
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
          'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasOverlay.min.js',
         'js_min/chart.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL)) {
         $this->interval = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL);
      } else {
         $this->interval = 30;
      }
      //self::$logger->debug('dataProvider '.PluginDataProviderInterface::PARAM_INTERVAL.'= '.$this->interval);

      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      $this->formatedBugidList = implode(', ', $bugidList);

      $this->bugResolvedStatusThreshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);


      // set default pluginSettings (not provided by the PluginDataProvider)

   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {
            switch ($pluginSettings[self::OPTION_INTERVAL]) {
               case 'oneWeek':
                  $this->interval = 7;
                  break;
               case 'twoWeeks':
                  $this->interval = 14;
                  break;
               case 'oneMonth':
                  $this->interval = 30;
                  break;
               default:
                  self::$logger->warn('option '.self::OPTION_INTERVAL.'= '.$pluginSettings[self::OPTION_INTERVAL]." (unknown value)");
            }
         }
      }
   }

  // ----------------------------------------------
  /**
    * Compare the EffortEstim to the elapsed time on a time period.
    *
    * REM: An issue that has been reopened before endTimestamp will NOT be recorded.
    * (For the bugs that where re-opened, the EffortEstim may not have been re-estimated,
    * and thus the result is not reliable.)
    *
    * EffortEstimReliabilityRate = EffortEstim / elapsed (on ResolvedIssues)
    *
    * @param type $startTimestamp
    * @param type $endTimestamp
    * @return int rate
    * @throws Exception
    */
   private function getEffortEstimReliabilityRate($startTimestamp, $endTimestamp) {

      $resolvedList = array();
      $EEReliability = array(); // {'MEE', 'EE'}
      $EEReliability['MEE'] = 0;
      $EEReliability['EE']  = 0;

      $totalElapsed = 0;

      // --------
      // all bugs which status changed to 'resolved' within the timestamp
      $query = "SELECT {bug}.id, ".
              "{bug_history}.new_value, ".
              "{bug_history}.old_value, ".
              "{bug_history}.date_modified ".
              "FROM `{bug}`, `{bug_history}` " .
              "WHERE {bug}.id = {bug_history}.bug_id " .
              "AND {bug}.id IN ($this->formatedBugidList) " .
              "AND {bug_history}.field_name='status' " .
              "AND {bug_history}.date_modified >= $startTimestamp " .
              "AND {bug_history}.date_modified <  $endTimestamp " .
              "AND {bug_history}.new_value = $this->bugResolvedStatusThreshold " .
              "ORDER BY {bug}.id DESC";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check if the bug has been reopened before endTimestamp
         $issue = IssueCache::getInstance()->getIssue($row->id);
         $latestStatus = $issue->getStatus($this->endTimestamp);

         if ($latestStatus >= $this->bugResolvedStatusThreshold) {

            // remove doubloons
            if (!in_array($row->id, $resolvedList)) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getEffortEstimReliabilityRate() Found : bugid = $row->id, old_status=$row->old_value, new_status=$row->new_value, mgrEE=" . $issue->getMgrEffortEstim() . " date_modified=" . date("d F Y", $row->date_modified) . ", effortEstim=" . $issue->getEffortEstim() . ", BS=" . $issue->getEffortAdd() . ", elapsed = " . $issue->getElapsed());
               }
               $resolvedList[] = $row->id;

               $totalElapsed += $issue->getElapsed();

               $EEReliability['MEE'] += $issue->getMgrEffortEstim();
               $EEReliability['EE'] += $issue->getEffortEstim() + $issue->getEffortAdd();

               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("getEffortEstimReliabilityRate(MEE) : ".$EEReliability['MEE']." + " . $issue->getMgrEffortEstim() . " = " . ($EEReliability['MEE'] + $issue->getMgrEffortEstim()));
                  self::$logger->debug("getEffortEstimReliabilityRate(EE) : ".$EEReliability['EE']." + (" . $issue->getEffortEstim() . " + " . $issue->getEffortAdd() . ") = " . ($EEReliability['EE'] + $issue->getEffortEstim() + $issue->getEffortAdd()));
               }
            }
         } else {
            $statusName = Constants::$statusNames[$latestStatus];
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("getEffortEstimReliabilityRate REOPENED : bugid = $row->id status = " . $statusName);
            }
         }
      }

      // -------
      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("getEffortEstimReliabilityRate: Reliability (MEE) = " . $EEReliability['MEE'] . " / $totalElapsed, nbBugs=" . count($resolvedList));
         self::$logger->debug("getEffortEstimReliabilityRate: Reliability (EE) = " . $EEReliability['EE'] . " / $totalElapsed, nbBugs=" . count($resolvedList));
      }

      if (0 != $totalElapsed) {
         $EEReliability['MEE'] /= $totalElapsed;
         $EEReliability['EE'] /= $totalElapsed;
      } else {
         $EEReliability['MEE'] = 1;
         $EEReliability['EE'] = 1;
      }

      return $EEReliability;
   }

   /**
    *
    *
    */
   public function execute() {

      if (0 == count($this->inputIssueSel->getIssueList())) {
               $this->execData = NULL;
      } else {
         $reliabilityTableMEE = array();
         $reliabilityTableEE = array();

         $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
         //$startTimestamp = mktime(23, 59, 59, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
         $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d',$this->endTimestamp), date('Y', $this->endTimestamp));

         // --------------------
         // interval = 4
         // [j1  0h, j4  23h59]
         // [j5  0h, j8  23h59]
         // [j9  0h, j12 23h59]
         $interval = $this->interval - 1; // tweek strtotime
         $timestamp = $startTimestamp;
         while ($timestamp < $endTimestamp) {

            // --- find period timestamps
            $periodStartTimestamp = $timestamp;
            $tmpTs = strtotime("+$interval day",$timestamp);
            $periodEndTimestamp = mktime(23, 59, 59, date('m', $tmpTs), date('d',$tmpTs), date('Y', $tmpTs));
            if ($periodEndTimestamp > $endTimestamp) {
               $periodEndTimestamp = $endTimestamp;
            }
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("period [ ".date("Y-m-d H:i:s", $periodStartTimestamp)." - ".date("Y-m-d H:i:s", $periodEndTimestamp));
            }

            // --- compute period rate
            $prodRate = $this->getEffortEstimReliabilityRate($periodStartTimestamp, $periodEndTimestamp);

            $formatedTimestamp = Tools::formatDate("%Y-%m-%d", $periodStartTimestamp);
            $reliabilityTableMEE[$formatedTimestamp] = $prodRate['MEE'];
            $reliabilityTableEE[$formatedTimestamp] = $prodRate['EE'];

            $timestamp = $periodEndTimestamp + 1;  // add 1 sec to flip to next day
         }

         $this->execData = array();
         $this->execData['MEE'] = $reliabilityTableMEE;
         $this->execData['EE'] = $reliabilityTableEE;
      }
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {

      if (!is_null($this->execData)) {
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
            $formattedDate = Tools::formatDate("%Y-%m-%d", $timestamp);

            $tableData[$formattedDate] = array(
                'prodRateMEE' => round($prodRateMEE, 2),
                'prodRateEE' => round($prodRateEE, 2)
            );
         }
      } else {
         $tableData = NULL;
         $graphData = NULL;
         $labels = NULL;
         $start = NULL;
         $end = NULL;
      }
      $smartyVariables = array(
         'effortEstimReliabilityIndicator2_tableData' => $tableData,
         'effortEstimReliabilityIndicator2_jqplotData' => $graphData,
         #'prodRate_history_dataColors' => $graphDataColors,
         'effortEstimReliabilityIndicator2_dataLabels' => $labels,
         'effortEstimReliabilityIndicator2_plotMinDate' => $start,
         'effortEstimReliabilityIndicator2_plotMaxDate' => $end,

         // add pluginSettings (if needed by smarty)
         'effortEstimReliabilityIndicator2_'.self::OPTION_INTERVAL => $this->interval,

      );
      if (false == $isAjaxCall) {
         $smartyVariables['effortEstimReliabilityIndicator2_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['effortEstimReliabilityIndicator2_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

EffortEstimReliabilityIndicator2::staticInit();
