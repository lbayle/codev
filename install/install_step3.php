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

include_once('i18n/i18n.inc.php');

$page_name = T_("Install - Step 3");
require_once('install/install_header.inc.php');

Config::getInstance()->setQuiet(true);

require_once('install/install_menu.inc.php');

$logger = Logger::getLogger("install");
?>

<script type="text/javascript">
   function checkReportsDir() {
      document.forms["form1"].action.value="checkReportsDir";
      document.forms["form1"].is_modified.value= "true";
      document.forms["form1"].submit();
   }

   function refresh() {
      document.forms["form1"].action.value="refresh";
      document.forms["form1"].is_modified.value= "true";
      document.forms["form1"].submit();
   }

   function proceedStep3() {
      document.forms["form1"].action.value="proceedStep3";
      document.forms["form1"].is_modified.value= "true";
      document.forms["form1"].submit();
   }
</script>

<div id="content">

<?php

function createGreasemonkeyFile() {

   //read the source file
   $str = implode("\n", file(Install::FILENAME_GREASEMONKEY_SAMPLE));

   $str = str_replace("\n\n", "\n", $str);

   //replace tags
   $str = str_replace('@TAG_MANTIS_URL@', Constants::$mantisURL, $str);
   $str = str_replace('@TAG_CODEVTT_URL@', Constants::$codevURL, $str);

   // write dest file
   $fp = fopen(Install::FILENAME_GREASEMONKEY, 'w');
   if (FALSE == $fp) {
      throw new Exception ("ERROR: creating file " . Install::FILENAME_GREASEMONKEY);
   }
   if (FALSE == fwrite($fp, $str, strlen($str))) {
      fclose($fp);
      echo "<script type=\"text/javascript\">console.error(ERROR: could not write to file " . Install::FILENAME_GREASEMONKEY."\");</script>";
      return "ERROR: could not write to file " . Install::FILENAME_GREASEMONKEY;
      // no exception, because this is not blocking
   }
   fclose($fp);
   return NULL;
}

/**
 *
 * @return null
 * @throws Exception
 */
function createLog4phpFile() {

   //read the source file
   $str = implode("\n", file(Install::FILENAME_LOG4PHP_SAMPLE));

   $str = str_replace("\n\n", "\n", $str);

   //replace tags
   $str = str_replace('@TAG_CODEVTT_LOGFILE_FULLPATH@', Constants::$codevtt_logfile, $str);

   // create log4php.xml file
   if (FALSE === file_put_contents(Install::FILENAME_LOG4PHP, $str)) {
      throw new Exception ("could not create logger configuration file: ".Install::FILENAME_LOG4PHP);
   }
   return NULL;
}


/**
 * append content of $toAppendFile at the end of $destFile
 * $destFile is created if !exists

 * @param String $destFile file to edit
 * @param String $toAppendFile content to append
 * @param String $checkStr if str found in $destFile, work will not be done.
 * @throws Exception
 */
function appendToFile($destFile, $toAppendFile, $checkStr = NULL) {

   // write constants
   $content = @file_get_contents($toAppendFile, true);
   if (FALSE === $content) {
      throw new Exception ("Could not read file: " . $toAppendFile);
   } else {
      if (is_null($checkStr)) { $checkStr = $content; }

      // check if already added
      $destContent = @file_get_contents($destFile);
      if ((!file_exists($destFile)) ||
          (FALSE === strpos($destContent, $checkStr))) {
         $errStr = @file_put_contents($destFile, $content, FILE_APPEND);
         if (FALSE === $errStr) {
            throw new Exception ("Could not update file " . $destFile . ": ".$errStr);
         }
      }
   }
   // SUCCESS
   return NULL;
}

/**
 * insert CodevTT config in Mantis custom files.
 *
 * (add relationships, functions, etc.)
 *
 * Files to update:
 * custom_constants_inc.php
 * custom_strings_inc.php
 * custom_relationships_inc.php
 *
 * NOTE: needs write access in mantis directory
 */
function updateMantisCustomFiles() {

   $mantisPath = Constants::$mantisPath;
   $codevttURL = Constants::$codevURL;

   // --- check mantis version (config files have been moved in v1.3)
   if (is_dir($mantisPath.DIRECTORY_SEPARATOR.'config')) {
      // mantis v1.3 or higher
      $path_mantis_config = $mantisPath.DIRECTORY_SEPARATOR.'config';
   } else {
      // mantis 1.2
      $path_mantis_config = $mantisPath;
   }

   if(!is_writable($path_mantis_config)) {
      throw new Exception("Path to mantis config ". $path_mantis_config." is NOT writable");
   }

   appendToFile($path_mantis_config.'/custom_constants_inc.php',
                Install::FILENAME_CUSTOM_CONSTANTS_CODEVTT,
                'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY');

   appendToFile($path_mantis_config.'/custom_strings_inc.php',
                Install::FILENAME_CUSTOM_STRINGS_CODEVTT,
                's_rel_constrained_by');

   appendToFile($path_mantis_config.'/custom_relationships_inc.php',
                  Install::FILENAME_CUSTOM_RELATIONSHIPS_CODEVTT,
                  'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY');

   // add COdevTT to mantis menu
   $path_mantis_config_inc = $path_mantis_config.'/config_inc.php';
   if (is_writable($path_mantis_config_inc)) {

      $stringToAdd  = "# --- Add CodevTT to mantis main menu ---\n";
      $stringToAdd .= "array_push(\$g_main_menu_custom_options, array( 'CodevTT', NULL, '$codevttURL' ));\n";

      $errStr = @file_put_contents($path_mantis_config_inc, $stringToAdd, FILE_APPEND);
      if (FALSE === $errStr) {
         throw new Exception ("Could not update file " . $path_mantis_config_inc . ": ".$errStr);
      }
   }

   return NULL;
}

