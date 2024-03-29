<?php
/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * contains CodevTT settings
 * @author lbayle
 */
class ConfigItem {

   public $id;
   public $value;
   public $type;

   public function __construct($id, $value, $type) {
      $this->id = $id;
      $this->type = $type;

      switch ($type) {
         case Config::configType_keyValue :
            $this->value = (NULL != $value) ? $this->value = Tools::doubleExplode(':', ',', $value) : NULL;
            break;
         case Config::configType_array :
            $this->value = (NULL != $value) ? $this->value = explode(',', $value) : NULL;
            break;
         default:
            $this->value = $value;
      }
   }

   /**
    * @param $key
    * @return mixed
    */
   public function getArrayValueFromKey($key) {
      return (Config::configType_keyValue == $this->type && array_key_exists($key,$this->value)) ? $this->value[$key] : NULL;
   }

   /**
    * @param $value
    * @return mixed
    */
   public function getArrayKeyFromValue($value) {
      return (Config::configType_keyValue == $this->type) ? array_search($value, $this->value) : NULL;
   }

}

/**
 * Example:
 * $admin_teamid = Config::getInstance()->getValue(Config::id_adminTeamId);
 */
class Config {

   // TODO Move to a more appropriate class
   const codevVersion = "v1.8.1";
   const codevVersionDate = "04 Feb 2024";
   const databaseVersion = 23; // used to check codev_config_table.database_version and apply upgrades.

   const configType_int = 1;
   const configType_string = 2;
   const configType_keyValue = 3;
   const configType_array = 4;

   // known Config ids
   const id_externalTasksProject = "externalTasksProject";
   const id_externalTasksCat_leave = "externalTasksCat_leave";
   const id_externalTasksCat_otherInternal = "externalTasksCat_otherInternal";
   const id_externalTask_leave = "externalTask_leave"; // DEPRECATED since 0.99.22
   const id_jobSupport = "job_support"; // DEPRECATED since 1.0.2
   const id_adminTeamId = "adminTeamId";
   const id_statusNames = "statusNames"; // DEPRECATED since 0.99.18
   const id_astreintesTaskList = "astreintesTaskList"; // DEPRECARED since 0.99.19
   const id_onDutyTaskList = "onDutyTaskList";
   const id_codevReportsDir = "codevReportsDir"; // DEPRECATED since 0.99.18
   const id_customField_ExtId  = "customField_ExtId";
   const id_customField_MgrEffortEstim = "customField_MgrEffortEstim";  // ex ETA/PrelEffortEstim
   const id_customField_effortEstim  = "customField_effortEstim"; //  BI
   const id_customField_backlog = "customField_backlog"; //  RAE
   const id_customField_deadLine = "customField_deadLine";
   const id_customField_addEffort = "customField_addEffort"; // BS DEPRECATED since v1.4.0
   #const id_customField_deliveryId = "customField_deliveryId"; // FDL (id of the associated Delivery Issue)
   const id_customField_deliveryDate = "customField_deliveryDate";
   const id_customField_type = "customField_type";
   const id_customField_dailyPrice = "customField_dailyPrice";
   const id_priorityNames = "priorityNames";     // DEPRECATED since 0.99.18
   const id_severityNames = "severityNames";     // DEPRECATED since 0.99.18
   const id_resolutionNames = "resolutionNames"; // DEPRECATED since 0.99.18
   const id_mantisPath = "mantisPath";           // DEPRECATED since 0.99.18
   const id_bugResolvedStatusThreshold = "bug_resolved_status_threshold"; // WORKAROUND FIXME !
   const id_timetrackingFilters = "timetrackingFilters";
   const id_timetrackingForbidenStatusList = 'timetrackingForbidenStatusList';
   const id_blogCategories = "blogCategories";
   const id_defaultTeamId = "defaultTeamId";
   const id_defaultLanguage = "defaultLanguage";
   const id_defaultProjectId = "defaultProjectId";
   #const id_ClientTeamid = "client_teamid"; // DEPRECATED since 1.0.3
   const id_projectFilters = "projectFilters"; // DEPRECATED since 1.4.0
   const id_commandFilters = "commandFilters"; // DEPRECATED since 1.4.0
   const id_commandSetFilters = "commandSetFilters"; // DEPRECATED since 1.4.0
   const id_serviceContractFilters = "serviceContractFilters"; // DEPRECATED since 1.4.0
   const id_consistencyCheckList = 'consistencyCheckList';
   const id_teamGeneralPreferences = 'teamGeneralPreferences';
   const id_durationList = 'durationList';
   const id_issueTooltipFields = 'issue_tooltip_fields';
   const id_cmdStateFilters = 'cmdStateFilters';
   const id_issueInfoFilters = 'issueInfoFilters';
   const id_dashboard = 'dashboard_'; // real key will be dashboard_<dashboard_ID>
   const id_planningOptions = 'planningOptions';
   const id_schedulerOptions = 'schedulerOptions';
   const id_blogPluginOptions = 'blogPluginOptions';
   const id_userGroups = 'userGroups';
   const id_importRelationshipTreeToCommandOptions = 'importRelationshipTreeToCommandOptions';

