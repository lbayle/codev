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
 * Description of FdjTimetracksPerTaskWithUOIndicator
 *
 * For each Task, return the sum of the backlog UO of its assigned tasks.
 *
 * @author lob
 */
class TimetrackList extends IndicatorPluginAbstract {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard

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
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return T_('Timetrack list');
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('List and edit timetracks');
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

   }

   /**
    * settings are saved by the Dashboard
    *
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
      }
   }


   /**
    *
    * returns an array of
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $timetracks = $this->inputIssueSel->getTimetracks(NULL, $this->startTimestamp, $this->endTimestamp);
      $nbTimetracks = count($timetracks);
      $realStartTimestamp = $this->endTimestamp; // note: inverted intentionnaly
      $realEndTimestamp = $this->startTimestamp; // note: inverted intentionnaly
      $jobs = new Jobs();

      foreach ($timetracks as $trackid => $track) {

         $issue = IssueCache::getInstance()->getIssue($track->getIssueId());

         // find real date range
         if ( (NULL == $realStartTimestamp) || ($track->getDate() < $realStartTimestamp)) {
            $realStartTimestamp = $track->getDate();
         }
         if ( (NULL == $realEndTimestamp) || ($track->getDate() > $realEndTimestamp)) {
            $realEndTimestamp = $track->getDate();
         }

         $user = UserCache::getInstance()->getUser($track->getUserId());
            $timetracksArray[$trackid] = array(
               'commandList' => implode(', ', $issue->getCommandList()),
               'issueId' => Tools::issueInfoURL($issue->getId(), $issue->getSummary(), true),
               #'task' => $issue->getSummary(),
               'user' => $user->getRealname(),
               'dateTimetrack' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
               'projectCategory' => $issue->getCategoryName(),
               'projectName' => $issue->getProjectName(),
               'jobName' => $jobs->getJobName($track->getJobId()),
               'note' => nl2br(htmlspecialchars($track->getNote())),
               #'elapsed' => str_replace('.', ',',round($track->getDuration(), 2)),
               'elapsed' => round($track->getDuration(), 2),
               'id' => $track->getId(),
            );
      }

      $this->execData = array();
      $this->execData['nbTimetracks'] = $nbTimetracks;
      $this->execData['timetracksArray'] = $timetracksArray;
      //$this->execData['totalArray'] = $totalArray;
      $this->execData['realStartTimestamp'] = $realStartTimestamp;
      $this->execData['realEndTimestamp'] = $realEndTimestamp;

      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = array(
         'timetrackList_timetracksArray' => $this->execData['timetracksArray'],
      );

      if (false == $isAjaxCall) {
         $smartyVariables['timetrackList_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['timetrackList_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      $startTimestamp = (NULL == $this->startTimestamp) ? $this->execData['realStartTimestamp'] : $this->startTimestamp;
      $endTimestamp   = (NULL == $this->endTimestamp) ?   $this->execData['realEndTimestamp']   : $this->endTimestamp;
      $smartyVariables['timetrackList_startDate'] = Tools::formatDate("%Y-%m-%d", $startTimestamp);
      $smartyVariables['timetrackList_endDate']   = Tools::formatDate("%Y-%m-%d", $endTimestamp);

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
TimetrackList::staticInit();
