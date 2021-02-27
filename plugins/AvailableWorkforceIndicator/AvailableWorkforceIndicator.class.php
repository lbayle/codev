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
 * Description of AvailableWorkforceIndicator
 *
 * @author lob
 */
class AvailableWorkforceIndicator extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval'; // weekly, monthly
   const OPTION_USER_SETTINGS = 'userSettings'; // weekly, monthly

   const GRAPH_NB_OCCUR_WEEKLY = 36;
   const GRAPH_NB_OCCUR_MONTHLY = 12;


   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $startTimestamp; // datePicker
   private $endTimestamp;   // datePicker
   private $teamid;

   // config options from Dashboard
   private $interval;
   private $userSettings;

   // internal
   protected $execData;
   protected $graphStartTimestamp;
   protected $graphNbOccur;



   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM,
         #self::DOMAIN_USER,
         #self::DOMAIN_COMMAND, // DEBUG
      );
      self::$categories = array (
         self::CATEGORY_PLANNING
      );
   }

   public static function getName() {
      return T_('Available Workforce'); // FR: Plan de charge
   }
   public static function getDesc($isShortDesc = true) {
      $desc = T_('Man-days available in period, except leaves and external tasks');
      if (!$isShortDesc) {
         $desc .= '<br><br>';
      }
      return $desc;   }
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
   public static function getSmartySubFilename2() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax2.html";
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         // datepicker default val
         $this->startTimestamp = strtotime("first day of this month");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         // datepicker default val
         $this->endTimestamp = strtotime("last day of this month");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }

      // set internal values

      // two month ago
      $this->graphStartTimestamp = strtotime("first day of last month");
      $this->graphStartTimestamp = strtotime("first day of last month", $this->graphStartTimestamp);


      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->interval = 'monthly';
      $this->graphNbOccur = self::GRAPH_NB_OCCUR_MONTHLY;
      $this->setDefaultUserSettings();
   }

   /**
    *
    */
   private function setDefaultUserSettings() {
      $this->userSettings = array();

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $startT = $this->graphStartTimestamp;
      for ($i=1; $i<=$this->graphNbOccur; $i++) {
         $endT = strtotime("last day of this month", $startT);
         $startT = strtotime("first day of next month", $startT);
      }
      $endT = mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT));

      $users = $team->getActiveMembers($this->graphStartTimestamp,$endT,TRUE); // TRUE=realNames

      foreach ($users as $uid => $uname) {
         $this->userSettings[$uid] = array(
             'name' => $uname,
             'availability' => 100, // 100%
             'prodCoef' => 1,
             'enabled' => true,
         );
         #self::$logger->error("team member: $uname ");
      }

   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      //self::$logger->error("pluginSettings".var_export($pluginSettings, true));

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {
            $this->interval = $pluginSettings[self::OPTION_INTERVAL];
         }
         if (array_key_exists(self::OPTION_USER_SETTINGS, $pluginSettings)) {
            // override each user values, do not replace the complete block
            $newUserSettings = $pluginSettings[self::OPTION_USER_SETTINGS];
            foreach(array_keys($this->userSettings) as $uid) {
               if (array_key_exists($uid, $newUserSettings)) {
                  $this->userSettings[$uid]['availability'] = $newUserSettings[$uid]['availability'];
                  $this->userSettings[$uid]['prodCoef'] = $newUserSettings[$uid]['prodCoef'];
                  $this->userSettings[$uid]['enabled'] = $newUserSettings[$uid]['enabled'] == 0 ? false : true;
               }
            }
            //self::$logger->error(var_export($this->userSettings, true));
         }
      }
   }

   /**
    * get the total availWorkforce for the team in the given range
    */
   public function getTeamAvailWorkforce($startTimestamp, $endTimestamp) {
      $startT = mktime(0, 0, 0, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));
      $endT = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));

      $teamAvailWkl = 0;
      $rangeUserDetails = array();
      foreach ($this->userSettings as $user_id => $userSettings) {
         if ($userSettings['enabled']) {
            $user = UserCache::getInstance()->getUser($user_id);
            $userRawWorkforce = $user->getAvailableWorkforce($startT, $endT, $this->teamid);
            $userWorkforce = $userRawWorkforce * $userSettings['availability'] / 100 * $userSettings['prodCoef'];
            $teamAvailWkl += $userWorkforce;
            $rangeUserDetails[$user_id] = array(
               'userName' => $user->getRealname(),
               'rangeWorkforce' => round($userWorkforce, 2),
            );
         }
      }
      $values = array(
         'rangeTeamWorkforce' => round($teamAvailWkl, 2),
         'rangeUserDetails' => $rangeUserDetails
      );
      return $values;
   }


   /**
    *
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $interval [weekly,monthly]
    */
   private function createTimestampRangeList($interval = 'monthly') {
      $timestampRangeList = array();

      // start 2 month ago
      $startT = $this->graphStartTimestamp;

      switch ($interval) {
         case 'weekly':
            $this->graphNbOccur = self::GRAPH_NB_OCCUR_WEEKLY;
            for ($i=1; $i<=$this->graphNbOccur; $i++) {
               $endT = strtotime("next sunday", $startT);
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("next monday", $startT);
            }
            break;
         default:
            // monthly
            $this->graphNbOccur = self::GRAPH_NB_OCCUR_MONTHLY;
            for ($i=1; $i<=$this->graphNbOccur; $i++) {
               $endT = strtotime("last day of this month", $startT);
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("first day of next month", $startT);
            }
      }
      return $timestampRangeList;
   }


  /**
    *
    */
   public function execute() {

      // compute data
      $availWorkforceList = array();
      $detailedAvailWorkforceList = array();
      $timestampRangeList = $this->createTimestampRangeList($this->interval);

      foreach ($this->userSettings as $user_id => $userSettings) {
         if ($userSettings['enabled']) {
            $user = UserCache::getInstance()->getUser($user_id);
            $userRangeDetail = array('userName' => $userSettings['name']);
            foreach ($timestampRangeList as $label => $ttRange) {
               $startT = $ttRange['start'];
               $endT   = $ttRange['end'];

               $userRawWorkforce = $user->getAvailableWorkforce($startT, $endT, $this->teamid);
               $userWorkforce = $userRawWorkforce * $userSettings['availability'] /100 * $userSettings['prodCoef'];
               //self::$logger->error($startT." user ".$user_id." ".$userRawWorkforce." x ".$userSettings['availability']);
               $userRangeDetail[$label] = (0 == $userWorkforce) ? '' : round($userWorkforce, 2);
               $availWorkforceList[$label] += $userWorkforce;
            }
            $detailedAvailWorkforceList[$user_id] = $userRangeDetail;
         }
      }

      $tableFooter = array(T_('Total'));
      foreach ($availWorkforceList as $v) {
         $tableFooter[] = round($v, 2);
      }

      $tableHeader = array();
      $tableHeader[] = ''; //T_('User \ Period');
      foreach ($timestampRangeList as $label => $ttRange) {
         $startT = $ttRange['start'];
         if ('weekly' == $this->interval) {
            $dateLabel = 'W'.date('W ++o', $startT);
         } else {
            $dateLabel = date("M", $startT)."<br>".date("o", $startT);
         }
         $tableHeader[] = $dateLabel;
      }

      list($startLabel, $endLabel) = Tools::getStartEndKeys($timestampRangeList);
      $graphMinTimestamp = $timestampRangeList[$startLabel]['start'];
      $graphMaxTimestamp = $timestampRangeList[$endLabel]['end'];

      foreach($availWorkforceList as $label => $wf) {
         $availWorkforceList[$label] = round($wf, 1);
      }

      $this->execData = array (
          'graph_availWorkforceList' => $availWorkforceList,
          'graph_minTimestamp' => $graphMinTimestamp,
          'graph_maxTimestamp' => $graphMaxTimestamp,
          'table_header' => $tableHeader,
          'table_footer' => $tableFooter,
          'table_availWorkforceList' => $detailedAvailWorkforceList,
          );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $rangeData = $this->getTeamAvailWorkforce($this->startTimestamp, $this->endTimestamp);
      $smartyPrefix = 'availableWorkforceIndicator_';
      $smartyVariables = array(
         $smartyPrefix.'jqplotXaxes' => json_encode(array_keys($this->execData['graph_availWorkforceList'])),
         $smartyPrefix.'jqplotData' => json_encode(array(array_values($this->execData['graph_availWorkforceList']))),

         $smartyPrefix.'tableHeader' => $this->execData['table_header'],
         $smartyPrefix.'tableData' => $this->execData['table_availWorkforceList'],
         $smartyPrefix.'tableFooter' => $this->execData['table_footer'],
         $smartyPrefix.'startDatepicker' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         $smartyPrefix.'endDatepicker' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         $smartyPrefix.'rangeValue' => $rangeData['rangeTeamWorkforce'],
         $smartyPrefix.'rangeUserDetails' => $rangeData['rangeUserDetails'],
         $smartyPrefix.'userSettings' => $this->userSettings,

         // add pluginSettings (if needed by smarty)
         $smartyPrefix.self::OPTION_INTERVAL => $this->interval,
      );

      if (false == $isAjaxCall) {
         $smartyVariables[$smartyPrefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$smartyPrefix.'ajaxFile2'] = self::getSmartySubFilename2();
         $smartyVariables[$smartyPrefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
AvailableWorkforceIndicator::staticInit();
