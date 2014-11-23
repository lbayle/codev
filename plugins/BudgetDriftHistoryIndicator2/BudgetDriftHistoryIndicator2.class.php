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
 * Description of BudgetDriftHistoryIndicator
 *
 */
class BudgetDriftHistoryIndicator2 extends IndicatorPluginAbstract {


   const OPTION_INTERVAL = 'interval';

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $provisionDays;

   // config options from Dashboard
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
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }

   public static function getName() {
      return 'Budget drift history';
   }
   public static function getDesc($isShortDesc = true) {
      $desc = 'Display the budget history';
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
         'js/helpdialog.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasOverlay.min.js',
         'js/chart.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROVISION_DAYS)) {
         $this->provisionDays = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROVISION_DAYS);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_PROVISION_DAYS);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL)) {
         // int value
         $this->interval = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL);
      } else {
         $this->interval = 30;
      }

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
    *
    * @param IssueSelection $inputIssueSel
    * @param array $timestampList
    */
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
         //self::$logger->error("elapsed[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$this->elapsedData[$midnight_timestamp]);
      }
   }

   /**
    *
    * CmdTotalDrift = Reestimated - (MEE + Provisions)
    */
   public function execute() {

      // -------- elapsed in the period
      $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d',$this->endTimestamp), date('Y', $this->endTimestamp));

      #echo "Backlog start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$this->interval."<br>";
      $timestampList2  = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);

      $this->getElapsedData($this->inputIssueSel, $timestampList2);

      // ------ compute
      // CmdTotalDrift = Reestimated - (MEE + Provisions)

      $driftDaysList = array();
      $driftPercentList = array();
      $tableData = array();
      $nbZeroDivErrors1 = 0;
      foreach ($timestampList2 as $timestamp) {
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

         $cmdProvAndMeeDays = $this->inputIssueSel->getMgrEffortEstim($timestamp) + $this->provisionDays;
         
			$reestimated = $this->inputIssueSel->getReestimated($timestamp);

         $driftDays = $reestimated - $cmdProvAndMeeDays;

         if (0 != $cmdProvAndMeeDays) {
            $driftPercent = ($driftDays * 100 / $cmdProvAndMeeDays);
         } else {
            $nbZeroDivErrors1 += 1;
            $driftPercent = 0;
         }
         #$totalDriftCost = $totalDrift * $cmd->getAverageDailyRate();

         $key = Tools::formatDate('%Y-%m-%d', $midnight_timestamp);
         $driftDaysList[$key] = round($driftDays, 2);
         $driftPercentList[$key] = round($driftPercent, 2);
         $tableData[$key] = array('driftDays' => $driftDaysList[$key], 
                                  'driftPercent' => $driftPercentList[$key],
                                  'provAndMeeDays' => $cmdProvAndMeeDays);

         #echo $key." CmdTotalDrift = $reestimated - $cmdProvAndMeeDays = ".$cmdTotalDrift[$key]."<br>";

      } // foreach timestamp

      // PERF logging is slow, factorize errors
      if ($nbZeroDivErrors1 > 0) {
         self::$logger->error("$nbZeroDivErrors1 Division by zero ! (cmdProvAndMeeDays)");
      }

      $this->execData['budgetDriftDays'] = $driftDaysList;
      $this->execData['budgetDriftPercent'] = $driftPercentList;
      $this->execData['budgetDriftTable'] = $tableData;

      //self::$logger->error(var_export($this->execData, true));
      return $this->execData;
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $interval = ceil($this->interval/20); // TODO why 20 ?
      #$graphDaysData  = '['.Tools::array2plot($this->execData['budgetDriftDays']).']';
      $graphPercentData = '['.Tools::array2plot($this->execData['budgetDriftPercent']).']';

      $smartyVariables = array(
         'budgetDriftHistoryIndicator2_tableData' => $this->execData['budgetDriftTable'],
         #'budgetDriftHistoryIndicator2_jqplotDaysData' => $graphDaysData,
         'budgetDriftHistoryIndicator2_jqplotPercentData' => $graphPercentData,
         'budgetDriftHistoryIndicator2_jqplotMinDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'budgetDriftHistoryIndicator2_jqplotMaxDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         'budgetDriftHistoryIndicator2_jqplotInterval' => $interval,

         // add pluginSettings (if needed by smarty)
         'budgetDriftHistoryIndicator2_'.self::OPTION_INTERVAL => $this->interval,

      );
      if (false == $isAjaxCall) {
         $smartyVariables['budgetDriftHistoryIndicator2_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['budgetDriftHistoryIndicator2_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize complex static variables
BudgetDriftHistoryIndicator2::staticInit();

