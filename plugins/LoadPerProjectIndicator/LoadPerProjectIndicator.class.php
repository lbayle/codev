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
 * Description of ActivityIndicator
 *
 * @author lob
 */
class LoadPerProjectIndicator extends IndicatorPluginAbstract {

   // if false, sidetasks (found in IssueSel) will be included in 'elapsed'
   // if true, sidetasks (found in IssueSel) will be displayed as 'sidetask'
   const OPTION_SHOW_SIDETASKS = 'showSidetasks';

   const OPTION_IS_GRAPH_ONLY = 'isGraphOnly';
   const OPTION_IS_TABLE_ONLY = 'isTableOnly';
   const OPTION_DATE_RANGE    = 'dateRange';
   
   
   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $dateRange;  // defaultRange | currentWeek | currentMonth
   
   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_USER,
         self::DOMAIN_TEAM,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Load per Project');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Check all the timetracks of the period and return their repartition per Project');
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
         'lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js',
         'js_min/chart.min.js', // TODO get rid of chart.js
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = NULL;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->dateRange = 'defaultRange';

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("checkParams() ISel=".$this->inputIssueSel->name.' startTimestamp='.$this->startTimestamp.' endTimestamp='.$this->endTimestamp);
      }
   }

   /**
    * settings are saved by the Dashboard
    * 
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DATE_RANGE, $pluginSettings)) {
            $this->dateRange = $pluginSettings[self::OPTION_DATE_RANGE];
            
            // update startTimestamp & endTimestamp
            switch ($this->dateRange) {
               case 'currentWeek':
                  $weekDates = Tools::week_dates(date('W'),date('Y'));
                  $this->startTimestamp = $weekDates[1];
                  $this->endTimestamp   = $weekDates[5];
                  break;
               case 'currentMonth':
                  $month = date('m');
                  $year  = date('Y');
                  $this->startTimestamp = mktime(0, 0, 0, $month, 1, $year);
                  $nbDaysInMonth = date("t", $this->startTimestamp);
                  $this->endTimestamp = mktime(0, 0, 0, $month, $nbDaysInMonth, $year);
                  break;
            }
         }
      }
   }


   /**
    *
    * returns an array of [user][activity]
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $members = $team->getActiveMembers($this->startTimestamp, $this->endTimestamp);
      $formatedUseridString = implode( ', ', array_keys($members));

      //$extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);

      $issueList = $this->inputIssueSel->getIssueList();
      $projectLoad = array();

      if (0 != count($issueList)) {
         $formatedBugidString = implode( ', ', array_keys($issueList));

         $query = "SELECT ROUND(SUM(tt.duration), 2) as duration, prj.name as prjName
                  FROM codev_timetracking_table as tt, {project} as prj, {bug} as bug
                  WHERE tt.bugid = bug.id
                  AND bug.project_id = prj.id
                  AND bug.id IN ($formatedBugidString)
                  AND tt.userid IN ($formatedUseridString) ";

         if (isset($this->startTimestamp)) { $query .= " AND tt.date >= $this->startTimestamp "; }
         if (isset($this->endTimestamp))   { $query .= " AND tt.date <= $this->endTimestamp "; }
         $query .= " GROUP BY prj.id
                     ORDER BY `prj`.`name` ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $projectLoad["$row->prjName"] = (float)$row->duration;
         }
      }

      // ---
      $this->execData = $projectLoad;
   }

   public function getSmartyVariables($isAjaxCall = false) {

      $totalLoad = 0;
      foreach ($this->execData as $duration) {
            $totalLoad += $duration;
      }

      // table data
      $tableData = array(
         'projectLoad' => $this->execData,
         'totalLoad' => $totalLoad,
         'workdays' => Holidays::getInstance()->getWorkdays($this->startTimestamp, $this->endTimestamp),
      );
      
      // ------------------------
      // pieChart data
      $jqplotData = Tools::array2plot($this->execData);

      $smartyVariables =  array(
         'loadPerProjectIndicator_tableData' => $tableData,
         'loadPerProjectIndicator_jqplotData' => empty($jqplotData) ? NULL : $jqplotData,
         'loadPerProjectIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'loadPerProjectIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
      );
      
      if (false == $isAjaxCall) {
         $smartyVariables['loadPerProjectIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['loadPerProjectIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      
      return $smartyVariables;
   }
   
   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
LoadPerProjectIndicator::staticInit();


