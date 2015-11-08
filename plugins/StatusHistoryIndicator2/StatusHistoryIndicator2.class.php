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
class StatusHistoryIndicator2 extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // defaultValue, oneWeek, twoWeeks, oneMonth
   const OPTION_FILTER_ISSUE_TYPE = 'issueType';    // noFilter, bugsOnly, tasksOnly
   const OPTION_FILTER_ISSUE_EXT_ID = 'issueExtId'; // noFilter, withExtId, withoutExtId

   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $interval;
   
   // internal
   private $statusData;
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
      return 'Issue Status History';
   }
   public static function getDesc() {
      return "Display Issue Status history";
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
         'lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
         'js_min/chart.min.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL)) {
         $this->interval = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL);
      } else {
         $this->interval = 30;
      }
      //self::$logger->debug('dataProvider '.PluginDataProviderInterface::PARAM_INTERVAL.'= '.$this->interval);
   }

   /**
    * settings are saved by the Dashboard
    * 
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {

            switch ($pluginSettings[self::OPTION_INTERVAL]) {
               case 'defaultValue':
                  break;
               case 'oneWeek':
                  $this->interval = 7;
                  break;
               case 'twoWeeks':
                  $this->interval = 14;
                  break;
               case 'oneMonth':
                  $this->interval = 30;
                  break;
               default:
                  self::$logger->error('option '.self::OPTION_INTERVAL.'= '.$pluginSettings[self::OPTION_INTERVAL]." (unknown value)");
            }
         }
         if (array_key_exists(self::OPTION_FILTER_ISSUE_TYPE, $pluginSettings)) {

            switch ($pluginSettings[self::OPTION_FILTER_ISSUE_TYPE]) {
               case 'noFilter':
                  break;
               case 'bugsOnly';
                  // Filter only BUGS
                  $bugFilter = new IssueCodevTypeFilter('bugFilter');
                  $bugFilter->addFilterCriteria(IssueCodevTypeFilter::tag_Bug);
                  $outputList = $bugFilter->execute($this->inputIssueSel);

                  if (empty($outputList)) {
                     #echo "TYPE Bug not found !<br>";
                     $this->inputIssueSel = new IssueSelection();
                  } else {
                     $this->inputIssueSel = $outputList[IssueCodevTypeFilter::tag_Bug];
                  }
                  break;
               case 'tasksOnly':
                  // Filter only BUGS
                  $bugFilter = new IssueCodevTypeFilter('bugFilter');
                  $bugFilter->addFilterCriteria(IssueCodevTypeFilter::tag_Task);
                  $outputList = $bugFilter->execute($this->inputIssueSel);

                  if (empty($outputList)) {
                     #echo "TYPE Bug not found !<br>";
                     $this->inputIssueSel = new IssueSelection();
                  } else {
                     $this->inputIssueSel = $outputList[IssueCodevTypeFilter::tag_Task];
                  }
                  break;
               default:
                  self::$logger->error('option '.self::OPTION_FILTER_ISSUE_TYPE.'= '.$pluginSettings[self::OPTION_FILTER_ISSUE_TYPE]." (unknown value)");
            }
         }
         if (array_key_exists(self::OPTION_FILTER_ISSUE_EXT_ID, $pluginSettings)) {

            switch ($pluginSettings[self::OPTION_FILTER_ISSUE_EXT_ID]) {
               case 'noFilter':
                  break;
               case 'withExtId';
                  $extIdFilter = new IssueExtIdFilter('extIdFilter');
                  $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_with_extRef);
                  $outputList2 = $extIdFilter->execute($this->inputIssueSel);

                  if (empty($outputList2)) {
                     #echo "noExtRef not found !<br>";
                     $this->inputIssueSel = new IssueSelection();
                  } else {
                     $this->inputIssueSel = $outputList2[IssueExtIdFilter::tag_with_extRef];
                  }
                  break;
               case 'withoutExtId':
                  $extIdFilter = new IssueExtIdFilter('extIdFilter');
                  $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_no_extRef);
                  $outputList2 = $extIdFilter->execute($this->inputIssueSel);

                  if (empty($outputList2)) {
                     #echo "noExtRef not found !<br>";
                     $this->inputIssueSel = new IssueSelection();
                  } else {
                     $this->inputIssueSel = $outputList2[IssueExtIdFilter::tag_no_extRef];
                  }
                  break;
               default:
                  self::$logger->error('option '.self::OPTION_FILTER_ISSUE_EXT_ID.'= '.$pluginSettings[self::OPTION_FILTER_ISSUE_EXT_ID]." (unknown value)");
            }
         }
      }
   }
   
   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {

      $startTimestamp = mktime(0, 0, 0, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      //$startTimestamp = mktime(23, 59, 59, date('m', $this->startTimestamp), date('d', $this->startTimestamp), date('Y', $this->startTimestamp));
      $endTimestamp   = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d',$this->endTimestamp), date('Y', $this->endTimestamp));

      //echo "StatusHistoryIndicator start ".date('Y-m-d H:i:s', $startTimestamp)." end ".date('Y-m-d H:i:s', $endTimestamp)." interval ".$this->period."<br>";
      $timestampList  = Tools::createTimestampList($startTimestamp, $endTimestamp, $this->interval);

      $this->statusData = $this->getStatusData($this->inputIssueSel, $timestampList);
   }

   /**
    * @param IssueSelection $inputIssueSel
    * @param int[] $timestampList
    */
   private function getStatusData(IssueSelection $inputIssueSel, array $timestampList) {
      $this->statusData = array();

      $historyStatusNew = array();  // timestamp => nbIssues
      $historyStatusFeedback = array();  // timestamp => nbIssues
      $historyStatusOngoing = array();  // timestamp => nbIssues
      $historyStatusResolved = array();  // timestamp => nbIssues
      $historyStatusTotal = array();  // timestamp => nbIssues


      // get a snapshot of the Status at each timestamp
      $issues = $inputIssueSel->getIssueList();
      foreach ($timestampList as $timestamp) {

         $midnight_timestamp = mktime(0, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));

        // all timestamps must be defined, even if empty
         $historyStatusNew["$midnight_timestamp"] = 0;
         $historyStatusFeedback["$midnight_timestamp"] = 0;
         $historyStatusOngoing["$midnight_timestamp"] = 0;
         $historyStatusResolved["$midnight_timestamp"] = 0;
         $historyStatusTotal["$midnight_timestamp"] = 0;

         foreach ($issues as $issue) {
            $issueStatus = $issue->getStatus($timestamp);

            // if issue exists at this date
            if ( (-1) != $issueStatus) {

               if ($issueStatus >= $issue->getBugResolvedStatusThreshold()) {
                  $historyStatusResolved["$midnight_timestamp"] += 1;
               } else if ($issueStatus == Constants::$status_feedback) {
                  $historyStatusFeedback["$midnight_timestamp"] += 1;
               } else if ($issueStatus == Constants::$status_new) {
                  $historyStatusNew["$midnight_timestamp"] += 1;
               } else {
                  $historyStatusOngoing["$midnight_timestamp"] += 1;
               }
               $historyStatusTotal["$midnight_timestamp"] += 1;
               #echo date('Y-m-d', $timestamp)." issue ".$issue->getId()." status ".$issueStatus."<br>";
            }
         }
         if (self::$logger->isDebugEnabled()) {
            self::$logger->debug('Y-m-d', $midnight_timestamp).
                    ' new '.$historyStatusNew["$midnight_timestamp"].
                    'feedback '.$historyStatusFeedback["$midnight_timestamp"].
                    'ongoing '.$historyStatusOngoing["$midnight_timestamp"].
                    'resolved '.$historyStatusResolved["$midnight_timestamp"].
                    'total '.$historyStatusTotal["$midnight_timestamp"];
         }
      }

      $statusData = array(
          'new'      => $historyStatusNew,
          'feedback' => $historyStatusFeedback,
          'ongoing'  => $historyStatusOngoing,
          'resolved' => $historyStatusResolved,
          'total'    => $historyStatusTotal);

      return $statusData;
   }





   public function getSmartyVariables($isAjaxCall = false) {

      $historyStatusNew      = $this->statusData['new'];  // timestamp => nbIssues
      $historyStatusFeedback = $this->statusData['feedback'];
      $historyStatusOngoing  = $this->statusData['ongoing'];
      $historyStatusResolved = $this->statusData['resolved'];
      $historyStatusTotal    = $this->statusData['total'];


      $xaxis = array();
      foreach(array_keys($historyStatusNew) as $timestamp) {
         $xaxis[] = '"'.date(T_('Y-m-d'), $timestamp).'"';
      }
      $json_xaxis = Tools::array2plot($xaxis);

      $jsonNew      = Tools::array2plot(array_values($historyStatusNew));
      $jsonOngoing  = Tools::array2plot(array_values($historyStatusOngoing));
      $jsonFeedback = Tools::array2plot(array_values($historyStatusFeedback));
      $jsonResolved = Tools::array2plot(array_values($historyStatusResolved));
      $jsonTotal    = Tools::array2plot(array_values($historyStatusTotal));


      $graphData = "[$jsonNew,$jsonOngoing,$jsonFeedback,$jsonResolved]";

      $graphDataColors = '["#fcbdbd", "#c2dfff", "#e3b7eb", "#d2f5b0"]';

      $labels1 = '["new", "ongoing", "feedback", "resolved"]';

      $smartyVariables = array(
         'statusHistoryIndicator_jqplotData1' => $graphData,
         'statusHistoryIndicator_jqplotData2' => $jsonTotal,
         'statusHistoryIndicator_jqplotSeriesColors' => $graphDataColors,
         'statusHistoryIndicator_jqplotLegendLabels' => $labels1,
         'statusHistoryIndicator_jqplotXaxis' => $json_xaxis,
      );

/*      
      if (false == $isAjaxCall) {
         $smartyVariables['statusHistoryIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['statusHistoryIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
*/      
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
StatusHistoryIndicator2::staticInit();