   const default_timetrackingFilters = "onlyAssignedTo:0,hideResolved:0,hideForbidenStatus:1";
   const default_timetrackingForbidenStatusList = "90"; // 90:closed (do not include 'new')

   // TODO Move to a more appropriate class
   public static $codevVersionHistory = array(
      "v0.01.0" => "(17 Mar 2010) - CodevTT project creation",
      "v0.99.0" => "(09 Sept 2010) - team management complete",
      "v0.99.1" => "(28 Sept 2010) - jobs management",
      "v0.99.2" => "(08 Dec  2010) - Project Management",
      "v0.99.3" => "(03 Jan  2011) - fix new year problems",
      "v0.99.4" => "(13 Jan  2011) - ConsistencyCheck",
      "v0.99.5" => "(21 Jan  2011) - Update directory structure & Apache config",
      "v0.99.6" => "(16 Feb  2011) - i18n (internationalization)",
      "v0.99.7" => "(25 Feb  2011) - Graph & Statistics",
      "v0.99.8" => "(25 Mar  2011) - Add Job and specificities for 'support' + createTeam enhancements",
      "v0.99.9" => "(11 Apr  2011) - Planning + enhance global performances",
      "v0.99.10" => "(28 May  2011) - Install Procedure (unpolished)",
      "v0.99.11" => "(16 Jun  2011) - Replace ETA with Preliminary Est. Effort",
      "v0.99.12" => "(25 Aug  2011) - bugfix release & Install Procedure (unpolished)",
      "v0.99.13" => "(27 Oct  2011) - GANTT chart + ExternalTasksProject",
      "v0.99.14" => "(02 Feb  2012) - JQuery,Log4php, ForecastingReport, uninstall",
      "v0.99.15" => "(28 Feb  2012) - MgrEffortEstim, install, timetrackingFilters",
      "v0.99.16" => "(11 Apr  2012) - Smarty+Ajax, install, ProjectInfo, Https, Sessions, Doxygen, Observers view all pages, greasemonkey, ConsistencyChecks",
      "v0.99.17" => "(29 Jun  2012) - Smarty+Ajax, install, Management section, datatables, GUI enhancements, 'Leave' task moved to ExternalTasks, ConsistencyChecks",
      "v0.99.18" => "(22 Sept 2012) - PERF, GUI enhancements, WinInstall, IndicatorPlugins, ProjectFilters, Import",
      "v0.99.19" => "(29 Nov  2012) - GUI enhancements, install, Cmd Provisions, Cmd Budget, CodevTT_type, disable project/team, HTML Tooltips, MantisPlugin, merge Progress & ProgressMgr",
      "v0.99.20" => "(06 Feb  2013) - teamSelector, custo-tooltips, custo-ccheck, install, unassignedTasks, dates ISO-8601, usort, maxTooltipsPerPage, BudgetDriftHistoryIndicator, ReopenedRateIndicator, exportODT",
      "v0.99.21" => "(07 Apr  2013) - ObserverView=MgrView, TeamMonthlyActivity, BackupDialogBox+Status, install:selectAdmin, install:subdirs",
      "v0.99.22" => "(25 Jun  2013) - install:log4php.xml, no default backlog, ExtTasksProjects categories, accessLevel_Customer, Fix SessionName, Fix reverseProxy",
      "v0.99.23" => "(01 Sep  2013) - install:windows, upgradeLog4php, basepath, IE, TimesheetNotes, ElapsedPerProjectPerUser, pageHeader, createSTProj, greaseMonkey, forecastingLateTasks",
      "v0.99.24" => "(04 Nov  2013) - install:windows, BL dialogBox, holidays:ifNotInTeam, doodles, IE, log4php, JsErrorCatch",
      "v1.0.0 RC1" => "(13 Jan  2014) - WBS, install, log file creation if missing",
      "v1.0.0" => "(17 Mar  2014) - Relationships, timetrack duration, check_latest_version, CmdStateFilter, periodic leaves",
      "v1.0.1" => "(16 Aug  2014) - fix:Gantt+CSVimport+graphColors+WBSrootName, Hide admin menu, install:proxy+checkMantisDefaultProjectWorkflow",
      "v1.0.2" => "(21 Jan  2015) - fix:WBS+more, MantisPlugin, FilterBugList, Jobs, Commands:totalProv, MinifyJS, EditTeam:combobox, Logs, Holidays:filters, ExportCSV",
      "v1.0.3" => "(15 Aug  2015) - fix:ImportCSV,Planning+SqlInjection+EscapeChars+NonNumeric+more, PlanningOptions, TimesheetEmails, BacklogDialogbox:status+handler, install:activatePlugins",
      "v1.1.0" => "(10 Feb  2016) - PluginManager, Mantis_1_3, MainMenu, NoFLASH, MantisPlugins, fix:WeekDates, fix:PlanningTooltips",
      "v1.2.0" => "(02 Jan  2017) - Scheduler,Gantt,Plugins,i18n,Mantis2.0,min.js",
      "v1.2.1" => "(02 Apr  2017) - fix:weekDates, fix:backlogCheck, i18n, WBS, teamOptions, UserTeamListPlugin, projectTypes",
      "v1.2.2" => "(11 Apr  2017) - fix big regression due to m1553 (mantis table prefix/suffix)",
      "v1.3.0" => "(18 Mar  2018) - adodb, PHP7, CostIndicator, mantis table prefix/suffix, fixCommand, Float customFields, wbs_order",
      "v1.4.0" => "(12 May  2019) - remove FilterBugList, multiple TeamAdmins, BlogPlugin, SubmittedResolvedPlugin, TimetrackListPlugin, ManagementCostsPlugin, WbsExportPlugin, TasksPivotTablePlugin, remove effortAdd",
      "v1.5.0" => "(01 Jun  2020) - customField:DailyPrice, remove CSV export pages, Domain:User, fix:mantisPlugin, NewPlugins:SallingPriceForPeriod,OngoingTasks,AdminTools",
      "v1.6.0" => "(21 Mar  2021) - ProjectJobTeam asso, Admin enhancements, timesheets JS, issueInfoFilters, NewPlugins:FillPeriodWithTimetracks,ImportRelationshipTreeToCommand,LoadPerUserGroups,BurndownChart,IssueSeniority,TimetrackingAnalysis",
      "v1.7.0" => "(14 Feb  2022) - PHP8, moveConfigFile, moveToGitHub, newPage:MissingTimetracks, Select2_lazyLoad, UserGroups, NewPlugins:ResetDashboard,LoadPerCustomfieldValues",
      "v1.8.0" => "(22 Oct  2023) - docker entrypoint.sh, newPage:TimetrackingpageLite, newPlugins:CustomUserData",
      "v1.8.1" => "(04 Feb  2024) - FIX big regression in Drift computing, introduced in 1.8.0, due to PHP8 migration",
   );

