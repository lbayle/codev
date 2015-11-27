
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
class DriftAlertIndicator extends IndicatorPluginAbstract {


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
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_RISK
      );
   }

   public static function getName() {
      //return T_('Task drift alert');
      return T_('Task drift alert');
   }
   public static function getDesc($isShortDesc = true) {
      //return T_('Display tasks in drift');
      $desc = T_('Display tasks where the elapsed time is greater than the estimated effort');
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
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/progress.min.js',
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
    * @param Issue $issue
    * @param boolean $isManager
    * @return mixed[]
    */
   private function getSmartyDirftedIssue(Issue $issue, $isManager) {
      $driftMgr = ($isManager) ? $issue->getDriftMgr() : 0;
      $drift = $issue->getDrift();
      $driftMgrColor = NULL;
      if ($isManager) {
         if ($driftMgr < -1) {
            $driftMgrColor = "#61ed66";
         } else if ($driftMgr > 1) {
            $driftMgrColor = "#fcbdbd";
         }
         $driftMgr = round($driftMgr, 2);
      }

      $driftColor = NULL;
      if ($drift < -1) {
         $driftColor = "#61ed66";
      } else if ($drift > 1) {
         $driftColor = "#fcbdbd";
      }

      return array(
         'issueURL' => Tools::issueInfoURL($issue->getId()),
         'mantisURL' => Tools::mantisIssueURL($issue->getId(), NULL, true),
         'projectName' => $issue->getProjectName(),
         'targetVersion' => $issue->getTargetVersion(),
         'driftMgrColor' => $driftMgrColor,
         'driftMgr' => $driftMgr,
         'driftColor' => $driftColor,
         'drift' => round($drift, 2),
         'backlog' => $issue->getBacklog(),
         'progress' => round(100 * $issue->getProgress()),
         'currentStatusName' => $issue->getCurrentStatusName(),
         'summary' => $issue->getSummary()
      );
   }


  /**
    *
    */
   public function execute() {

      $user = UserCache::getInstance()->getUser($this->session_userid);

      $isManager = $user->isTeamManager($this->teamid);
      $isObserver = $user->isTeamObserver($this->teamid);
      
      $currentIssuesInDrift = NULL;
      $resolvedIssuesInDrift = NULL;
      foreach ($this->inputIssueSel->getIssuesInDrift(($isManager || $isObserver)) as $issue) {
         $smartyIssue = $this->getSmartyDirftedIssue($issue, ($isManager || $isObserver));
         if(NULL != $smartyIssue) {
            if ($issue->isResolved()) {
               $resolvedIssuesInDrift[] = $smartyIssue;
            } else {
               $currentIssuesInDrift[] = $smartyIssue;
            }
         }
      }

      $this->execData = array(
         'currentIssuesInDrift' => $currentIssuesInDrift,
         'resolvedIssuesInDrift' => $resolvedIssuesInDrift,
      );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables = array(
         'driftAlertIndicator_currentIssuesInDrift' => $this->execData['currentIssuesInDrift'],
         'driftAlertIndicator_resolvedIssuesInDrift' => $this->execData['resolvedIssuesInDrift'],

         // add pluginSettings (if needed by smarty)
      );

      if (false == $isAjaxCall) {
         $smartyVariables['driftAlertIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['driftAlertIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
DriftAlertIndicator::staticInit();
