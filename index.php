<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
   $_POST[page_name] = "Bienvenu sur le serveur CoDev"; 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>




<?php

include_once 'consistency_check.class.php'; 
include_once 'user.class.php';


// -----------------------------
function displayLoginForm() {
        
  echo "<div align=center>\n";      
  echo("<form action='login.php' method='post' name='loginForm'>\n");

  echo("Login: <input name='codev_login' type='text' id='codev_login'>\n");
  echo("Password: <input name='codev_passwd' type='password' id='codev_passwd'>\n");
  echo("<input type='submit' name='Submit' value='Login'>\n");
     
  echo "<input type=hidden name=action      value=pleaseLogin>\n";
  echo "<input type=hidden name=currentForm value=loginForm>\n";
  echo "<input type=hidden name=nextForm    value=loginForm>\n";
     
  echo("</form>\n");
  echo "</div>\n";      
}

function displayLinks() {

   echo "<div id='homepage_list'  class='left'>\n";
	echo "<br/>\n";
   echo "<br/>\n";
   echo "	<ul>\n";
   echo "   <li>\n";
   echo "        <a href='http://".$_SERVER['HTTP_HOST']."/mantis.php'>Mantis</a>";
   echo "   </li>\n";
   echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/time_tracking.php'>Saisie des CRA</a>";
   echo "   </li>\n";
   echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/holidays_report.php'>Affichage des cong&eacute;s</a>";
   echo "   </li>\n";
   echo "</ul>\n";
 
   echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";

   echo "<ul>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports'>Suivi des fiches Mantis</a>";
   echo "   </li>\n";
   echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/week_activity_report.php'>Activit&eacute; hebdomadaire</a>";
   echo "   </li>\n";
   echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/productivity_report.php'>Indicateurs de production</a>";
   echo "   </li>\n";
   echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/issue_info.php'>Informations sur une fiche</a>";
   echo "   </li>\n";
   echo "</ul>\n";
   echo "</div>\n";
   
}

// -----------------------------
function displayConsistencyErrors($sessionUser) {

   // get projects i'm involved in (dev, Leader, Manager)
   $devTeamList = $sessionUser->getDevTeamList();
   $leadedTeamList = $sessionUser->getLeadedTeamList();
   $managedTeamList = $sessionUser->getManagedTeamList();
   $teamList = $devTeamList + $leadedTeamList + $managedTeamList; 
   $projectList = $sessionUser->getProjectList($teamList);

   $ccheck = new ConsistencyCheck($projectList);

   $cerrList = $ccheck->check();

   if (0 == count($cerrList)) {
      #echo "Pas d'erreur.<br/>\n";
   } else {

      echo "<br/>\n";
      echo "<hr/>\n";
      echo "<br/>\n";
      echo "<br/>\n";
   	echo "<div align='left'>\n";
      foreach ($cerrList as $cerr) {

         if ($sessionUser->id == $cerr->userId) {
            $user = new User($cerr->userId);
            echo "ERREUR Mantis: <span style='color:red'>".mantisIssueURL($cerr->bugId)."&nbsp;&nbsp;".date("Y-m-d", $cerr->timestamp)."&nbsp;&nbsp;".$statusNames[$cerr->status]."&nbsp;&nbsp;$cerr->desc</span><br/>\n";
         }
      }
      echo "</div>\n";
   }
   
}

// ================ MAIN =================


if (!isset($_SESSION['userid'])) {
   displayLoginForm();
} else {

   $link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
      or die("Impossible de se connecter");
   mysql_select_db($db_mantis_database) or die("Could not select database");

   $userid = $_SESSION['userid'];
   $sessionUser = new User($userid);

   displayLinks();
   
   displayConsistencyErrors($sessionUser);
}

?>

<br/>
<br/>

<?php include 'footer.inc.php'; ?>
