<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
?>

<?php include '../header.inc.php'; ?>
<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>
<br/>
<h1>CoDev Administration : Team Edition</h1>
<?php include 'menu_admin.inc.php'; ?>


<script language="JavaScript">
  function submitTeam(){
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n"
        
    if (0 == document.forms["teamSelectForm"].f_teamid.value)  { msgString += "Team\n"; ++foundError; }
                   
    if (0 == foundError) {
      document.forms["teamSelectForm"].submit();
    } else {
      alert(msgString);    
    }    
  }


  function addTeamMember(){
     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:\n\n"
         
     if (0 == document.forms["addTeamMemberForm"].f_memberid.value)  { msgString += "Team Member\n"; ++foundError; }
                    
     if (0 == foundError) {
       document.forms["addTeamMemberForm"].action.value="addTeamMember";
       document.forms["addTeamMemberForm"].submit();
     } else {
       alert(msgString);    
     }    
   }

  function addTeamProject(){
     // check fields
     foundError = 0;
     msgString = "Les champs suivants ont ete oublies:\n\n"
         
     if (0 == document.forms["addTeamProjectForm"].f_projectid.value)  { msgString += "Project\n"; ++foundError; }
                    
     if (0 == foundError) {
       document.forms["addTeamProjectForm"].action.value="addTeamProject";
       document.forms["addTeamProjectForm"].submit();
     } else {
       alert(msgString);    
     }    
   }

  function removeTeamMember(id, description){
     confirmString = "Desirez-vous vraiment supprimer ce membre de l'equipe ?\n\n" + description;
     if (confirm(confirmString)) {
       document.forms["removeTeamMemberForm"].action.value="removeTeamMember";
       document.forms["removeTeamMemberForm"].f_memberid.value=id;
       document.forms["removeTeamMemberForm"].submit();
     }
   }
  
  function removeTeamProject(id, description){
     confirmString = "Desirez-vous vraiment supprimer ce Projet de l'equipe ?\n\n" + description;
     if (confirm(confirmString)) {
       document.forms["removeTeamProjectForm"].action.value="removeTeamProject";
       document.forms["removeTeamProjectForm"].f_projectid.value=id;
       document.forms["removeTeamProjectForm"].submit();
     }
   }
  
</script>


<?php
include_once "../constants.php";
include_once "../tools.php";
include_once "../auth/user.class.php";
require_once('../timetracking/calendar/classes/tc_calendar.php');

function setTeamForm($originPage, $defaultSelection) {
  $session_user = new User($_SESSION['userid']);
  $teamList = $session_user->getLeadedTeamList();
   
  // create form
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage'>\n";

  echo "Team :\n";
  echo "<select name='f_teamid'>\n";
  echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {
  
    if ($tid == $defaultSelection) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=button value='Select' onClick='javascript: submitTeam()'>\n";
   
  echo "<input type=hidden name=currentForm value=teamSelectForm>\n";
  echo "<input type=hidden name=nextForm    value=editTeamForm>\n";

  echo "</form>\n";
}



// ----------------------------------------------------
function displayTeamMemberTuples($teamid) {
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   echo "<caption>Team Members</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>login</th>\n";
   echo "<th>Nom</th>\n";
   echo "<th title='Date d arivee dans l equipe'>Date d'arivee</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_team_user_table.id, codev_team_user_table.user_id, codev_team_user_table.team_id, ".
                       "codev_team_user_table.arrival_date, mantis_user_table.username, mantis_user_table.realname ".
                "FROM `codev_team_user_table`, `mantis_user_table` ".
                "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
                "AND codev_team_user_table.team_id=$teamid ".
                "ORDER BY mantis_user_table.username";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      echo "<a title='delete this row' href=\"javascript: removeTeamMember('".$row->id."', '$row->username')\" ><img src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td>".$row->username."</td>\n";
      echo "<td>".$row->realname."</td>\n";
      echo "<td>".date("Y-m-d", $row->arrival_date)."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "<div>\n";
}


// ----------------------------------------------------
function addTeamMemberForm($originPage, $defaultDate) {
   
	list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate);

   $myCalendar = new tc_calendar("date1", true, false);
   $myCalendar->setIcon("../timetracking/calendar/images/iconCalendar.gif");
   $myCalendar->setDate($defaultDay, $defaultMonth, $defaultYear);
   $myCalendar->setPath("../timetracking/calendar/");
   $myCalendar->setYearInterval(2010, 2015);
   $myCalendar->dateAllow('2010-01-01', '2015-12-31');
   $myCalendar->setDateFormat('Y-m-d');
   $myCalendar->startMonday(true);

   // Display form
   echo "<h2>Add Team Member:</h2>\n";

   #echo "<div style='text-align: center;'>";
   echo "<div>";
   
   echo "<form id='addTeamMemberForm' name='addTeamMemberForm' method='post' Action='$originPage'>\n";

   echo "Member: <select name='f_memberid'>\n";
   echo "<option value='0'></option>\n";
   
   $query     = "SELECT id, username FROM `mantis_user_table` ORDER BY username";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<option value='".$row->id."'>".$row->username."</option>\n";
   }
   echo "</select>\n";
   
   echo "Arrival Date: "; $myCalendar->writeScript();
   

   echo "<input type=button name='btAddMember' value='Add' onClick='javascript: addTeamMember()'>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTeamMemberForm>\n";
   echo "<input type=hidden name=nextForm     value=addTeamMemberForm>\n";
   echo "</form>\n";
   
   
   echo "<form id='removeTeamMemberForm' name='removeTeamMemberForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=f_memberid   value='0'>\n";
   echo "</form>\n";
   
   echo "</div>";
}


