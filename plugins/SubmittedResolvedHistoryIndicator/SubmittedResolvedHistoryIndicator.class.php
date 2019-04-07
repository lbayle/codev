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
class SubmittedResolvedHistoryIndicator extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // weekly, monthly
   #const OPTION_FILTER_ISSUE_TYPE = 'issueType';    // noFilter, bugsOnly, tasksOnly
   #const OPTION_FILTER_ISSUE_EXT_ID = 'issueExtId'; // noFilter, withExtId, withoutExtId

   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $interval;

   // internal
   protected $execData;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_COMMAND,
         self::DOMAIN_TEAM,
//         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }

   public static function getName() {
      return T_('Submitted/Resolved Issues History');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Display the number of issues submitted/resolved in a period');
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
         'js_min/datepicker.min.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
         'js_min/chart.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
      );
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         // datepicker default val
         $this->startTimestamp = strtotime("first day of this year");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         // datepicker default val
         $this->endTimestamp = strtotime("last day of this month");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL)) {
         $this->interval = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL);
      } else {
         $this->interval = 'monthly';
      }

   }

   /**
    * settings are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {
            $this->interval = $pluginSettings[self::OPTION_INTERVAL];
         }
      }
   }


   /**
    *
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $interval [weekly,monthly]
    */
   private function createTimestampRangeList($interval = 'monthly') {
      $timestampRangeList = array();

      $startT = $this->startTimestamp;

      switch ($interval) {
         case 'weekly':
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("next sunday", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("next monday", $startT);
            }
            break;
         default:
            // monthly
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("last day of this month", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("first day of next month", $startT);
            }
      }
      return $timestampRangeList;
   }

   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {
      $timestampRangeList = $this->createTimestampRangeList($this->interval);

      $periodData = array();
      foreach ($timestampRangeList as $label => $ttRange) {
         $startT = $ttRange['start'];
         $endT   = $ttRange['end'];

         $submittedIssues = $this->inputIssueSel->getSubmittedIssues($startT, $endT);
         $resolvedIssues  = $this->inputIssueSel->getResolvedIssues($startT, $endT);

         $periodData[$label] = array(
            'nbSubmitted' => count($submittedIssues),
            'nbResolved'  => count($resolvedIssues),
            'submittedBugidList' => implode( ', ', array_keys($submittedIssues)),
            'resolvedBugidList' => implode( ', ', array_keys($resolvedIssues)),
         );
      }
      $this->execData = array (
         #'startTimestamp' => $this->startTimestamp,
         'startDate' => date('Y-m-d', $this->startTimestamp),
         'endDate' => date('Y-m-d', $this->endTimestamp),
         'periodData' => $periodData,
         );
   }


   public function getSmartyVariables($isAjaxCall = false) {


      // format data for jqPlot
      // Xaxes as string "['YYYY-MM-DD', 'YYYY-MM-DD', 'YYYY-MM-DD', 'YYYY-MM-DD']"
      // data as string "[[5, 32, 40, 48],[32, 41, 44, 14]]"
      $valuesOne = array();
      $valuesTwo = array();
      foreach ($this->execData['periodData'] as $periodData) {
         $valuesOne[] = $periodData['nbSubmitted'];
         $valuesTwo[] = $periodData['nbResolved'];
      }
      $values = array($valuesOne,$valuesTwo);

      $smartyPrefix = 'submittedResolvedHistoryIndicator_';
      $smartyVariables = array(
         $smartyPrefix.'startDate' => $this->execData['startDate'],
         $smartyPrefix.'endDate'   => $this->execData['endDate'],
         $smartyPrefix.'tableData' => $this->execData['periodData'],
         $smartyPrefix.'jqplotData' => json_encode($values),
         $smartyPrefix.'jqplotXaxes' => json_encode(array_keys($this->execData['periodData'])),

         // add pluginSettings (if needed by smarty)
         $smartyPrefix.self::OPTION_INTERVAL => $this->interval,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['submittedResolvedHistoryIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['submittedResolvedHistoryIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
SubmittedResolvedHistoryIndicator::staticInit();

