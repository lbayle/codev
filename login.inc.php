<?php

include_once "constants.php";

//
// MAIN
//
echo "<div id='login'>";
if (isset($_SESSION['userid'])) {
  echo "Logged in as <a href='http://55.7.137.27/codev/logout.php' title='logout'>".$_SESSION['username']."</a> (".$_SESSION['realname'].").\n";
} else {
  echo "<a href='http://55.7.137.27/codev/login.php' title='login'>Logged out</a> !\n";
}
echo "</div>";
   
?>
