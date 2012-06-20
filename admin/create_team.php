<?php
include_once('../include/session.inc.php');

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

include_once '../path.inc.php';

include_once 'i18n.inc.php';

$page_name = T_("CodevTT Administration : Team Creation");
require_once 'header.inc.php';
require_once 'login.inc.php';
require_once 'menu.inc.php';
?>

<br/>
<?php include 'menu_admin.inc.php'; ?>

<script language="JavaScript">

function addTeam(){
   // check fields
   foundError = 0;
   msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n";

   if (0 == document.forms["teamCreationForm"].team_name.value)     { msgString += "Team Name\n"; ++foundError; }
   if (0 == document.forms["teamCreationForm"].team_desc.value)     { msgString += "Team Description\n"; ++foundError; }
   if (0 == document.forms["teamCreationForm"].teamleader_id.value) { msgString += "Team Leader\n";  ++foundError; }

   if (0 == foundError) {
     document.forms["teamCreationForm"].action.value="addTeam";
     document.forms["teamCreationForm"].submit();
   } else {
     alert(msgString);
   }
 }

function updateTeamCreationForm(onLoadFocus) {

   document.forms["teamCreationForm"].action.value="updateTeamCreationForm";
   document.forms["teamCreationForm"].is_modified.value= "true";
   document.forms["teamCreationForm"].on_load_focus.value = onLoadFocus;
   document.forms["teamCreationForm"].submit();
}

</script>

<div id="content">


<?php
include_once "project.class.php";
include_once "user.class.php";
include_once "team.class.php";

$logger = Logger::getLogger("create_team");

// -----------------------------
/**
 *
 * @param unknown_type $isCreateSTProj // true: checkbox CHECKED
 */
