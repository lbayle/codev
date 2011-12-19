<?php /*
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
*/ ?>
<?php

   /*
    * The Variables in here are not expected to be changed in any way.
    *
    * most of them are initialyzed from the 'codev_config_table'.
    *
    */
   include_once "config.class.php";

   // CoDevTT project started on: 17 May 2010

   $codevVersion = "v0.99.13 (27 Oct 2011)";

   $codevVersionHistory = array("v0.99.0" => "(09 Sept 2010) - team management complete",
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
                                "v0.99.13" => "(27 Oct  2011) - GANTT chart + ExternalTasksProject"
                                );


  // ---
  // il peut y avoir plusieurs observer
  // il n'y a qu'un seul teamLeader
  // il peut y avoir plusieurs managers, mais ils ne peuvent imputer que sur des SideTasks
  // un observer ne fait jamais partie de l'equipe, il n'a acces qu'a des donnees impersonnelles

  // ==================

  $admin_teamid = Config::getInstance()->getValue(Config::id_adminTeamId); // users allowed to do CoDev administration

  // this is the custom field added to mantis issues for TimeTracking
  $tcCustomField           = Config::getInstance()->getValue(Config::id_customField_ExtId);
  $estimEffortCustomField  = Config::getInstance()->getValue(Config::id_customField_effortEstim); //  BI
  $remainingCustomField    = Config::getInstance()->getValue(Config::id_customField_remaining); //  RAF
  $deadLineCustomField     = Config::getInstance()->getValue(Config::id_customField_deadLine);
  $addEffortCustomField    = Config::getInstance()->getValue(Config::id_customField_addEffort); // BS
  $deliveryIdCustomField   = Config::getInstance()->getValue(Config::id_customField_deliveryId); // FDL (id of the associated Delivery Issue)
  $deliveryDateCustomField = Config::getInstance()->getValue(Config::id_customField_deliveryDate);


  // ---
  // TODO translate astreinte = "on duty"
  $astreintesTaskList = Config::getInstance()->getValue(Config::id_astreintesTaskList); // fiches de SuiviOp:Inactivite qui sont des astreintes
  if (NULL == $astreintesTaskList) {
  	$astreintesTaskList = array();
  }

  // --- Mantis Values ---
  $priorityNames   = Config::getInstance()->getValue(Config::id_priorityNames);
  $resolutionNames = Config::getInstance()->getValue(Config::id_resolutionNames);

  // ---
  $externalTasksProject = Config::getInstance()->getValue(Config::id_externalTasksProject);

  $codevReportsDir = Config::getInstance()->getValue(Config::id_codevReportsDir);
  $_POST['codevReportsDir'] = $codevReportsDir; // used by tools/download.php


  // --- log to file
  /**
   * NOT TESTED !!!!
   * http://www.cyberciti.biz/tips/php-howto-turn-on-error-log-file.html
   * On all production web server you must turn off displaying error
   * to end users via a web browser. Remember PHP gives out lots of information about path,
   * database schema and all other sort of sensitive information.
   * You are strongly advised to use error logging in place of error displaying on production web sites
   */
  #ini_set("log_errors" , "1");
  #ini_set("error_log" , "Errors.log.txt");
  #ini_set("display_errors" , "0");


?>
