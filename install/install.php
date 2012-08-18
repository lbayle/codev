<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

# WARN: this avoids the display of some PHP errors...
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

date_default_timezone_set("Europe/Paris");

require_once('lib/dynamic_autoloader/ClassFileMapAutoloader.php');
$_autoloader = unserialize(file_get_contents(BASE_PATH."/classmap.ser"));
$_autoloader->registerAutoload();

# WARN: order of these includes is important.
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("default");
   $logger->info("LOG activated !");

   // test
   #echo "configure LOG ".Logger::getConfigurationFile()."</br>";
   #echo "configure LOG ".Logger::getConfigurationClass()."</br>";
   #echo "configure LOG header exists: ".$logger->exists("header")."</br>";
}

/**
 * handle uncaught exceptions
 * @param Exception $e
 */
function exception_handler(Exception $e) {
   global $logger;
   echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
   $logger->error("UNCAUGHT EXCEPTION : ".$e->getMessage());
   $logger->error("UNCAUGHT EXCEPTION stack-trace:\n".$e->getTraceAsString());
}
set_exception_handler('exception_handler');

include_once('i18n/i18n.inc.php');

$page_name = T_("Install");
require_once('install/install_header.inc.php');

require_once('install/install_menu.inc.php');

// check CodevTT already installed
if (file_exists(Install::FILENAME_CONSTANTS) && file_exists(Install::FILENAME_MYSQL_CONFIG)) {
   echo "CodevTT ".Config::codevVersion." already installed.<br />";

   // TODO Check if the database is installed
} else {

   //echo 'Id: ' . getmyuid() . '<br />';
   //echo 'Gid: ' . getmygid() . '<br />';

   // check write access rights to codevTT directory
   $testDir = realpath ( ".." );
   $error = Tools::checkWriteAccess($testDir);
   if (strstr($error, "ERROR")) {
      echo "<span class='error_font'>$error</span><br />";
      echo "<br />";
      echo "- does apache user have write access to codevTT directory ?<br />";
      echo "- Are you sure SELINUX is well configured ?<br />";
      exit;
   }

   // check write access rights to codevTT/include directory
   $testDir = realpath ( "../include" );
   $error = Tools::checkWriteAccess($testDir);
   if (strstr($error, "ERROR")) {
      echo "<span class='error_font'>$error</span><br />";
      echo "<br />";
      echo "- does apache user have write access to codevTT /include directory ?<br />";
      echo "- Are you sure SELINUX is well configured ?<br />";
      exit;
   }

   echo "Pre-install check SUCCEEDED.<br />";
   echo "<br /><br /><br />";
   echo "Before you continue, please ensure that user '<b>".exec('whoami')."</b>' has write access to your mantis directory<br>";
}

?>