function displayCreateTeamForm($team_name, $teamleader_id, $team_desc,
                               $isCreateSTProj, $stproj_name,
                               $isCatIncident, $isCatTools, $isCatOther,
                               $isTaskLeave, $isTaskOnDuty, $isTaskProjManagement, $isTaskMeeting, $isTaskIncident, $isTaskTools, $isTaskOther,
                               $task_leave, $task_onDuty, $task_projManagement, $task_meeting, $task_incident, $task_tools, $task_other1,
                               $is_modified = "false"
                               ) {
  global $logger;

  echo "<form action='create_team.php' method='post' name='teamCreationForm'>\n";

  // ----------- Team Info
  echo "<hr align='left' width='20%'/>\n";
  echo "<h2>".T_("Team Info")."</h2>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td>".T_("Team Name")."</td>\n";
  echo "    <td><input size='30' value='$team_name' name='team_name' type='text' id='team_name' onblur=\"javascript: updateTeamCreationForm('document.teamCreationForm.team_desc')\"> <span style='color:red'>*</span></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td>".T_("Description")."</td>\n";
  echo "    <td><input size='100' value='$team_desc' name='team_desc' type='text' id='team_desc'> <span style='color:red'>*</span></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td>".T_("Team Leader")."</td>\n";
  echo "    <td>\n";
  $query = "SELECT DISTINCT id, username, realname FROM `mantis_user_table` ORDER BY username";
  echo "      <select name='teamleader_id'>\n";
  echo "        <option value='0'></option>\n";
  $result = mysql_query($query);
  if (!$result) {
     $logger->error("Query FAILED: $query");
     $logger->error(mysql_error());
     echo "<span style='color:red'>ERROR: Query FAILED</span>";
     exit;
  }

  while($row = mysql_fetch_object($result))
  {
      if ($row->id == $teamleader_id) {
         echo "        <option selected value='".$row->id."'>$row->username</option>\n";
      } else {
         echo "        <option value='".$row->id."'>$row->username</option>\n";
      }
  }
  echo "      </select>\n";
  echo "      <span style='color:red'>*</span>";
  echo "    </td>\n";
  echo "  </tr>\n";
  echo "</table>\n";

  echo "  <br/>\n";
  echo "  <br/>\n";
  // ----------- associated SideTaskProject

  echo "<hr align='left' width='20%'/>\n";
  echo "<h2>".T_("SideTasks Project")."</h2>\n";

  echo "<input type=CHECKBOX CHECKED DISABLED name='cb_commonSideTaskProj' id='cb_commonSideTaskProj' >".
       T_("Add common ExternalTasks Project")."</input> <span style='color:red'>*</span><br/>\n";

  echo "  <br/>\n";

  $isChecked = $isCreateSTProj ? "CHECKED" : "";
  echo "<input type=CHECKBOX $isChecked name='cb_createSideTaskProj' id='cb_createSideTaskProj' onChange=\"javascript: updateTeamCreationForm('document.teamCreationForm.cb_createSideTaskProj')\" >".
       T_("Create specific SideTask Project")."</input>\n";


  $isDisplayed = $isCreateSTProj ? "" : "display:none";
  echo "<div style='$isDisplayed'>\n";
  echo "<ul>\n";
  echo "<li><b>".T_("Project Name")."</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <input size='30' value='$stproj_name' type='text' name='stproj_name'  id='stproj_name'> <span style='color:red'>*</span></li>\n";

  echo "  <br/>\n";

  echo "<li><b>".T_("Categories")."</b><br/>\n";
  echo "<table class='invisible'>\n";

  echo "  <tr>\n";
  echo "    <td width='150'><input type=CHECKBOX CHECKED DISABLED name='cb_catInactivity' id='cb_catInactivity'>".T_("Inactivity")."</span></input>\n";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Holidays, Leave, On Duty").")</span></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  echo "    <td width='150'><input type=CHECKBOX CHECKED DISABLED name='cb_catProjManagement' id='cb_catProjManagement'>".T_("Project Management")."</input>\n";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Meeting, Pre-sales, ProjectManagement, ...").")</span></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  echo "    <td width='150'><input type=CHECKBOX CHECKED DISABLED name='cb_catMngtProvision' id='cb_catMngtProvision'>".T_("Effort Provision")."</input>\n";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Budget for Management,Garantie,Risk...").")</span></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  $isChecked = $isCatIncident ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_catIncident' id='cb_catIncident'>".
       T_("Incident")."</input></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Server or Platform down, ...").")</span></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  $isChecked = $isCatTools ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked  name='cb_catTools' id='cb_catTools'>".
       T_("Tools")."</input></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Sys. Admin, Scritps, ...").")</span></td>\n";
  echo "  </tr>\n";

  echo "  <tr>\n";
  $isChecked = $isCatOther ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_catOther' id='cb_catOther' >".
       T_("Team Workshop")."</input></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("Support, Doc, Training, Wiki, ...").")</span></td>\n";
  echo "  </tr>\n";

  echo "</table>\n";

  echo "  <br/>\n";


  echo "</li>\n";
  echo "<li><b>".T_("Custom Fields")."</b><br/>\n";

  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td width='150'><input type=CHECKBOX CHECKED DISABLED name='cb_customFields' id='cb_customFields'>".T_("CodevTT custom fields")."</input>\n";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "    <td><span class='help_font'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(".T_("EffortEstim, AddEffort, Remaining, DeadLine, DeliveryDate").")</span></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";
  echo "  <br/>\n";
  echo "</li>\n";
  echo "<li><b>".T_("SideTasks")."</b><br/>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";

  // ---
  echo "    <td width='150'><input type=CHECKBOX CHECKED DISABLED name='cb_taskLeave' id='cb_taskLeave'>".
       T_("Inactivity")." (".T_("leave").")</input>";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "    <td><input size='100' type='text' name='task_leave'  id='task_leave' value='$task_leave'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
