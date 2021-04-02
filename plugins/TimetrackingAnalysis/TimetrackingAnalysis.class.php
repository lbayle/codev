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
class TimetrackingAnalysis extends IndicatorPluginAbstract {

   const OPTION_IS_ONLY_TEAM_MEMBERS = 'isOnlyActiveTeamMembers';
   const OPTION_INTERVAL = 'interval'; // weekly, monthly
   #const OPTION_FILTER_ISSUE_TYPE = 'issueType';    // noFilter, bugsOnly, tasksOnly
   #const OPTION_FILTER_ISSUE_EXT_ID = 'issueExtId'; // noFilter, withExtId, withoutExtId

   private static $logger;
   private static $domains;
   private static $categories;

   private $teamid;
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $managedUserId; // DOMAIN_USER only

   // config options from Dashboard
   private $interval;
   private $isOnlyActiveTeamMembers;

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
         //self::DOMAIN_USER, // do we realy want to rate the staff ? this is not the goal of CodevTT...
         self::DOMAIN_PROJECT,
         //self::DOMAIN_COMMAND,
         //self::DOMAIN_COMMAND_SET,
         //self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_RISK
      );
   }

   public static function getName() {
      return T_('Timetracking analysis');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Display the delay between the timetrack date and it\'s creation date');
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
         'js_min/datepicker.min.js',
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
         'js_min/chart.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         // datepicker default val
         $this->startTimestamp = strtotime("first day of this year");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         // datepicker default val
         $this->endTimestamp = strtotime("last day of this month");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL)) {
         $this->interval = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_INTERVAL);
      } else {
         $this->interval = 'monthly';
      }

      $this->isOnlyActiveTeamMembers= true;
   }

   /**
    * settings are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_ONLY_TEAM_MEMBERS, $pluginSettings)) {
            $this->isOnlyActiveTeamMembers = $pluginSettings[self::OPTION_IS_ONLY_TEAM_MEMBERS];
         }
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {
            $this->interval = $pluginSettings[self::OPTION_INTERVAL];
         }
      }
   }


   /**
    *
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $interval [weekly,monthly]
    */
   private function createTimestampRangeList($interval = 'monthly') {
      $timestampRangeList = array();

      $startT = $this->startTimestamp;

      switch ($interval) {
         case 'weekly':
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("next sunday", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("next monday", $startT);
            }
            break;
         default:
            // monthly
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("last day of this month", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("first day of next month", $startT);
            }
      }
      return $timestampRangeList;
   }

   private function median(array $arr)
   {
       if (0 === count($arr)) {
           return -1; // null;
       }

       // sort the data
       $count = count($arr);
       asort($arr);

       // get the mid-point keys (1 or 2 of them)
       $mid  = floor(($count - 1) / 2);
       $keys = array_slice(array_keys($arr), $mid, (1 === $count % 2 ? 1 : 2));
       $sum  = 0;
       foreach ($keys as $key) {
           $sum += $arr[$key];
       }
       return $sum / count($keys);
   }

   private function getMissingDays($startTimestamp, $endTimestamp) {

      if ($startTimestamp >= mktime(0, 0, 0, date('m'), date('d'), date('Y'))) {
         return 0;
      }
      if ($endTimestamp >= mktime(23, 59, 59, date('m'), date('d'), date('Y'))) {
         $endTimestamp = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
      }

      // === get timetracks for each Issue
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      if (NULL !== $this->managedUserId) {
         $useridList = array($this->managedUserId);
      } else {
         $useridList = array_keys($team->getActiveMembers($startTimestamp, $endTimestamp));
      }

      $nbDays = 0;
      foreach($useridList as $userid) {
         $user = UserCache::getInstance()->getUser($userid);
         $incompleteDays = $user->checkIncompleteDays($startTimestamp, $endTimestamp);
         $missingDays = $user->checkMissingDays($this->teamid, $startTimestamp, $endTimestamp);
         $nbDays += array_sum($incompleteDays) + count($missingDays);
      }
      return $nbDays;
   }

   /**
    *
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {
      $timestampRangeList = $this->createTimestampRangeList($this->interval);

      // - regular Projects
      // - sideTasksProjects
      // - Do NOT include externalTasks
      $team = TeamCache::getInstance()->getTeam($this->teamid);

      // === get timetracks for each Issue
      if (NULL !== $this->managedUserId) {
         $useridList = array($this->managedUserId);
      } else {
         if ($this->isOnlyActiveTeamMembers) {
            $useridList = array_keys($team->getActiveMembers($this->startTimestamp, $this->endTimestamp));
         } else {
            // include also timetracks of users not in the team (relevant on ExternalTasksProjects)
            $useridList = NULL;
         }
      }

      $periodData = array();
      foreach ($timestampRangeList as $label => $ttRange) {
         $startT = $ttRange['start'];
         $endT   = $ttRange['end'];

         $timetracks = $this->inputIssueSel->getTimetracks($useridList, $startT, $endT);
         $nbTimetracks = 0;
         $elapsedDays = 0;
         $delayData = array(); // cound number of timetracks for each delay

         /* @var $track TimeTrack */
         foreach ($timetracks as $track) {

            // - Do NOT include externalTasks
            $project = ProjectCache::getInstance()->getProject($track->getProjectId());
            if ($project->isExternalTasksProject()) { continue; }

            // compute timetracking delay
            $trackTimestamp = $track->getDate();
            $trackCommitTimestamp = $track->getCommitDate();
            $midnightCommitTimestamp = mktime(0, 0, 0, date('m', $trackCommitTimestamp), date('d', $trackCommitTimestamp), date('Y', $trackCommitTimestamp));
            $delay = $midnightCommitTimestamp - $trackTimestamp;
            $delayInDays = round($delay / 86400, 0); // (60 * 60 * 24)

            if ($delayInDays < 0) {
               $delayInDays = 0; // date entered in advance is no bonus !
            }

//self::$logger->error("date=".date('Y-m-d H:i:s', $trackTimestamp)." commitDate=".date('Y-m-d H:i:s', $trackCommitTimestamp));
//self::$logger->error("delay = $delay, inDays=".$delayInDays);

            $delayData[$delayInDays] += 1;
            $nbTimetracks += 1;
            $elapsedDays += $track->getDuration();
         }

         // compute average
         $delays = array_keys($delayData);
         if(0 == $nbTimetracks) {
            $average = -1;
         } else {
            $average = array_sum($delays) / $nbTimetracks;
         }
         // compute median
         $median = $this->median($delays);

         // mode is where there is the most elements
         $mode = -1;
         foreach($delayData as $delay => $nbtracks) {
            if (-1 == $mode) {
               $mode = $delay;
            } else {
               if ($nbtracks > $delayData[$mode]) {
                  $mode = $delay;
               }
            }
         }

         $missingDays = $this->getMissingDays($startT, $endT);
         $periodData[$label] = array(
            'endDate' => date('Y-m-d', $endT),
            'median' => $median,
            'mode' => $mode,
            'mode_nbTimetracks' => $delayData[$mode],
            'mode_pcent' => (0 == $nbTimetracks) ? 0 : round($delayData[$mode]/$nbTimetracks*100,2),
            'average' => round($average, 2),
            'nbTimetracks' => $nbTimetracks,
            'elapsedDays' => round($elapsedDays, 2),
            'missingDays' => round($missingDays,2),
            'missingDays_pcent' => (0 == ($elapsedDays + $missingDays)) ? 0 : round($missingDays/($elapsedDays + $missingDays)*100,2),
         );
      }

      $this->execData = array (
         #'startTimestamp' => $this->startTimestamp,
         'startDate' => date('Y-m-d', $this->startTimestamp),
         'endDate' => date('Y-m-d', $this->endTimestamp),
         'periodData' => $periodData,
      );
   }


   public function getSmartyVariables($isAjaxCall = false) {


      // format data for jqPlot
      // Xaxes as string "['YYYY-MM-DD', 'YYYY-MM-DD', 'YYYY-MM-DD', 'YYYY-MM-DD']"
      // data as string "[[5, 6, 7, 8],[32, 41, 44, 14],[37, 47, 51, 22]]"
      //$values1 = array();
      //$values2 = array();
      $values3 = array();
      foreach ($this->execData['periodData'] as $periodData) {
         //$values1[] = $periodData['median'];
         //$values2[] = $periodData['mode'];
         $values3[] = $periodData['average'];
      }
      $values = array($values3);

      $smartyPrefix = 'TimetrackingAnalysis_';
      $smartyVariables = array(
         $smartyPrefix.'startDate' => $this->execData['startDate'],
         $smartyPrefix.'endDate'   => $this->execData['endDate'],
         $smartyPrefix.'tableData' => $this->execData['periodData'],
         $smartyPrefix.'jqplotData' => json_encode($values),
         $smartyPrefix.'jqplotXaxes' => json_encode(array_keys($this->execData['periodData'])),

         // add pluginSettings (if needed by smarty)
         $smartyPrefix.self::OPTION_IS_ONLY_TEAM_MEMBERS => $this->isOnlyActiveTeamMembers,
         $smartyPrefix.self::OPTION_INTERVAL => $this->interval,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['TimetrackingAnalysis_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['TimetrackingAnalysis_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
TimetrackingAnalysis::staticInit();

