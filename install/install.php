<?php
include_once('../include/session.inc.php');

/*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once '../path.inc.php';
include_once 'i18n.inc.php';

$page_name = T_("Install");
require_once 'install_header.inc.php';

require_once 'install_menu.inc.php';

include_once 'install.class.php';

// check CodevTT already installed
if (file_exists(Install::FILENAME_CONSTANTS) &&
     file_exists(Install::FILENAME_MYSQL_CONFIG)) {

 	include_once "mysql_connect.inc.php";
 	include_once "config.class.php";
 	include_once "internal_config.inc.php";

 	echo "CodevTT ".InternalConfig::$codevVersion." already installed.<br/>";

   echo "</br>";
 	$error = Install::checkMysqlAccess();
 	if (TRUE == strstr($error, T_("ERROR"))) {
 		echo "<span class='error_font'>$error</span><br/>";
 		exit;
 	}

 } else {

 	//echo 'Id: ' . getmyuid() . '<br />';
    //echo 'Gid: ' . getmygid() . '<br />';

 	// check write access rights to codevTT directory
   $testDir = realpath ( ".." );
   $error = Install::checkWriteAccess($testDir);
   if (TRUE == strstr($error, T_("ERROR"))) {
 		echo "<span class='error_font'>$error</span><br/>";
 		echo "<br>";
 		echo "- does apache user have write access to codevTT directory ?<br>";
 		echo "- Are you sure SELINUX is well configured ?<br>";
   	    exit;
   }

 	// check write access rights to codevTT/include directory
   $testDir = realpath ( "../include" );
   $error = Install::checkWriteAccess($testDir);
   if (TRUE == strstr($error, T_("ERROR"))) {
 		echo "<span class='error_font'>$error</span><br/>";
 		echo "<br>";
 		echo "- does apache user have write access to codevTT /include directory ?<br>";
 		echo "- Are you sure SELINUX is well configured ?<br>";
   	    exit;
   }

   $error = Install::checkMysqlAccess();
   if (TRUE == strstr($error, T_("ERROR"))) {
      echo "<span class='error_font'>$error</span><br/>";
      exit;
   }

   echo "Pre-install check SUCCEEDED.<br>";

   echo "<br>";
   echo "<br>";
   echo "<br>";
   echo "Before you continue, please ensure that user '<b>".exec('whoami')."</b>' has write access to your mantis directory<br>";


 }



?>
