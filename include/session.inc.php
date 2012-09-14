<?php
if (!isset($_SESSION)) {
   $tokens = explode('/', $_SERVER['PHP_SELF'], 3);
   $sname = str_replace('.', '_', $tokens[1]);
   session_name($sname);
   ini_set("session.gc_maxlifetime","83200"); // 1 day = 86400
   session_set_cookie_params(83200); // 1 day = 86400
   session_start();
   setcookie( session_name(), session_id(), time() + 83200 ); // 1 day = 86400
   header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
}
?>
