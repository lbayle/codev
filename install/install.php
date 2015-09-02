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

include_once('i18n/i18n.inc.php');

$page_name = T_("Install");
require_once('install/install_header.inc.php');

require_once('install/install_menu.inc.php');


// check CodevTT already installed
if (file_exists(Constants::$config_file)) {
   echo "CodevTT ".Config::codevVersion." already installed.<br />";

   // TODO Check if the database is installed
} else {
   #Constants::setQuiet(TRUE);


   echo "<br><br>";
   echo "Please choose your language: &nbsp;&nbsp;";
   echo "<a href='".Tools::curPageName()."?locale=fr'><img src='".Tools::getServerRootURL()."/images/blank.png' class='flag flag-fr' title='Francais' width='18px' height='12px' /></a>";
   echo "&nbsp;";
   echo "<a href='".Tools::curPageName()."?locale=en'><img src='".Tools::getServerRootURL()."/images/blank.png' class='flag flag-gb' title='English' width='18px' height='12px' /></a>";
   echo "&nbsp;";
   echo "<a href='".Tools::curPageName()."?locale=de_DE'><img src='".Tools::getServerRootURL()."/images/blank.png' class='flag flag-de' title='Deutsch' width='18px' height='12px' /></a>";
   echo "&nbsp;";
   echo "<a href='".Tools::curPageName()."?locale=it_IT'><img src='".Tools::getServerRootURL()."/images/blank.png' class='flag flag-it' title='Italian' width='18px' height='12px' /></a>";
   echo "&nbsp;";
   echo "<a href='".Tools::curPageName()."?locale=pt_BR'><img src='".Tools::getServerRootURL()."/images/blank.png' class='flag flag-br' title='Brazil' width='18px' height='12px' /></a>";
   echo "<br><br><br>";


   //echo 'Id: ' . getmyuid() . '<br />';
   //echo 'Gid: ' . getmygid() . '<br />';


   $isReady = TRUE;
   $checkList = array();

   // ---------- PHP version
   if (!Tools::checkPhpVersion("5.3")) {
      $isReady = FALSE;
      $error = ('FAILED').' (current PHP version is '.phpversion().')';
      $test_result = "<span class='error_font'>$error</span>";
   } else {
      $test_result = '<span class="success_font">'.('SUCCESS').'</span>';
   }
   $checkList['PHP version (&gt;= 5.3)'] = $test_result;

   // ---------- timezone
   if (!date_default_timezone_get()) {
      $isReady = FALSE;
      $test_result  = "<span class='error_font'>".('FAILED').'<br>';
      $test_result .=  "Please check your php.ini file (ex: date.timezone = Europe/Paris)</span>";
   } else {
      $test_result = '<span class="success_font">'.('SUCCESS').'</span>';
   }
   $checkList['PHP date_default_timezone'] = $test_result;

   // ---------- Write access to codevtt dir
   $testDir = realpath ( ".." );
   if (!is_writable($testDir)) {
      $isReady = FALSE;
      $test_result  = "<span class='error_font'>".('FAILED').'<br>';
      $test_result .= '- Does apache user have write access to codevTT directory ?<br>';
      $test_result .= '- Are you sure SELINUX is well configured ?<br></span>';
   } else {
      $test_result = '<span class="success_font">'.('SUCCESS').'</span>';
   }
   $checkList['Write access to CodevTT directory'] = $test_result;

   // ---------- Write access to codevtt classmap.ser file
   $filename = '../classmap.ser';
   if (!is_writable($filename)){
      $isReady = FALSE;
      $test_result  = "<span class='error_font'>".('FAILED').'<br>';
      $test_result .= "- User '<b>".exec('whoami')."</b>' does not have write access to classmap.ser file<br>";
      $test_result .= '- Please check file permissions.<br></span>';
   } else {
      $test_result = '<span class="success_font">'.('SUCCESS').'</span>';
   }
   $checkList['Write access to classmap.ser'] = $test_result;
   

   echo "<h3>Pre-install check</h3>";
   echo "<table class='invisible'>\n";
   foreach ($checkList as $title => $test_result) {
      echo '<tr><td valign="top">'.T_($title).'</td><td>'.$test_result.'</td></tr>';
   }

   $test_result = "Please ensure that user '<b>".exec('whoami')."</b>' has write access to your <b>mantis</b> directory";
   echo '<tr><td valign="top">Write access to MantisBT directory</td><td><span class="success_font">'.$test_result.'</span></td></tr>';
   echo "</table>\n";

   echo "<br><br><br>";


}

?>
