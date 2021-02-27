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
 * Description of ProgressHistoryIndicator
 */
class ProgressHistoryIndicator2 extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // defaultValue, oneWeek, twoWeeks, oneMonth

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   /** @var IssueSelection */
   private $inputIssueSel;

   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $interval;

   // internal
   protected $execData;
   protected $durationData;
   protected $elapsedData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }


   public static function getName() {
      return T_('Progress history');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Display the progress history');
      if (!$isShortDesc) {
         $desc .= '<br><br>';
      }
      return $desc;
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.1.0';
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
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'js_min/chart.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/progress.min.js',
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
         $this->interval = 'oneMonth';
      }
      //self::$logger->debug('dataProvider '.PluginDataProviderInterface::PARAM_INTERVAL.'= '.$this->interval);

      // set default pluginSettings (not provided by the PluginDataProvider)

   }

   /**
    * Override PluginDataProvider values with user preferences.
    *
    * User preferences are saved by the Dashboard.
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
   private function createTimestampRangeList($startTimestamp, $endTimestamp, $interval = 'monthly') {
      $timestampRangeList = array();

      switch ($interval) {
         case 'oneWeek':
            $strtotimeStr = "next monday";
            break;
         case 'twoWeeks':
            $strtotimeStr = "+2 weeks"; // we would like "monday in 2 weeks"
            break;
         case 'oneMonth':
         default:
            $strtotimeStr = "first day of next month";
      }

      $startT = $startTimestamp;
      while ($startT < $endTimestamp) {
         //$timestampRangeList[date('Y-m-d', $startT)] =
         $timestampRangeList[] =
             mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT));

         $startT = strtotime($strtotimeStr, $startT);
      }
      // latest value should be endTimestamp
      //$timestampRangeList[date('Y-m-d', $this->endTimestamp)] =
      $timestampRangeList[] = $endTimestamp;

      return $timestampRangeList;
   }

   /**
    * Duration = (backlog) ? backlog : max(MgrEffortEstim, EffortEstim)
    *
    * But here, for the need of the graph, we cannot use the regular getDuration()
    * there is a special case when the issue does not exist at timestamp.
    *
    * @param IssueSelection $inputIssueSel
    * @param int[] $timestampList
    */
   private function getDurationData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->durationData = array();

      $mgrEffortEstimCache = array();

      // get a snapshot of the Backlog at each timestamp
      $issues = $inputIssueSel->getIssueList();
      krsort($timestampList);
      foreach ($timestampList as $midnight_timestamp) {
         $timestamp = mktime(23, 59, 59, date('m', $midnight_timestamp), date('d', $midnight_timestamp), date('Y', $midnight_timestamp));

         $backlog = 0;
         foreach ($issues as $issue) {
            if(!array_key_exists($issue->getId(),$mgrEffortEstimCache)) {
               if($timestamp >= $issue->getDateSubmission()) {

                  if ($issue->isResolved($timestamp)) {
                     #echo "issue ".$issue->getId()." isResolved at ".date('Y-m-d H:i:s', $timestamp)."<br>";
                     $issueBacklog = 0;
                  } else {
                     $issueBL = $issue->getBacklog($timestamp);
                     if ((!is_null($issueBL) && ('' != $issueBL))) {
                        $issueBacklog = $issueBL;
                     } else {
                        // if not fount in history, take max(MgrEffortEstim, EffortEstim)
                        $issueEE    = $issue->getEffortEstim();
                        $issueEEMgr = $issue->getMgrEffortEstim();
                        $issueBacklog = max(array($issueEE, $issueEEMgr));

                        $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
                     }
                  }
               } else {
                  // issue does not exist at this date, take max(MgrEffortEstim, EffortEstim)
                  // Note: getDuration() would return 0 which in this case is wrong
                  $issueEE    = $issue->getEffortEstim();
                  $issueEEMgr = $issue->getMgrEffortEstim();
                  $issueBacklog = max(array($issueEE, $issueEEMgr));
                  $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
               }
            } else {
               $issueBacklog = $mgrEffortEstimCache[$issue->getId()];
            }
            $backlog += $issueBacklog;
         }
         $this->durationData[$midnight_timestamp] = $backlog;
      }
   }

   private function getElapsedData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->elapsedData = array();

      // get a snapshot of the Backlog at each timestamp
      foreach ($timestampList as $midnight_timestamp) {
         $timestamp = mktime(23, 59, 59, date('m', $midnight_timestamp), date('d', $midnight_timestamp), date('Y', $midnight_timestamp));
         $elapsed = $inputIssueSel->getElapsed(NULL, $timestamp);
         $this->elapsedData[$midnight_timestamp] = $elapsed;
      }
   }

   /**
    *
    * Deux courbes:
    *
    * RAF Theorique = charge initiale - cumul consomé
    *
    * RAF Reel      = cumul consomé / (cumul consomé +  RAF)
    *
    *
    */
   public function execute() {

      $timestampList  = $this->createTimestampRangeList($this->startTimestamp, $this->endTimestamp, $this->interval);

      $this->getDurationData($this->inputIssueSel, $timestampList);
      $this->getElapsedData($this->inputIssueSel, $timestampList);

      // ------ compute
      $theoBacklog = array();
      $realBacklog = array();
      $tableData = array();

      $iselMaxEE = max(array($this->inputIssueSel->mgrEffortEstim, $this->inputIssueSel->effortEstim));
      $sumElapsed = 0;
      $nbZeroDivErrors1 = 0;
      $nbZeroDivErrors2 = 0;
      foreach ($timestampList as $midnight_timestamp) {
         $formattedMidnight = Tools::formatDate("%Y-%m-%d", $midnight_timestamp);

         // ========= RAF theorique
         // Indicateur = charge initiale - cumul consomé
         if(array_key_exists($midnight_timestamp,$this->elapsedData)) {
            $sumElapsed = $this->elapsedData[$midnight_timestamp];
         }
         if (0 != $iselMaxEE) {
            $val1 = $sumElapsed / $iselMaxEE;

         } else {
            $val1 = 0;
            $nbZeroDivErrors1 += 1;
            //self::$logger->error("Division by zero ! (mgrEffortEstim)");
         }
         if ($val1 > 1) {$val1 = 1;}
         $theoBacklog[$formattedMidnight] = round($val1 * 100, 2);

         // =========  RAF reel
        // Indicateur = Conso. Cumulé / (Conso. Cumulé +  RAF)

         $tmp = ($sumElapsed + $this->durationData[$midnight_timestamp]);
         if (0 != $tmp) {
            $val2 = $sumElapsed / $tmp;
         } else {
            $val2 = 0;
            $nbZeroDivErrors2 += 1;
            //self::$logger->error("Division by zero ! (elapsed + realBacklog)");
         }
         $realBacklog[$formattedMidnight] = round($val2 * 100, 2);

         $reestimated = ($sumElapsed + $this->durationData[$midnight_timestamp]);

         $tableData[$formattedMidnight] = array(
           'progress' => (0 != $reestimated) ? round(($sumElapsed / $reestimated)*100, 2) : 0,
           'reestimated' => round($reestimated, 2),
           'elapsed' => $sumElapsed,
           'backlog' => $this->durationData[$midnight_timestamp],
         );

      } // foreach timestamp

      if (count($this->inputIssueSel->getIssueList()) > 0) {
         // PERF logging is slow, factorize errors
         if ($nbZeroDivErrors1 > 0) {
            self::$logger->error("$nbZeroDivErrors1 Division by zero ! (mgrEffortEstim)");
         }
         if ($nbZeroDivErrors2 > 0) {
            self::$logger->error("$nbZeroDivErrors2 Division by zero ! (elapsed + realBacklog)");
         }
      }
      $this->execData = array();
      $this->execData['theo'] = $theoBacklog;
      $this->execData['real'] = $realBacklog;
      $this->execData['tableData'] = $tableData;
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $theoBacklog = $this->execData['theo'];
      $realBacklog = $this->execData['real'];

      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      $interval = 1;
      $graphData = "[".Tools::array2plot($theoBacklog).','.Tools::array2plot($realBacklog)."]";

      $smartyPrefix = 'progressHistoryIndicator2_';
      $smartyVariables = array(
         $smartyPrefix.'jqplotData' => $graphData,
         $smartyPrefix.'plotMinDate' => Tools::formatDate("%Y-%m-%d", strtotime("first day of this month",$startTimestamp)),
         $smartyPrefix.'plotMaxDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         $smartyPrefix.'plotInterval' => $interval,
         $smartyPrefix.'tableData' => $this->execData['tableData'],

         // add pluginSettings (if needed by smarty)
         'progressHistoryIndicator2_'.self::OPTION_INTERVAL => $this->interval,

      );
      if (false == $isAjaxCall) {
         $smartyVariables['progressHistoryIndicator2_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['progressHistoryIndicator2_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize complex static variables
ProgressHistoryIndicator2::staticInit();
