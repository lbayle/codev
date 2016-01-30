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
class IssueBacklogVariationIndicator extends IndicatorPluginAbstract {

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // internal
   private $issue;
   protected $execData;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }

   public static function getName() {
      return T_('Task burndown chart');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Display task backlog updates since the task creation');
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

      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      if ( 1 != count($bugidList)) {
         throw new Exception('There should be only one issue in IssueSelection !');
      }
      $bugid = current($bugidList);
      $this->issue = IssueCache::getInstance()->getIssue($bugid);
      
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
      }
   }

  // ----------------------------------------------
   public function execute() {

      $backlogList = $this->issue->getBacklogHistory();

      $formattedBlList = array();
      foreach ($backlogList as $t => $b) {
         $formattedBlList[Tools::formatDate("%Y-%m-%d", $t)] = $b;
      }

      // Graph start/stop dates
      reset($formattedBlList);
      $plotMinDate = key($formattedBlList);
      end($formattedBlList);
      $plotMaxDate = key($formattedBlList);

      // Calculate a nice week interval
      $minTimestamp = Tools::date2timestamp($plotMinDate);
      $maxTimestamp = Tools::date2timestamp($plotMaxDate);
      $nbWeeks = ($maxTimestamp - $minTimestamp) / 60 / 60 / 24 / 7;
      $interval = ceil($nbWeeks / 10);

      $jqplotData =  empty($formattedBlList) ? NULL : Tools::array2plot($formattedBlList);

      $this->execData = array(
         'issueBacklogVariationIndicator_interval'         => $interval,
         'issueBacklogVariationIndicator_plotMinDate'      => $plotMinDate,
         'issueBacklogVariationIndicator_plotMaxDate'      => $plotMaxDate,
         'issueBacklogVariationIndicator_jqplotYaxisLabel' => T_('Backlog (days)'),
         'issueBacklogVariationIndicator_jqplotData'       => $jqplotData,
         'issueBacklogVariationIndicator_tableData'        => $formattedBlList,
      );
      return $this->execData;
   }

   /**
    *
    * @return type
    */
   public function getSmartyVariables($isAjaxCall = false) {
      
      $smartyVariables = $this->execData;
      
      if (false == $isAjaxCall) {
         $smartyVariables['issueBacklogVariationIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['issueBacklogVariationIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

IssueBacklogVariationIndicator::staticInit();
