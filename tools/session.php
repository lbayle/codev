<?php
include_once('../include/session.inc.php');

$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
echo "(used for session name) = ".$tokens[1]."<br>";
echo "<br><br>";

foreach ($_SESSION as $key => $val) {
	echo "$key => $val<br>";
}
echo "<br><br>";

foreach ($_SERVER as $key => $val) {
	echo "$key => $val<br>";
}

?>