/**
 * create SideTasks Project and assign N/A Job
 * @param string $projectName
 * @param string $projectDesc
 * @return int|string
 * @throws Exception
 */
function createExternalTasksProject($projectName = "CodevTT_ExternalTasks", $projectDesc = "CodevTT ExternalTasks Project") {
   // create project
   $projectid = Project::getIdFormName($projectName);
   if (false === $projectid) {
      throw new Exception("CodevTT external tasks project creation failed");
   }
   if (-1 !== $projectid) {
      echo "<script type=\"text/javascript\">console.info(\"INFO: CodevTT external tasks project already exists: $projectName\");</script>";
   } else {
      $projectid = Project::createExternalTasksProject($projectName, $projectDesc);
   }
   return $projectid;
}


/**
 * create Admin team & add to codev_config_table
 * @param string $name
 * @param int $leader_id
 * @return int
 */
function createAdminTeam($name, $leader_id) {
   $now = time();
   $formatedDate = date("Y-m-d", $now);
   $today = Tools::date2timestamp($formatedDate);


   // create admin team
   $teamId = Team::getIdFromName($name);
   if (-1 === $teamId) {
      $teamId = Team::create($name, T_("CodevTT Administrators team"), $leader_id, $today);
   }

   if (-1 != $teamId) {

      // --- add to codev_config_table
      Config::getInstance()->setQuiet(true);
      Config::getInstance()->setValue(Config::id_adminTeamId, $teamId, Config::configType_int);
      Config::getInstance()->setQuiet(false);

      // add leader as member
      $adminTeam = TeamCache::getInstance()->getTeam($teamId);
      $adminTeam->addMember($leader_id, $today, Team::accessLevel_dev);
      $adminTeam->setEnabled(false);

      // add default ExternalTasksProject
      // TODO does Admin team need ExternalTasksProject ?
      $adminTeam->addExternalTasksProject();

      // NOTE: CodevTT Admin team does not need any side task project.

   } else {
      throw new Exception ("Admin Team creation failed: ".$name);
   }
   return $teamId;
}

function setConfigItems() {

   // nothing to do.
}


// get existing Mantis custom fields
$fieldList = array();

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

      echo "<script type=\"text/javascript\">console.warn(\"WARN: using default attributes for CustomField $fieldName\");</script>";
   }

   $query = "SELECT id, name FROM `mantis_custom_field_table`";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      throw new Exception ("create custom field FAILED");
   }
   while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
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

      #echo "DEBUG INSERT $fieldName --- query $query2 <br>";

      $result2 = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result2) {
         throw new Exception ("create custom field failed: $configId");
      }
      $fieldId = SqlWrapper::getInstance()->sql_insert_id();

      #echo "custom field '$configId' created.<br>";
   } else {
      echo "<script type=\"text/javascript\">console.info(\"INFO: custom field '$configId' already exists.\");</script>";
   }

   // add to codev_config_table
   Config::getInstance()->setValue($configId, $fieldId, Config::configType_int);
}

