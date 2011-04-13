<?php
   include_once "mysql_config.inc.php";

   #echo "db_mantis_database $db_mantis_database<br/>";
   
   $bugtracker_link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Could not connect to database: ". mysql_error());
   
   mysql_select_db($db_mantis_database) or die("Could not select database: ". mysql_error());

?>