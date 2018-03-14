
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
 * Description of WBSExport
 *
 * @author lob
 */
class WBSExport extends IndicatorPluginAbstract {

   const OPTION_IS_DATE_DISPLAYED = 'isDateDisplayed';

   private static $logger;
   private static $domains;
   private static $categories;
   private $wbsElement;
   // internal
   protected $execData;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array(
          self::DOMAIN_COMMAND,
      );
      self::$categories = array(
          self::CATEGORY_ROADMAP,
      );
   }

   public static function getName() {
      return T_('WBS Export');
   }

   public static function getDesc($isShortDesc = true) {
      return T_('...');
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
          'js_min/datatable.min.js',
      );
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProver) {

      $commandId = $pluginDataProver->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID);
      if (null !== $commandId) {
         $command = CommandCache::getInstance()->getCommand($commandId);
         $wbsId = $command->getWbsid();
         $this->wbsElement = new WBSElement($wbsId);
      } else {
         throw new Exception("Missing parameter: " . $commandId);
      }

      if (self::$logger->isDebugEnabled()) {
//         self::$logger->debug("checkParams() startTimestamp=" . $this->startTimestamp);
      }
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

//      if (NULL != $pluginSettings) {
//         // override default with user preferences
//         if (array_key_exists(self::OPTION_IS_DATE_DISPLAYED, $pluginSettings)) {
//            $this->isDateDisplayed = $pluginSettings[self::OPTION_IS_DATE_DISPLAYED];
//         }
//      }
   }

   /**
    *
    */
   public function execute() {

      $this->wbsElement->getIssues();
      $this->getStructure();
      $rows = $this->buildRows();

      $nbWBSColumnList = array_map(function ($row) {
         return (count($row['path']));
      }, $rows);
      $nbWBSColumnMax = max($nbWBSColumnList);

      $columnHeader = [
          'ID',
          'Target',
          'ExtRef',
          'User',
          'Type',
          'Status',
          'Progress',
          'MgrEffortEstim',
          'EffortEstim',
          'Elapsed',
          'Backlog',
          'Drift Mgr',
          'Summary'
      ];
      for ($i = $nbWBSColumnMax - 1; $i > 0; $i--) {
         array_unshift($columnHeader, 'Col ' . $i);
      }

      array_unshift($columnHeader, 'Project');

      $data = [];
      foreach ($rows as $key => $row) {
         $path = $row['path'];
         $issue = $row['issue'];
         $id = $issue->getId();
         $projectName = $issue->getProjectName();

         for ($i = 0; $i < $nbWBSColumnMax; $i++) {
            if (0 === $i) {
               $data[$key][] = $projectName;
               continue;
            }
            if ($path[$i]) {
               $data[$key][] = $path[$i];
            } else {
               $data[$key][] = '';
            }
         }

         $data[$key][] = $id;
         $data[$key][] = $issue->getTargetVersion();
         $data[$key][] = $issue->getTcId();
         $data[$key][] = $issue->getHandlerId();
         $data[$key][] = $issue->getType();
         $data[$key][] = $issue->getStatus();
         $data[$key][] = $issue->getProgress();
         $data[$key][] = $issue->getMgrEffortEstim();
         $data[$key][] = $issue->getEffortEstim();
         $data[$key][] = $issue->getElapsed();
         $data[$key][] = $issue->getBacklog();
         $data[$key][] = $issue->getDriftMgr();
         $data[$key][] = $issue->getSummary();
      }

      $tableHeaders = $columnHeader;
      $tableRows = $data;

      $this->execData = [
          'tableHeaders' => $tableHeaders,
          'tableRows' => $tableRows
      ];
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {
      $smartyVariables = [
          'wbsExportTableHeaders' => $this->execData['tableHeaders'],
          'wbsExportTableRows' => $this->execData['tableRows'],
      ];

      if (false === $isAjaxCall) {
         $smartyVariables['wbsExportAjaxFile'] = self::getSmartySubFilename();
         $smartyVariables['wbsExportAjaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

   public function getStructure($wbsElement = null) {
      $wbsElement = is_null($wbsElement) ? $this->wbsElement : $wbsElement;
      $subFolders = $wbsElement->getChildFolders();
      if ($wbsElement->hasSubFolders()) {
         foreach ($subFolders as $subFolder) {
            $subFolder->getIssues();
            $this->getStructure($subFolder);
         }
      }
   }

   public function buildRows($wbsElement = null) {
      $wbsElement = is_null($wbsElement) ? $this->wbsElement : $wbsElement;
      static $rows = [];
      static $path = [];

      array_push($path, $wbsElement->getTitle());
      foreach ($wbsElement->getIssueList() as $issue) {
         array_push($rows, [
             'path' => $path,
             'issue' => $issue
         ]);
      }
      if ($wbsElement->hasSubFolders()) {
         foreach ($wbsElement->getSubFolders() as $subFolder) {

            $this->buildRows($subFolder);
            array_pop($path);
         }
      }
      return $rows;
   }

}

// Initialize static variables
WBSExport::staticInit();