function createCustomFields($isCreateExtIdField = TRUE) {
   // Mantis customFields types
   $mType_string = 0;
   $mType_numeric = 1;
   $mType_enum = 3;
   $mType_list = 6;
   $mType_date = 8;

   $access_viewer = 10;
   $access_reporter = 25;
   $access_manager = 70;

   // default values, to be updated for each Field
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

   $attributes["require_report"] = 1;
   $attributes["display_report"] = 1;
   $defaultValue = 1;
   createCustomField(T_("CodevTT_EffortEstim"), $mType_numeric, "customField_effortEstim", $attributes, $defaultValue);
   $defaultValue = NULL;
   $possible_values = 'Bug|Task';
   createCustomField(T_("CodevTT_Type"), $mType_list, "customField_type", $attributes, $defaultValue, $possible_values);

   $attributes["require_report"] = 0;
   $attributes["display_report"] = 1;
   $defaultValue = 0;
   $attributes["access_level_r"] = $access_manager;
   $attributes["access_level_rw"] = $access_manager;
   createCustomField(T_("CodevTT_Manager EffortEstim"), $mType_numeric, "customField_MgrEffortEstim", $attributes, $defaultValue);

   $attributes["access_level_r"] = $access_viewer;
   $attributes["access_level_rw"] = $access_reporter;

   if ($isCreateExtIdField) {
      createCustomField(T_("CodevTT_External ID"), $mType_string, "customField_ExtId", $attributes);
   }

   createCustomField(T_("CodevTT_Deadline"), $mType_date, "customField_deadLine", $attributes);

   $attributes["display_report"] = 0;
   createCustomField(T_("CodevTT_Additional Effort"), $mType_numeric, "customField_addEffort", $attributes);

   $attributes["require_report"] = 0;
   $attributes["display_report"] = 0;
   $attributes["display_closed"] = 1;
   $attributes["display_resolved"] = 1;
   createCustomField(T_("CodevTT_Backlog"), $mType_numeric, "customField_backlog", $attributes);

   $attributes["require_report"] = 0;
   $attributes["display_report"] = 0;
   $attributes["require_resolved"] = 0;
   $attributes["require_closed"] = 0;
   #createCustomField(T_("CodevTT_Delivery ticket"),   $mType_string,  "customField_deliveryId", $attributes);  // CoDev FDJ custom
   createCustomField(T_("CodevTT_Delivery Date"), $mType_date, "customField_deliveryDate", $attributes);
}

/**
 * find the existing mantis customFields that could be used as ExternalID
 * (only strings and numeric)
 *
 * @return array customFieldID => name
 */
function getExtIdCustomFieldCandidates() {
   // Mantis customFields types
   $mType_string = 0;
   $mType_numeric = 1;

   $query = "SELECT * FROM `mantis_custom_field_table` WHERE `type` IN ($mType_string, $mType_numeric) ORDER BY name";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      throw new Exception("get ExtId candidates FAILED");
   }

   $candidates = array();
   while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $candidates["$row->id"] = $row->name;
   }
   return $candidates;
}

Config::getInstance()->setQuiet(true);


