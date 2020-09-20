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
class OngoingTasks extends IndicatorPluginAbstract {

   const OPTION_IS_ONLY_TEAM_MEMBERS = 'isOnlyActiveTeamMembers';
   const OPTION_IS_DISPLAY_COMMANDS = 'isDisplayCommands';
   const OPTION_IS_DISPLAY_PROJECT = 'isDisplayProject';
   const OPTION_IS_DISPLAY_CATEGORY = 'isDisplayCategory';
   const OPTION_IS_DISPLAY_SUMMARY = 'isDisplayTaskSummary';
   const OPTION_IS_DISPLAY_EXTID = 'isDisplayTaskExtID';
   const OPTION_IS_DISPLAY_INVOLVED_USERS = 'isDisplayInvolvedUsers';
   const OPTION_IS_DISPLAY_WBS_PATH = 'isDisplayWbsPath';

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
   private $commandId; // DOMAIN_COMMAND only

   // config options from Dashboard
   private $isOnlyActiveTeamMembers;
   private $isDisplayCommands;
   private $isDisplayProject;
   private $isDisplayCategory;
   private $isDisplayTaskSummary;
   private $isDisplayTaskExtID;
   private $isDisplayInvolvedUsers;
   private $isDisplayWbsPath;

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
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
      return T_('Ongoing tasks');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('List the active tasks on a given period');
      if (!$isShortDesc) {
         $desc .= '<br><br>'.T_('Tasks having received timetracks during the period');
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
      if (IndicatorPluginInterface::DOMAIN_COMMAND === $this->domain) {
         if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID)) {
            $this->commandId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID);
         } else {
            throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_COMMAND_ID);
         }
      } else {
         $this->commandId = NULL;
         $this->isDisplayWbsPath = FALSE;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isOnlyActiveTeamMembers= TRUE;
      $this->isDisplayInvolvedUsers = TRUE;
      $this->isDisplayWbsPath = FALSE;
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
         if (array_key_exists(self::OPTION_IS_DISPLAY_INVOLVED_USERS, $pluginSettings)) {
            $this->isDisplayInvolvedUsers = $pluginSettings[self::OPTION_IS_DISPLAY_INVOLVED_USERS];
         }
         if (array_key_exists(self::OPTION_IS_DISPLAY_WBS_PATH, $pluginSettings)) {
            if (NULL != $this->commandId) {
               $this->isDisplayWbsPath = $pluginSettings[self::OPTION_IS_DISPLAY_WBS_PATH];
            } else {
               $this->isDisplayWbsPath = FALSE;
            }
         }
      }
   }


   /**
    *
    * returns an array of
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      // === get timetracks for each Issue
      if (NULL !== $this->managedUserId) {
         $useridList = array($this->managedUserId);

      } else {
         if ($this->isOnlyActiveTeamMembers) {
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
         } else {
            // include also timetracks of users not in the team (relevant on ExternalTasksProjects)
            $useridList = NULL;
         }
      }
      $timetracks = $this->inputIssueSel->getTimetracks($useridList, $this->startTimestamp, $this->endTimestamp);
      $nbTimetracks = count($timetracks);
      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly
      $totalElapsedOnPeriod = 0;

      $ongoingTasksArray = array(); // key = bugid
      foreach ($timetracks as $track) {

         // find real date range
         if ( (NULL == $realStartTimestamp) || ($track->getDate() < $realStartTimestamp)) {
            $realStartTimestamp = $track->getDate();
         }
         if ( (NULL == $realEndTimestamp) || ($track->getDate() > $realEndTimestamp)) {
            $realEndTimestamp = $track->getDate();
         }

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());
         $user = UserCache::getInstance()->getUser($track->getUserId());
         $bugId = $track->getIssueId();

         if (!array_key_exists($bugId, $ongoingTasksArray)) {
            $ongoingTasksArray[$bugId] = array(
               'issueId' => $bugId, //Tools::issueInfoURL($bugId, NULL, true),
               //'type' => $issue->getType(),
               //'targetVersion' => $issue->getTargetVersion(),
               'currentStatusName' => $issue->getCurrentStatusName(),
               'progress' => round(100 * $issue->getProgress()),
               'backlog' => $issue->getBacklog(),
               'elapsedOnPeriod' => $track->getDuration(),
            );
            if ($this->isDisplayCommands) {
               $ongoingTasksArray[$bugId]['commandList'] = implode(', ', $issue->getCommandList());
            }
            if ($this->isDisplayProject) {
               $ongoingTasksArray[$bugId]['projectName'] = $issue->getProjectName();
            }
            if ($this->isDisplayCategory) {
               $ongoingTasksArray[$bugId]['projectCategory'] = $issue->getCategoryName();
            }
            if ($this->isDisplayTaskSummary) {
               $ongoingTasksArray[$bugId]['taskSummary'] = htmlspecialchars($issue->getSummary());
            }
            if ($this->isDisplayTaskExtID) {
               $ongoingTasksArray[$bugId]['taskExtID'] = $issue->getTcId();
            }
            if ($this->isDisplayInvolvedUsers) {
               $handler = UserCache::getInstance()->getUser($issue->getHandlerId());
               $ongoingTasksArray[$bugId]['assignedTo'] = $handler->getName();
               $ongoingTasksArray[$bugId]['involvedUsers'] = $user->getName();
            }
            if ($this->isDisplayWbsPath) {
               $ongoingTasksArray[$bugId]['wbsPath'] = $issue->getWbsPath($this->commandId);
            }
         } else {
            $ongoingTasksArray[$bugId]['elapsedOnPeriod'] += $track->getDuration();

            if ($this->isDisplayInvolvedUsers) {
               $involvedUsers =  $ongoingTasksArray[$bugId]['involvedUsers'];
               if (FALSE === strpos($involvedUsers, $user->getName())) {
                  $ongoingTasksArray[$bugId]['involvedUsers'] = $involvedUsers.', '.$user->getName();
               }
            }
         }
         $totalElapsedOnPeriod += $track->getDuration();
      }

      $this->execData = array();
      $this->execData['realStartTimestamp'] = $realStartTimestamp;
      $this->execData['realEndTimestamp'] = $realEndTimestamp;
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['ongoingTasksArray'] = $ongoingTasksArray;
      $this->execData['totalElapsedOnPeriod'] = $totalElapsedOnPeriod;

      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $prefix='ongoingTasks_';
      $smartyVariables = array(
         $prefix.'isOnlyActiveTeamMembers' => $this->isOnlyActiveTeamMembers,
         $prefix.'isDisplayCommands' => $this->isDisplayCommands,
         $prefix.'isDisplayProject' =>  $this->isDisplayProject,
         $prefix.'isDisplayCategory' =>  $this->isDisplayCategory,
         $prefix.'isDisplayTaskSummary' =>  $this->isDisplayTaskSummary,
         $prefix.'isDisplayTaskExtID' =>  $this->isDisplayTaskExtID,
         $prefix.'isDisplayInvolvedUsers' =>  $this->isDisplayInvolvedUsers,
         $prefix.'isDisplayWbsPath' =>  $this->isDisplayWbsPath,

         $prefix.'nbTimetracks' =>  $this->execData['nbTimetracks'],
         $prefix.'ongoingTasksArray' =>  $this->execData['ongoingTasksArray'],
         $prefix.'totalElapsedOnPeriod' =>  $this->execData['totalElapsedOnPeriod'],

      );

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;
      $smartyVariables[$prefix.'startDate'] = Tools::formatDate("%Y-%m-%d", $startTimestamp);
      $smartyVariables[$prefix.'endDate']   = Tools::formatDate("%Y-%m-%d", $endTimestamp);
//self::$logger->error($smartyVariables);

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
OngoingTasks::staticInit();
