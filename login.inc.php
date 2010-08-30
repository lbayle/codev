<?php

include_once "constants.php";
include_once "tools.php";

//
// MAIN
//
echo "<div id='login'>\n";
if (isset($_SESSION['userid'])) {
  echo "Logged in as ".$_SESSION['username']." (".$_SESSION['realname'].") <span class='floatr'><a href='".getServerRootURL()."/logout.php' title='logout'>logout</a></span>\n";
} else {
  echo "Logged out ! <span class='floatr'><a href='".getServerRootURL()."/login.php'>Login</a></span>\n";
}
echo "</div>";
   
?>