function displayForm($originPage, $defaultOutputDir, $checkReportsDirError,
                     $isJob2, $isJob3, $isJob4, $isJob5,
                     $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
                     $jobSupport_color, $jobNA_color, $job2_color, $job3_color, $job4_color, $job5_color,
                     $projectList, $groupExtID, $extIdCustomFieldCandidates, $extIdCustomField, $userList, $admin_id,
                     $statusList, $status_new, $status_feedback, $status_open, $status_closed,
                     $is_modified = "false") {

   checkMantisPluginDir();

   // ---
   echo "\n".'<div id="divErrMsg" style="display: none;">';
   echo "\n".'   <span class="error_font" style="font-size:larger; font-weight: bold;">Please fix the following errors and try again:</span><br><br>';
	echo "\n".'   <span class="error_font" id="errorMsg"></span><br>';
   echo "\n".'</div>';


   echo "\n<form id='form1' name='form1' method='post' action='$originPage' >\n";

   // ------ Reports
   echo "<h2>".T_("Path to output files")."</h2>\n";
   // T_("Note: <b>/var/local/codevtt</b> is a good location for this, but you'll need to create it first and give read/write access.")
   echo "<span class='help_font'>".T_("Path to log files and other temporary files.")."</span><br><br>\n";
   echo "<code><input size='50' type='text' style='font-family: sans-serif' name='outputDir'  id='outputDir' value='$defaultOutputDir'></code></td>\n";
   echo "<input type=button value='".T_("Check")."' onClick='javascript: checkReportsDir()'>\n";

   echo "&nbsp;&nbsp;&nbsp;&nbsp;";
   if (!is_null($checkReportsDirError)) {
      if (FALSE === strstr($checkReportsDirError, "SUCCESS")) {
         echo "<span class='error_font'>$checkReportsDirError</span>\n";
      } else {
         echo "<span class='success_font'>$checkReportsDirError</span>\n";
      }
   }

   echo "  <br>\n";
   echo "  <br>\n";

   // ------ Status
   echo "<h2>".T_("Workflow")."</h2>\n";
   echo "<span class='help_font'>".T_("Set equivalences in accordance to your Mantis workflow")."</span><br><br>\n";
   echo '<table class="invisible">';
   echo '<tr>';
   echo '<td>Status NEW</td>';
   echo '<td>';
   echo '<select id="status_new" name="status_new">'."<br>\n";
   foreach ($statusList as $statusid => $name) {
      if ($status_new == $statusid) {
         echo "<option value='$statusid' selected='selected'>$name</option>\n";
      } else {
         echo "<option value='$statusid'>$name</option>\n";
      }
   }
   echo '</select>';
   echo '</td>';
   echo '</tr>';
   echo '<tr>';
   echo '<td>Status FEEDBACK</td>';
   echo '<td>';
   echo '<select id="status_feedback" name="status_feedback">'."<br>\n";
   foreach ($statusList as $statusid => $name) {
      if ($status_feedback == $statusid) {
         echo "<option value='$statusid' selected='selected'>$name</option>\n";
      } else {
         echo "<option value='$statusid'>$name</option>\n";
      }
   }
   echo '</td>';
   echo '</tr>';
   echo '<tr>';
   echo '<td>Status OPEN</td>';
   echo '<td>';
   echo '<select id="status_open" name="status_open">'."<br>\n";
   foreach ($statusList as $statusid => $name) {
      if ($status_open == $statusid) {
         echo "<option value='$statusid' selected='selected'>$name</option>\n";
      } else {
         echo "<option value='$statusid'>$name</option>\n";
      }
   }
   echo '</td>';
   echo '</tr>';
   echo '<tr>';
   echo '<td>Status CLOSED</td>';
   echo '<td>';
   echo '<select id="status_closed" name="status_closed">'."<br>\n";
   foreach ($statusList as $statusid => $name) {
      if ($status_closed == $statusid) {
         echo "<option value='$statusid' selected='selected'>$name</option>\n";
      } else {
         echo "<option value='$statusid'>$name</option>\n";
      }
   }
   echo '</td>';
   echo '</tr>';
   echo '</table>';

   // ------ Administrator
   echo "<h2>".T_("CodevTT Administrator")."</h2>\n";

   echo '<select id="codevttAdmin" name="codevttAdmin">'."<br>\n";
   #echo '   <option value="0"> </option>'."\n";
   foreach ($userList as $userid => $name) {
      if ($admin_id == $userid) {
         echo "<option value='$userid' selected='selected'>$name</option>\n";
      } else {
         echo "<option value='$userid'>$name</option>\n";
      }
   }
   echo '</select>';
   echo "&nbsp;&nbsp;&nbsp;&nbsp;<span class='help_font'>".T_("More administrators can be defined later, for now you just need one.")."</span>\n";
	echo "<br><br>\n";

	// ------- External ID
   echo "<h2>".T_("Tasks external ID field")."</h2>";
   echo "<div align='left'>\n";
   echo "<script type='text/javascript'>\n";
   echo "
      function setCheckedValue(radioObj, newValue) {
         if(!radioObj)
            return;
         var radioLength = radioObj.length;
         if(radioLength == undefined) {
            radioObj.checked = (radioObj.value == newValue.toString());
            return;
         }
         for(var i = 0; i < radioLength; i++) {
            radioObj[i].checked = false;
            if(radioObj[i].value == newValue.toString()) {
               radioObj[i].checked = true;
            }
         }
      }
   ";
   echo "</script>";
   $checked = ('createExtID' === $groupExtID) ? 'CHECKED' : '';
   echo "<input type='radio' name='groupExtID' value='createExtID' $checked > ".T_("Create a new CustomField")."<br>\n";
   $checked = ('createExtID' !== $groupExtID) ? 'CHECKED' : '';
   echo "<input type='radio' name='groupExtID' value='existingExtID' $checked > ".T_("Use this CustomField :")."\n";
   echo '<select id="extIdCustomField" name="extIdCustomField" onChange="javascript:setCheckedValue(document.forms[\'form1\'].elements[\'groupExtID\'], \'existingExtID\');">'."\n";
   echo '   <option value="0"> </option>'."\n";
   foreach ($extIdCustomFieldCandidates as $fieldid => $fname) {
      if ($extIdCustomField == $fieldid) {
         echo "<option value='$fieldid'  selected='selected'>$fname</option>\n";
      } else {
         echo "<option value='$fieldid'>$fname</option>\n";
      }
   }
   echo '</select><br>';
   #echo "<input type='radio' name='groupExtID' value='noExtID' > ".T_("I don't need any").'<br>';
   echo "</div>\n";


   // ------ Default ExternalTasks
   /*
     echo "<h2>".T_("Default ExternalTasks")."</h2>\n";
     echo "<table class='invisible'>\n";
     echo "  <tr>\n";
     echo "    <td width='100'><input type=CHECKBOX  CHECKED DISABLED name='cb_taskLeave' id='cb_taskLeave'>".
          T_("Absence")."</input></td>\n";
     echo "    <td><input size='40' type='text' name='task_leave'  id='task_leave' value='$task_leave'></td>\n";
     echo "  </tr>\n";
   */
   // ------
   echo "  <br>\n";
   echo "<h2>".T_("Default Jobs")."</h2>\n";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support' /></td>\n";
   echo "    <td>";
   echo "         <table class='invisible'><tr>";
   echo "            <td width='70'>$job_support</td>";
   echo "            <td><span class='help_font'>".T_("CodevTT support management")."</span></td>";
   echo "         </tr></table>";
   echo "    </td>\n";
   echo "    <td>".T_("Color").": <input name='jobSupport_color' id='jobSupport_color' type='text' value='$jobSupport_color' size='6' maxlength='6' style='background-color: #$jobSupport_color;' onblur='javascript: refresh()'>";
   echo "   &nbsp;&nbsp;&nbsp;<a href='http://www.colorpicker.com' target='_blank' title='".T_("open a colorPicker in a new Tab")."'>ColorPicker</A></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support' /></td>\n";
   echo "    <td>";
   echo "         <table class='invisible'><tr>";
   echo "            <td width='70'>$job_sideTasks</td>";
   echo "            <td><span class='help_font'>".T_("Specific to SideTasks")."</span></td>";
   echo "         </tr></table>";
   echo "    </td>\n";
   echo "    <td>".T_("Color").":  <input name='jobNA_color' id='jobNA_color' type='text' value='$jobNA_color' size='6' maxlength='6' style='background-color: #$jobNA_color;' onblur='javascript: refresh()'></td>";
   echo "  </tr>\n";
   $isChecked = $isJob2 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job2' id='cb_job2' /></td>\n";
   echo '    <td><input size="40" type="text" name="job2"  id="job2" value="'.$job2.'"></td>'."\n";
   echo "    <td>".T_("Color").": <input name='job2_color' id='job2_color' type='text' value='$job2_color' size='6' maxlength='6' style='background-color: #$job2_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   $isChecked = $isJob3 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job3' id='cb_job3' /></td>\n";
   echo '    <td><input size="40" type="text" name="job3"  id="job3" value="'.$job3.'"></td>'."\n";
   echo "    <td>".T_("Color").": <input name='job3_color' id='job3_color' type='text' value='$job3_color' size='6' maxlength='6' style='background-color: #$job3_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   $isChecked = $isJob4 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job4' id='cb_job4' /></td>\n";
   echo '    <td><input size="40" type="text" name="job4"  id="job4" value="'.$job4.'"></td>'."\n";
   echo "    <td>".T_("Color").": <input name='job4_color' id='job4_color' type='text' value='$job4_color' size='6' maxlength='6' style='background-color: #$job4_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   $isChecked = $isJob5 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job5' id='cb_job5' /></td>\n";
   echo '    <td><input size="40" type="text" name="job5"  id="job5" value="'.$job5.'"></td>'."\n";
   echo "    <td>".T_("Color").": <input name='job5_color' id='job5_color' type='text' value='$job5_color' size='6' maxlength='6' style='background-color: #$job5_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   // ------ Add custom fields to existing projects
   #echo "  <br>\n";
   #echo "<h2>".T_("Configure existing Projects")."</h2>\n";
   #echo "<span class='help_font'>".T_("Select the projects to be managed with CodevTT")."</span><br>\n";
   #echo "  <br>\n";

   #echo "<select name='projects[]' multiple size='5'>\n";
   #foreach ($projectList as $id => $name) {
   #   echo "<option selected value='$id'>$name</option>\n";
   #}
   #echo "</select>\n";

   echo "  <br>\n";
   echo "  <br>\n";
   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 3")."' onClick='javascript: proceedStep3()'>\n";
   echo "</div>\n";

   // ------
   echo "<input type=hidden name=action      value=noAction>\n";
   echo "<input type=hidden name=is_modified value=$is_modified>\n";

   echo "</form>";
}

