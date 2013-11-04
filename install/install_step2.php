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

$page_name = T_("Install - Step 2");
require_once('install/install_header.inc.php');

Config::getInstance()->setQuiet(true);

require_once('install/install_menu.inc.php');
?>

<script type="text/javascript">
   function proceedStep2() {
      document.forms["form2"].action.value="proceedStep2";
      document.forms["form2"].submit();
   }
</script>

<div id="content">

<?php

/**
 * Creates constants.php that contains variable
 * definitions that the codev admin may want to tune.
 *
 * WARN: depending on your HTTP server installation, the file may be created
 * by user 'apache', so be sure that this user has write access
 * to the CoDev install directory
 *
 * @return NULL if OK, or an error message starting with 'ERROR' .
 */
function createConstantsFile($mantisPath, $mantisURL, $codevURL) {

   // --- general ---
   Constants::$homepage_title = 'Welcome';
   Constants::$codevURL = $codevURL;
   Constants::$mantisURL = $mantisURL;
   Constants::$mantisPath = $mantisPath;
   Constants::$codevRootDir = dirname(dirname(__FILE__));
   Constants::$codevtt_logfile = Constants::$codevRootDir.'/codevtt.log';

   // --- database ---
   // already set...

   // --- mantis ---
   // already set...

   // --- status ---
   $status_new          = array_search('new', Constants::$statusNames);
   $status_feedback     = array_search('feedback', Constants::$statusNames);
   #$status_acknowledged = array_search('acknowledged', Constants::$statusNames);
   $status_open         = array_search('open', Constants::$statusNames);
   $status_closed       = array_search('closed', Constants::$statusNames);

   Constants::$status_new = $status_new;
   Constants::$status_feedback = $status_feedback;
   #Constants::$status_acknowledged = $status_acknowledged;
   Constants::$status_open = (NULL != $status_open) ? $status_open : 50; // (50 = 'assigned' in default mantis workflow)
   Constants::$status_closed = $status_closed;

   // --- resolution ---
   Constants::$resolution_fixed    = array_search('fixed',    Constants::$resolution_names);
   Constants::$resolution_reopened = array_search('reopened', Constants::$resolution_names);

   // --- relationships ---
   define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY', 2500 );
   define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS', 2501 );
   Constants::$relationship_constrained_by = 2500;
   Constants::$relationship_constrains = 2501;

   $retCode = Constants::writeConfigFile();

   if (!$retCode) {
      // TODO throw exception...
      return "ERROR: Could not create file ".Constants::$config_file;
   }
   return NULL;
}

function displayForm($originPage, $path_mantis, $url_mantis, $url_codevtt) {

   echo "<form id='form2' name='form2' method='post' action='$originPage' >\n";
   echo "<h2>".T_("Get Mantis customizations")."</h2>\n";

   echo "<table class='invisible'>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Path to mantis")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='path_mantis'  id='path_mantis' value='$path_mantis'></td>\n";
   echo "  </tr>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("URL to mantis")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='url_mantis'  id='url_mantis' value='$url_mantis'></td>\n";
   echo "  </tr>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("URL to CodevTT")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='url_codevtt'  id='url_codevtt' value='$url_codevtt'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   // ---
   echo "  <br/>\n";
   echo "  <br/>\n";

   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 2")."' onClick='javascript: proceedStep2()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================
$originPage = "install_step2.php";
$default_path_mantis           = dirname(BASE_PATH).DIRECTORY_SEPARATOR."mantis"; // "/var/www/html/mantis";

$hostname =  Tools::isWindowsServer() ? php_uname('n') : getHostName();
$default_url_mantis            = 'http://'.$hostname.'/mantis'; // 'http://'.$_SERVER['HTTP_HOST'].'/mantis'; // getHostByName(getHostName())
$default_url_codevtt           = 'http://'.$hostname.'/codevtt'; // 'http://'.$_SERVER['HTTP_HOST'].'/codevtt'; // getHostByName(getHostName())

$filename_strings              = "strings_english.txt";
$filename_custom_strings       = "custom_strings_inc.php";
$filename_custom_constant      = "custom_constant_inc.php";
$filename_custom_relationships = "custom_relationships_inc.php";

$path_mantis = Tools::getSecurePOSTStringValue('path_mantis', $default_path_mantis);
$url_mantis = Tools::getSecurePOSTStringValue('url_mantis', $default_url_mantis);
$url_codevtt = Tools::getSecurePOSTStringValue('url_codevtt', $default_url_codevtt);

$action = Tools::getSecurePOSTStringValue('action', '');

#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayForm($originPage, $path_mantis, stripslashes($url_mantis), stripslashes($url_codevtt));

