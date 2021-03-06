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
 * Description of ActivityIndicator
 *
 * @author lob
 */
class LoadPerUserIndicator extends IndicatorPluginAbstract {

   // if false, sidetasks (found in IssueSel) will be included in 'elapsed'
   // if true, sidetasks (found in IssueSel) will be displayed as 'sidetask'
   const OPTION_SHOW_SIDETASKS = 'showSidetasks';
   const OPTION_DATE_RANGE    = 'dateRange';
   const OPTION_IS_ONLY_TEAM_MEMBERS = 'isOnlyActiveTeamMembers';

   // if false, display only the elapsed time on the IssueSel
   // if true, display elapsed user's complete activity (other, external, inactivity)
   const OPTION_SHOW_ALL_ACTIVITY = 'showAllActivity';


   /**
    * @var Logger The logger
    */
   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;
   private $teamid;

   // config options from Dashboard
   private $showSidetasks;
   private $dateRange;  // defaultRange | currentWeek | currentMonth
   private $showAllActivity; // boolean
   private $isOnlyActiveTeamMembers;

   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_TEAM,
//         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_ACTIVITY
      );
   }

   public static function getName() {
      return T_('Load per User');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Check all the timetracks of the period and return their repartition per User');
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
         'lib/jquery.jqplot/plugins/jqplot.pieRenderer.min.js',
         'js_min/chart.min.js',
         'js_min/table2csv.min.js',
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
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = NULL;
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = NULL;
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      // if false, sidetasks (found in IssueSel) will be included in 'elapsed'
      // if true, sidetasks (found in IssueSel) will be displayed as 'sidetask'
      $this->showSidetasks = false;
      $this->dateRange = 'defaultRange';
      $this->showAllActivity = false;
      $this->isOnlyActiveTeamMembers= TRUE;
   }

   /**
    * settings are saved by the Dashboard
    *
    * @param array $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {
      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_ONLY_TEAM_MEMBERS, $pluginSettings)) {
            $this->isOnlyActiveTeamMembers = $pluginSettings[self::OPTION_IS_ONLY_TEAM_MEMBERS];
         }
         if (array_key_exists(self::OPTION_SHOW_SIDETASKS, $pluginSettings)) {
            $this->showSidetasks = $pluginSettings[self::OPTION_SHOW_SIDETASKS];
         }
         if (array_key_exists(self::OPTION_SHOW_ALL_ACTIVITY, $pluginSettings)) {
            // WARNING: combine showAllActivity=true with isOnlyActiveTeamMembers=false will return full codevtt timetracks !!!!
            if ($this->isOnlyActiveTeamMembers) {
               $this->showAllActivity = $pluginSettings[self::OPTION_SHOW_ALL_ACTIVITY];
            } else {
               $this->showAllActivity = false;
            }
         }
         if (array_key_exists(self::OPTION_DATE_RANGE, $pluginSettings)) {
            $this->dateRange = $pluginSettings[self::OPTION_DATE_RANGE];

            // update startTimestamp & endTimestamp
            switch ($this->dateRange) {
               case 'currentWeek':
                  $weekDates = Tools::week_dates(date('W'),date('Y'));
                  $this->startTimestamp = $weekDates[1];
                  $this->endTimestamp   = $weekDates[5];
                  break;
               case 'currentMonth':
                  $month = date('m');
                  $year  = date('Y');
                  $this->startTimestamp = mktime(0, 0, 0, $month, 1, $year);
                  $nbDaysInMonth = date("t", $this->startTimestamp);
                  $this->endTimestamp = mktime(0, 0, 0, $month, $nbDaysInMonth, $year);
                  break;
            }
         }
      }
   }


   /**
    *
    * returns an array of [user][activity]
    * activity in (elapsed, sidetask, other, external, leave)
    *
    */
   public function execute() {

      $sql = AdodbWrapper::getInstance();
      $team = TeamCache::getInstance()->getTeam($this->teamid);

      $members = $team->getActiveMembers($this->startTimestamp, $this->endTimestamp);

      if (count($members) > 0) {
        $formatedUseridString = implode( ', ', array_keys($members));

        $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
        $extTasksCatLeave = Config::getInstance()->getValue(Config::id_externalTasksCat_leave);

        // get timetracks for each Issue,
        $issueList = $this->inputIssueSel->getIssueList();
        $bugidList = array_keys($issueList);

        $query = "SELECT * FROM codev_timetracking_table WHERE 1 = 1 ";

        if ($this->isOnlyActiveTeamMembers) {
           $query .=  " AND userid IN (".$formatedUseridString.") ";
        }

        // WARN: combine showAllActivity=true with isOnlyActiveTeamMembers=false will return full timetracks !!!!
        if ((false == $this->showAllActivity) && (0 < count($bugidList))) {
           $formattedBugidList=implode( ', ', $bugidList);
           $query .= " AND bugid IN (".$formattedBugidList.") ";
        }

        if (isset($this->startTimestamp)) { $query .= " AND date >= ".$sql->db_param() ; $q_params[]=$this->startTimestamp;}
        if (isset($this->endTimestamp))   { $query .= " AND date <= ".$sql->db_param() ; $q_params[]=$this->endTimestamp;}

        $result = $sql->sql_query($query, $q_params);

        $timeTracks = array();
        while($row = $sql->fetchObject($result)) {
           $timeTracks[$row->id] = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);
        }
      } else {
          $timeTracks = array();
      }
      // ---
      // un tablean de users avec repartition temps en categories: regular,external,sidetask
      $teams = array($this->teamid);

      $usersActivity = array();
      foreach ($timeTracks as $tt) {

         $issueId = $tt->getIssueId();
         try {
            $issue = IssueCache::getInstance()->getIssue($issueId);
         } catch (Exception $e) {
            self::$logger->error("execute() skip issue $issueId : ".$e->getMessage());
            continue;
         }
         $userid = $tt->getUserId();

         if (!array_key_exists($userid, $usersActivity)) {
            $usersActivity[$userid] = array();
         }
         //$activityList = $usersActivity[$userid];

         $duration = $tt->getDuration();
         try {
            if ($extProjId == $tt->getProjectId()) {
               #self::$logger->error("external ".$tt->getIssueId());
               if ($extTasksCatLeave == $issue->getCategoryId()) {
                  if(array_key_exists('leave',$usersActivity[$userid])) {
                     $usersActivity[$userid]['leave'] += $duration;
                  } else {
                     $usersActivity[$userid]['leave'] = $duration;
                  }
               } else {
                  if(array_key_exists('external',$usersActivity[$userid])) {
                     $usersActivity[$userid]['external'] += $duration;
                  } else {
                     $usersActivity[$userid]['external'] = $duration;
                  }
               }

            } else if ($issue->isSideTaskNonProductionIssue($teams)) {
               #self::$logger->error("execute showSidetasks = ".$this->showSidetasks.' cat='.$cat);

               // if sideTask is in the IssueSelection, then it is considered as 'normal',
               // else it should not be included
               if (in_array($issueId, $bugidList)) {
                  $cat = $this->showSidetasks ? 'sidetask' : 'elapsed';
                  if(array_key_exists($cat,$usersActivity[$userid])) {
                     $usersActivity[$userid][$cat] += $duration;
                  } else {
                     $usersActivity[$userid][$cat] = $duration;
                  }
               } else {
                  // all sideTasks are in 'other' except inactivity tasks.
                  $project = ProjectCache::getInstance()->getProject($issue->getProjectId());
                  if ($project->getCategory(Project::cat_st_inactivity) == $issue->getCategoryId()) {
                     if(array_key_exists('leave',$usersActivity[$userid])) {
                        $usersActivity[$userid]['leave'] += $duration;
                     } else {
                        $usersActivity[$userid]['leave'] = $duration;
                     }
                  } else {
                     if(array_key_exists('other',$usersActivity[$userid])) {
                        $usersActivity[$userid]['other'] += $duration;
                     } else {
                        $usersActivity[$userid]['other'] = $duration;
                     }
                  }
               }
            } else if (in_array($issueId, $bugidList)) {
               #self::$logger->error("selection ".$tt->getIssueId());
               if(array_key_exists('elapsed',$usersActivity[$userid])) {
                  $usersActivity[$userid]['elapsed'] += $duration;
               } else {
                  $usersActivity[$userid]['elapsed'] = $duration;
               }
            } else {
               #echo "other ".$tt->getIssueId()."<br>";
               if(array_key_exists('other',$usersActivity[$userid])) {
                  $usersActivity[$userid]['other'] += $duration;
               } else {
                  $usersActivity[$userid]['other'] = $duration;
               }
            }
         } catch (Exception $e) {
            // Issue::isSideTaskIssue() throws an Ex if project not found in mantis
            self::$logger->error("Unknown activity for issue $issueId, duration ($duration) added to 'elapsed'\n".$e->getMessage());
            $usersActivity[$userid]['elapsed'] += $duration;
            // should it be added in userActivity[$userid]['unknown'] ?
         }
      }
      #var_dump($usersActivity);
      $this->execData = $usersActivity;
   }

   public function getSmartyVariables($isAjaxCall = false) {
      $usersActivities = array();

      $totalLeave = 0;
      $totalExternal = 0;
      $totalElapsed = 0;
      $totalOther = 0;
      $totalSidetask = 0;
      foreach ($this->execData as $userid => $userActivity) {
         $user = UserCache::getInstance()->getUser($userid);
         $usersActivities[$user->getName()] = $userActivity;

         if(array_key_exists('leave',$userActivity)) {
            $totalLeave += $userActivity['leave'];
         }
         if(array_key_exists('external',$userActivity)) {
            $totalExternal += $userActivity['external'];
         }
         if(array_key_exists('elapsed',$userActivity)) {
            $totalElapsed += $userActivity['elapsed'];
         }
         if(array_key_exists('other',$userActivity)) {
            $totalOther += $userActivity['other'];
         }
         if ($this->showSidetasks && array_key_exists('sidetask',$userActivity)) {
            $totalSidetask += $userActivity['sidetask'];
         }
      }

      $totalActivity = array();
      $totalActivity['leave'] = $totalLeave;
      $totalActivity['external'] = $totalExternal;
      $totalActivity['elapsed'] = $totalElapsed;
      $totalActivity['other'] = $totalOther;
      if ($this->showSidetasks) {
         $totalActivity['sidetask'] += $totalSidetask;
      }

      ksort($usersActivities);

      // table data
      $tableData = array(
         'usersActivities' => $usersActivities,
         'totalActivity' => $totalActivity,
         'workdays' => Holidays::getInstance()->getWorkdays($this->startTimestamp, $this->endTimestamp),
      );

      // ------------------------
      // pieChart data
      $jqplotData = array(
         T_('Elapsed') => $totalActivity['elapsed'],
         T_('Other') => $totalActivity['other'],
         T_('External') => $totalActivity['external'],
         T_('Inactivity') => $totalActivity['leave']
      );

      $formatedColors = array("#92C5FC", "#C2DFFF", "#75FFDA", "#A8FFBD");
      if ($this->showSidetasks) {
         $jqplotData[T_('Waste')] = $totalActivity['sidetask'];
         $formatedColors[] = "#FFF494";
      } else {
      }
      $seriesColors = '["'.implode('","', $formatedColors).'"]';  // ["#FFCD85","#C2DFFF"]

      $smartyVariables =  array(
         'loadPerUserIndicator_tableData' => $tableData,
         'loadPerUserIndicator_jqplotData' => empty($jqplotData) ? NULL : Tools::array2json($jqplotData),
         'loadPerUserIndicator_colors' => $formatedColors,
         'loadPerUserIndicator_jqplotSeriesColors' => $seriesColors, // TODO get rid of this
         'loadPerUserIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->startTimestamp),
         'loadPerUserIndicator_endDate' => Tools::formatDate("%Y-%m-%d", $this->endTimestamp),
         'loadPerUserIndicator_showSidetasks' =>  $this->showSidetasks,
         'loadPerUserIndicator_showAllActivity' =>  $this->showAllActivity,
         'loadPerUserIndicator_isOnlyActiveTeamMembers' =>  $this->isOnlyActiveTeamMembers
      );

      if (false == $isAjaxCall) {
         $smartyVariables['loadPerUserIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['loadPerUserIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }

      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
LoadPerUserIndicator::staticInit();

