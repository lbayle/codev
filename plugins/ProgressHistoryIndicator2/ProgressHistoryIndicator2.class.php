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
   protected $backlogData;
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
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
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
    * @param IssueSelection $inputIssueSel
    * @param int[] $timestampList
    */
   private function getBacklogData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->backlogData = array();

      $mgrEffortEstimCache = array();

      // get a snapshot of the Backlog at each timestamp
      $issues = $inputIssueSel->getIssueList();
      krsort($timestampList);
      foreach ($timestampList as $timestamp) {
         $backlog = 0;
         #echo "=========getBacklogData ".date('Y-m-d H:i:s', $timestamp)."<br>";
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
                        $issueEE    = $issue->getEffortEstim() + $issue->getEffortAdd();
                        $issueEEMgr = $issue->getMgrEffortEstim();
                        $issueBacklog = max(array($issueEE, $issueEEMgr));

                        $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
                     }

                  }

               } else {
                  // issue does not exist at this date, take max(MgrEffortEstim, EffortEstim)
                  // Note: getDuration() would return 0 which in this case is wrong
                  $issueEE    = $issue->getEffortEstim() + $issue->getEffortAdd();
                  $issueEEMgr = $issue->getMgrEffortEstim();
                  $issueBacklog = max(array($issueEE, $issueEEMgr));
                  $mgrEffortEstimCache[$issue->getId()] = $issueBacklog;
               }
            } else {
               $issueBacklog = $mgrEffortEstimCache[$issue->getId()];
            }
            #echo "issue ".$issue->getId()." issueBacklog = $issueBacklog  getBacklog = $issueBL<br>";
            $backlog += $issueBacklog;
         }

         #echo "backlog(".date('Y-m-d H:i:s', $timestamp).") = ".$backlog.'<br>';
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
         $this->backlogData[$midnight_timestamp] = $backlog;
      }
      // No need to sort, values are get by the index
      //ksort($this->backlogData);
   }

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
         #echo "elapsed[".date('Y-m-d H:i:s', $midnight_timestamp)."] (".date('Y-m-d H:i:s', $start)." - ".date('Y-m-d H:i:s', $end).") = ".$this->elapsedData[$midnight_timestamp].'<br>';
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

      $startTimestamp = mktime(23, 59, 59, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d',$this->endTimestamp), date('Y', $this->endTimestamp));

      #echo "Backlog start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$this->interval."<br>";
      $timestampList  = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);


      // -------- elapsed in the period
      $startTimestamp = mktime(0, 0, 0, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));
      $endTimestamp = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));

      //echo "Elapsed start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$this->interval."<br>";

      $timestampList2 = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);


      $this->getBacklogData($this->inputIssueSel, $timestampList);
      $this->getElapsedData($this->inputIssueSel, $timestampList2);

      // ------ compute
      $theoBacklog = array();
      $realBacklog = array();
      $iselMaxEE = max(array($this->inputIssueSel->mgrEffortEstim, ($this->inputIssueSel->effortEstim + $this->inputIssueSel->effortAdd)));
      $sumElapsed = 0;
      $nbZeroDivErrors1 = 0;
      $nbZeroDivErrors2 = 0;
      foreach ($timestampList as $timestamp) {
         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

         // ========= RAF theorique
         // Indicateur = charge initiale - cumul consomé
         if(array_key_exists($midnight_timestamp,$this->elapsedData)) {
            #echo "sumElapsed += ".$this->elapsedData[$midnight_timestamp]." from ".date('Y-m-d H:i:s', $midnight_timestamp)."<br>";
            $sumElapsed += $this->elapsedData[$midnight_timestamp];
         }
         if (0 != $iselMaxEE) {
            $val1 = $sumElapsed / $iselMaxEE;

         } else {
            // TODO
            $val1 = 0;
            $nbZeroDivErrors1 += 1;
            //self::$logger->error("Division by zero ! (mgrEffortEstim)");
         }
         if ($val1 > 1) {$val1 = 1;}
         $theoBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val1 * 100, 2);

         // =========  RAF reel
        // Indicateur = Conso. Cumulé / (Conso. Cumulé +  RAF)

         $tmp = ($sumElapsed + $this->backlogData[$midnight_timestamp]);
         if (0 != $tmp) {
            $val2 = $sumElapsed / $tmp;
         } else {
            // TODO
            $val2 = 0;
            $nbZeroDivErrors2 += 1;
            //self::$logger->error("Division by zero ! (elapsed + realBacklog)");
         }
         $realBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)] = round($val2 * 100, 2);

         #echo "(".date('Y-m-d', $midnight_timestamp).") sumElapsed = $sumElapsed BacklogData = ".$this->backlogData[$midnight_timestamp]." MaxEE = ".$iselMaxEE.'<br>';
         #echo "(".date('Y-m-d', $midnight_timestamp).") theoBacklog = ".$theoBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)]." realBacklog = ".$realBacklog[Tools::formatDate("%Y-%m-%d", $midnight_timestamp)].'<br>';


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

      $interval = ceil($this->interval/20); // TODO why 20 ?
      $graphData = "[".Tools::array2plot($theoBacklog).','.Tools::array2plot($realBacklog)."]";

      $smartyVariables = array(
         'progressHistoryIndicator2_jqplotData' => $graphData,
         'progressHistoryIndicator2_plotMinDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'progressHistoryIndicator2_plotMaxDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         'progressHistoryIndicator2_plotInterval' => $interval,

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
