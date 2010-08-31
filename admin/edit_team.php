<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
if (!isset($_SESSION['userid'])) {
  echo ("Sorry, you need to <a href='../login.php'\">login</a> to access this page.");
  exit;
} 
?>

<?php include '../header.inc.php'; ?>

<?php include '../login.inc.php'; ?>

<h1>CoDev Admin : Team Edition</h1>


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

#if ($_POST[nextForm] == "editTeamForm") {
   echo "editTeamForm teamid=".$teamid."<br/>";
#}

}







?>
