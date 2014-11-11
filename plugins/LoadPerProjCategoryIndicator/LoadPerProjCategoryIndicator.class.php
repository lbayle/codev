
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
 * Description of HelloWorldIndicator
 *
 * @author lob
 */
class LoadPerProjCategoryIndicator extends IndicatorPluginAbstract {

   const OPTION_DEFAULT_PROJECT = 'defaultProject';
   const OPTION_DISPLAY_TASKS   = 'isDisplayTasks';
   const OPTION_DATE_RANGE      = 'dateRange';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $selectedProject;

   // config options from Dashboard
   private $isDisplayTasks;
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
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_USER,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return 'Load per project categories';
   }
   public static function getDesc() {
      return 'Check all the timetracks of the period and return their repartition per project categories';
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
         'js/tooltip.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID)) {
         $this->selectedProject = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID);
      } else {
         $this->selectedProject = 'allSidetasksProjects';
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isDisplayTasks = false;
      $this->dateRange   = 'defaultRange';
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DISPLAY_TASKS, $pluginSettings)) {
            $this->isDisplayTasks = $pluginSettings[self::OPTION_DISPLAY_TASKS];
         }
         if (array_key_exists(PluginDataProviderInterface::PARAM_PROJECT_ID, $pluginSettings)) {
            $this->selectedProject = $pluginSettings[PluginDataProviderInterface::PARAM_PROJECT_ID];
         }
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

      
      // === get timetracks for each Issue,
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
      $timeTracks = $this->inputIssueSel->getTimetracks($useridList, $this->startTimestamp, $this->endTimestamp);

      // === get project list
      // TODO
      $projectidList = array_keys($this->getProjectList());

      // === process timetracks
      $rawInfoPerCat = $this->getRawInfoPerCategory($timeTracks, $projectidList);
      
      // === build $infoPerCat
      $infoPerCat = array();
      foreach ($rawInfoPerCat['durationPerCat'] as $catName => $duration) {

         $catInfo = array(
            'catName' => $catName,
            'duration' => $duration,
         );

         // create formatedBugList (with tooltips)
         if ($this->isDisplayTasks) {
            $bugList = $rawInfoPerCat['bugidsPerCat'][$catName];
            $formatedBugs = '';
            foreach ($bugList as $bugid => $bugDuration) {
               $issue = IssueCache::getInstance()->getIssue($bugid);
               $tooltipAttributes = array(
                     T_('Project') => $issue->getProjectName(),
                     T_('Summary') => $issue->getSummary(),
                     T_('Elapsed') => $bugDuration,
               );
               if (!empty($formatedBugs)) {
                  $formatedBugs .= ', '.Tools::issueInfoURL($bugid, $tooltipAttributes);
               } else {
                  $formatedBugs = Tools::issueInfoURL($bugid, $tooltipAttributes);
               }
            }
            $catInfo['formatedBugsPerCategory'] = $formatedBugs;
         }

         $infoPerCat[$catName] = $catInfo;
      }
      
      $this->execData = $infoPerCat;
      
      return $this->execData;
   }

   private function getProjectList() {
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamProjects = $team->getProjects(false, true, true);

      switch ($this->selectedProject) {
         case 'allSidetasksProjects':
            $projectList = array();
            foreach ($teamProjects as $projectid => $pname) {
               if ($team->isSideTasksProject($projectid)) {
                  $projectList[$projectid] = $pname;
               }
            }
            break;
         case 'allProdProjects':
            $projectList = $team->getProjects(false, true, false);
            break;
         case 'allProjects':
            $projectList = $teamProjects;
            break;
         default:
            if (array_key_exists($this->selectedProject, $teamProjects)) {
               // it's a real project
               $projectList = array($this->selectedProject => $teamProjects[$this->selectedProject]);
            } else {
               // error case (unknown value)
               self::$logger->error('getProjectList(): Unknown project_id='.$this->selectedProject);
               $projectList = array();
            }
      }
      //self::$logger->error('selectedProject='.$this->selectedProject);
      //self::$logger->error('getProjectList='.var_export($projectList, true));

      return $projectList;
   }

      /**
    * returns $durationPerCategory[CategoryName]['duration'] = duration
    * 
    * @param array $projectidList list of project_id
    * @return array[] $durationPerCategory[CategoryName] = array (duration, bugidList), 
    */
   private function getRawInfoPerCategory($timeTracks, $projectidList) {
      $durPerCat = array();
      $bugsPerCat = array();

      foreach($timeTracks as $timeTrack) {
         try {
            $bugid = $timeTrack->getIssueId();
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $project_id = $issue->getProjectId();
            $catName = $issue->getCategoryName();
            $duration = $timeTrack->getDuration();
            
            if (in_array($project_id, $projectidList)) {
               if(self::$logger->isDebugEnabled()) {
                  self::$logger->debug("project[$project_id][" . $catName . "]( bug ".$bugid.") = ".$duration);
               }
               
               // save duration per category
               if (array_key_exists($catName, $durPerCat)) {
                  $durPerCat[$catName] += $duration;
               } else {
                  $durPerCat[$catName] = $duration;
               }
               
               // save bugid list per category
               if ($this->isDisplayTasks) {
                  if (!array_key_exists($catName, $bugsPerCat)) {
                     $bugsPerCat[$catName] = array();
                  }
                  if(array_key_exists($bugid,$bugsPerCat[$catName])) {
                     $bugsPerCat[$catName][$bugid] += $duration;
                  } else {
                     $bugsPerCat[$catName][$bugid] = $duration;
                  }
               }
            }
         } catch (Exception $e) {
            self::$logger->warn("getDurationPerProjectCategory() issue ".$timeTrack->getIssueId()." not found in Mantis DB (duration = ".$timeTrack->getDuration()." on ".date('Y-m-d', $timeTrack->getDate()).')');
         }
      }
      $ret =  array('durationPerCat' => $durPerCat);
      if ($this->isDisplayTasks) {
         $ret['bugidsPerCat'] = $bugsPerCat;
      }      
      return $ret;
   }
   
   
   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamProjects = $team->getProjects(false, true, true);
      $teamProjects['allSidetasksProjects'] = '-- '.T_('All Sidetasks Projects').' --';
      $teamProjects['allProdProjects'] = '-- '.T_('All Production Projects').' --';
      $teamProjects['allProjects'] = '-- '.T_('All Projects').' --';
      $projects = SmartyTools::getSmartyArray($teamProjects,$this->selectedProject);
      //self::$logger->error(var_export($projects, true));
      
      $data = array();
      foreach ($this->execData as $catInfo) {
         if (0 != $catInfo['duration']) {
            $data[$catInfo['catName']] = $catInfo['duration'];
         }
      }
      $jqplotData = empty($data) ? NULL : Tools::array2plot($data);
      
      
      $smartyVariables = array(
         'loadPerProjCategoryIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'loadPerProjCategoryIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         'loadPerProjCategoryIndicator_projects' => $projects,

         'loadPerProjCategoryIndicator_tableData' => $this->execData,
         'loadPerProjCategoryIndicator_jqplotData' => $jqplotData,
              
         // add pluginSettings (if needed by smarty)
         'loadPerProjCategoryIndicator_'.self::OPTION_DISPLAY_TASKS => $this->isDisplayTasks,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['loadPerProjCategoryIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['loadPerProjCategoryIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
LoadPerProjCategoryIndicator::staticInit();