/*
  $isChecked = $isTaskOnDuty ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskOnDuty' id='cb_taskOnDuty'>".
       T_("Inactivity")." (".T_("on duty").")</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_onDuty'  id='task_onDuty' value='$task_onDuty'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
*/
  $isChecked = $isTaskProjManagement ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskProjManagement' id='cb_taskProjManagement'>".
       T_("Project Management")."</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_projManagement'  id='task_projManagement' value='$task_projManagement'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";

  echo "  <tr>\n";
  $isChecked = $isTaskMeeting ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskMeeting' id='cb_taskMeeting'>".
       T_("Project Management")."</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_meeting'  id='task_meeting' value='$task_meeting'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";

  $isChecked = $isTaskIncident ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskIncident' id='cb_taskIncident'>".
       T_("Incident")."</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_incident'  id='task_incident' value='$task_incident'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isTaskTools ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskTools' id='cb_taskTools'>".
       T_("Tools")."</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_tools'  id='task_tools' value='$task_tools'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  $isChecked = $isTaskOther ? "CHECKED" : "";
  echo "    <td width='150'><input type=CHECKBOX $isChecked name='cb_taskOther' id='cb_taskOther'>".
       T_("Team Workshop")."</input></td>\n";
  echo "    <td><input size='100' type='text' name='task_other1'  id='task_other1' value='$task_other1'></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";
  echo "</li>\n";
  echo "</ul>\n";

  echo "</div>\n"; # display:none


  echo "  <br/>\n";
  echo "  <br/>\n";
  echo "<input type=button value='".T_("Create")."' onClick='javascript: addTeam()'>\n";

  echo "<input type=hidden name=action        value=noAction>\n";
  echo "<input type=hidden name=is_modified   value=$is_modified>\n";
  echo "<input type=hidden name=on_load_focus value='team_name'>\n";

  echo("</form>\n");
}







// ================ MAIN =================


$cat_projManagement = T_("Project Management");
$cat_EffortProvision = T_("Provision");
$cat_inactivity     = T_("Inactivity");
$cat_incident       = T_("Incident");
$cat_tools          = T_("Tools");
$cat_other          = T_("Team Workshop");

$teamSideTaskProjectName = T_("SideTasks")." my_team";

// ---- if not codev admin then stop now.
// REM: who is allowed to create a new team ? anyone ?
#$admin_teamid = Config::getInstance()->getValue(Config::id_adminTeamId);
#$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
#if (false == $session_user->isTeamDeveloper($admin_teamid)) {
#  echo ("Sorry, you need to be Codev Administrator to access this page.");
#  exit;
#}

$action      = isset($_POST['action']) ? $_POST['action'] : '';
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";


// Form user selections
$team_name = isset($_POST['team_name']) ? $_POST['team_name'] : "";
$team_desc = isset($_POST['team_desc']) ? $_POST['team_desc'] : "";
$teamleader_id = isset($_POST['teamleader_id']) ? $_POST['teamleader_id'] : "";

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
if ("false" == $is_modified) {
   $isCreateSTProj       = true;
   $isCatIncident        = true;
   $isCatTools           = true;
   $isCatOther           = true;
   $isTaskProjManagement = true;
   $isTaskLeave          = true;
   $isTaskOnDuty         = false;
   $isTaskMeeting        = true;
   $isTaskIncident       = false;
   $isTaskTools          = false;
   $isTaskOther          = false;

   $stproj_name = $teamSideTaskProjectName;

} else {
   $isCreateSTProj       = $_POST['cb_createSideTaskProj'];
   $isCatIncident        = $_POST['cb_catIncident'];
   $isCatTools           = $_POST['cb_catTools'];
   $isCatOther           = $_POST['cb_catOther'];
   $isTaskLeave          = $_POST['cb_taskLeave'];
   $isTaskOnDuty         = $_POST['cb_taskOnDuty'];
   $isTaskProjManagement = $_POST['cb_taskProjManagement'];
   $isTaskMeeting        = $_POST['cb_taskMeeting'];
   $isTaskIncident       = $_POST['cb_taskIncident'];
   $isTaskTools          = $_POST['cb_taskTools'];
   $isTaskOther          = $_POST['cb_taskOther'];

   $stproj_name = ("" == $team_name) ? $teamSideTaskProjectName : T_("SideTasks")." $team_name";
}

