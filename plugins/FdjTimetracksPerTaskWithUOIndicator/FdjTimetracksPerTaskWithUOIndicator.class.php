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
class FdjTimetracksPerTaskWithUOIndicator extends IndicatorPluginAbstract {

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
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY,
      );
   }

   public static function getName() {
      return '== FDJ == Imputations par tache';
   }
   public static function getDesc($isShortDesc = true) {
      $desc = 'Retourne les imputations de la tache courante';
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

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }

      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      if ( 1 != count($bugidList)) {
         throw new Exception('There should be only one issue in IssueSelection !');
      }
      $bugid = current($bugidList);
      $this->issue = IssueCache::getInstance()->getIssue($bugid);
      
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
      //$timetracksArray = array();
      
      $dateTimetracks = array();
      
      $timetracks = $this->issue->getTimetracks();
      $timetrackidList = array_keys($timetracks);
      $formated_trackids = implode(', ', $timetrackidList);
      if($formated_trackids != "") {

         $query2 = "SELECT timetrackid, value FROM `codev_uo_table` WHERE `timetrackid` IN ($formated_trackids);";
         $result2 = SqlWrapper::getInstance()->sql_query($query2);
         if(SqlWrapper::getInstance()->mysql_num_rows($result2) != 0) {
            while($row2 = SqlWrapper::getInstance()->sql_fetch_object($result2)) {
               $UOs[$row2->timetrackid] = $row2->value;
            }
         }
         
         
      }
     
      foreach ($timetracks as $trackid => $track) {
            $timetracksArray[$trackid] = array(
               'dateTimetrack' => Tools::formatDate("%Y-%m-%d", $track->getDate()),
               'CategorieProject' => $this->issue->getCategoryName(),
               'Note' => $track->getNote(),
               'UO' => round($UOs[$trackid], 2),
            );
      }

      $this->execData = array();
      $this->execData['timetracksArray'] = $timetracksArray;
      //$this->execData['totalArray'] = $totalArray; 
      
      return $this->execData;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = array(
         'fdjTimetracksPerTaskWithUOIndicator_timetracksArray' => $this->execData['timetracksArray'],
      );

      if (false == $isAjaxCall) {
         $smartyVariables['fdjTimetracksPerTaskWithUOIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['fdjTimetracksPerTaskWithUOIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
FdjTimetracksPerTaskWithUOIndicator::staticInit();
