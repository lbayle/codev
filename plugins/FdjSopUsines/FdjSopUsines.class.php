
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
 * Description of CostsIndicator
 *
 * @author lob
 */
class FdjSopUsines extends IndicatorPluginAbstract {

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // internal
   protected $execData;
   protected $pcentMngt;  // management is included and represents 7% of each timetrack


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('== FDJ == SOP - Consommé par usines et par projet');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Consommé par usine et par projet');
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
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
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
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_TEAM_ID);
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

      // set default pluginSettings
      $this->pcentMngt = 0.075;  // 7.5% dans le contrat, ne bouge jamais...
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

   /**
    *
    * @return array execData
    */
   public function execute() {
      $jobs = new Jobs();
      $iselTimetracks = $this->inputIssueSel->getTimetracks(NULL, $this->startTimestamp, $this->endTimestamp);
      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly

      $catPerJobs = array(); // jobs[job][projCategory] = elapsed
      $totalJob = array(); // $totalElapsedArray[job] = totalElapsed
      foreach ($iselTimetracks as $tt) {

         // find real date range
         if ( (NULL == $realStartTimestamp) || ($tt->getDate() < $realStartTimestamp)) {
            $realStartTimestamp = $tt->getDate();
         }
         if ( (NULL == $realEndTimestamp) || ($tt->getDate() > $realEndTimestamp)) {
            $realEndTimestamp = $tt->getDate();
         }

         $jobName = $jobs->getJobName($tt->getJobId());
         $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());
         $categoryName = Project::getCategoryName($issue->getCategoryId());

         if (!array_key_exists($jobName, $catPerJobs)) {
            $catPerJobs[$jobName] = array();
         }
         if (!array_key_exists($categoryName, $catPerJobs[$jobName])) {
            $catPerJobs[$jobName][$categoryName] = array(
               'nbDays' => $tt->getDuration(),
               'pcent' => '0' // will be computed later
            );
         } else {
            $catPerJobs[$jobName][$categoryName]['nbDays'] += $tt->getDuration();
         }
         $totalJob[$jobName] += $tt->getDuration();
      }

      // compute percent for each category
      $iselElapsed = $this->inputIssueSel->getElapsed($this->startTimestamp, $this->endTimestamp);
      foreach ($catPerJobs as $jobName => $catArray) {
         foreach (array_keys($catArray) as $categoryName) {
            $nbDays = $catPerJobs[$jobName][$categoryName]['nbDays'];
            $pcent = round(($nbDays*100/$iselElapsed), 2);
            $pcentNoMngt = round(($nbDays*100/$iselElapsed*(1-$this->pcentMngt)), 2);
            $catPerJobs[$jobName][$categoryName]['pcent'] = $pcent;
            $catPerJobs[$jobName][$categoryName]['pcentNoMngt'] = $pcentNoMngt;
         }
      }

      // compute percent for each job
      $tableJobs = array();
      foreach($totalJob as $jobName => $jobElapsed) {
         $tableJobs[$jobName] = array(
            'nbDays' => $jobElapsed,
            'pcent' => round(($jobElapsed*100/$iselElapsed), 2),
            //'pcentNoMngt' => round(($jobElapsed*100/$iselElapsed*(1-$this->pcentMngt)), 2)  // remove 7.5%
         );
      }

      $jobMngt = array(
         'name' => "PILOTAGE OPERATIONNEL",
         'nbDays' => round(($jobElapsed*$this->pcentMngt),2),
         'pcent' => ($this->pcentMngt*100),
         'pcentNoMngt' => ''  // N/A
      );

      $this->execData= array(
         'tableCatPerJobs'    => $catPerJobs,
         'tableJobs'    => $tableJobs,
         'jobMngt' => $jobMngt,
         'realStartTimestamp' => $realStartTimestamp,
         'realEndTimestamp' => $realEndTimestamp,
      );

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables= array();
      foreach ($this->execData as $key => $val) {
         $smartyVariables['fdjSopUsines_'.$key] = $val;
      }
      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;
      $smartyVariables['fdjSopUsines_startDate'] = Tools::formatDate("%Y-%m-%d", $startTimestamp);
      $smartyVariables['fdjSopUsines_endDate']   = Tools::formatDate("%Y-%m-%d", $endTimestamp);

      if (false == $isAjaxCall) {
         $smartyVariables['fdjSopUsines_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjSopUsines_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
FdjSopUsines::staticInit();
