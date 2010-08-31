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
</script>


<?php
include_once "../constants.php";
include_once "../tools.php";
include_once "../auth/user.class.php";

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



function displayTeamMemberTuples($teamid) {
   // Display previous entries
   echo "<div>\n";
   echo "<table>\n";
   echo "<caption>Team Members</caption>\n";   
   echo "<tr>\n";
   echo "<th></th>\n";
   echo "<th>login</th>\n";
   echo "<th>Nom</th>\n";
   echo "<th>Date d'arivee</th>\n";
   echo "</tr>\n";

   $query     = "SELECT codev_team_user_table.id, codev_team_user_table.user_id, codev_team_user_table.team_id, ".
                       "codev_team_user_table.arrival_date, mantis_user_table.username, mantis_user_table.realname ".
                "FROM `codev_team_user_table`, `mantis_user_table` ".
                "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
                "AND codev_team_user_table.team_id=$teamid ".
                "ORDER BY mantis_user_table.username DESC";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      echo "<a title='delete this row' href=\"javascript: removeTeamMember('".$row->id."', '__description__')\" ><img src='../images/b_drop.png'></a>\n";
      echo "</td>\n";
      echo "<td>".$row->username."</td>\n";
      echo "<td>".$row->realname."</td>\n";
      echo "<td>".date("Y-m-d", $row->arrival_date)."</td>\n";

      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "<div>\n";
}


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
                "ORDER BY mantis_project_table.name DESC";
   $result    = mysql_query($query) or die("Query failed: $query");
   while($row = mysql_fetch_object($result))
   {
      echo "<tr>\n";
      echo "<td>\n";
      // if SuiviOp do not allow tu delete
      if (0 == $row->type) {
         echo "<a title='delete this row' href=\"javascript: removeTeamMember('".$row->id."', '__description__')\" ><img src='../images/b_drop.png'></a>\n";
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
   
   displayTeamMemberTuples($teamid);
   
   echo "<br/>\n";
   echo "<br/>\n";
   displayTeamProjectTuples($teamid);
   
   
   
   if ($_POST[action] == "addTeamMember") {
   	
   } elseif ($_POST[action] == "addTeamProject") {
   	
   }
   	
}

?>

<?php include '../footer.inc.php'; ?>
