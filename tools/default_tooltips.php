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
      echo "<span style='color:red'>ERROR: Query FAILED $query<br/>" . mysql_error() . "</span>";
      exit;
   }
   return $result;
}


/**
 * update 0.99.19 to 0.99.20 (DB v10 to DB v11)
 * 
 * there is no .sql script to run, but db version is increased to 11 (add default issue_tooltip_fields)
 *
 */
function update_v10_to_v11() {

   // add default issue tooltips
   $customField_type = Config::getInstance()->getValue(Config::id_customField_type);
   $backlogField = Config::getInstance()->getValue(Config::id_customField_backlog);
   $fieldList = array('project_id', 'category_id', 'custom_'.$customField_type,
       'codevtt_elapsed', 'custom_'.$backlogField, 'codevtt_drift');
   $serialized = serialize($fieldList);
   Config::setValue('issue_tooltip_fields', $serialized, Config::configType_string, 'fields to be displayed in issue tooltip');

   $query = "UPDATE `codev_config_table` SET `value`='11' WHERE `config_id`='database_version';";
   $result = execQuery($query);

}

// =========== MAIN ==========
$logger = Logger::getLogger("versionUpdater");

update_v10_to_v11();
echo "<br><br>UPDATE DONE.<br>";



?>
