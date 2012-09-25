<?php

include_once('../include/session.inc.php');

/*
  This file is part of CodevTT.

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
require('../path.inc.php');

include_once "include/mysql_config.inc.php";

// ================ MAIN =================
#if(isset($_SESSION['userid'])) {

   echo "convert constants.php to config.ini<br><br>";

   Constants::$db_mantis_host     = $db_mantis_host;
   Constants::$db_mantis_database = $db_mantis_database;
   Constants::$db_mantis_user     = $db_mantis_user;
   Constants::$db_mantis_pass     = $db_mantis_pass;
   $retcode = Constants::writeConfigFile();


   if (false != $retcode) {

      include_once "constants.php";

      Constants::$codevInstall_timestamp = $codevInstall_timestamp;
      Constants::$codevtt_logfile        = $codevtt_logfile;
      Constants::$codevOutputDir         = Config::getInstance()->getValue(Config::id_codevReportsDir).DIRECTORY_SEPARATOR.'..';
      Constants::$homepage_title         = $homepage_title;
      Constants::$codevRootDir           = $codevRootDir;
      Constants::$mantisPath             = Config::getInstance()->getValue(Config::id_mantisPath);
      Constants::$mantisURL              = $mantisURL;

      Constants::$statusNames      = Config::getInstance()->getValue(Config::id_statusNames);
      Constants::$priority_names   = Config::getInstance()->getValue(Config::id_priorityNames);
      Constants::$resolution_names = Config::getInstance()->getValue(Config::id_resolutionNames);
      Constants::$severity_names   = Config::getInstance()->getValue(Config::id_severityNames);
      Constants::$bug_resolved_status_threshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);

      Constants::$status_new          = $status_new;
      Constants::$status_feedback     = $status_feedback;
      Constants::$status_acknowledged = $status_acknowledged;
      Constants::$status_open         = $status_open;
      Constants::$status_closed       = $status_closed;

      Constants::$resolution_fixed    = array_search('fixed',    Constants::$resolution_names);
      Constants::$resolution_reopened = array_search('reopened',    Constants::$resolution_names);

      Constants::$relationship_constrained_by = BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY;
      Constants::$relationship_constrains     = BUG_CUSTOM_RELATIONSHIP_CONSTRAINS;

      $retcode = Constants::writeConfigFile();

      if (false != $retcode) {
         header('Location:../index.php');
      }
   }
#}

?>
