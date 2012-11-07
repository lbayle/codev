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




function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   return $result;
}

/**
 * update 0.99.18 to 0.99.19 (DB v9 to DB v10)
 */
function update_v9_to_v10() {

   // the CodevTT_Type field must be created before the DB update
   $mType_list = 6;
   $access_viewer = 10;
   $access_reporter = 25;
   $attributes = array();
   $attributes["access_level_r"] = $access_viewer;
   $attributes["access_level_rw"] = $access_reporter;
   $attributes["require_report"] = 1;
   $attributes["display_report"] = 1;
   $attributes["require_update"] = 0;
   $attributes["display_update"] = 1;
   $attributes["require_resolved"] = 0;
   $attributes["display_resolved"] = 0;
   $attributes["require_closed"] = 0;
   $attributes["display_closed"] = 0;
   $defaultValue = NULL;
   $possible_values = 'Bug|Task';
   createCustomField(T_("CodevTT_Type"), $mType_list, "customField_type", $attributes, $defaultValue, $possible_values);

   // execute the SQL script
   //
   $sqlScriptFilename = '../install/codevtt_update_v9_v10.sql';
   $retCode = Tools::execSQLscript2($sqlScriptFilename);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: $sqlScriptFilename</span><br/>";
      exit;
   }



}

// =========== MAIN ==========
$logger = Logger::getLogger("versionUpdater");

/*
 * 1) check administration rights
 * 2) check DB version
 * 3) execute PHP & DB actions
 *
 *
 */



?>
