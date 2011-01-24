<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
   $_POST[page_name] = "CoDev Login"; 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<?php

include_once "constants.php";

// -----------------------------
function displayLoginForm() {

  echo "<div align=center>\n";      
  echo("<form action='login.php' method='post' name='loginForm'>\n");
  echo(T_("Login").": <input name='codev_login' type='text' id='codev_login'>\n");
  echo(T_("Password").": <input name='codev_passwd' type='password' id='codev_passwd'>\n");
  echo("<input type='submit' name='Submit' value='".T_("Login")."'>\n");
     
  echo "<input type=hidden name=action      value=pleaseLogin>\n";
  echo "<input type=hidden name=currentForm value=loginForm>\n";
  echo "<input type=hidden name=nextForm    value=loginForm>\n";
     
  echo("</form>\n");
  echo "</div>\n";      
}

//  
// MAIN
//
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die(T_("Could not connect to DB"));
mysql_select_db($db_mantis_database) or die(T_("Could not select database"));

$action = $_POST[action];
$user = $_POST[codev_login];
$password = md5($_POST[codev_passwd]);
   
#if (isset($_SESSION['userid'])) {
#    displayLogoutForm();
#} else {
      
if ("pleaseLogin" == $action) {
  $query= "SELECT id, username, realname FROM `mantis_user_table` WHERE username = '$user' and password = '$password'";
  $result = mysql_query($query) or die("Query failed: $query");
                
  if ($row_login = mysql_fetch_object($result)) {
    $_SESSION['userid']=$row_login->id;
    $_SESSION['username']=$row_login->username;
    $_SESSION['realname']=$row_login->realname;

    echo ("<script> parent.location.replace('../codev'); </script>");                  
  } else {
    echo "login failed !<br />";
  }
} else {
   echo "<br />";
   echo "<br />";
   echo "<br />";
   displayLoginForm();
}
#}
   
?>

<?php include 'footer.inc.php'; ?>