// ----------------------------------------------------
function displayTeamProjectTuples($teamid) {
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   echo "<caption>Team Projects</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>Nom</th>\n";
   echo "<th>Description</th>\n";
   echo "<th>Type</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_team_project_table.id, codev_team_project_type_table.name AS type_name, codev_team_project_table.type, ".
                       "mantis_project_table.name, mantis_project_table.description ".
                "FROM `codev_team_project_table`, `mantis_project_table`, `codev_team_project_type_table` ".
                "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
                "AND codev_team_project_type_table.id = codev_team_project_table.type ".
                "AND codev_team_project_table.team_id=$teamid ".
                "ORDER BY mantis_project_table.name";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      // if SuiviOp do not allow tu delete
      if (0 == $row->type) {
         echo "<a title='delete this row' href=\"javascript: removeTeamProject('".$row->id."', '$row->name')\" ><img src='../images/b_drop.png'></a>\n";
      }
      echo "</td>\n";
      echo "<td>".$row->name."</td>\n";
      echo "<td>".$row->description."</td>\n";
      echo "<td>".$row->type_name."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "<div>\n";
}

// ----------------------------------------------------
function addTeamProjectForm($originPage) {
   
   // Display form
   echo "<h2>Add Team Project:</h2>\n";

   #echo "<div style='text-align: center;'>";
   echo "<div>";
   
   echo "<form id='addTeamProjectForm' name='addTeamProjectForm' method='post' Action='$originPage'>\n";

   echo "Project: <select name='f_projectid'>\n";
   echo "<option value='0'></option>\n";
/*   
   $query     = "SELECT DISTINCT mantis_project_table.id, mantis_project_table.name, mantis_project_table.description ".
                "FROM `mantis_project_table`, `codev_team_project_table` ".
                "WHERE codev_team_project_table.project_id = mantis_project_table.id ".
                "AND codev_team_project_table.type = 0 ".
                "ORDER BY name";
*/                
   $query     = "SELECT DISTINCT mantis_project_table.id, mantis_project_table.name, mantis_project_table.description ".
                "FROM `mantis_project_table` ".
                "ORDER BY name";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<option value='".$row->id."'>".$row->name."</option>\n";
   }
   echo "</select>\n";
   
   

   echo "<input type=button name='btAddProject' value='Add' onClick='javascript: addTeamProject()'>\n";

   echo "<input type=hidden name=action       value=noAction>\n";
   echo "<input type=hidden name=currentForm  value=addTeamProjectForm>\n";
   echo "<input type=hidden name=nextForm     value=addTeamProjectForm>\n";
   echo "</form>\n";
   
   
   echo "<form id='removeTeamProjectForm' name='removeTeamProjectForm' method='post' Action='$originPage'>\n";
   echo "   <input type=hidden name=action       value=noAction>\n";
   echo "   <input type=hidden name=f_projectid   value='0'>\n";
   echo "</form>\n";
   
   echo "</div>";
}


// ================ MAIN =================

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");



// use the teamid set in the form, if not defined (first page call) use session teamid
if (isset($_POST[f_teamid])) {
	$teamid = $_POST[f_teamid];
	$_SESSION['teamid'] = $teamid;
} else {
   $teamid = $_SESSION['teamid'];
}

#echo "session_team=".$_SESSION['teamid']."<br/>";

// --- show team selection form
setTeamForm("edit_team.php", $teamid);
echo "<br/>\n";
echo "<hr/>\n";
echo "<br/>\n";
echo "<br/>\n";


if ("" != $teamid) {

   echo "<br/>\n";
   echo "<br/>\n";
   $defaultDate  = date("Y-m-d", time());
   addTeamMemberForm("edit_team.php", $defaultDate);   
   displayTeamMemberTuples($teamid);
   
   echo "<br/>\n";
   echo "<br/>\n";
   addTeamProjectForm($originPage);
   displayTeamProjectTuples($teamid);
   
   
   
   if ($_POST[action] == "addTeamMember") {
      
    $formatedDate      = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : "";
    $timestamp = date2timestamp($formatedDate);
    $memberid = $_POST[f_memberid];
    
    // TODO check if not already in table !
    
    // save to DB
    $query = "INSERT INTO `codev_team_user_table`  (`user_id`, `team_id`, `arrival_date`) VALUES ('$memberid','$teamid','$timestamp');";
    mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
    
    // reload page
    echo ("<script> parent.location.replace('edit_team.php'); </script>"); 
    
      
   	
   } elseif ($_POST[action] == "addTeamProject") {
   	
      $projectid = $_POST[f_projectid];
      $projecttype= 0;
    
      // TODO check if not already in table !
    
      // save to DB
      $query = "INSERT INTO `codev_team_project_table`  (`project_id`, `team_id`, `type`) VALUES ('$projectid','$teamid','$projecttype');";
      mysql_query($query) or die("<span style='color:red'>Query FAILED: $query</span>");
    
      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>"); 
   } elseif ($_POST[action] == "removeTeamMember") {
   	
      $memberid = $_POST[f_memberid];
      $query = "DELETE FROM `codev_team_user_table` WHERE id = $memberid;";
      mysql_query($query) or die("Query failed: $query");
   	
      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>"); 
   
   } elseif ($_POST[action] == "removeTeamProject") {
      
      $projectid = $_POST[f_projectid];
      $query = "DELETE FROM `codev_team_project_table` WHERE id = $projectid;";
      mysql_query($query) or die("Query failed: $query");

      // reload page
      echo ("<script> parent.location.replace('edit_team.php'); </script>"); 
   }

   
}

?>

<?php include '../footer.inc.php'; ?>
