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
         'lib/jquery.jqplot/plugins/jqplot.dateAxisRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.pointLabels.min.js',
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
      foreach ($this->userSettings as $user_id => $userSettings) {
         if ($userSettings['enabled']) {
            $user = UserCache::getInstance()->getUser($user_id);
            $userRawWorkforce = $user->getAvailableWorkforce($startT, $endT, $this->teamid);
            $userWorkforce = $userRawWorkforce * $userSettings['availability'] / 100 * $userSettings['prodCoef'];
            $teamAvailWkl += $userWorkforce;
         }
      }
      return $teamAvailWkl;
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
               $userRangeDetail[$label] = (0 == $userWorkforce) ? '' : $userWorkforce;
               $availWorkforceList[$label] += $userWorkforce;
            }               
            $detailedAvailWorkforceList[$user_id] = $userRangeDetail;
         }
      }
      
      $tableFooter = array_values($availWorkforceList);
      array_unshift($tableFooter, T_('Total'));
      
      $tableHeader = array();
      $tableHeader[] = ''; //T_('User \ Period');
      foreach ($timestampRangeList as $label => $ttRange) {
         $startT = $ttRange['start'];
         if ('weekly' == $this->interval) {
            $dateLabel = 'W'.date('W o', $startT);
         } else {
            $dateLabel = date('m/o', $startT);
         }
         $tableHeader[] = $dateLabel;
      }               
      
      list($startLabel, $endLabel) = Tools::getStartEndKeys($timestampRangeList);
      $graphMinTimestamp = $timestampRangeList[$startLabel]['start'];
      $graphMaxTimestamp = $timestampRangeList[$endLabel]['end'];
      
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

      $smartyVariables = array(
         'availableWorkforceIndicator_jqplotData' => Tools::array2plot(array($this->execData['graph_availWorkforceList'])),
         'availableWorkforceIndicator_jqplotMinDate' => date('Y-m-d', $this->execData['graph_minTimestamp']),
         'availableWorkforceIndicator_jqplotMaxDate' => date('Y-m-d', $this->execData['graph_maxTimestamp']),
         'availableWorkforceIndicator_tableHeader' => $this->execData['table_header'],
         'availableWorkforceIndicator_tableData' => $this->execData['table_availWorkforceList'],
         'availableWorkforceIndicator_tableFooter' => $this->execData['table_footer'],
         'availableWorkforceIndicator_startDatepicker' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'availableWorkforceIndicator_endDatepicker' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         'availableWorkforceIndicator_rangeValue' => $this->getTeamAvailWorkforce($this->startTimestamp, $this->endTimestamp),
         'availableWorkforceIndicator_userSettings' => $this->userSettings,
          
         // add pluginSettings (if needed by smarty)
         'availableWorkforceIndicator_'.self::OPTION_INTERVAL => $this->interval,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['availableWorkforceIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['availableWorkforceIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
AvailableWorkforceIndicator::staticInit();
