<?php

include_once "constants.php";
include_once 'i18n.inc.php';
include_once "tools.php";

//
// MAIN
//
echo "<div id='login'>\n";
if (isset($_SESSION['userid'])) {
  echo T_("Logged in as ").$_SESSION['username']." (".$_SESSION['realname'].") <span class='floatr'><a href='".getServerRootURL()."/logout.php' title='logout'>".T_("log out")."</a></span>\n";
} else {
  echo "<a href='".getServerRootURL()."/'>".T_("Logged out !")."</a> <span class='floatr'><a href='".getServerRootURL()."'>".T_("log in")."</a></span>\n";
}
echo "</div>";
   
?>
