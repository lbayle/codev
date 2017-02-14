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
 * Description of ReopenedRateIndicator
 *
 */
class ReopenedRateIndicator2 extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // defaultValue, oneWeek, twoWeeks, oneMonth

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $interval;

   // internal
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
      return T_('Reopened rate history');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Display the bug reopened rate history');
      if (!$isShortDesc) {
         $desc .= '<br><br>';
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
         'js_min/helpdialog.min.js',
         'js_min/progress.min.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         //'lib/jquery.jqplot/plugins/jqplot.canvasOverlay.min.js',
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

      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      $this->formatedBugidList = implode(', ', $bugidList);
/*      if (empty($this->formatedBugidList)) {
         throw new Exception('No issues in IssueSelection !');
      }
 */
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

   /**
    * all bugs changing status from 'resolved' (or higher) status to a lower status
    *
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getReopened($formattedBugidList, $start, $end) {

      // 1) get all reopened bugs within the timestamp
      $query = "SELECT bug.* " .
               "FROM `mantis_bug_table` as bug ".
               "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id " .
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND history.field_name='status' " .
               "AND history.date_modified >= $start AND history.date_modified < $end " .
               "AND history.old_value >= get_project_resolved_status_threshold(bug.project_id) " .
               "AND history.new_value <  get_project_resolved_status_threshold(bug.project_id) " .
               "GROUP BY bug.id ;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // 2) count reopened and (still) fixed issues at the end of the period
      $bugidList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check that it is still in a 'non-resolved' state.
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $latestStatus = $issue->getStatus($end);
         if ($latestStatus < $issue->getBugResolvedStatusThreshold()) {
            $bugidList[] = $row->id;
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("reopened but then resolved: $row->id");
            }
         }

      }

      // remove duplicated values
      $bugidList = array_unique($bugidList);
      return $bugidList;
   }

   /**
    * all bugs changing status from 'resolved' to a higher status (validated, closed)
    * and that it is still validated at the end of the period.
    *
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getValidated($formattedBugidList, $start, $end) {

      $query = "SELECT bug.* ".
               "FROM `mantis_bug_table` as bug ".
               "JOIN `mantis_bug_history_table` as history ON bug.id = history.bug_id " .
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND history.field_name='status' " .
               "AND history.date_modified >= $start AND history.date_modified < $end " .
               "AND history.old_value <= get_project_resolved_status_threshold(bug.project_id) " .
               "AND history.new_value >  get_project_resolved_status_threshold(bug.project_id) " .
               "GROUP BY bug.id ;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $bugidList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         // check that it is still in a 'validated' state.
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $latestStatus = $issue->getStatus($end);
         if ($latestStatus > $issue->getBugResolvedStatusThreshold()) {
            $bugidList[] = $row->id;
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug( "validated but then reopened: $row->id");
            }
         }
      }

      // remove duplicated values
      $bugidList = array_unique($bugidList);
      return $bugidList;
   }

   /**
    * Returns all Issues resolved in the period, including re-opened and validated.
    * 
    * @param array $formattedBugidList comma-separated list to be included in SQL request
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @return array bugidList
    */
   private function getResolved($formattedBugidList, $start, $end) {

      // all bugs which status changed to 'resolved' whthin the timestamp
      $query = "SELECT bug.id ".
               "FROM `mantis_bug_table` as bug, `mantis_bug_history_table` as history ".
               "WHERE bug.id IN ($formattedBugidList) " .
               "AND bug.id = history.bug_id ".
               "AND history.field_name='status' ".
               "AND history.date_modified >= $start AND history.date_modified < $end ".
               "AND history.new_value = get_project_resolved_status_threshold(project_id) ";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $resolvedList = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         $resolvedList[] = $row->id;
      }

      // remove duplicated values
      $resolvedList = array_unique($resolvedList);
      return $resolvedList;
   }


   /**
    *
    * 
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {

      if (0 == count($this->inputIssueSel->getIssueList())) {
               $this->execData = NULL;
      } else {
         // -------- elapsed in the period
         $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
         $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d', $this->endTimestamp), date('Y', $this->endTimestamp));

         $timestampList = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);

         // ------ compute

         #$resolvedList = array();
         #$nbResolvedList = array();
         $reopenedList = array();
         $nbReopenedList = array();
         $validatedList = array();
         $nbValidatedList = array();
         $reopenedPercentList = array();
         $validatedPercentList = array();

         $formattedBugidList = implode(', ', array_keys($this->inputIssueSel->getIssueList()));

         for($i = 1, $size = count($timestampList); $i < $size; ++$i) {

            $start = $timestampList[$i-1];

            $lastDay = ($i + 1 < $size) ? strtotime("-1 day",$timestampList[$i]) : $timestampList[$i];
            $end   = mktime(23, 59, 59, date('m', $lastDay), date('d',$lastDay), date('Y', $lastDay));

            $midnight_timestamp = mktime(0, 0, 0, date('m', $timestampList[$i]), date('d', $timestampList[$i]), date('Y', $timestampList[$i]));
            $key = Tools::formatDate('%Y-%m-%d', $midnight_timestamp);

            // -------

            $reopenedBugidList = $this->getReopened($formattedBugidList, $start, $end);
            $validatedBugidList = $this->getValidated($formattedBugidList, $start, $end);

            // WARN: the tiestamp list may return something like this:
            // timestamp = 2013-01-14 00:00:00
            // timestamp = 2013-01-21 00:00:00
            // timestamp = 2013-01-21 23:59:59
            if (array_key_exists($key, $reopenedList)) {
               $reopenedBugidList = array_merge($reopenedBugidList, $reopenedList[$key]);
               $validatedBugidList = array_merge($validatedBugidList, $validatedList[$key]);
            }

            // PHP.net: It's often faster to use foreach and array_keys than array_unique
            $tmpBugidList = array_merge($reopenedBugidList, $validatedBugidList);
            $allBugidList = array();
            foreach($tmpBugidList as $val) {
               $allBugidList[$val] = true;
            }
            $allBugidList = array_keys($allBugidList);

            // --------
            $nbResolved = count($allBugidList);
            $nbReopened = count($reopenedBugidList);
            $nbValidated = count($validatedBugidList);

            if (0 != $nbResolved) {
               $pcentReopened = round((100 * $nbReopened / $nbResolved), 2);
               $pcentValidated = round((100 * $nbValidated / $nbResolved), 2);
            } else {
               $pcentReopened = 0;
               $pcentValidated = 0;
            }
            // ---------

            $reopenedList[$key] = $reopenedBugidList;
            $validatedList[$key] = $validatedBugidList;

            $nbReopenedList[$key] = $nbReopened;
            $nbValidatedList[$key] = $nbValidated;

            $reopenedPercentList[$key] = $pcentReopened;
            $validatedPercentList[$key] = $pcentValidated;

            $tableData[$key] = array(#'nbResolved' => $nbResolved,
                                     'nbReopened' => $nbReopened,
                                     'reopenedPercent' => $pcentReopened,
                                     'nbValidated' => $nbValidated,
                                     'validatedPercent' => $pcentValidated,
                                     'dateTooltip' => date('d M H:i:s', $start).' - '.date('d M H:i:s', $end)
                                    );

            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("reopened [".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$nbReopened." reopened  : ".implode(', ', $reopenedBugidList));
               self::$logger->debug("validated[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$nbValidated." validated : ".implode(', ', $validatedBugidList));
               #echo '---<br>';
            }
         }

         $this->execData['nbReopened'] = $nbReopenedList;
         $this->execData['reopenedIssues'] = $reopenedList;
         $this->execData['reopenedPercent'] = $reopenedPercentList;

         $this->execData['nbValidated'] = $nbValidatedList;
         $this->execData['validatedIssues'] = $validatedList;
         $this->execData['validatedPercent'] = $validatedPercentList;

         $this->execData['tableData'] = $tableData;
      }
      return $this->execData;
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {

      if (!is_null($this->execData)) {
         $startTimestamp = $this->startTimestamp;
         $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

         #$graphData = "[".Tools::array2plot($this->execData['nbValidated']).','.Tools::array2plot($this->execData['nbReopened'])."]";
         $graphData = "[".Tools::array2plot($this->execData['validatedPercent']).','.Tools::array2plot($this->execData['reopenedPercent'])."]";
         $interval = ceil($this->interval/20); // TODO why 20 ?
         $tableData = $this->execData['tableData'];
      } else {
         $tableData = NULL;
         $graphData = NULL;
         $startTimestamp = 0;
         $endTimestamp = 0;
      }
      $smartyVariables = array(
         'reopenedRateIndicator2_tableData' => $tableData,
         'reopenedRateIndicator2_jqplotData' => $graphData,
         'reopenedRateIndicator2_plotMinDate' => Tools::formatDate("%Y-%m-01", $startTimestamp),
         'reopenedRateIndicator2_plotMaxDate' => Tools::formatDate("%Y-%m-01", $endTimestamp),
         'reopenedRateIndicator2_plotInterval' => $interval,

         #'reopenedRateIndicator2_reopenedIssues' => $this->execData['reopenedIssues'],
         #'reopenedRateIndicator2_validatedIssues' => $this->execData['validatedIssues'],

         // add pluginSettings (if needed by smarty)
         'reopenedRateIndicator2_'.self::OPTION_INTERVAL => $this->interval,

      );
      if (false == $isAjaxCall) {
         $smartyVariables['reopenedRateIndicator2_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['reopenedRateIndicator2_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }


}

// Initialize complex static variables
ReopenedRateIndicator2::staticInit();

