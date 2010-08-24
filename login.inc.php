<?php

include_once "constants.php";
include_once "tools.php";

//
// MAIN
//
echo "<div id='login'>";
if (isset($_SESSION['userid'])) {
  echo "Logged in as <a href='".getServerRootURL()."/logout.php' title='logout'>".$_SESSION['username']."</a> (".$_SESSION['realname'].").\n";
} else {
  echo "<a href='".getServerRootURL()."/login.php' title='login'>Logged out</a> !\n";
}
echo "</div>";
   
?>