/**
 * get all existing projects, except ExternalTasksProject & SideTasksProjects
 * @return string[] : name[id]
 */
function getProjectList() {
   global $logger;

   $projects = Project::getProjects();
   if($projects != NULL) {
      $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $smartyProjects = array();
      foreach($projects as $id => $name) {
         // exclude ExternalTasksProject
         if ($extproj_id == $id) {
            echo "<script type=\"text/javascript\">console.log(\"   getProjectList - project $id: ExternalTasksProject is excluded\");</script>";
            continue;
         }

         // exclude SideTasksProjects
         try {
            $p = ProjectCache::getInstance()->getProject($id);
            if ($p->isSideTasksProject()) {
               echo "<script type=\"text/javascript\">console.log(\"   getProjectList - project $id: sideTaskProjects are excluded\");</script>";
               continue;
            }
         } catch (Exception $e) {
            // could not determinate, so the project should be included in the list
            echo "<script type=\"text/javascript\">console.log(\"   getProjectList - project $id: Unknown type, project included anyway\");</script>";
            // nothing to do.
         }
         $smartyProjects[$id] = $name;
      }
      return $smartyProjects;
   } else {
      return NULL;
   }
}

function checkMantisPluginDir() {
   $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';

   if (!is_writable($mantisPluginDir)) {
      echo '<br>';
      echo "<span class='warn_font'>WARN: <b>'" . $mantisPluginDir . "'</b> directory is <b>NOT writable</b>: Please give write access to user '<b>".exec('whoami')."</b>' if you want the Mantis plugin to be installed.</span><br>";
      echo '<br>';
      return false;
   }
   return true;
}

/**
 * copy plugin in mantis plugins directory
 * info: same functyion exists in update_codevtt.php
 *
 * @return NULL or error string
 */
