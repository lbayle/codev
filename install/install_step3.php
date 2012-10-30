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
   $serverIP = $_SERVER['SERVER_ADDR'];

   //read the source file
   $str = implode("\n", file(Install::FILENAME_GREASEMONKEY_SAMPLE));

   //replace tags
   $str = str_replace('@TAG_CODEVTT_IP@', $serverIP, $str);

   // write dest file
   $fp = fopen(Install::FILENAME_GREASEMONKEY, 'w');
   if (FALSE == $fp) {
      return "ERROR: creating file " . Install::FILENAME_GREASEMONKEY;
   }
   if (FALSE == fwrite($fp, $str, strlen($str))) {
      fclose($fp);
      return "ERROR: could not write to file " . Install::FILENAME_GREASEMONKEY;
   }
   fclose($fp);
   return NULL;
}

/**
 * insert CodevTT config in Mantis custom files.
 *
 * (add relationships, functions, etc.)
 *
 * Files to update:
 * custom_constant_inc.php
 * custom_strings_inc.php
 * custom_relationships_inc.php
 *
 * NOTE: needs write access in mantis directory
 */
function updateMantisCustomFiles() {
   global $logger;

   $mantisPath = Constants::$mantisPath;

   // write constants
   $myFile = "$mantisPath/custom_constant_inc.php";
   $fh = fopen($myFile, 'a');
   if (FALSE != $fh) {
      $content = file_get_contents(Install::FILENAME_CUSTOM_CONSTANT_CODEVTT, true);
      if (FALSE == $content) {
         echo "ERROR: Could not read file: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT . "</br>";
         $logger->error("Could not read file in append mode: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT);
      } else {
         fwrite($fh, $content);
      }
      fclose($fh);
   } else {
      echo "ERROR: Could not edit file: " . $myFile . "</br>";
      $logger->error("Could not open file in append mode: " . $myFile);
   }

   // write strings
   $myFile = "$mantisPath/custom_strings_inc.php";
   $fh = fopen($myFile, 'a');
   if (FALSE != $fh) {
      $content = file_get_contents(Install::FILENAME_CUSTOM_STRINGS_CODEVTT, true);
      if (FALSE == $content) {
         echo "ERROR: Could not read file: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT . "</br>";
         $logger->error("Could not read file in append mode: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT);
      } else {
         fwrite($fh, $content);
      }
      fclose($fh);
   } else {
      echo "ERROR: Could not edit file: " . $myFile . "</br>";
      $logger->error("Could not open file in append mode: " . $myFile);
   }

   // write relationships
   $myFile = "$mantisPath/custom_relationships_inc.php";
   $fh = fopen($myFile, 'a');
   if (FALSE != $fh) {
      $content = file_get_contents(Install::FILENAME_CUSTOM_RELATIONSHIPS_CODEVTT, true);
      if (FALSE == $content) {
         echo "ERROR: Could not read file: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT . "</br>";
         $logger->error("Could not read file in append mode: " . Install::FILENAME_CUSTOM_CONSTANT_CODEVTT);
      } else {
         fwrite($fh, $content);
      }
      fclose($fh);
   } else {
      echo "ERROR: Could not edit file: " . $myFile . "</br>";
      $logger->error("Could not open file in append mode: " . $myFile);
   }
}

/**
 * create SideTasks Project and assign N/A Job
 * @param string $projectName
 * @param string $projectDesc
 * @return int|string
 */
function createExternalTasksProject($projectName = "CodevTT_ExternalTasks", $projectDesc = "CoDevTT ExternalTasks Project") {
   // create project
   $projectid = Project::createExternalTasksProject($projectName);

   if (-1 != $projectid) {

      // --- update ExternalTasksProject in codev_config_table
      Config::getInstance()->setValue(Config::id_externalTasksProject, $projectid, Config::configType_int, $projectDesc);

      // --- assign ExternalTasksProject specific Job
      #REM: 'N/A' job_id = 1, created by SQL file
      Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);
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
   $teamId = Team::create($name, T_("CoDevTT Administrators team"), $leader_id, $today);

   if (-1 != $teamId) {

      // --- add to codev_config_table
      Config::getInstance()->setQuiet(true);
      Config::getInstance()->setValue(Config::id_adminTeamId, $teamId, Config::configType_int);
      Config::getInstance()->setQuiet(false);

      // add leader as member
      $adminTeam = TeamCache::getInstance()->getTeam($teamId);
      $adminTeam->addMember($leader_id, $today, Team::accessLevel_dev);

      // add default ExternalTasksProject
      $adminTeam->addExternalTasksProject();

      // add <team> SideTaskProject
      $stproj_id = $adminTeam->createSideTaskProject(T_("SideTasks") . " $name");

      if ($stproj_id < 0) {
         die("ERROR: SideTaskProject creation FAILED.<br/>\n");
      } else {
         $stproj = ProjectCache::getInstance()->getProject($stproj_id);

         $stproj->addCategoryInactivity(T_("Inactivity"));
         $stproj->addCategoryTools(T_("Tools"));

         $stproj->addIssueTools(T_("Mantis/CoDevTT administration"));
      }
   } else {
      echo "ERROR: $name team creation failed</br>";
   }
   return $teamId;
}

function setConfigItems() {
   #echo "DEBUG create Variable : ".Config::id_astreintesTaskList."<br/>";
   $desc = T_("The absence SideTasks considered as astreinte");
   Config::getInstance()->setValue(Config::id_astreintesTaskList, NULL, Config::configType_array, $desc);

   #echo "DEBUG create Variable : ".Config::id_ClientTeamid."<br/>";
   # TODO should be a table, there can be more than one client !
   $desc = T_("Custommer teamId");
   Config::getInstance()->setValue(Config::id_ClientTeamid, NULL, Config::configType_int, $desc);
}


/**
 * Add a new entry in MantisBT menu (main_menu_custom_options)
 *
 * ex: addCustomMenuItem('CodevTT', '../codev/index.php')
 *
 * @param string $name
 * @param string $url
 * @return string
 */
function addCustomMenuItem($name, $url) {
   $pos = '10'; // invariant

   // get current mantis custom menu entries
   $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
   $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>" . mysql_error() . "</span>");

   $serialized = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

   // add entry
   if ((NULL != $serialized) && ("" != $serialized)) {
      $menuItems = unserialize($serialized);
   } else {
      $menuItems = array();
   }

   $menuItems[] = array($name, $pos, $url);
   $newSerialized = serialize($menuItems);

   // update mantis menu
   if (NULL != $serialized) {
      $query = "UPDATE `mantis_config_table` SET value = '$newSerialized' " .
         "WHERE config_id = 'main_menu_custom_options'";
   } else {
      $query = "INSERT INTO `mantis_config_table` (`config_id`, `value`, `type`, `access_reqd`) " .
         "VALUES ('main_menu_custom_options', '$newSerialized', '3', '90');";
   }
   mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>" . mysql_error() . "</span>");

   return $newSerialized;
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

      #echo "DEBUG INSERT $fieldName --- query $query2 <br/>";

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
   $defaultValue = 1;
   $attributes["access_level_r"] = $access_manager;
   $attributes["access_level_rw"] = $access_manager;
   createCustomField(T_("CodevTT_Manager EffortEstim"), $mType_numeric, "customField_MgrEffortEstim", $attributes, $defaultValue);

   $attributes["access_level_r"] = $access_viewer;
   $attributes["access_level_rw"] = $access_reporter;
   createCustomField(T_("CodevTT_External ID"), $mType_string, "customField_ExtId", $attributes);
   createCustomField(T_("CodevTT_Dead Line"), $mType_date, "customField_deadLine", $attributes);

   $attributes["display_report"] = 0;
   createCustomField(T_("CodevTT_Aditional Effort"), $mType_numeric, "customField_addEffort", $attributes);

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


Config::getInstance()->setQuiet(true);

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "Step 1,2 finished";
   echo "<br/>";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Set path for .CSV reports (Excel)</li>";
   echo "<li>Create default tasks</li>";
   echo "<li>Create default jobs</li>";
   echo "<li>Add custom fields to existing projects</li>";
   echo "</ul>\n";
   echo "";
}

function displayForm($originPage, $defaultOutputDir, $checkReportsDirError,
                     $isJob1, $isJob2, $isJob3, $isJob4, $isJob5,
                     $job1, $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
                     $jobSupport_color, $jobNA_color, $job1_color, $job2_color, $job3_color, $job4_color, $job5_color,
                     $projectList,
                     $is_modified = "false") {

   echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";

   // ------ Reports
   echo "<h2>".T_("Path to output files (logs, reports, ...)")."</h2>\n";
   if (NULL != $checkReportsDirError) {
      if (FALSE == strstr($checkReportsDirError, T_("ERROR"))) {
         echo "<span class='success_font'>$checkReportsDirError</span><br/>\n";
      } else {
         echo "<span class='error_font'>$checkReportsDirError</span><br/>\n";
      }
   }
   echo "<code><input size='50' type='text' style='font-family: sans-serif' name='outputDir'  id='outputDir' value='$defaultOutputDir'></code></td>\n";
   echo "<input type=button value='".T_("Check")."' onClick='javascript: checkReportsDir()'>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";

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
   echo "  <br/>\n";
   echo "<h2>".T_("Default Jobs")."</h2>\n";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support' /></td>\n";
   echo "    <td>";
   echo "         <table class='invisible'><tr>";
   echo "            <td width='70'>$job_support</td>";
   echo "            <td><span class='help_font'>".T_("CoDev support management")."</span></td>";
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
   echo "  <tr>\n";
   $isChecked = $isJob1 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job1' id='cb_job1' /></td>\n";
   echo "    <td><input size='40' type='text' name='job1'  id='job1' value='$job1'></td>\n";
   echo "    <td>".T_("Color").": <input name='job1_color' id='job1_color' type='text' value='$job1_color' size='6' style='background-color: #$job1_color;' onblur='javascript: refresh()'></td>";
   echo "  </tr>\n";
   echo "  <tr>\n";
   $isChecked = $isJob2 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job2' id='cb_job2' /></td>\n";
   echo "    <td><input size='40' type='text' name='job2'  id='job2' value='$job2'></td>\n";
   echo "    <td>".T_("Color").": <input name='job2_color' id='job2_color' type='text' value='$job2_color' size='6' maxlength='6' style='background-color: #$job2_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   $isChecked = $isJob3 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job3' id='cb_job3' /></td>\n";
   echo "    <td><input size='40' type='text' name='job3'  id='job3' value='$job3'></td>\n";
   echo "    <td>".T_("Color").": <input name='job3_color' id='job3_color' type='text' value='$job3_color' size='6' maxlength='6' style='background-color: #$job3_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   $isChecked = $isJob4 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job4' id='cb_job4' /></td>\n";
   echo "    <td><input size='40' type='text' name='job4'  id='job4' value='$job4'></td>\n";
   echo "    <td>".T_("Color").": <input name='job4_color' id='job4_color' type='text' value='$job4_color' size='6' maxlength='6' style='background-color: #$job4_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   $isChecked = $isJob5 ? "CHECKED" : "";
   echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job5' id='cb_job5' /></td>\n";
   echo "    <td><input size='40' type='text' name='job5'  id='job5' value='$job5'></td>\n";
   echo "    <td>".T_("Color").": <input name='job5_color' id='job5_color' type='text' value='$job5_color' size='6' maxlength='6' style='background-color: #$job5_color;' onblur='javascript: refresh()'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   // ------ Add custom fields to existing projects
   echo "  <br/>\n";
   echo "<h2>".T_("Configure existing Projects")."</h2>\n";
   echo "<span class='help_font'>".T_("Select the projects to be managed with CoDev Timetracking")."</span><br/>\n";
   echo "  <br/>\n";

   echo "<select name='projects[]' multiple size='5'>\n";
   foreach ($projectList as $id => $name) {
      echo "<option selected value='$id'>$name</option>\n";
   }
   echo "</select>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";
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
            if($logger->isDebugEnabled()) {
               $logger->debug("project $id: ExternalTasksProject is excluded");
            }
            continue;
         }

         // exclude SideTasksProjects
         try {
            $p = ProjectCache::getInstance()->getProject($id);
            if ($p->isSideTasksProject()) {
               if($logger->isDebugEnabled()) {
                  $logger->debug("project $id: sideTaskProjects are excluded");
               }
               continue;
            }
         } catch (Exception $e) {
            // could not determinate, so the project should be included in the list
            if($logger->isDebugEnabled()) {
               $logger->debug("project $id: Unknown type, project included anyway.");
            }
            // nothing to do.
         }
         $smartyProjects[$id] = $name;
      }
      return $smartyProjects;
   } else {
      return NULL;
   }
}

