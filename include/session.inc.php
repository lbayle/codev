<?php
if (!isset($_SESSION)) {
   $tokens = explode('/', $_SERVER['PHP_SELF'], 3);
   $sname = str_replace('.', '_', $tokens[1]);
   session_name($sname);
   session_start();
   header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
}
?>
