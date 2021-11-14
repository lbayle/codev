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
 *
 *
 * For each Task, return the sum of the elapsed time.
 *
 * @author lob
 */
class SellingPriceForPeriod extends IndicatorPluginAbstract {

   const OPTION_IS_ONLY_TEAM_MEMBERS = 'isOnlyActiveTeamMembers';
   const OPTION_IS_INCLUDE_SIDE_TASKS = 'isIncludeSideTasks';
   const OPTION_IS_DISPLAY_COMMANDS = 'isDisplayCommands';
   const OPTION_IS_DISPLAY_PROJECT = 'isDisplayProject';
   const OPTION_IS_DISPLAY_CATEGORY = 'isDisplayCategory';
   const OPTION_IS_DISPLAY_SUMMARY = 'isDisplayTaskSummary';
   const OPTION_IS_DISPLAY_EXTID = 'isDisplayTaskExtID';

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
   private $managedUserId; // DOMAIN_USER only

   // config options from Dashboard
   private $isOnlyActiveTeamMembers;
   private $isIncludeSideTasks;
   private $isDisplayCommands;
   private $isDisplayProject;
   private $isDisplayCategory;
   private $isDisplayTaskSummary;
   private $isDisplayTaskExtID;

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_PROJECT,
         self::DOMAIN_TEAM,
         self::DOMAIN_USER,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return T_("Selling Price for the Period");
   }
   public static function getDesc($isShortDesc = true) {
      // Si vous vendez de la prestation de service à la journée avec un prix spécifique pour chaque tâche, ce plugin vous donnera le prix de votre lot de tâches sur une période donnée
      $desc =
         T_("If you sell daily services with a specific price for each task, this plugin will give you the price of your batch of tasks over a given period of time.").
         ' '.T_('For this plugin, you need to add the "CodevTT_DailyPrice" customField to your mantis projects and set a value for each task.');
      if (!$isShortDesc) {
         $desc .= '<br><br>'.T_('SellingPrice = DailyPrice x Elapsed on period');

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
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
         'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
         'js_min/datatable.min.js',
         'js_min/tabs.min.js',
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

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }
      if (IndicatorPluginInterface::DOMAIN_USER === $this->domain) {
         if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID)) {
            $this->managedUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         } else {
            throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_MANAGED_USER_ID);
         }
      } else {
         $this->managedUserId = NULL; // consider complete team
      }


      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         // WARN: no start date can return loads of results and eventualy overload the server
         $this->startTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = NULL;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isOnlyActiveTeamMembers= TRUE;
      $this->isIncludeSideTasks = FALSE;
   }

   /**
    * settings are saved by the Dashboard
    *
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_ONLY_TEAM_MEMBERS, $pluginSettings)) {
            $this->isOnlyActiveTeamMembers = $pluginSettings[self::OPTION_IS_ONLY_TEAM_MEMBERS];
         }
         if (array_key_exists(self::OPTION_IS_INCLUDE_SIDE_TASKS, $pluginSettings)) {
            $this->isIncludeSideTasks = $pluginSettings[self::OPTION_IS_INCLUDE_SIDE_TASKS];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_COMMANDS, $pluginSettings)) {
            $this->isDisplayCommands = $pluginSettings[self::OPTION_IS_DISPLAY_COMMANDS];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_PROJECT, $pluginSettings)) {
            $this->isDisplayProject = $pluginSettings[self::OPTION_IS_DISPLAY_PROJECT];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_CATEGORY, $pluginSettings)) {
            $this->isDisplayCategory = $pluginSettings[self::OPTION_IS_DISPLAY_CATEGORY];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_SUMMARY, $pluginSettings)) {
            $this->isDisplayTaskSummary = $pluginSettings[self::OPTION_IS_DISPLAY_SUMMARY];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_EXTID, $pluginSettings)) {
            $this->isDisplayTaskExtID = $pluginSettings[self::OPTION_IS_DISPLAY_EXTID];
         }
      }
   }

   /**
    * check if customField is assign on concerned mantis projects
    * @param type $projectList
    * @return string list of projects with no DailyPricecustomField assigned. NULL if all projects are ok
    */
   private function checkCustomField($projectList) {

      $customFieldId = Config::getInstance()->getValue(Config::id_customField_dailyPrice);

      foreach($projectList as $projectId => $projectName) {
         $project = ProjectCache::getInstance()->getProject($projectId);
         if ($project->hasCustomField($customFieldId)) {
            unset($projectList[$projectId]);
         }
      }
      $formatedProjList = NULL;
      if (!empty($projectList)) {
         $formatedProjList = implode( ', ', $projectList);
      }
      return $formatedProjList;
   }

   /**
    *
    * returns an array of
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $team = TeamCache::getInstance()->getTeam($this->teamid);

      // === get timetracks for each Issue
      if (NULL !== $this->managedUserId) {
         $useridList = array($this->managedUserId);

      } else {
         if ($this->isOnlyActiveTeamMembers) {
            $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
         } else {
            // include also timetracks of users not in the team (relevant on ExternalTasksProjects)
            $useridList = NULL;
         }
      }

      // ExternalTasks are non-production tasks and must be excluded
      $projectList = array();
      foreach ($this->inputIssueSel->getIssueList() as $issue) {
         $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
         if ($project->isExternalTasksProject() ||
             $project->isNoStatsProject(array($this->teamid))) {
            $this->inputIssueSel->removeIssue($issue->getId());
            continue;
         }
         if ($project->isNoStatsProject()) {
            $this->inputIssueSel->removeIssue($issue->getId());
            continue;
         }
         if ((FALSE == $this->isIncludeSideTasks) && (
            $team->isSideTasksProject($issue->getProjectId()))) {
            $this->inputIssueSel->removeIssue($issue->getId());
            continue;
         }
         $projectList[$project->getId()] = $project->getName();
      }

      // if != NULL, then some projects have no DailyPrice customField assigned
      $formatedProjList = $this->checkCustomField($projectList);

      $timetracks = $this->inputIssueSel->getTimetracks($useridList, $this->startTimestamp, $this->endTimestamp);
      $nbTimetracks = count($timetracks);
      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly
      $totalElapsedOnPeriod = 0;
      $totalSellingPriceForPeriod = 0;

      $sellingPriceForPeriodArray = array(); // key = bugid
      foreach ($timetracks as $track) {

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $bugId = $track->getIssueId();

         // find real date range
         if ( (NULL == $realStartTimestamp) || ($track->getDate() < $realStartTimestamp)) {
            $realStartTimestamp = $track->getDate();
         }
         if ( (NULL == $realEndTimestamp) || ($track->getDate() > $realEndTimestamp)) {
            $realEndTimestamp = $track->getDate();
         }


         if (!array_key_exists($bugId, $sellingPriceForPeriodArray)) {

            $remainingAtEndOfPeriod = ((float)$issue->getMgrEffortEstim() - (float)$issue->getElapsed(NULL, NULL, $this->endTimestamp));

            $sellingPriceForPeriodArray[$bugId] = array(
               'issueId' => $bugId, //Tools::issueInfoURL($bugId, NULL, true),
               //'type' => $issue->getType(),
               //'targetVersion' => $issue->getTargetVersion(),
               'currentStatusName' => $issue->getCurrentStatusName(),
               'progress' => round(100 * $issue->getProgress()),
               'backlog' => $issue->getBacklog(),
               'elapsedOnPeriod' => $track->getDuration(),
               'remainingAtEndOfPeriod' => $remainingAtEndOfPeriod,
            );
            if ($this->isDisplayCommands) {
               $sellingPriceForPeriodArray[$bugId]['commandList'] = implode(', ', $issue->getCommandList());
            }
            if ($this->isDisplayProject) {
               $sellingPriceForPeriodArray[$bugId]['projectName'] = $issue->getProjectName();
            }
            if ($this->isDisplayCategory) {
               $sellingPriceForPeriodArray[$bugId]['projectCategory'] = $issue->getCategoryName();
            }
            if ($this->isDisplayTaskSummary) {
               $sellingPriceForPeriodArray[$bugId]['taskSummary'] = htmlspecialchars($issue->getSummary());
            }
            if ($this->isDisplayTaskExtID) {
               $sellingPriceForPeriodArray[$bugId]['taskExtID'] = $issue->getTcId();
            }

            $sellingPriceForPeriodArray[$bugId]['taskDailyPrice'] = sprintf("%01.2f", $issue->getDailyPrice());
            $taskPriceOnPeriod = ($track->getDuration() * $issue->getDailyPrice());
            $sellingPriceForPeriodArray[$bugId]['taskPriceOnPeriod'] = sprintf("%01.2f", $taskPriceOnPeriod);
            $totalSellingPriceForPeriod += $taskPriceOnPeriod;
         } else {
            $sellingPriceForPeriodArray[$bugId]['elapsedOnPeriod'] += $track->getDuration();
            $sellingPriceForPeriodArray[$bugId]['taskPriceOnPeriod'] = sprintf("%01.2f", ($sellingPriceForPeriodArray[$bugId]['elapsedOnPeriod'] * $issue->getDailyPrice()));
            $totalSellingPriceForPeriod += ($track->getDuration() * $issue->getDailyPrice());
         }
         $totalElapsedOnPeriod += $track->getDuration();
      }

      // Remaining concerns all issues, not only the ones having been modified during the period
      $totalRemainingAtEndOfPeriod = (float)$this->inputIssueSel->getMgrEffortEstim() - (float)$this->inputIssueSel->getElapsed(NULL, $this->endTimestamp);

      $this->execData = array();
      $this->execData['realStartTimestamp'] = $realStartTimestamp;
      $this->execData['realEndTimestamp'] = $realEndTimestamp;
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['sellingPriceForPeriodArray'] = $sellingPriceForPeriodArray;
      $this->execData['totalSellingPriceForPeriod'] = sprintf("%01.2f", $totalSellingPriceForPeriod);
      $this->execData['totalElapsedOnPeriod'] = $totalElapsedOnPeriod;
      $this->execData['totalRemainingAtEndOfPeriod'] = $totalRemainingAtEndOfPeriod;
      $this->execData['projectsWithoutRequiredCustomField'] = $formatedProjList;

      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $prefix='sellingPriceForPeriod_';
      $team = TeamCache::getInstance()->getTeam($this->teamid);

      $smartyVariables = array(
         $prefix.'isOnlyActiveTeamMembers' => $this->isOnlyActiveTeamMembers,
         $prefix.'isDisplayCommands' => $this->isDisplayCommands,
         $prefix.'isDisplayProject' =>  $this->isDisplayProject,
         $prefix.'isDisplayCategory' =>  $this->isDisplayCategory,
         $prefix.'isDisplayTaskSummary' =>  $this->isDisplayTaskSummary,
         $prefix.'isDisplayTaskExtID' =>  $this->isDisplayTaskExtID,
         $prefix.'teamCurrency' =>  $team->getTeamCurrency(),

         $prefix.'nbTimetracks' =>  $this->execData['nbTimetracks'],
         $prefix.'sellingPriceForPeriodArray' =>  $this->execData['sellingPriceForPeriodArray'],
         $prefix.'totalSellingPriceForPeriod' =>  $this->execData['totalSellingPriceForPeriod'],
         $prefix.'totalElapsedOnPeriod' =>  $this->execData['totalElapsedOnPeriod'],
         $prefix.'totalRemainingAtEndOfPeriod' =>  $this->execData['totalRemainingAtEndOfPeriod'],
         $prefix.'projectsWithoutRequiredCustomField' =>  $this->execData['projectsWithoutRequiredCustomField'],

      );

      if (NULL != $this->execData['projectsWithoutRequiredCustomField']) {
         $smartyVariables[$prefix.'generalErrorMsg'] = T_('WARNING: You need to assign the "CodevTT_DailyPrice" customField to the following projects: ').
            $this->execData['projectsWithoutRequiredCustomField'];
      }


      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;
      $smartyVariables[$prefix.'startDate'] = Tools::formatDate("%Y-%m-%d", $startTimestamp);
      $smartyVariables[$prefix.'endDate']   = Tools::formatDate("%Y-%m-%d", $endTimestamp);

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
SellingPriceForPeriod::staticInit();
