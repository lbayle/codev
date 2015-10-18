
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
 * Description of HelloWorldIndicator
 *
 * @author lob
 */
class TimetrackDetailsIndicator extends IndicatorPluginAbstract {

   const OPTION_DISPLAYED_TEAM = 'displayedTeam';
   
   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   //private $inputIssueSel;
   private $startTimestamp;

   // config options from Dashboard
   private $displayedTeam;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_ADMIN,
      );
      self::$categories = array (
         self::CATEGORY_ADMIN
      );
   }

   public static function getName() {
      return 'Timetrack details';
   }
   public static function getDesc() {
      return 'Display additional info on timetracks';
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
         'js/datepicker.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      $weekDates = Tools::week_dates(date('W'),date('Y'));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = $weekDates[1];
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = $weekDates[5];
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->displayedTeam = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         $this->displayedTeam = 0;
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("checkParams() startTimestamp=".$this->startTimestamp);
      }
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_DISPLAYED_TEAM, $pluginSettings)) {
            $this->displayedTeam = $pluginSettings[self::OPTION_DISPLAYED_TEAM];
         }
      }
   }


  /**
    *
    */
   public function execute() {

      $my_endTimestamp = mktime(23, 59, 59, date('m', $this->endTimestamp), date('d',$this->endTimestamp), date('Y', $this->endTimestamp));

      // candidate teams
      $teamList = Team::getTeams(true);
      
      if (!array_key_exists($this->displayedTeam,$teamList)) {
         $teamIds = array_keys($teamList);
         if(count($teamIds) > 0) {
            $this->displayedTeam = $teamIds[0];
         } else {
            $this->displayedTeam = 0;
         }
      }
      
      // get timetracks
      $timetracks = array();
      if (0 != $this->displayedTeam) {
         
         $members = TeamCache::getInstance()->getTeam($this->displayedTeam)->getActiveMembers();

         if (!empty($members)) {
            $memberIdList = array_keys($members);
            $formatedMembers = implode( ', ', $memberIdList);

            $query = "SELECT * FROM `codev_timetracking_table` " .
                     "WHERE date >= $this->startTimestamp AND date <= $my_endTimestamp " .
                     "AND userid IN ($formatedMembers)" .
                     "ORDER BY date;";

            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }

            $jobs = new Jobs();
            while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
               $tt = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);

               $user = UserCache::getInstance()->getUser($tt->getUserId());
               $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());

               if(!is_null($tt->getCommitterId())) {
                  $committer = UserCache::getInstance()->getUser($tt->getCommitterId());
                  $committer_name = $committer->getName();
                  $commit_date = date('Y-m-d H:i:s', $tt->getCommitDate());
               } else {
                  $committer_name = ''; // this info does not exist before v1.0.4
                  $commit_date = '';
               }

               $timetracks[$row->id] = array(
                  #'id' => $row->id,
                  'user' => $user->getName(),
                  'date' => date('Y-m-d', $tt->getDate()),
                  'job' => $jobs->getJobName($tt->getJobId()),
                  'duration' => $tt->getDuration(),
                  'committer' => $committer_name,
                  'commit_date' => $commit_date,
                  'task_id' => $issue->getId(),
                  'task_extRef' => $issue->getTcId(),
                  'task_summary' => $issue->getSummary(),
               );
            }
         }
      }

      $this->execData = array (
         'teamList' => $teamList,
         'startTimestamp' => $this->startTimestamp,
         'endTimestamp' => $this->endTimestamp,
         'timetracks' => $timetracks,
         );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $availableTeams = SmartyTools::getSmartyArray($this->execData['teamList'],$this->displayedTeam);

      $smartyVariables = array(
         'timetrackDetailsIndicator_availableTeams' => $availableTeams,
         'timetrackDetailsIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->execData['startTimestamp']),
         'timetrackDetailsIndicator_endDate'   => Tools::formatDate("%Y-%m-%d", $this->execData['endTimestamp']),
         'timetrackDetailsIndicator_timetracks' => $this->execData['timetracks'],

         // add pluginSettings (if needed by smarty)
         'timetrackDetailsIndicator_'.self::OPTION_DISPLAYED_TEAM => $this->displayedTeam,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['timetrackDetailsIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['timetrackDetailsIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
TimetrackDetailsIndicator::staticInit();
