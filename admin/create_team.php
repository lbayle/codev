<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include_once '../path.inc.php'; ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
} 
?>

<?php
   include_once 'i18n.inc.php';
   $_POST[page_name] = T_("CoDev Administration : Team Creation"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_admin.inc.php'; ?>

<script language="JavaScript">

function addTeam(){
   // check fields
   foundError = 0;
   msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n"
       
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



</script>

<div id="content">


<?php 
include_once "constants.php";
include_once "tools.php";
include_once "user.class.php";
include_once "team.class.php";


// -----------------------------
function displayCreateTeamForm() {

  echo "<form action='create_team.php' method='post' name='teamCreationForm'>\n";
  
  
  // ----------- Team Info
  echo "<hr align='left' width='20%'/>\n";
  echo "<h2>".T_("Team Info")."</h2>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td>".T_("Team Name")."</td>\n";
  echo "    <td><input size='30' name='team_name' type='text' id='team_name'> <span style='color:red'>*</span></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td>".T_("Description")."</td>\n";
  echo "    <td><input size='100' name='team_desc' type='text' id='team_desc'> <span style='color:red'>*</span></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n"; 
  echo "    <td>".T_("Team Leader")."</td>\n";
  echo "    <td>\n";
  $query = "SELECT DISTINCT id, username, realname FROM `mantis_user_table` ORDER BY username";
  echo "      <select name='teamleader_id'>\n";
  echo "        <option value='0'></option>\n";
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {  echo "        <option value='".$row->id."'>$row->username</option>\n";
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
  echo "<input type=CHECKBOX CHECKED name='cb_createSideTaskProj' id='cb_createSideTaskProj'>".T_("Create Associated SideTask Project")."</input>\n";
  
  
  echo "<ul>\n";
  echo "<li>".T_("Project Name")."  <input size='30' type='text' name='stproj_name'  id='stproj_name'> <span style='color:red'>*</span></li>\n";
  
  echo "  <br/>\n";

  echo "<li>".T_("Categories")."<br/>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED DISABLED name='cb_catProjManagement' id='cb_catProjManagement'>".T_("ProjectManagement")."</input></td>\n";
  echo "    <td><input size='30' type='text' value='".T_("Project Management")."' name='cat_projManagement'  id='cat_projManagement'>\n";
  echo "    <span style='color:red'>*</span></td>\n";
  echo "  </tr>\n";
/*
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED name='cb_catAbsence' id='cb_catAbsence'>".T_("Absence")."</input></td>\n";
  echo "    <td><input type='text' value='".T_("Absence")."' name='cat_absence'  id='cat_absence'></td>\n";
  echo "  </tr>\n";
*/  
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED name='cb_catIncident' id='cb_catIncident'>".T_("Incident")."</input></td>\n";
  echo "    <td><input size='30' type='text' value='".T_("Network Disruption")."' name='cat_incident'  id='cat_incident'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED name='cb_catTools' id='cb_catTools'>".T_("Tools")."</input></td>\n";
  echo "    <td><input size='30' type='text' value='".T_("System Administration")."' name='cat_tools'  id='cat_tools'></td>\n";
  echo "  </tr>\n";
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED name='cb_catDoc' id='cb_catDoc'>".T_("Documentation")."</input></td>\n";
  echo "    <td><input size='30' type='text' value='".T_("Wiki update")."' name='cat_doc'  id='cat_doc'></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";
  
  echo "  <br/>\n";
  
  
  echo "</li>\n";
  echo "<li>".T_("Default SideTasks")."<br/>\n";
  echo "<table class='invisible'>\n";
  echo "  <tr>\n";
  echo "    <td><input type=CHECKBOX CHECKED name='cb_taskProjManagement' id='cb_taskProjManagement'>".T_("ProjectManagement")."</input></td>\n";
  echo "    <td><input size='30' type='text' name='task_projManagement'  id='task_projManagement' value='(generic) $teamName Project Management'></td>\n";
  echo "  </tr>\n";
  echo "</table>\n";
  echo "</li>\n";
  echo "</ul>\n";
  
  
  echo "  <br/>\n";
  echo "  <br/>\n";
  echo "<input type=button value='".T_("Create")."' onClick='javascript: addTeam()'>\n";
  
  #echo("<input type='submit' name='Submit' value='Create Team'>\n");
     
  echo "<input type=hidden name=action      value=noAction>\n";
  echo "<input type=hidden name=currentForm value=teamCreationForm>\n";
  echo "<input type=hidden name=nextForm    value=teamCreationForm>\n";
     
  echo("</form>\n");
}







// ================ MAIN =================

global $admin_teamid;
global $defaultSideTaskProject;

global $sideTaskProjectType;


$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die(T_("Could not connect to database"));
mysql_select_db($db_mantis_database) or die(T_("Could not select database"));


// ---- if not codev admin then stop now.
// REM: who is allowed to create a new team ? anyone ?
#$session_user = new User($_SESSION['userid']);
#if (false == $session_user->isTeamDeveloper($admin_teamid)) {
#  echo ("Sorry, you need to be Codev Administrator to access this page.");
#  exit;
#}

$action = $_POST[action];
$team_name = $_POST[team_name];
$team_desc = $_POST[team_desc];
$teamleader_id = $_POST[teamleader_id];


#unset($_SESSION['teamid']);


if ("addTeam" == $action) {
	echo T_("Create")." $team_name !<br/>";
	
	
	$formatedDate  = date("Y-m-d", time());
	$now = date2timestamp($formatedDate);

	// --- save to DB
   Team::create($team_name, $team_desc, $teamleader_id, $now);

   $teamid = Team::getIdFromName($team_name);
   echo "teamId = $teamid !<br/>";
   
   
   // --- add default SuiviOp project
   
   if (-1 != $teamid) {
	   $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$defaultSideTaskProject','$teamid','$sideTaskProjectType');";
	   mysql_query($query) or die("Query failed: $query");
	   
	   $_SESSION['teamid'] = $teamid;
	   
	   echo ("<script> parent.location.replace('./edit_team.php'); </script>"); 
   }
   
} elseif ("editTeam" == $action) {
   echo T_("Create and go to Edit")."<br/>";

}else if ("noAction" == $action) {
    echo "browserRefresh<br/>";
} else {
	
	// first call, display form
   displayCreateTeamForm();
}

  
  
  






?>
</div>

<?php include 'footer.inc.php'; ?>
