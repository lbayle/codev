<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../'\">login</a> to access this page.");
  exit;
} 
?>

<?php
   include_once 'i18n.inc.php';
   $_POST[page_name] = "CoDev Administration : Team Creation"; 
   include '../header.inc.php'; 
?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>
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
include_once "../constants.php";
include_once "../tools.php";
include_once "user.class.php";


// -----------------------------
function displayCreateTeamForm() {

  echo("<form action='create_team.php' method='post' name='teamCreationForm'>\n");
  echo("Team Name: <input name='team_name' type='text' id='team_name'>\n");
  
  $query = "SELECT DISTINCT id, username, realname FROM `mantis_user_table` ORDER BY username";
  
  
  echo "Team Leader : <select name='teamleader_id'>\n";
  echo "<option value='0'></option>\n";
   
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {  echo "<option value='".$row->id."'>$row->username</option>\n";
  }
  echo "</select>\n";
  
  echo("Description: <input name='team_desc' type='text' id='team_desc'>\n");
  
  echo "<input type=button value='Create Team' onClick='javascript: addTeam()'>\n";
  
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
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");


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
	echo "Create $team_name !<br/>";
	
   // --- save to DB
   $query = "INSERT INTO `codev_team_table`  (`name`, `description`, `leader_id`) VALUES ('$team_name','$team_desc','$teamleader_id');";
   mysql_query($query) or die("Query failed: $query");
	
   
   // --- add default SuiviOp project
   $query = "SELECT id FROM `codev_team_table` WHERE name = '$team_name';";
   $result = mysql_query($query) or die("Query failed: $query");
   $teamid = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : "-1";
   
   echo "teamId = $teamid !<br/>";
   
   if (-1 != $teamid) {
	   $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$defaultSideTaskProject','$teamid','$sideTaskProjectType');";
	   mysql_query($query) or die("Query failed: $query");
	   
	   $_SESSION['teamid'] = $teamid;
	   
	   echo ("<script> parent.location.replace('./edit_team.php'); </script>"); 
   }
   
} elseif ("editTeam" == $action) {
   echo "Create and go to Edit<br/>";

}else if ("noAction" == $action) {
    echo "browserRefresh<br/>";
} else {
	
	// first call, display form
   displayCreateTeamForm();
}

  
  
  






?>
</div>

<?php include '../footer.inc.php'; ?>
