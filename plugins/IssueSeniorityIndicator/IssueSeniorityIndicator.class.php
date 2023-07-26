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
 * Description of StatusHistoryIndicator
 *
 */
class IssueSeniorityIndicator extends IndicatorPluginAbstract {

   const OPTION_DISPLAY_TASKS = 'isDisplayTasks';
   const OPTION_TASK_TYPE_FILTER = 'taskTypeFilter'; // all, bugsOnly, tasksOnly

   const CONST_ALL = 'all';
   const CONST_BUGS_ONLY = 'bugsOnly';
   const CONST_TASKS_ONLY = 'tasksOnly';

   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $managedUserId; // DOMAIN_USER only
   private $teamid;
   private $sessionUserId;

   // config options from Dashboard
   private $isDisplayTasks;
   private $taskTypeFilter;

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
      return T_('Open issues seniority statistics');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Statistics on the age of open tasks');
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
         'lib/jquery.jqplot/plugins/jqplot.barRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
         'js_min/chart.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
      );
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
          $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
          throw new Exception('Missing parameter: ' . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_TEAM_ID);
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

      $this->isDisplayTasks = true;
      $this->taskTypeFilter = self::CONST_ALL;
   }

   /**
    * settings are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DISPLAY_TASKS, $pluginSettings)) {
            $this->isDisplayTasks = $pluginSettings[self::OPTION_DISPLAY_TASKS];
         }
         if (array_key_exists(self::OPTION_TASK_TYPE_FILTER, $pluginSettings)) {
            $this->taskTypeFilter = $pluginSettings[self::OPTION_TASK_TYPE_FILTER];
         }
      }
   }


   private function createTimestampRangeList() {
      $timestampRangeList = array();

      $strtotimeArray = array(
         '-7 days'  => T_('Last week'),
         '-14 days' => T_('+1 week'),
         '-21 days' => T_('+2 week'),
         '-1 month' => T_('+3 week'),
         '-2 month' => T_('+1 months'),
         '-3 month' => T_('+2 months'),
         '-6 month' => T_('+3 months'),
         '-1 year'  => T_('+6 months'),
         '-10 year' => T_('Over a year')
      );
      $now = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

      $startT = time();
      foreach ($strtotimeArray as $strTime => $desc) {
         $endT = strtotime($strTime, $now);
         $timestampRangeList[$startT] = array(
            'periodName' => $desc,
            'periodDesc' => date('Y-m-d', $startT).' - '.date('Y-m-d', $endT),
            'startT' => $startT,
            'endT' => $endT,
            'nbTasks' => 0,
            'nbNew' => 0,
            'nbOpen' => 0,
            'backlog' => 0,
            'tasks' => '',
         );
         $startT = $endT;
      }
      //self::$logger->error("timestampRangeList = ".var_export($timestampRangeList, true));
      return $timestampRangeList;

   }

   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {
      $issueSeniorityData = $this->createTimestampRangeList();

      $totalNbTasks = 0;
      $totalNbNew = 0;
      $totalNbOpen = 0;
      $totalBacklog = 0;

      /* @var $issue Issue */
      foreach ($this->inputIssueSel->getIssueList() as $bugid => $issue) {

         if ($issue->isResolved()) { continue; }

         if ((NULL !== $this->managedUserId) && ($issue->getHandlerId() != $this->managedUserId)) { continue; }

         if ((self::CONST_BUGS_ONLY == $this->taskTypeFilter) && ('Bug' !== $issue->getType())) { continue; }
         if ((self::CONST_TASKS_ONLY == $this->taskTypeFilter) && ('Bug' === $issue->getType())) { continue; }

         $submissionDate = $issue->getDateSubmission();

         foreach ($issueSeniorityData as $k => $ttRange) {
            if (($submissionDate <= $ttRange['startT'])&&
                ($submissionDate > $ttRange['endT'])) {

               if ($issue->getCurrentStatus() == Constants::$status_new) {
                  $issueSeniorityData[$k]['nbNew'] += 1;
                  $totalNbNew += 1;
               } else {
                  $issueSeniorityData[$k]['nbOpen'] += 1;
                  $totalNbOpen += 1;
               }
               $issueSeniorityData[$k]['nbTasks'] += 1;
               $totalNbTasks += 1;
               $issueSeniorityData[$k]['backlog'] += (float) $issue->getDuration();

               //$issueSeniorityData[$k]['tasks'] .= "$bugid, ";
               $tooltipAttr = $issue->getTooltipItems($this->teamid, $this->sessionUserId);
               $tooltipAttr = array(T_('Summary') => $issue->getSummary()) + $tooltipAttr;
               $formattedTaskId = Tools::issueInfoURL($bugid, $tooltipAttr, FALSE, $bugid);
               $issueSeniorityData[$k]['tasks'] .= "$formattedTaskId, ";

               $totalBacklog += (float) $issue->getDuration();
               break;
            }

         }
      }
      //self::$logger->error("issueSeniorityData = ".var_export($issueSeniorityData, true));

      $taskTypeFilter = '';
      switch ($this->taskTypeFilter) {
         case self::CONST_BUGS_ONLY:
            $taskTypeFilter = T_('Bugs only');
            break;
         case self::CONST_TASKS_ONLY:
            $taskTypeFilter = T_('Tasks only');
            break;
         default:
            $taskTypeFilter = T_('All (tasks & bugs)');
      }


      $this->execData = array (
         'tableData' => $issueSeniorityData,
         'totalNbTasks' => $totalNbTasks,
         'totalNbNew' => $totalNbNew,
         'totalNbOpen' => $totalNbOpen,
         'totalBacklog' => $totalBacklog,
         'taskTypeFilter' => $taskTypeFilter,
      );
   }


   public function getSmartyVariables($isAjaxCall = false) {

      // format data for jqPlot
      $values1 = array();
      $values2 = array();
      $jqplotBarNames = array();
      foreach ($this->execData['tableData'] as $ttRange) {
         $values1[] = $ttRange['nbNew'];
         $values2[] = $ttRange['nbOpen'];
         $jqplotBarNames[] = $ttRange['periodName'];
      }
      $values = array($values1, $values2);

      $smartyPrefix = 'IssueSeniorityIndicator_';
      $smartyVariables = array (
         $smartyPrefix.'jqplotBarNames' => json_encode($jqplotBarNames),
         $smartyPrefix.'jqplotData' => json_encode($values),
         $smartyPrefix.self::OPTION_DISPLAY_TASKS => $this->isDisplayTasks,
      );
      foreach ($this->execData as $key => $val) {
         $smartyVariables[$smartyPrefix.$key] = $val;
      }

      if (false == $isAjaxCall) {
         $smartyVariables[$smartyPrefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$smartyPrefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
IssueSeniorityIndicator::staticInit();