// ================ MAIN =================
$originPage = "install_step3.php";

$adminTeamName = T_("CodevTT admin");
$adminTeamLeaderId = 1; // 1 is mantis administrator

#$defaultReportsDir = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";
$defaultReportsDir = "/tmp/codevtt";

$action               = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified          = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
$codevOutputDir      = isset($_POST['outputDir']) ? $_POST['outputDir'] : $defaultReportsDir;

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
if ("false" == $is_modified) {

   $isJob1 = true;;
   $isJob2 = true;;
   $isJob3 = true;;
   $isJob4 = true;;
   $isJob5 = true;;

} else {
   $isJob1   = $_POST['cb_job1'];
   $isJob2   = $_POST['cb_job2'];
   $isJob3   = $_POST['cb_job3'];
   $isJob4   = $_POST['cb_job4'];
   $isJob5   = $_POST['cb_job5'];
}

$task_otherActivity = isset($_POST['task_otherActivity']) ? $_POST['task_otherActivity'] : T_("Other external activity");
$task_leave     = isset($_POST['task_leave']) ? $_POST['task_leave'] : T_("Leave");
$job1           = isset($_POST['job1']) ? $_POST['job1'] : T_("Study of the existing");
$job2           = isset($_POST['job2']) ? $_POST['job2'] : T_("Analyse");
$job3           = isset($_POST['job3']) ? $_POST['job3'] : T_("Development");
$job4           = isset($_POST['job4']) ? $_POST['job4'] : T_("Tests");
$job5           = isset($_POST['job5']) ? $_POST['job5'] : T_("Documentation");
$job_support    = "Support";
$job_sideTasks  = "N/A";
$job1_color       = isset($_POST['job1_color']) ? $_POST['job1_color'] : "FFF494";
$job2_color       = isset($_POST['job2_color']) ? $_POST['job2_color'] : "FFCD85";
$job3_color       = isset($_POST['job3_color']) ? $_POST['job3_color'] : "C2DFFF";
$job4_color       = isset($_POST['job4_color']) ? $_POST['job4_color'] : "92C5FC";
$job5_color       = isset($_POST['job5_color']) ? $_POST['job5_color'] : "E0F57A";
$jobSupport_color = isset($_POST['jobSupport_color']) ? $_POST['jobSupport_color'] : "A8FFBD";
$jobNA_color      = isset($_POST['jobNA_color']) ? $_POST['jobNA_color'] : "A8FFBD";


