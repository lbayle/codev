
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
 * Description of DeadlineAlertIndicator
 *
 * @author lob
 */
class DeadlineAlertIndicator extends IndicatorPluginAbstract {


   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $teamid;
   private $session_userid;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_USER,
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }

   public static function getName() {
      return 'Task deadlines alert';
   }
   public static function getDesc() {
      return 'Display unresolved tasks that should have been delivered';
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
         'js_min/progress.min.js',
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
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->session_userid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }

      // set default pluginSettings
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
//         if (array_key_exists(self::OPTION_IS_DATE_DISPLAYED, $pluginSettings)) {
//            $this->isDateDisplayed = $pluginSettings[self::OPTION_IS_DATE_DISPLAYED];
//         }
      }
   }


  /**
    *
    */
   public function execute() {

      $issueList = $this->inputIssueSel->getIssueList();

      $today = time();
      $midnightTimestamp = mktime(0, 0, 0, date('m', $today), date('d', $today), date('Y', $today));
      $issueArray = NULL;

      foreach ($issueList as $issue) {
         $deadline = $issue->getDeadLine();

         if (($deadline > 0) && ($deadline < $midnightTimestamp) && !$issue->isResolved()) {

            $tooltipAttr = $issue->getTooltipItems($this->teamid, $this->session_userid);
            $user=  UserCache::getInstance()->getUser($issue->getHandlerId());
            #$driftMgrEE = $issue->getDriftMgr($withSupport);
            #$driftEE = $issue->getDrift($withSupport);
            $issueArray[] = array(
               'bugId' => Tools::issueInfoURL($issue->getId(), $tooltipAttr),
               'handlerName' => $user->getName(),
               'projectName' => $issue->getProjectName(),
               'deadline' => date('Y-m-d', $deadline),
               'progress' => round(100 * $issue->getProgress()),
               #'effortEstimMgr' => $issue->getMgrEffortEstim(),
               #'effortEstim' => ($issue->getEffortEstim() + $issue->getEffortAdd()),
               'elapsed' => $issue->getElapsed(),
               'reestimated' => $issue->getReestimated(),
               'backlog' => $issue->getBacklog(),
               #'driftPrelEE' => $driftMgrEE,
               #'driftEE' => $driftEE,
               'statusName' => $issue->getCurrentStatusName(),
               'summary' => $issue->getSummary()
            );
         }
      }

      $this->execData = $issueArray;
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables = array(
         'deadlineAlertIndicator_issueArray' => $this->execData,

         // add pluginSettings (if needed by smarty)
      );

      if (false == $isAjaxCall) {
         $smartyVariables['deadlineAlertIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['deadlineAlertIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
DeadlineAlertIndicator::staticInit();
