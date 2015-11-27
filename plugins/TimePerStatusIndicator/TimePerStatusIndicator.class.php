
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
class TimePerStatusIndicator extends IndicatorPluginAbstract {


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
         self::DOMAIN_TASK,
//         self::DOMAIN_TEAM,
//         self::DOMAIN_PROJECT,
//         self::DOMAIN_COMMAND,
//         self::DOMAIN_COMMAND_SET,
//         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ROADMAP
      );
   }

   public static function getName() {
      return T_('Time per status');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Time allocation by status');
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

      $statusNamesSmarty = array();
      $durations = array();

      $issueList = $this->inputIssueSel->getIssueList();
      /* @var $issue Issue */
      foreach ($issueList as $issue) {
         $issue->computeDurationsPerStatus();

         foreach($issue->getStatusList() as $status_id => $status) {
            if (array_key_exists($status_id, Constants::$statusNames)) {
               if (!array_key_exists($status_id, $statusNamesSmarty)) {
                  $statusNamesSmarty[$status_id] = Constants::$statusNames[$status_id];
               }
            } else {
               $statusNamesSmarty[$status_id] = 'status_'.$status_id;
               self::$logger->error("Status ".$status_id." not found in statusNames constants");
            }
         }

         try {
            //if (!$issue->isSideTaskNonProductionIssue()) {
               foreach($issue->getStatusList() as $status) {
                  $durations[$status->statusId] += $status->duration;
               }
            //}
         } catch (Exception $e) {
            self::$logger->error("issue ".$issue->getId().": ".$e->getMessage());
         }
      }

      $this->execData= array(
         "statusNames" => $statusNamesSmarty,
         "durations" => $durations
      );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $displayedDurations = array();
      foreach ($this->execData['durations'] as $statusId => $dur) {
         $displayedDurations[$statusId] = Tools::getDurationLiteral($dur);
      }

      $smartyVariables= array(
         "timePerStatusIndicator_statusNames" => $this->execData['statusNames'],
         "timePerStatusIndicator_durations" => $displayedDurations,
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
TimePerStatusIndicator::staticInit();
