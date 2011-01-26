<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
   include 'i18n.inc.php';
   $_POST[page_name] = T_("Bienvenu sur le serveur CoDev"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>




<?php

include_once 'consistency_check.class.php'; 
include_once 'user.class.php';
include_once 'issue.class.php';

function disclaimer () {
	$useragent = $_SERVER["HTTP_USER_AGENT"];

   if (preg_match("|MSIE ([0-9].[0-9]{1,2})|",$useragent,$matched)) {
      $browser_version=$matched[1];
      $browser = "IE";
      echo "<span style='color:red'>\n";
      echo T_("IE may not correctly display this website,<br/>");
      echo T_("please consider using a standard complient web-browser.<br/>");
      echo "</span>\n";
   }
}

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
   echo "        <a href='".getServerRootURL()."/timetracking/time_tracking.php'>".T_("Time Tracking")."</a>"; // Saisie des CRA
   echo "   </li>\n";
   echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/holidays_report.php'>".T_("Holidays")."</a>"; // Affichage des cong&eacute;s
   echo "   </li>\n";
   echo "</ul>\n";
 
   //echo "<br/>\n";
   echo "<br/>\n";
   echo "<br/>\n";

   echo "<ul>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports'>".T_("Mantis Reports")."</a>"; // Suivi des fiches Mantis
   echo "   </li>\n";
   echo "   <br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/timetracking/week_activity_report.php'>".T_("Weekly activity")."</a>"; // Activit&eacute; hebdomadaire
   echo "   </li>\n";
   echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/productivity_report.php'>".T_("Productivity Reports")."</a>"; // Indicateurs de production
   echo "   </li>\n";
   echo "<br/>\n";
   echo "   <li>\n";
   echo "        <a href='".getServerRootURL()."/reports/issue_info.php'>".T_("Task info")."</a>"; // Informations sur une fiche
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
            $issue = new Issue($cerr->bugId);
            echo T_("ERROR on task ").mantisIssueURL($cerr->bugId, $issue->summary)." : &nbsp;&nbsp;<span style='color:red'>".date("Y-m-d", $cerr->timestamp)."&nbsp;&nbsp;".$statusNames[$cerr->status]."&nbsp;&nbsp;$cerr->desc</span><br/>\n";
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
      or die(T_("Could not connect to DB"));
   mysql_select_db($db_mantis_database) or die(T_("Could not select database"));

   $userid = $_SESSION['userid'];
   $sessionUser = new User($userid);

   disclaimer();
   
   displayLinks();
   
   displayConsistencyErrors($sessionUser);
}

?>

<br/>
<br/>

<?php include 'footer.inc.php'; ?>