function installMantisPlugin($pluginName, $isReplace=true) {
   try {

      $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';

      // load core/constant_inc.php and check for 'MANTIS_VERSION'
      $mantisVersion = Tools::getMantisVersion(Constants::$mantisPath);
      if (NULL != $mantisVersion) {
         if (version_compare($mantisVersion, '1.2', 'ge') && version_compare($mantisVersion, '1.3', 'lt')) {
            $mantis_plugin_version = 'mantis_1_2';

         } else if (version_compare($mantisVersion, '1.3', 'ge') && version_compare($mantisVersion, '2.0', 'lt')) {
            $mantis_plugin_version = 'mantis_1_3';

         } else if (version_compare($mantisVersion, '2.0', 'ge')) {
            $mantis_plugin_version = 'mantis_2_0';
         } else {
            return "ERROR unsupported mantis version : $mantisVersion";
         }
      } else {
         return "ERROR could not guess mantis version !";
      }
      $srcDir = Constants::$codevRootDir . DIRECTORY_SEPARATOR . 'mantis_plugin' . DIRECTORY_SEPARATOR . $mantis_plugin_version . DIRECTORY_SEPARATOR . $pluginName;
      $destDir = $mantisPluginDir . DIRECTORY_SEPARATOR . $pluginName;

      if (!is_writable($mantisPluginDir)) {
         return "ERROR Path to mantis plugins directory '" . $mantisPluginDir . "' is NOT writable: $pluginName plugin must be installed manualy.";
      }
      if (!is_dir($srcDir)) {
         return "ERROR mantis plugin directory '" . $srcDir . "' NOT found !";
      }
      
      // do not replace if already installed
      if (!$isReplace && is_dir($destDir)) {
         echo "<script type=\"text/javascript\">console.info(\"INFO Mantis $pluginName plugin is already installed\");</script>";
         return NULL;
      }

      // remove previous installed plugin
      if (is_writable($destDir)) {
         Tools::deleteDir($destDir);
      }

      // copy plugin
      if (is_dir($srcDir)) {
         $result = Tools::recurse_copy($srcDir, $destDir);
      } else {
         return "ERROR: plugin directory '" . $srcDir . "' NOT found: $pluginName plugin must be installed manualy";
      }

      if (!$result) {
         return "ERROR: mantis plugin installation failed: $pluginName plugin must be installed manualy";
      }
      
      // activate plugin
      $query = "INSERT INTO mantis_plugin_table (basename, enabled, protected, priority)".
              " SELECT * FROM (SELECT '$pluginName', '1', '0', '3') AS tmp".
              " WHERE NOT EXISTS (".
              " SELECT basename FROM mantis_plugin_table WHERE basename = '$pluginName') LIMIT 1;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return "WARNING: mantis $pluginName plugin must be activated manualy";
      }
      
   } catch (Exception $e) {
      echo "<script type=\"text/javascript\">console.error(\"ERROR mantis plugin installation failed: " . $e->getMessage()."\");</script>";
      return "ERROR: mantis $pluginName plugin installation failed: " . $e->getMessage();
   }
   return NULL;
}

/**
 * return a formatted list of mantis status
 * @return array
 */
function getStatusList() {
   $statusList = array();
   foreach (Constants::$statusNames as $id => $name) {
      $statusList[$id] = "$id - $name";
   }
   return $statusList;
}

// ================ MAIN =================
$originPage = "install_step3.php";

$adminTeamName = T_("CodevTT admin");
$defaultCodevttAdmin = 1; // 1 is mantis administrator

#$defaultReportsDir = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";
$defaultReportsDir = "/var/tmp/codevtt";

$action               = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified          = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
$codevOutputDir       = isset($_POST['outputDir']) ? $_POST['outputDir'] : $defaultReportsDir;
$adminTeamLeaderId    = isset($_POST['codevttAdmin']) ? $_POST['codevttAdmin'] : $defaultCodevttAdmin;

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
if ("false" == $is_modified) {

   $isJob2 = true;;
   $isJob3 = true;;
   $isJob4 = true;;
   $isJob5 = true;;

} else {
   $isJob2   = $_POST['cb_job2'];
   $isJob3   = $_POST['cb_job3'];
   $isJob4   = $_POST['cb_job4'];
   $isJob5   = $_POST['cb_job5'];
}

$task_otherActivity = isset($_POST['task_otherActivity']) ? $_POST['task_otherActivity'] : T_("Other external activity");
$task_leave     = isset($_POST['task_leave']) ? $_POST['task_leave'] : T_("Leave");
$task_sickleave = isset($_POST['task_sickleave']) ? $_POST['task_sickleave'] : T_("Sick Leave");
$job2           = Tools::getSecurePOSTStringValue('job2', T_("Analyse"));
$job3           = Tools::getSecurePOSTStringValue('job3', T_("Development"));
$job4           = Tools::getSecurePOSTStringValue('job4', T_("Tests"));
$job5           = Tools::getSecurePOSTStringValue('job5', T_("Documentation"));
$job_support    = "Support";
$job_sideTasks  = "N/A";
$job2_color       = isset($_POST['job2_color']) ? $_POST['job2_color'] : "FFCD85";
$job3_color       = isset($_POST['job3_color']) ? $_POST['job3_color'] : "C2DFFF";
$job4_color       = isset($_POST['job4_color']) ? $_POST['job4_color'] : "92C5FC";
$job5_color       = isset($_POST['job5_color']) ? $_POST['job5_color'] : "E0F57A";
$jobSupport_color = isset($_POST['jobSupport_color']) ? $_POST['jobSupport_color'] : "A8FFBD";
$jobNA_color      = isset($_POST['jobNA_color']) ? $_POST['jobNA_color'] : "A8FFBD";

