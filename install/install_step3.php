<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST[page_name] = T_("Install - Step 3");
   include 'install_header.inc.php';

   include 'install_menu.inc.php';
?>

<script language="JavaScript">

function setDatabaseInfo(){
   // check fields
   foundError = 0;
   msgString = "The following fields are missing:\n\n"

   if (0 == document.forms["databaseForm"].db_mantis_host.value)     { msgString += "Hostname\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_database.value)     { msgString += "Database\n"; ++foundError; }
   if (0 == document.forms["databaseForm"].db_mantis_user.value)     { msgString += "User\n"; ++foundError; }
   //if (0 == document.forms["databaseForm"].db_mantis_password.value)     { msgString += Password"\n"; ++foundError; }

   if (0 == foundError) {
     document.forms["databaseForm"].action.value="setDatabaseInfo";
     document.forms["databaseForm"].submit();
   } else {
     alert(msgString);
   }
 }

</script>

<div id="content">


<?php

include_once 'install.class.php';

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


function displayForm($originPage, $defaultReportsDir,
                     $isTaskAstreinte, $isTaskIncident1, $isTaskTools1,
                     $task_leave, $task_astreinte, $task_incident1, $task_tools1,
                     $isJob1, $isJob2, $isJob3, $isJob4,
                     $job1, $job2, $job3, $job4, $job_support, $job_sideTasks,
                     $is_modified = "false") {

   echo "<form id='reportsDirForm' name='reportsDirForm' method='post' action='$originPage' >\n";
   echo "<hr align='left' width='20%'/>\n";

   // ------ Reports
   echo "<h2>".T_("Path for .CSV reports")."</h2>\n";
   echo "<code><input size='50' type='text' style='font-family: sans-serif' name='reportsDir'  id='reportsDir' value='$defaultReportsDir'></code></td>\n";
   echo "<input type=button value='".T_("Check")."' onClick='javascript: checkReportsDir()'>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";

   // ------ Default SideTasks
  echo "<h2>".T_("Default SideTasks")."</h2>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td width='100'><input type=CHECKBOX  CHECKED DISABLED name='cb_taskLeave' id='cb_taskLeave'>".
       T_("Absence")."</input></td>\n";
  echo "    <td><input size='40' type='text' name='task_leave'  id='task_leave' value='$task_leave'></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  $isChecked = $isTaskAstreinte ? "CHECKED" : "";
  echo "    <td width='100'><input type=CHECKBOX $isChecked name='cb_taskAstreinte' id='cb_taskAstreinte'>".
       T_("Astreinte")."</input></td>\n";
  echo "    <td><input size='40' type='text' name='task_astreinte'  id='task_astreinte' value='$task_astreinte'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";

  $isChecked = $isTaskIncident1 ? "CHECKED" : "";
  echo "    <td width='100'><input type=CHECKBOX $isChecked name='cb_taskIncident1' id='cb_taskIncident1'>".
       T_("Incident")."</input></td>\n";
  echo "    <td><input size='40' type='text' name='task_incident1'  id='task_incident1' value='$task_incident1'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isTaskTools1 ? "CHECKED" : "";
  echo "    <td width='100'><input type=CHECKBOX $isChecked name='cb_taskTools1' id='cb_taskTools1'>".
       T_("Tools")."</input></td>\n";
  echo "    <td><input size='40' type='text' name='task_tools1'  id='task_tools1' value='$task_tools1'></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";

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
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td width='10'><input type=CHECKBOX CHECKED DISABLED name='cb_job_support' id='cb_support'></input></td>\n";
  echo "    <td>";
  echo "         <table class='invisible'><tr>";
  echo "            <td width='70'>$job_sideTasks</td>";
  echo "            <td><span class='help_font'>".T_("Specific to SideTasks")."</span></td>";
  echo "         </tr></table>";
  echo "    </td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  $isChecked = $isJob1 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job1' id='cb_job1'></input></td>\n";
  echo "    <td><input size='30' type='text' name='job1'  id='job1' value='$job1'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isJob2 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job2' id='cb_job2'></input></td>\n";
  echo "    <td><input size='30' type='text' name='job2'  id='job2' value='$job2'></td>\n";
  echo "  </tr>\n";
  $isChecked = $isJob3 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job3' id='cb_job3'></input></td>\n";
  echo "    <td><input size='30' type='text' name='job3'  id='job3' value='$job3'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isJob4 ? "CHECKED" : "";
  echo "    <td width='10'><input type=CHECKBOX  $isChecked name='cb_job4' id='cb_job4'></input></td>\n";
  echo "    <td><input size='30' type='text' name='job4'  id='job4' value='$job4'></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";



   echo "  <br/>\n";
   echo "<input type=button value='".T_("Proceed Step 3")."' onClick='javascript: proceedStep3()'>\n";

   // ------
   echo "<input type=hidden name=action      value=noAction>\n";
   echo "<input type=hidden name=is_modified value=$is_modified>\n";

   echo "</form>";
}

// ================ MAIN =================


$originPage = "install_step3.php";

$codevReportsDir      = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";


$action      = $_POST[action];
$is_modified = isset($_POST[is_modified]) ? $_POST[is_modified] : "false";


if ("false" == $is_modified) {

   $isTaskAstreinte   = true;
   $isTaskIncident1   = true;
   $isTaskTools1      = true;
   $isJob1 = true;;
   $isJob2 = true;;
   $isJob3 = true;;
   $isJob4 = true;;

} else {
}

$task_leave     = isset($_POST[task_leave]) ? $_POST[task_leave] : T_("(generic) Absence");
$task_astreinte = isset($_POST[task_astreinte]) ? $_POST[task_astreinte] : T_("(generic) Astreinte");
$task_incident1 = isset($_POST[task_incident1]) ? $_POST[task_incident1] : T_("(generic) Network is down");
$task_tools1    = isset($_POST[task_tools1]) ? $_POST[task_tools1] : T_("(generic) Mantis/CoDev administration");
$job1           = isset($_POST[job1]) ? $_POST[job1] : T_("Study of the existing");
$job2           = isset($_POST[job2]) ? $_POST[job2] : T_("Analysis");
$job3           = isset($_POST[job3]) ? $_POST[job3] : T_("Development");
$job4           = isset($_POST[job4]) ? $_POST[job4] : T_("Tests");
$job_support    = "Support";
$job_sideTasks  = "N/A";

displayStepInfo();

displayForm($originPage, $codevReportsDir,
            $isTaskAstreinte, $isTaskIncident1, $isTaskTools1,
            $task_leave, $task_astreinte, $task_incident1, $task_tools1,
            $isJob1, $isJob2, $isJob3, $isJob4,
            $job1, $job2, $job3, $job4, $job_support, $job_sideTasks,
            $is_modified);


if ("checkReportsDir" == $action) {

   $install = new Install();

   $install->checkReportsDir($codevReportsDir);


}

?>

