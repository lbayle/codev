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

include_once('classes/config.class.php');

// CoDevTT project started on: 17 May 2010

/*
* The Variables in here are not expected to be changed in any way.
* most of them are initialyzed from the 'codev_config_table'.
*/
class InternalConfig {

   public static $codevVersion = "v0.99.17 (29 Jun 2012)";

   public static $codevVersionHistory = array(
      "v0.01.0"  => "(17 May 2010) - CodevTT project creation",
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
      "v0.99.14" => "(2 Feb  2012) - JQuery,Log4php, ForecastingReport, uninstall",
      "v0.99.15" => "(28 Feb  2012) - MgrEffortEstim, install, timetrackingFilters",
      "v0.99.16" => "(11 Apr  2012) - Smarty+Ajax, install, ProjectInfo, Https, Sessions, Doxygen, Observers view all pages, greasemonkey, ConsistencyChecks",
      "v0.99.17" => "(29 Jun  2012) - Smarty+Ajax, install, Management section, datatables, GUI enhancements, 'Leave' task moved to ExternalTasks, ConsistencyChecks"
   );

   // il peut y avoir plusieurs observer
   // il n'y a qu'un seul teamLeader
   // il peut y avoir plusieurs managers, mais ils ne peuvent imputer que sur des SideTasks
   // un observer ne fait jamais partie de l'equipe, il n'a acces qu'a des donnees impersonnelles

   public static $admin_teamid; // users allowed to do CoDev administration

   // this is the custom field added to mantis issues for TimeTracking
   public static $tcCustomField;
   public static $estimEffortCustomField; //  BI
   public static $backlogCustomField; //  RAF
   public static $deadLineCustomField;
   public static $addEffortCustomField; // BS
   #public static $deliveryIdCustomField; // FDL (id of the associated Delivery Issue)
   public static $deliveryDateCustomField;

   // TODO translate astreinte = "on duty"
   public static $astreintesTaskList; // fiches de SuiviOp:Inactivite qui sont des astreintes

   // --- Mantis Values ---
   public static $priorityNames;
   public static $resolutionNames;

   public static $externalTasksProject;

   public static $codevReportsDir;

   public static $default_timetrackingFilters = "onlyAssignedTo:0,hideResolved:0,hideDevProjects:0";


   public static function staticInit() {
      self::$admin_teamid = Config::getInstance()->getValue(Config::id_adminTeamId);

      // this is the custom field added to mantis issues for TimeTracking
      self::$tcCustomField           = Config::getInstance()->getValue(Config::id_customField_ExtId);
      self::$estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim);
      self::$backlogCustomField    = Config::getInstance()->getValue(Config::id_customField_backlog);
      self::$deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
      self::$addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort);
      #self::$deliveryIdCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryId);
      self::$deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);

      // TODO translate astreinte = "on duty"
      self::$astreintesTaskList = Config::getInstance()->getValue(Config::id_astreintesTaskList); // fiches de SuiviOp:Inactivite qui sont des astreintes
      if (NULL == self::$astreintesTaskList) {
         self::$astreintesTaskList = array();
      }

      self::$priorityNames   = Config::getInstance()->getValue(Config::id_priorityNames);
      self::$resolutionNames = Config::getInstance()->getValue(Config::id_resolutionNames);

      self::$externalTasksProject = Config::getInstance()->getValue(Config::id_externalTasksProject);

      self::$codevReportsDir = Config::getInstance()->getValue(Config::id_codevReportsDir);
   }

}

// Initialize complex variables
InternalConfig::staticInit();

$_POST['codevReportsDir'] = InternalConfig::$codevReportsDir; // used by tools/download.php

?>
