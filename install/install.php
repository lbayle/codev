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

require_once('classes/sqlwrapper.class.php');
require_once('install/install.class.php');

include_once('i18n/i18n.inc.php');

include_once('tools.php');

$page_name = T_("Install");
require_once 'install_header.inc.php';

require_once 'install_menu.inc.php';

// check CodevTT already installed
if (file_exists(Install::FILENAME_CONSTANTS) && file_exists(Install::FILENAME_MYSQL_CONFIG)) {
   include_once "config.class.php";
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