if ("proceedStep2" == $action) {
   if(!file_exists($path_mantis)) {
      echo "<span class='error_font'>Path to mantis ". $path_mantis." doesn't exist</span><br/>";
      exit;
   }
   if(!is_writable($path_mantis)) {
      echo "<span class='error_font'>Path to mantis ". $path_mantis." is NOT writable</span><br/>";
      exit;
   }

   // ---- load mantis configuration files to extract the information
   $filename_constant_inc = $path_mantis.DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR."constant_inc.php";
   if (file_exists($filename_constant_inc)) {
      include_once($filename_constant_inc);
   } else {
      echo "File not loaded: $filename_constant_inc<br />";
   }

   $filename_config_defaults_inc = $path_mantis.DIRECTORY_SEPARATOR."config_defaults_inc.php";
   if (file_exists($filename_config_defaults_inc)) {
      include_once($filename_config_defaults_inc);
   } else {
      echo "File not loaded: $filename_config_defaults_inc<br />";
   }

   $path_strings = $path_mantis.DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR.$filename_strings;
   if (file_exists($path_strings)) {
      include_once($path_strings);
   } else {
      echo "File not loaded: $path_strings<br />";
   }

   $path_custom_strings = $path_mantis.DIRECTORY_SEPARATOR.$filename_custom_strings;
   if (file_exists($path_custom_strings)) {
      include_once($path_custom_strings);
   } else {
      echo "File not loaded: $path_custom_strings<br />";
   }

   $filename_config_inc = $path_mantis.DIRECTORY_SEPARATOR."config_inc.php";
   if (file_exists($filename_config_inc)) {
      include_once($filename_config_inc);
   } else {
      echo "File not loaded: $filename_config_inc<br />";
   }

   global $s_status_enum_string;
   global $s_priority_enum_string;
   global $s_severity_enum_string;
   global $s_resolution_enum_string;

   // get information from mantis config files
   $status_enum_string = isset($g_status_enum_string) ? $g_status_enum_string : $s_status_enum_string;
   $priority_enum_string = isset($g_priority_enum_string) ? $g_priority_enum_string : $s_priority_enum_string;
   $severity_enum_string = isset($g_severity_enum_string) ? $g_severity_enum_string : $s_severity_enum_string;
   $resolution_enum_string = isset($g_resolution_enum_string) ? $g_resolution_enum_string : $s_resolution_enum_string;

   // and set codev Config variables

   echo "DEBUG 1/7 check that mantis custom files are writable<br/>";
   $retCode = true;
   if (file_exists($path_custom_strings)) {
      if (!is_writable($path_custom_strings)) {
         echo "<span class='error_font'>".$path_custom_strings." is NOT writable</span><br/>";
         $retCode = false;
      }
   }
   $path = $path_mantis.DIRECTORY_SEPARATOR.$filename_custom_constant;
   if (file_exists($path)) {
      if (!is_writable($path)) {
         echo "<span class='error_font'>".$path." is NOT writable</span><br/>";
         $retCode = false;
      }
   }
   $path = $path_mantis.DIRECTORY_SEPARATOR.$filename_custom_relationships;
   if (file_exists($path)) {
      if (!is_writable($path)) {
         echo "<span class='error_font'>".$path." is NOT writable</span><br/>";
         $retCode = false;
      }
   }
   if (!$retCode) { exit; }

   echo "DEBUG 2/7 add statusNames<br/>";
   $desc = T_("status Names as defined in Mantis (status_enum_string)");
   Constants::$statusNames = Tools::doubleExplode(':', ',', $status_enum_string);

   echo "DEBUG 3/7 add priorityNames<br/>";
   $desc = T_("priority Names as defined in Mantis (priority_enum_string)");
   $formatedString = str_replace("'", " ", $priority_enum_string);
   Constants::$priority_names = Tools::doubleExplode(':', ',', $priority_enum_string);

   echo "DEBUG 4/7 add severityNames<br/>";
   $desc = T_("severity Names as defined in Mantis (severity_enum_string)");
   $formatedString = str_replace("'", " ", $severity_enum_string);
   Constants::$severity_names = Tools::doubleExplode(':', ',', $severity_enum_string);

   echo "DEBUG 5/7 add resolutionNames<br/>";
   $desc = T_("resolution Names as defined in Mantis (resolution_enum_string)");
   $formatedString = str_replace("'", " ", $resolution_enum_string);
   Constants::$resolution_names = Tools::doubleExplode(':', ',', $resolution_enum_string);

   echo "DEBUG 6/7 add bug_resolved_status_threshold<br/>";
   $bug_resolved_status_threshold = isset($g_bug_resolved_status_threshold) ? $g_bug_resolved_status_threshold : constant("RESOLVED");
   Constants::$bug_resolved_status_threshold = $bug_resolved_status_threshold;

   echo "DEBUG 7/7 create ".Constants::$config_file." file<br/>";
   $errStr = createConstantsFile($path_mantis, $url_mantis, $url_codevtt);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   // Note: config.ini is needed on step3

   // everything went fine, goto step3
   echo ("<script type='text/javascript'> parent.location.replace('install_step3.php'); </script>");
}

?>
