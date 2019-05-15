<?php
if (!isset($_SESSION)) {

   $basepath = realpath(dirname(__FILE__));
   $configfile = dirname($basepath).'/config.ini';

   // NOTE: doing a md5_file at each page call is very expensive !
   //if (file_exists($configfile)) {
   //$sname = 'codevtt_'.md5_file($configfile);
   //} else {
   //   $sname = 'codevtt_default';
   //}
   $sname = 'codevtt_'.md5($configfile);
   session_name($sname);
   ini_set("session.gc_maxlifetime","83200"); // 1 day = 86400
   //session_set_cookie_params(83200); // 1 day = 86400
   session_start();
   //setcookie( session_name($sname), session_id(), time() + 83200 ); // 1 day = 86400
   header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
}
?>
