
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
 * Description of TimePerStatusIndicator
 *
 * @author lob
 */
class IssueConsistencyCheck extends IndicatorPluginAbstract {


   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $teamid;
   private $sessionUserId;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      // A plugin can be displayed in multiple domains
      self::$domains = array (
         self::DOMAIN_HOMEPAGE,
      );
      // A plugin should have only one category
      self::$categories = array (
         self::CATEGORY_RISK
      );
   }

   public static function getName() {
      return T_('Issue consistency check');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Check for errors in issues');
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
         //'js_min/progress.min.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
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
      }
   }

  /**
    *
    * Table Repartition du temps par status
    */
   public function execute() {

      $consistencyErrors = array();

      try {
         $issueList = $this->inputIssueSel->getIssueList();
         //$session_user = UserCache::getInstance()->getUser($this->sessionUserId);
         $ccheck = new ConsistencyCheck2($issueList, $this->teamid);
         $cerrList = $ccheck->check();

         if (count($cerrList) > 0) {
            foreach ($cerrList as $cerr) {

               if (NULL != $cerr->userId) {
                  $user = UserCache::getInstance()->getUser($cerr->userId);
               } else {
                  $user = NULL;
               }
               if (Issue::exists($cerr->bugId)) {
                  $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
                  $summary = $issue->getSummary();
                  $projName = $issue->getProjectName();
                  $refExt = $issue->getTcId();

                  $consistencyErrors[] = array(
                     'userName' => isset($user) ? $user->getName() : '',
                     'issueURL' => (NULL == $cerr->bugId) ? '' : Tools::issueInfoURL($cerr->bugId, $summary),
                     'mantisURL' => (NULL == $cerr->bugId) ? '' : Tools::mantisIssueURL($cerr->bugId, NULL, true),
                     'extRef' =>  (NULL == $refExt) ? '' : $refExt,
                     'date' =>  (NULL == $cerr->timestamp) ? '' : date("Y-m-d", $cerr->timestamp),
                     'status' => (NULL == $cerr->status) ? '' : Constants::$statusNames[$cerr->status],
                     'severity' => $cerr->getLiteralSeverity(),
                     'severityColor' => $cerr->getSeverityColor(),
                     'project' => $projName,
                     'errDesc' => $cerr->desc,
                     'summary' => $summary,
                  );
               }
            }
         }
      } catch (Exception $e) {
         self::$logger->error('IssueConsistencyCheck: '.$e->getMessage());
      }
   
      $this->execData= array(
         "nbErrors" => count($consistencyErrors),
         "errors" => $consistencyErrors,
      );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables= array(
         "issueConsistencyCheck_nbErrors" => $this->execData['nbErrors'],
         "issueConsistencyCheck_errors" => $this->execData['errors'],
      );

      if (false == $isAjaxCall) {
         $smartyVariables['timePerStatusIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['timePerStatusIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
IssueConsistencyCheck::staticInit();
