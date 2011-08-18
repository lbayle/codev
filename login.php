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

<?php include_once 'path.inc.php'; ?>

<?php
   include_once 'i18n.inc.php';
   $_POST[page_name] = T_("CoDev Login"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<?php

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

    echo '<script language="javascript"> window.location="',getServerRootURL(),'"; </script>';
  } else {
    echo T_("login failed !")."<br />";
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