$projectList = getProjectList();

$checkReportsDirError = NULL;
// ---
if ("checkReportsDir" == $action) {

   $checkReportsDirError = Tools::checkWriteAccess($codevOutputDir);


} else if ("proceedStep3" == $action) {

   echo "DEBUG 1/11 create Greasemonkey file<br/>";
   $errStr = createGreasemonkeyFile();
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
   }

   echo "DEBUG 2/11 create default Config variables<br/>";
   setConfigItems();

   echo "DEBUG 3/11 update Mantis custom files<br/>";

   updateMantisCustomFiles();

   echo "DEBUG 4/11 add CodevTT to Mantis menu<br/>";
   $tok = strtok($_SERVER["SCRIPT_NAME"], "/");
   addCustomMenuItem('CodevTT', '../'.$tok.'/index.php');  #  ../codev/index.php

   echo "DEBUG 5/11 create CodevTT Custom Fields<br/>";
   createCustomFields();

   echo "DEBUG 6/11 create ExternalTasks Project<br/>";
   $extproj_id = createExternalTasksProject(T_("CodevTT_ExternalTasks"), T_("CodevTT ExternalTasks Project"));

   $adminLeader = UserCache::getInstance()->getUser($adminTeamLeaderId);
   echo "DEBUG 7/11 createAdminTeam  with leader:  ".$adminLeader->getName()."<br/>";
   createAdminTeam($adminTeamName, $adminTeamLeaderId);

   // Set path for .CSV reports (Excel)
   echo "DEBUG 8/11 add CodevTT output directory<br/>";
   Constants::$codevOutputDir = $codevOutputDir;
   $retCode = Constants::writeConfigFile();
   if (FALSE == $retCode) {
      echo "<span class='error_font'>ERROR: could not add codevtt_output_dir to ".Constants::$config_file."</span><br/>";
      exit;
   }

   // Create default tasks
   echo "DEBUG 9/11 Create external tasks<br/>";
   $extproj = ProjectCache::getInstance()->getProject($extproj_id);

   // cat="[All Projects] General", status="closed"
   $extproj->addIssue(Project::cat_st_inactivity, $task_otherActivity, T_("Any external task, NOT referenced in any mantis project"), 90);

   // --- Create the 'Leave' task in ExternalTasks Project
   $LeaveTaskId = $extproj->addIssue(Project::cat_st_inactivity, $task_leave, T_("On holiday, sick leave, ..."), 90);
   Config::getInstance()->setValue(Config::id_externalTask_leave, $LeaveTaskId, Config::configType_int , T_("Common Leave task"));

   // Create default jobs
   // Note: Support & N/A jobs already created by SQL file
   // Note: N/A job association to ExternalTasksProject already done in Install::createExternalTasksProject()

   echo "DEBUG 10/11 Create default jobs<br/>";
   if ($isJob1) {
      Jobs::create($job1, Job::type_commonJob, $job1_color);
   }
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

   // Add custom fields to existing projects
   echo "DEBUG 11/11 Prepare existing projects<br/>";
   if(isset($_POST['projects']) && !empty($_POST['projects'])){
      $selectedProjects = $_POST['projects'];
      foreach($selectedProjects as $projectid){
         $project = ProjectCache::getInstance()->getProject($projectid);
         echo "DEBUG prepare project: ".$project->getName()."<br/>";
         Project::prepareProjectToCodev($projectid);
      }
   }

   echo "DEBUG done.<br/>";

   // load homepage
   echo ("<script type='text/javascript'> parent.location.replace('install_step4.php'); </script>");
}

// ----- DISPLAY PAGE
#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayForm($originPage, $codevOutputDir, $checkReportsDirError,
   $isJob1, $isJob2, $isJob3, $isJob4, $isJob5,
   $job1, $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
   $jobSupport_color, $jobNA_color, $job1_color, $job2_color, $job3_color, $job4_color, $job5_color,
   $projectList,
   $is_modified);

?>

</div>
