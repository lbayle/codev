<?php
require('../include/session.inc.php');

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

require('../path.inc.php');


/**
 * create a customField in Mantis (if not exist) & update codev_config_table
 *
 * ex: $install->createCustomField("ExtRef", 0, "customField_ExtId");
 *
 * @param string $fieldName Mantis field name
 * @param int $fieldType Mantis field type
 * @param string $configId  codev_config_table.config_id
 * @param type $attributes
 * @param type $default_value
 * @param type $possible_values
 */
function createCustomField($fieldName, $fieldType, $configId, $attributes = NULL,
                           $default_value = '', $possible_values = '') {
   global $fieldList;

   if (NULL == $attributes) {
      $attributes = array();

      $attributes["access_level_r"] = 10;
      $attributes["access_level_rw"] = 25;
      $attributes["require_report"] = 1;
      $attributes["require_update"] = 1;
      $attributes["require_resolved"] = 0;
      $attributes["require_closed"] = 0;
      $attributes["display_report"] = 1;
      $attributes["display_update"] = 1;
      $attributes["display_resolved"] = 0;
      $attributes["display_closed"] = 0;

      echo "<span class='warn_font'>WARN: using default attributes for CustomField $fieldName</span><br/>";
   }

   $query = "SELECT id, name FROM `mantis_custom_field_table`";
   $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>" . mysql_error() . "</span>");
   while ($row = mysql_fetch_object($result)) {
      $fieldList["$row->name"] = $row->id;
   }

   $fieldId = $fieldList[$fieldName];
   if (!$fieldId) {
      $query2 = "INSERT INTO `mantis_custom_field_table` " .
         "(`name`, `type` ,`access_level_r`," .
         "                 `access_level_rw` ,`require_report` ,`require_update` ,`display_report` ,`display_update` ,`require_resolved` ,`display_resolved` ,`display_closed` ,`require_closed` ";
      $query2 .= ", `possible_values`, `default_value`";

      $query2 .= ") VALUES ('$fieldName', '$fieldType', '" . $attributes["access_level_r"] . "', '" .
         $attributes["access_level_rw"] . "', '" .
         $attributes["require_report"] . "', '" .
         $attributes["require_update"] . "', '" .
         $attributes["display_report"] . "', '" .
         $attributes["display_update"] . "', '" .
         $attributes["require_resolved"] . "', '" .
         $attributes["display_resolved"] . "', '" .
         $attributes["display_closed"] . "', '" .
         $attributes["require_closed"] . "'";

      $query2 .= ", '$possible_values', '$default_value'";
      $query2 .= ");";

      echo "DEBUG --- query $query2 <br/>";

      $result2 = mysql_query($query2) or die("<span style='color:red'>Query FAILED: $query2 <br/>" . mysql_error() . "</span>");
      $fieldId = mysql_insert_id();

      #echo "custom field '$configId' created.<br/>";
   } else {
      echo "<span class='success_font'>INFO: custom field '$configId' already exists.</span><br/>";
   }

   // add to codev_config_table
   Config::getInstance()->setValue($configId, $fieldId, Config::configType_int);
}

function createCustomFields() {
   // Mantis customFields types
   $mType_numeric = 1;

   $access_manager = 70;

   // default values, to be updated for each Field
   $attributes = array();
   $attributes["access_level_r"] = $access_manager;
   $attributes["access_level_rw"] = $access_manager;
   $attributes["require_report"] = 0;
   $attributes["display_report"] = 1;
   $attributes["require_update"] = 0;
   $attributes["display_update"] = 1;
   $attributes["require_resolved"] = 0;
   $attributes["display_resolved"] = 0;
   $attributes["require_closed"] = 0;
   $attributes["display_closed"] = 0;

   $defaultValue = 1;
   createCustomField("CodevTT_Manager EffortEstim", $mType_numeric, "customField_MgrEffortEstim", $attributes, $defaultValue);

}


// ================ MAIN =================
if(Tools::isConnectedUser()) {
   
   // 1) recuperer table de conversion ETA -> jour
   $ETA_balance = Config::getInstance()->getValue("ETA_balance");
   if (is_null($ETA_balance)) {
      echo "could not get ETA_Balance from DB<br>";
      exit;
   }
   // change value for 'None'
   $ETA_balance[10] = '0';
   
   var_dump($ETA_balance);
   echo "OK<br>";

   // 2) create new customField "CodevTT_Manager EffortEstim"
   createCustomFields();
   $mgrEffortEstimCustomField = Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim);
   if (is_null($mgrEffortEstimCustomField)) {
      echo "could not get mgrEffortEstimCustomField from DB<br>";
      exit;
   }
   echo "OK<br>";
   
   // 3) for each Issue, get ETA and create mgrEffortEstim value
/*
   $query = "SELECT id, eta FROM `mantis_bug_table`;";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $meeValue = $ETA_balance[$row->eta];
      echo "Issue $row->id eta=<$row->eta> mee=<$meeValue> <br>\n";

      $query2 = "INSERT INTO `mantis_custom_field_string_table`  (`field_id`, `bug_id`, `value`) VALUES ('".$mgrEffortEstimCustomField."','".$row->id."','".$meeValue."');";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         echo "<span style='color:red'>ERROR: Query FAILED $query2</span>";
         //exit;
      }
   }
*/   
   // 4) add MEE field to all projects having EffortEstim
   $query = "SELECT * FROM `mantis_custom_field_project_table` WHERE field_id =3";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $query2 = "INSERT INTO `mantis_custom_field_project_table` ".
              " (`field_id`, `project_id`, `sequence`) ".
              "VALUES ('".$mgrEffortEstimCustomField."','".$row->project_id."','".$row->sequence."');";
      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         echo "<span style='color:red'>ERROR: Query FAILED $query2</span>";
         //exit;
      } else {
         echo "Project $row->project_id : add mgrEffortEstim customField<br>";
      }
   }   
   echo "OK<br>";
   
   
   echo "done";
}

?>
