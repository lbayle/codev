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
 * Description of FdjBacklogUOPerTaskIndicator
 *
 * For each Task, return the sum of the backlog UO of its assigned tasks.
 * 
 * @author lob
 */
class FdjBacklogUOPerTaskIndicator extends IndicatorPluginAbstract {

   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;

   // config options from Dashboard

   // internal
   private $issue;
   private $timetracks;
   private $chargeInitUO;
   private $customFieldName;
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
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return '== FDJ == Dérive des UO';
   }
   public static function getDesc($isShortDesc = true) {
      $desc = 'Retourne la dérive des UO pour la tache courante';
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
         'js_min/table2csv.min.js',
         'js_min/progress.min.js',
         'js_min/tooltip.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {
      
      $this->timetracks = array();
      $this->chargeInitUO = 0;
      $this->customFieldName = 'FDJ_ChargeInit_UO';
      
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }

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
      
      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      foreach($bugidList as $bugid){
         $this->issue = IssueCache::getInstance()->getIssue($bugid);
         $this->timetracks = array_merge($this->timetracks, array_keys($this->issue->getTimetracks()));
         
         $query = "SELECT value FROM `mantis_custom_field_string_table` ".
              "WHERE bug_id=$bugid ".
              "AND field_id = (SELECT id FROM `mantis_custom_field_table` where name = '$this->customFieldName')";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if(SqlWrapper::getInstance()->mysql_num_rows($result) != 0) {
            $this->chargeInitUO += SqlWrapper::getInstance()->sql_result($result, 0);
         }
      }

      $formated_trackids = implode(', ', $this->timetracks);
      
      $SumUO = 0;
      
      if($formated_trackids != "") {
         $query = "SELECT SUM(value) FROM `codev_uo_table` WHERE `timetrackid` IN ($formated_trackids);";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if(SqlWrapper::getInstance()->mysql_num_rows($result) != 0) {
            $SumUO = SqlWrapper::getInstance()->sql_result($result, 0);
         }
      }
      if($SumUO == NULL){
         $SumUO = 0;
      }
      
      $drift = $SumUO - $this->chargeInitUO;
      
      $driftColor = UniteOeuvre::getDriftColor($this->issue->getBugResolvedStatusThreshold(), $this->issue->getCurrentStatus(), $drift);

      $taskArray = array (
          'chargeInitUO' => round($this->chargeInitUO,2),
          'elapsed' => round($SumUO,2),
          'drift' => round($drift,2),
          'driftColor' => $driftColor,
      );

      $this->execData = array();
      $this->execData['taskArray'] = $taskArray;
      
      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = array(
         'fdjBacklogUOPerTaskIndicator_taskArray' => $this->execData['taskArray']
      );

      if (false == $isAjaxCall) {
         $smartyVariables['fdjBacklogUOPerTaskIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjBacklogUOPerTaskIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
FdjBacklogUOPerTaskIndicator::staticInit();
