<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
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

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST['page_name'] = T_("Install - Step 3");
   include 'install_header.inc.php';

   include_once "mysql_connect.inc.php";

   include_once "config.class.php";
   Config::getInstance()->setQuiet(true);

   include_once "internal_config.inc.php";
   include_once "constants.php";

   include 'install_menu.inc.php';
?>

<script language="JavaScript">
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

include_once 'install.class.php';
include_once 'project.class.php';
include_once 'jobs.class.php';
#include_once 'config.class.php';

// ------------------------------------------------
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


// ------------------------------------------------
function displayForm($originPage, $defaultReportsDir, $checkReportsDirError,
                     $isJob1, $isJob2, $isJob3, $isJob4, $isJob5,
                     $job1, $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
                     $jobSupport_color, $jobNA_color, $job1_color, $job2_color, $job3_color, $job4_color, $job5_color,
                     $projectList,
                     $is_modified = "false") {

   echo "<form id='form1' name='form1' method='post' action='$originPage' >\n";
   echo "<hr align='left' width='20%'/>\n";

   // ------ Reports
   echo "<h2>".T_("Path for .CSV reports")."</h2>\n";
   if (NULL != $checkReportsDirError) {
   	  if (FALSE == strstr($checkReportsDirError, T_("ERROR"))) {
      	echo "<span class='success_font'>$checkReportsDirError</span><br/>\n";
   	  } else {
      	echo "<span class='error_font'>$checkReportsDirError</span><br/>\n";
   	  }
   }
   echo "<code><input size='50' type='text' style='font-family: sans-serif' name='reportsDir'  id='reportsDir' value='$defaultReportsDir'></code></td>\n";
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
  echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support'></input></td>\n";
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
  echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support'></input></td>\n";
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
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job1' id='cb_job1'></input></td>\n";
  echo "    <td><input size='40' type='text' name='job1'  id='job1' value='$job1'></td>\n";
  echo "    <td>".T_("Color").": <input name='job1_color' id='job1_color' type='text' value='$job1_color' size='6' style='background-color: #$job1_color;' onblur='javascript: refresh()'></td>";

  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isJob2 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job2' id='cb_job2'></input></td>\n";
  echo "    <td><input size='40' type='text' name='job2'  id='job2' value='$job2'></td>\n";
  echo "    <td>".T_("Color").": <input name='job2_color' id='job2_color' type='text' value='$job2_color' size='6' maxlength='6' style='background-color: #$job2_color;' onblur='javascript: refresh()'></td>\n";
  echo "  </tr>\n";
  $isChecked = $isJob3 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job3' id='cb_job3'></input></td>\n";
  echo "    <td><input size='40' type='text' name='job3'  id='job3' value='$job3'></td>\n";
  echo "    <td>".T_("Color").": <input name='job3_color' id='job3_color' type='text' value='$job3_color' size='6' maxlength='6' style='background-color: #$job3_color;' onblur='javascript: refresh()'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isJob4 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job4' id='cb_job4'></input></td>\n";
  echo "    <td><input size='40' type='text' name='job4'  id='job4' value='$job4'></td>\n";
  echo "    <td>".T_("Color").": <input name='job4_color' id='job4_color' type='text' value='$job4_color' size='6' maxlength='6' style='background-color: #$job4_color;' onblur='javascript: refresh()'></td>\n";
  echo "  </tr>\n";
  $isChecked = $isJob5 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job5' id='cb_job5'></input></td>\n";
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


// ------------------------------------------------
/**
 * get all existing projects, except ExternalTasksProject & SideTasksProjects
 */
function getProjectList() {
	$projectList = array();

	$extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);

	$query  = "SELECT id, name ".
                "FROM `mantis_project_table` ";
                #"WHERE mantis_project_table.id = $this->id ";

	$result = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
   	$p = ProjectCache::getInstance()->getProject($row->id);
   	if (($extproj_id != $row->id) && (!$p->isSideTasksProject())) {
          $projectList[$row->id] = $row->name;
   	}
   }
	return $projectList;
}

// ================ MAIN =================


$originPage = "install_step3.php";

#$defaultReportsDir = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";
$defaultReportsDir = "/tmp/codevReports";

$action               = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified          = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
$codevReportsDir      = isset($_POST['reportsDir']) ? $_POST['reportsDir'] : $defaultReportsDir;


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

$task_otherActivity = isset($_POST['task_otherActivity']) ? $_POST['task_otherActivity'] : T_("(generic) other external activity");
$job1           = isset($_POST['job1']) ? $_POST['job1'] : T_("Study of the existing");
$job2           = isset($_POST['job2']) ? $_POST['job2'] : T_("Impact Analysis");
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

   $checkReportsDirError = Install::checkWriteAccess($codevReportsDir);


} else if ("proceedStep3" == $action) {

    // Set path for .CSV reports (Excel)
    echo "DEBUG 1/3 add codevReportsDir<br/>";
    $desc = T_("path for .CSV reports");
    Config::getInstance()->setValue(Config::id_codevReportsDir, $codevReportsDir, Config::configType_string , $desc);

    // Create default tasks
    echo "DEBUG 2/3 Create external tasks<br/>";
    $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
    $extproj = ProjectCache::getInstance()->getProject($extproj_id);

    // cat="[All Projects] General", status="closed"
    $extproj->addIssue(1, $task_otherActivity, T_("Any external task, NOT referenced in any mantis project"), 90);

    // Create default jobs
    // Note: Support & N/A jobs already created by SQL file
    // Note: N/A job association to ExternalTasksProject already done in Install::createExternalTasksProject()

    echo "DEBUG 3/3 Create default jobs<br/>";
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
    if(isset($_POST['projects']) && !empty($_POST['projects'])){
       $selectedProjects = $_POST['projects'];
       foreach($selectedProjects as $projectid){
         $project = ProjectCache::getInstance()->getProject($projectid);
       	echo "DEBUG prepare project: ".$project->name."<br/>";
         $project->prepareProjectToCodev();
       }
    }

    echo "DEBUG done.<br/>";

    // load next step page
   #echo ("<script> parent.location.replace('$nextPage'); </script>");
}




// ----- DISPLAY PAGE
displayStepInfo();

displayForm($originPage, $codevReportsDir, $checkReportsDirError,
            $isJob1, $isJob2, $isJob3, $isJob4, $isJob5,
            $job1, $job2, $job3, $job4, $job5, $job_support, $job_sideTasks,
            $jobSupport_color, $jobNA_color, $job1_color, $job2_color, $job3_color, $job4_color, $job5_color,
            $projectList,
            $is_modified);

?>

</div>