   /**
    * @var Config singleton instance
    */
   private static $instance;

   /**
    * @var ConfigItem[]
    */
   private static $configVariables;

   private static $quiet; // do not display any warning message (used for install procedures only)

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * Private constructor to respect the singleton pattern
    */
   private function __construct() {
      self::$configVariables = array();
      $query = 'SELECT * FROM codev_config_table';

      $result = AdodbWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      while($row = AdodbWrapper::getInstance()->fetchArray($result)) {
         $key = $row['config_id']."_".$row['user_id'].$row['project_id'].$row['team_id'].$row['servicecontract_id'].$row['commandset_id'].$row['command_id'];
         self::$configVariables[$key] = new ConfigItem($row['config_id'], $row['value'], $row['type']);
      }

      if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
         self::$logger->trace("Config ready");
      }
   }

   /**
    * The singleton pattern
    * @static
    * @return Config
    */
   public static function getInstance() {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }

   /**
    * If true, then no info/warning messages will be displayed.
    * this shall only be set during install procedures.
    * @static
    * @param bool $isQuiet
    */
   public static function setQuiet($isQuiet = false) {
      self::$quiet = $isQuiet;
   }

   /**
    * @static
    * @param $id
    * $param $arr_subid (array primary keys : user_id, project_id, team_id, servicecontract_id, commandset_id, command_id)
    * @return true if key exists
    */
   public static function keyExists($id, $arr_subid=NULL) {
      $key = $id."_";

      if ($arr_subid == NULL) {
     	  $arr_subid = array(0, 0, 0, 0, 0, 0);
      }
      foreach ($arr_subid as $subid) {
      	$key .= $subid;
      }
      return isset(self::$configVariables[$key]);
   }

   /**
    * @static
    * @param $id
    * $param $arr_subid (array primary keys : user_id, project_id, team_id, servicecontract_id, commandset_id, command_id)
    * @return mixed
    */
   public static function getValue($id, $arr_subid=NULL, $isQuiet=NULL) {
      $value = NULL;
      $key = $id."_";

      if ($arr_subid == NULL) {
     	  $arr_subid = array(0, 0, 0, 0, 0, 0);
      }
      foreach ($arr_subid as $subid) {
      	$key .= $subid;
      }
      $variable = isset(self::$configVariables[$key]) ? self::$configVariables[$key] : NULL;

      if (NULL != $variable) {
         $value = $variable->value;
      } else {
         //self::$logger->warn("getValue($id): variable not found !");
         if ($isQuiet != NULL) {
         	$tmp = self::$quiet;
         	self::setQuiet($isQuiet);
         }
         if (!self::$quiet) {
            echo "<span class='warn_font'>WARN: Config::getValue($id): variable not found !</span><br/>";
         }
         if ($isQuiet != NULL) {
         	self::setQuiet($tmp);
         }
      }
      return $value;
   }

   /**
    * if the variable type is a configType_keyValue,
    * returns the key for a given value.
    * example: $status_new = Config::getVariableKeyFromValue(Config::id_statusNames, 'new');
    * @static
    * @param $id
    * @param $value
    * @return mixed
    */
   public static function getVariableKeyFromValue($id, $value, $arr_subid=NULL) {
      $key = NULL;
   	  $new_id = $id."_";

      if ($arr_subid == NULL) {
     	  $arr_subid = array(0, 0, 0, 0, 0, 0);
      }
      foreach ($arr_subid as $subid) {
      	$new_id .= $subid;
      }
      $variable = self::$configVariables[$new_id];
      if (NULL != $variable) {
         $key = $variable->getArrayKeyFromValue($value);
      } else {
         self::$logger->warn("getVariableKeyFromValue($id, $value): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getVariableKeyFromValue($id, $value): variable not found !</span><br/>";
         }
      }
      return $key;
   }

   /**
    * if the variable type is a configType_keyValue,
    * returns the value for a given key.
    * @static
    * @param $id
    * @param $key
    * @return mixed
    */
   public static function getVariableValueFromKey($id, $key, $arr_subid=NULL) {
      $value = NULL;
      $new_id = $id."_";

      if ($arr_subid == NULL) {
      	$arr_subid = array(0, 0, 0, 0, 0, 0);
      }
      foreach ($arr_subid as $subid) {
      	$new_id .= $subid;
      }
      $variable = self::$configVariables[$new_id];

      if (NULL != $variable) {
         $value = $variable->getArrayValueFromKey($key);
      } else {
         self::$logger->warn("getVariableValueFromKey($id, $key): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getVariableValueFromKey($id, $key): variable not found !</span><br/>";
         }
      }
      return $value;
   }

   /**
    * @static
    * @param $id
    * @return string
    */
   public static function getType($id, $arr_subid=NULL) {
      $type = NULL;
      $new_id = $id."_";

      if ($arr_subid == NULL) {
      	$arr_subid = array(0, 0, 0, 0, 0, 0);
      }
      foreach ($arr_subid as $subid) {
      	$new_id .= $subid;
      }
      $variable = self::$configVariables[$new_id];

      if (NULL != $variable) {
         $type = $variable->type;
      } else {
         self::$logger->warn("getType($id): variable not found !");
         if (!self::$quiet) {
            echo "<span class='error_font'>WARN: Config::getType($id): variable not found !</span><br/>";
         }
      }
      return $type;
   }

   /**
    * Add or update an Item (in DB and Cache)
    * Note: update does not change the type.
    * @static
    * @param string $id
    * @param string $value
    * @param int $type
    * @param string $desc
    * @param int $project_id
    * @param int $user_id
    * @param int $team_id
    * @param int $command_id
    * @param int $commandset_id
    * @param int $servicecontract_id
    */
   public static function setValue($id, $value, $type, $desc=NULL, $project_id=0, $user_id=0, $team_id=0, $command_id=0, $cset_id=0, $service_id=0) {

      $sql = AdodbWrapper::getInstance();

      // add/update DB
      $query = 'SELECT * FROM codev_config_table ' .
         'WHERE config_id ='. $sql->db_param().
               ' AND project_id ='. $sql->db_param().
               ' AND user_id ='. $sql->db_param().
               ' AND team_id ='. $sql->db_param().
               ' AND command_id = '. $sql->db_param().
               ' AND commandset_id = '. $sql->db_param().
               ' AND servicecontract_id = ' . $sql->db_param();

      $query_params = array($id, $project_id, $user_id, $team_id, $command_id, $cset_id, $service_id);

      $result = $sql->sql_query($query, $query_params);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 != $sql->getNumRows($result)) {
         $query = 'UPDATE codev_config_table ' .
            ' SET value = '.$sql->db_param(). // $formattedValue.
                  ' WHERE config_id ='. $sql->db_param().
                  ' AND project_id ='. $sql->db_param().
                  ' AND user_id ='. $sql->db_param().
                  ' AND team_id ='. $sql->db_param().
                  ' AND command_id = '. $sql->db_param().
                  ' AND commandset_id = '. $sql->db_param().
                  ' AND servicecontract_id = ' . $sql->db_param();
         $query_params = array($value,
                               $id, $project_id, $user_id, $team_id,
                               $command_id, $cset_id, $service_id);

      } else {
         $query = 'INSERT INTO codev_config_table ' .
            '(config_id, value, type, description, project_id, user_id, team_id, command_id, commandset_id, servicecontract_id) '.
						 ' VALUES ( ' . $sql->db_param() . ',' . $sql->db_param() . ',' . $sql->db_param() . ',' . $sql->db_param() . ',
						   			' . $sql->db_param() . ',' . $sql->db_param() . ',' . $sql->db_param() . ',' . $sql->db_param() . ',
						   			' . $sql->db_param() . ',' . $sql->db_param() . ')';

         $query_params = array($id, $value, $type, $desc, $project_id, $user_id, $team_id,
                               $command_id, $cset_id, $service_id);

      }

      $result = $sql->sql_query($query, $query_params);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $new_id = $id."_".$user_id.$project_id.$team_id.$service_id.$cset_id.$command_id;
      // add/replace Cache
      self::$configVariables[$new_id] = new ConfigItem($id, $value, $type);
   }

   /**
    * removes a ConfigItem from DB and Cache
    * @static
    * @param $id
    */
   public static function deleteValue($id, $arr_subid=NULL) {
   	  $new_id = $id."_";

   	  if ($arr_subid == NULL) {
   		  $arr_subid = array(0, 0, 0, 0, 0, 0);
   	  }
   	  foreach ($arr_subid as $subid) {
   		  $new_id .= $subid;
      }
      if (NULL != self::$configVariables[$new_id]) {

         $sql = AdodbWrapper::getInstance();

         // delete from DB
         $query = "DELETE FROM codev_config_table WHERE config_id = " . $sql->db_param();
         $query_params = array($id);

         $cols = array("user_id", "project_id", "team_id", "servicecontract_id", "commandset_id", "command_id");
         if ($arr_subid != NULL && is_array($arr_subid)) {
         	$i = 0;
         	while ($i < count($cols)) {
         		$query .= ' AND ' . $cols[$i] . ' = ' . $sql->db_param();
               $query_params[] = $arr_subid[$i];
         		$i++;
         	}
         }

         $result = $sql->sql_query($query, $query_params);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // remove from cache
         unset(self::$configVariables[$new_id]);
      } else {
         self::$logger->warn("DELETE variable <$id> not found in cache !");
      }
   }
}

Config::staticInit();

