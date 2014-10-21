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

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginMangerFacade
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $pluginSettings;

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
         self::DOMAIN_MACRO_COMMAND,
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
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.cursor.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'lib/jquery.jqplot/plugins/jqplot.highlighter.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
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

         // TODO set default valus, then override with $indicatorOptions

         if (array_key_exists(self::OPTION_IS_GRAPH_ONLY, $pluginSettings)) {
            $this->pluginSettings[self::OPTION_IS_GRAPH_ONLY] = $pluginSettings[self::OPTION_IS_GRAPH_ONLY];
         }
      }
   }


  /**
    *
    */
   public function execute() {
      

      $issueList = $this->inputIssueSel->getIssueList();
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamMembers = $team->getMembers();
      $jobs = new Jobs();

      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly
      $loadPerJobs = array();
      foreach($issueList as $issue) {
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

            // check if sidetask
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


   public function getSmartyVariables() {

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

      return array(
         'loadPerJobIndicator_tableData' => $loadPerJobs,
         'loadPerJobIndicator_jqplotData' => Tools::array2json($data),
         'loadPerJobIndicator_colors' => $formatedColors,
         'loadPerJobIndicator_jqplotSeriesColors' => $seriesColors, // TODO get rid of this
         'loadPerJobIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'loadPerJobIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         #'loadPerJobIndicatorFile' => LoadPerJobIndicator::getSmartyFilename(), // added in controller
         'loadPerJobIndicator_ajaxFile' => self::getSmartySubFilename(),
         'loadPerJobIndicator_ajaxPhpURL' => self::getAjaxPhpURL(),
      );
   }

   /**
    * a subset of variables usefull for loadPerJobIndicatorDiv and workingDaysPerJobChart
    * defined in LoadPerJobIndicator2_ajax.html
    *
    * @return array
    */
   public function getSmartyVariablesForAjax() {

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

      // TODO add pluginSettings needed for the _ajax.php
      
      return array(
         'loadPerJobIndicator_tableData' => $loadPerJobs,
         'loadPerJobIndicator_jqplotData' => Tools::array2json($data),
         'loadPerJobIndicator_colors' => $formatedColors,
         'loadPerJobIndicator_jqplotSeriesColors' => $seriesColors, // TODO get rid of this
         'loadPerJobIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $startTimestamp),
         'loadPerJobIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $endTimestamp),
         'loadPerJobIndicator_jsFiles' => Tools::array2json(self::getJsFiles()),
         'loadPerJobIndicator_cssFiles' => Tools::array2json(self::getCssFiles()),
      );
   }

}

// Initialize static variables
LoadPerJobIndicator2::staticInit();