$statusList = getStatusList();
$status_new = isset($_POST['status_new']) ? $_POST['status_new'] : 10;
$status_feedback = isset($_POST['status_feedback']) ? $_POST['status_feedback'] : 20;
$status_open = isset($_POST['status_open']) ? $_POST['status_open'] : 50;
$status_closed = isset($_POST['status_closed']) ? $_POST['status_closed'] : 90;

$admin_id = isset($_POST['codevttAdmin']) ? $_POST['codevttAdmin'] : 1;

$groupExtID = isset($_POST['groupExtID']) ? $_POST['groupExtID'] : 'createExtID';
$extIdCustomField = isset($_POST['extIdCustomField']) ? $_POST['extIdCustomField'] : 0;


#echo "<script type=\"text/javascript\">console.log(\"DEBUG getProjectList\");</script>";
$projectList = getProjectList();

echo "<script type=\"text/javascript\">console.log(\"DEBUG getUsers\");</script>";
$userList = User::getUsers();

#$checkReportsDirError = NULL;
$checkReportsDirError = Tools::checkWriteAccess($codevOutputDir);
if (NULL === $checkReportsDirError) { $checkReportsDirError = "SUCCESS !"; }

// ---
#if ("checkReportsDir" == $action) {

#   $checkReportsDirError = Tools::checkWriteAccess($codevOutputDir);
#   if (NULL === $checkReportsDirError) { $checkReportsDirError = "SUCCESS !"; }


// ----- DISPLAY PAGE
#echo "<hr align='left' width='20%'/>\n";

$extIdCustomFieldCandidates = getExtIdCustomFieldCandidates();

displayForm($originPage, $codevOutputDir, $checkReportsDirError,
   $isJob2, $isJob3, $isJob4, $isJob5,
   $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
   $jobSupport_color, $jobNA_color, $job2_color, $job3_color, $job4_color, $job5_color,
   $projectList, $groupExtID, $extIdCustomFieldCandidates, $extIdCustomField, $userList, $admin_id,
   $statusList, $status_new, $status_feedback, $status_open, $status_closed,
   $is_modified);


