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
 * Description of ManagementLoadHistoryIndicator
 */
class ManagementLoadHistoryIndicator extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // defaultValue, oneWeek, twoWeeks, oneMonth

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $serviceContractId;

   // config options from Dashboard
   private $interval; // 'monthly' or 'weekly'

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }


   public static function getName() {
      return T_('Management load history');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Compares the elapsed time on management sideTasks to the management provisions');
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
    * @param \PluginDataProviderInterface $pluginDataProv
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID)) {
         $this->serviceContractId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->interval = 'monthly';

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
               case 'weekly':
                  $this->interval = 'weekly';
                  break;
               case 'monthly':
                  $this->interval = 'monthly';
                  break;
               default:
                  self::$logger->warn('option '.self::OPTION_INTERVAL.'= '.$pluginSettings[self::OPTION_INTERVAL]." (unknown value)");
            }
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
               if ($endT > $this->endTimestamp) { $endT = $this->endTimestamp; }
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
               if ($endT > $this->endTimestamp) { $endT = $this->endTimestamp; }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("first day of next month", $startT);
            }
      }
      return $timestampRangeList;
   }

   public function execute() {

      $this->execData = array();
      try {
         $contract = new ServiceContract($this->serviceContractId);

         // the management elapsed ticks are based on a preiod interval (monthly, weekly).
         // the provision ticks are strictly on provision creation dates.

         // get management sidetasks of the ServiceContract
         $sidetasksPerCategoryType = $contract->getSidetasksPerCategoryType(true);
         $iselMngtSidetasks = $sidetasksPerCategoryType[Project::cat_mngt_regular];

         if (NULL != $iselMngtSidetasks) {
            // get cumulative Management elapsed time
            $mngtCumulElapsedList = array();
            $cumulatedElapsed = 0;
            $timestampRangeList = $this->createTimestampRangeList($this->interval);
            foreach ($timestampRangeList as $ttRange) {
               $elapsedInPeriod = $iselMngtSidetasks->getElapsed($ttRange['start'], $ttRange['end']);
               $cumulatedElapsed += round($elapsedInPeriod, 2);
               $formatedDate = Tools::formatDate("%Y-%m-%d", $ttRange['start']);
               $mngtCumulElapsedList[$formatedDate] = "$cumulatedElapsed";
            }

            // get cumulative management provisions
            $cumulatedProv = 0;
            $mngtCumulProvList = array();
            $provList = $contract->getProvisionList(CommandSet::type_general, Command::type_general, CommandProvision::provision_mngt);
            foreach ($provList as $prov) {
               $cumulatedProv += round($prov->getProvisionDays(), 2);
               $formatedDate = Tools::formatDate("%Y-%m-%d",  $prov->getDate());
               $mngtCumulProvList[$formatedDate] = "$cumulatedProv";
            }

            // other info
            $mngtBugidList = array_keys($iselMngtSidetasks->getIssueList());

            $this->execData['MngtElapsed'] = $mngtCumulElapsedList;
            $this->execData['MngtProvisions'] = $mngtCumulProvList;
            $this->execData['MngtBugids'] = $mngtBugidList;
         }
      } catch (Exception $e) {
         self::$logger->error("EXCEPTION ManagementLoadHistoryIndicator: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }

/*
      self::$logger->error("MngtElapsed:\n".  var_export($mngtCumulElapsedList, true));
      self::$logger->error("MngtProvisions:\n".  var_export($mngtCumulProvList, true));
      self::$logger->error("MngtBugids:\n".  var_export($mngtBugidList, true));
*/
      //return $this->execData;
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $mngtCumulElapsedList = $this->execData['MngtElapsed'];
      $mngtCumulProvList = $this->execData['MngtProvisions'];


      $startTimestamp = $this->startTimestamp;
      $endTimestamp = strtotime(date("Y-m-d",$this->endTimestamp)." +1 month");

      //$interval = ceil($this->interval/20); // TODO why 20 ?

      if ($mngtCumulElapsedList && $mngtCumulElapsedList) {
      $jsonElapsed = Tools::array2plot($mngtCumulElapsedList);
      $jsonProv    = Tools::array2plot($mngtCumulProvList);
      $graphData   = "[".$jsonElapsed.','.$jsonProv."]";
      }
      $smartyVariables = array(
         'managementLoadHistoryIndicator_jqplotData' => $graphData,
         'managementLoadHistoryIndicator_plotMinDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'managementLoadHistoryIndicator_plotMaxDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         'managementLoadHistoryIndicator_plotInterval' => 2, //$interval,

         // add pluginSettings (if needed by smarty)
         'managementLoadHistoryIndicator_'.self::OPTION_INTERVAL => $this->interval,

      );
      if (false == $isAjaxCall) {
         $smartyVariables['managementLoadHistoryIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['managementLoadHistoryIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize complex static variables
ManagementLoadHistoryIndicator::staticInit();