$task_leave = isset($_POST['task_leave']) ? $_POST['task_leave'] : T_("(generic) Leave");
$task_onDuty = isset($_POST['task_onDuty']) ? $_POST['task_onDuty'] : T_("(generic) Leave but on duty");
$task_projManagement = isset($_POST['task_projManagement']) ? $_POST['task_projManagement'] : T_("(generic) Project Management");
$task_meeting = isset($_POST['task_meeting']) ? $_POST['task_meeting'] : T_("(generic) Meeting");
$task_incident = isset($_POST['task_incident']) ? $_POST['task_incident'] : T_("(generic) Network is down");
$task_tools = isset($_POST['task_tools']) ? $_POST['task_tools'] : T_("(generic) Compilation Scripts");
$task_other1 = isset($_POST['task_other1']) ? $_POST['task_other1'] : T_("(generic) Update team WIKI");


#unset($_SESSION['teamid']);

if ("addTeam" == $action) {
	#echo T_("Create")." $team_name !<br/>";


	$formatedDate  = date("Y-m-d", time());
	$now = date2timestamp($formatedDate);

	// 1) --- create new Team
   $teamid = Team::create($team_name, $team_desc, $teamleader_id, $now);
   #echo "teamId = $teamid !<br/>";

   if ($teamid > 0) {

      $team = new Team($teamid);

      // 2) --- add ExternalTasksProject
      $team->addExternalTasksProject();


      if ($isCreateSTProj) {

      	// 3) --- add <team> SideTaskProject
      	$stproj_id = $team->createSideTaskProject($stproj_name);
      	if ($stproj_id < 0) {
            $logger->error("SideTaskProject creation FAILED");
            echo "<span style='color:red'>ERROR: SideTaskProject creation FAILED</span>";
            exit;
      	} else {
            $stproj = ProjectCache::getInstance()->getProject($stproj_id);

            // 4) --- add SideTaskProject Categories
      	   $stproj->addCategoryProjManagement($cat_projManagement);
      	   $stproj->addCategoryMngtProvision($cat_EffortProvision);

            $stproj->addCategoryInactivity($cat_inactivity);

      	   if ($isCatIncident) {
               $stproj->addCategoryIncident($cat_incident);
	         }
            if ($isCatTools) {
               $stproj->addCategoryTools($cat_tools);
            }
            if ($isCatOther) {
               $stproj->addCategoryWorkshop($cat_other);
            }

            // 5) --- add SideTaskProject default SideTasks

            $stproj->addIssueInactivity($task_leave);

            if ($isTaskProjManagement) {
        	      $stproj->addIssueProjManagement($task_projManagement);
            }
            if ($isTaskMeeting) {
               $stproj->addIssueProjManagement($task_meeting);
            }
            if ($isTaskIncident) {
               $stproj->addIssueIncident($task_incident);
            }
            if ($isTaskTools) {
               $stproj->addIssueTools($task_tools);
            }
            if ($isTaskOther) {
               $stproj->addIssueWorkshop($task_other1);
            }

      	}
     }
	 // 6) --- open EditTeam Page
	 $_SESSION['teamid'] = $teamid;
	 echo ("<script> parent.location.replace('./edit_team.php'); </script>");

   }

} elseif ("editTeam" == $action) {
   echo T_("Create and go to Edit")."<br/>";
   //echo ("<script> parent.location.replace('./edit_team.php'); </script>");

}else if ("noAction" == $action) {
    echo "browserRefresh<br/>";
} # else {

	// display form
   displayCreateTeamForm($team_name, $teamleader_id, $team_desc,
                         $isCreateSTProj, $stproj_name,
                         $isCatIncident, $isCatTools, $isCatOther,
                         $isTaskLeave, $isTaskOnDuty, $isTaskProjManagement, $isTaskMeeting, $isTaskIncident, $isTaskTools, $isTaskOther,
                         $task_leave, $task_onDuty, $task_projManagement, $task_meeting, $task_incident, $task_tools, $task_other1,
                         $is_modified);
#}


?>
</div>

<?php include 'footer.inc.php'; ?>