#} else if ("proceedStep3" == $action) {
if ("proceedStep3" == $action) {

   $errMsg = '';

   try {

      echo "<script type=\"text/javascript\">console.log(\"DEBUG create Greasemonkey file\");</script>";
      $errStr = createGreasemonkeyFile();
      if (NULL !== $errStr) {
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
         $errMsg .= $errStr.'<br>';
      }

      echo "<script type=\"text/javascript\">console.log(\"DEBUG update Mantis custom files\");</script>";
      updateMantisCustomFiles();

      echo "<script type=\"text/javascript\">console.log(\"DEBUG create CodevTT Custom Fields\");</script>";
      if ('createExtID' == $groupExtID) {
         $isCreateExtIdField = TRUE;
      } else {
         if ('0' != $extIdCustomField) {
            // add existing to codev_config_table
            Config::getInstance()->setValue("customField_ExtId", $extIdCustomField, Config::configType_int);
            $isCreateExtIdField = FALSE;
         } else {
            // if none selected, create one...
            $isCreateExtIdField = TRUE;
         }
      }
      createCustomFields($isCreateExtIdField);

      echo "<script type=\"text/javascript\">console.log(\"DEBUG create ExternalTasks Project\");</script>";
      $extproj_id = createExternalTasksProject(T_("CodevTT_ExternalTasks"), T_("CodevTT ExternalTasks Project"));

      $adminLeader = UserCache::getInstance()->getUser($adminTeamLeaderId);
      echo "<script type=\"text/javascript\">console.log(\"DEBUG createAdminTeam  with leader:  ".$adminLeader->getName()."\");</script>";
      createAdminTeam($adminTeamName, $adminTeamLeaderId);

      echo "<script type=\"text/javascript\">console.log(\"DEBUG update status list\");</script>";
      Constants::$status_new          = $status_new;
      Constants::$status_feedback     = $status_feedback;
      Constants::$status_open         = $status_open;
      Constants::$status_closed       = $status_closed;

      // Set path for .CSV reports (Excel)
      echo "<script type=\"text/javascript\">console.log(\"DEBUG add CodevTT output directory\");</script>";
      Constants::$codevOutputDir = $codevOutputDir;
      Constants::$codevtt_logfile = $codevOutputDir.'/logs/codevtt.log';

      $retCode = Constants::writeConfigFile();
      if (FALSE === $retCode) {
         throw new Exception("could not update config file: ".Constants::$config_file);
      }

      echo "<script type=\"text/javascript\">console.log(\"DEBUG create Logger configuration file\");</script>";
      createLog4phpFile();


      echo "<script type=\"text/javascript\">console.log(\"DEBUG create output directories (logs, reports, cache)\");</script>";
      $retCode = Tools::checkOutputDirectories();
      if (NULL !== $retCode) {
         throw new Exception(nl2br($retCode));
      }

      // Create default tasks
      echo "<script type=\"text/javascript\">console.log(\"DEBUG create external tasks\");</script>";
      $extproj = ProjectCache::getInstance()->getProject($extproj_id);
      $extTasksCatLeave = Config::getInstance()->getValue(Config::id_externalTasksCat_leave);
      $extTasksCatOther = Config::getInstance()->getValue(Config::id_externalTasksCat_otherInternal);

      // cat="OtherInternal", status="closed"
      $extproj->addIssue($extTasksCatOther, $task_otherActivity, T_("Any external task, NOT referenced in any Mantis project"), 90);

      // --- Create the 'Leave' task in ExternalTasks Project
      $extproj->addIssue($extTasksCatLeave, $task_leave, T_("On holiday, leave, ..."), 90);
      $extproj->addIssue($extTasksCatLeave, $task_sickleave, T_("Sick"), 90);

      // Create default jobs
      // Note: Support & N/A jobs already created by SQL file
      // Note: N/A job association to ExternalTasksProject already done in Install::createExternalTasksProject()

      echo "<script type=\"text/javascript\">console.log(\"DEBUG create default jobs\");</script>";
      if ($isJob2) {
         Jobs::create($job2, Job::type_commonJob, $job2_color);
      }
      if ($isJob3) {
         Jobs::create($job3, Job::type_commonJob, $job3_color);
      }
      if ($isJob4) {
         Jobs::create($job4, Job::type_commonJob, $job4_color);
      }
      if ($isJob5) {
         Jobs::create($job5, Job::type_commonJob, $job5_color);
      }

      // Set default Issue tooltip content
      echo "<script type=\"text/javascript\">console.log(\"DEBUG set default content for Issue tooltip\");</script>";
      $customField_type = Config::getInstance()->getValue(Config::id_customField_type);
      $backlogField = Config::getInstance()->getValue(Config::id_customField_backlog);
      $fieldList = array('project_id', 'category_id', 'custom_'.$customField_type, 'status',
          'codevtt_elapsed', 'custom_'.$backlogField, 'codevtt_drift');
      $serialized = serialize($fieldList);
      Config::setValue(Config::id_issueTooltipFields, $serialized, Config::configType_string, 'fields to be displayed in issue tooltip');

      // Add custom fields to existing projects
#      echo "<script type=\"text/javascript\">console.log(\"DEBUG prepare existing projects\");</script>";
#      if(isset($_POST['projects']) && !empty($_POST['projects'])){
#         $selectedProjects = $_POST['projects'];
#         foreach($selectedProjects as $projectid){
#            $project = ProjectCache::getInstance()->getProject($projectid);
#            echo "<script type=\"text/javascript\">console.log(\"   prepare project: ".$project->getName()."\");</script>";
#            Project::prepareProjectToCodev($projectid);
#         }
#      }

      echo "<script type=\"text/javascript\">console.log(\"DEBUG install Mantis plugin: CodevTT\");</script>";
      $errStr = installMantisPlugin('CodevTT', true);
      if (NULL !== $errStr) {
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
         $errMsg .= $errStr.'<br>';
      }
      echo "<script type=\"text/javascript\">console.log(\"DEBUG install Mantis plugin: FilterBugList\");</script>";
      $errStr = installMantisPlugin('FilterBugList', false);
      if (NULL !== $errStr) {
         echo "<script type=\"text/javascript\">console.error(\"$errStr\");</script>";
         $errMsg .= $errStr.'<br>';
      }


      // === consistency check !
      // these errors are not as severe as exceptions, they do not block 
      if ('' !== $errMsg) {
         echo '<script type="text/javascript">';
         echo '  document.getElementById("divErrMsg").style.display = "block";';
         echo "  document.getElementById(\"errorMsg\").innerHTML=\"".$errMsg."\";";
         echo '</script>';
         exit;
      }

      // FINISHED: load Step4
      #echo ("<script type='text/javascript'> alert('install done.'); </script>");
      echo ("<script type='text/javascript'> parent.location.replace('install_step4.php'); </script>");

   } catch (Exception $e) {
      echo "<script type=\"text/javascript\">console.error(\"FATAL ".$e->getMessage()."\");</script>";

      // === consistency check !
      echo '<script type="text/javascript">';
      echo '  document.getElementById("divErrMsg").style.display = "block";';
      echo "  document.getElementById(\"errorMsg\").innerHTML=\"".$e->getMessage()."\";";
      echo '</script>';
      exit;
   }


} // end proceedStep3


?>

</div>
