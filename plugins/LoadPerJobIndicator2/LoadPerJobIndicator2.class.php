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
 * Description of LoadPerJobIndicator2
 *
 * @author lob
 */
class LoadPerJobIndicator2 extends IndicatorPluginAbstract {

   const OPTION_IS_GRAPH_ONLY = 'isGraphOnly';
   const OPTION_IS_TABLE_ONLY = 'isTableOnly';
   const OPTION_IS_SIDETASK_CAT_DETAILED = 'isSideTasksCategoryDetailed';
   const OPTION_DATE_RANGE    = 'dateRange';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $pluginSettings;
   private $dateRange;  // defaultRange | currentWeek | currentMonth

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
         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_QUALITY
      );
   }

   public static function getName() {
      return 'Load per Job';
   }
   public static function getDesc() {
      return 'Check all the timetracks of the period and return their repartition per Job';
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
         'js/datepicker.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js',
         'js/chart.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));      

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
      $this->dateRange   = 'defaultRange';

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("checkParams() ISel=".$this->inputIssueSel->name.' startTimestamp='.$this->startTimestamp.' endTimestamp='.$this->endTimestamp);
      }
   }

   /**
    * Values will be saved by the Dashboard
    * @return array
    */
   //public function getPluginSettings() {
   //   return $this->pluginSettings;
   //}

   /**
    * settings are saved by the Dashboard
    * 
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {

         // then override with $pluginSettings
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
    */
   public function execute() {
      
      $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $issueList = $this->inputIssueSel->getIssueList();
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamMembers = $team->getMembers();
      $jobs = new Jobs();

      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly
      $loadPerJobs = array();
      foreach($issueList as $issue) {

         if ($extProjId == $issue->getProjectId()) {
            continue; 
         }
            
         $issueTimetracks = $issue->getTimeTracks(NULL, $this->startTimestamp, $this->endTimestamp);
         foreach ($issueTimetracks as $tt) {

            // check if user in team
            if (!array_key_exists($tt->getUserId(), $teamMembers)) { continue; }

            // find real date range
            if ( (NULL == $realStartTimestamp) || ($tt->getDate() < $realStartTimestamp)) {
               $realStartTimestamp = $tt->getDate();
            }
            if ( (NULL == $realEndTimestamp) || ($tt->getDate() > $realEndTimestamp)) {
               $realEndTimestamp = $tt->getDate();
            }
            if ($team->isSideTasksProject($issue->getProjectId())) {
               // TODO check category (detail all sidetasks categories)

               $jobid = '999_SideTasks';
               if (!array_key_exists($jobid, $loadPerJobs)) {
                  // create job if not exist in jobList
                  $loadPerJobs[$jobid] = array(
                     'name' => T_('SideTasks'),
                     'color' => 'C2C2C2', // TODO hardcoded !
                     'nbDays' => floatval($tt->getDuration()),
                     );
               } else {
                  $loadPerJobs[$jobid]['nbDays'] += floatval($tt->getDuration());
               }
            } else {
               $jobid = $tt->getJobId();
               if (!array_key_exists($jobid, $loadPerJobs)) {
                  // create job if not exist in jobList
                  $loadPerJobs[$jobid] = array(
                     'name' => htmlentities($jobs->getJobName($jobid), ENT_QUOTES | ENT_HTML401, "UTF-8"),
                     'color' => $jobs->getJobColor($jobid),
                     'nbDays' => floatval($tt->getDuration()),
                     );
               } else {
                  $loadPerJobs[$jobid]['nbDays'] += floatval($tt->getDuration());
               }
            }
         }
      }
      
      //self::$logger->error("date range: ".date('Y-m-d', $this->startTimestamp).'-'.date('Y-m-d', $this->endTimestamp));
      //self::$logger->error("real date range: ".date('Y-m-d', $realStartTimestamp).'-'.date('Y-m-d', $realEndTimestamp));
      
      // array sort to put sideTasks categories at the bottom
      ksort($loadPerJobs);

      $this->execData = array (
         'loadPerJobs' => $loadPerJobs,
         'realStartTimestamp' => $realStartTimestamp,
         'realEndTimestamp' => $realEndTimestamp,
         );
      return $this->execData;
   }


   public function getSmartyVariables($isAjaxCall = false) {

      $loadPerJobs = $this->execData['loadPerJobs'];
      $data = array();
      $formatedColors = array();
      foreach ($loadPerJobs as $jobItem) {
         $data[$jobItem['name']] = $jobItem['nbDays'];
         $formatedColors[] = '#'.$jobItem['color'];
      }
      $seriesColors = '["'.implode('","', $formatedColors).'"]';  // ["#FFCD85","#C2DFFF"]

      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;

      $smartyVariables = array(
         'loadPerJobIndicator_tableData' => $loadPerJobs,
         'loadPerJobIndicator_jqplotData' => empty($data) ? NULL : Tools::array2json($data),
         'loadPerJobIndicator_colors' => $formatedColors,
         'loadPerJobIndicator_jqplotSeriesColors' => $seriesColors, // TODO get rid of this
         'loadPerJobIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'loadPerJobIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         #'loadPerJobIndicatorFile' => LoadPerJobIndicator::getSmartyFilename(), // added in controller
      );

      if (false == $isAjaxCall) {
         $smartyVariables['loadPerJobIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['loadPerJobIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   /**
    * a subset of variables usefull for loadPerJobIndicatorDiv and workingDaysPerJobChart
    * defined in LoadPerJobIndicator2_ajax.html
    *
    * @return array
    */
   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
LoadPerJobIndicator2::staticInit();
