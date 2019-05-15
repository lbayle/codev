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
   echo "    <td width='120'>".T_("Path to Mantis")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='path_mantis'  id='path_mantis' value='$path_mantis'></td>\n";
   echo "  </tr>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("URL to Mantis")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='url_mantis'  id='url_mantis' value='$url_mantis'></td>\n";
   echo "  </tr>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("URL to CodevTT")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='url_codevtt'  id='url_codevtt' value='$url_codevtt'></td>\n";
   echo "  </tr>\n";
   echo "</table><br>\n";

   // ---
   echo '<div id="divErrMsg" style="display: none;">';
   echo '   <span class="error_font" style="font-size:larger; font-weight: bold;">Please fix the following points and try again:</span><br><br>';
	echo '   <span class="error_font" id="errorMsg"></span><br>';
   echo '</div>';
   
   echo "  <br>\n";
   echo "  <br>\n";

   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 2")."' onClick='javascript: proceedStep2()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

/**
 * Add a new entry in MantisBT menu (main_menu_custom_options)
 *
 * ex: addCustomMenuItem('CodevTT', '../codev/index.php')
 *
 */
function addCustomMenuItem($name, $url) {

   // $tok = strtok($_SERVER["SCRIPT_NAME"], "/");
   // $url = '../'.$tok.'/index.php';  #  ../codevtt/index.php

   // TODO add this line to config_inc.php :
   //array_push($g_main_menu_custom_options, array( "CodevTT", NULL, 'http://localhost/codevtt/' ));
}




/**
 * FIX ERROR http://codevtt.org/site/?topic=install-step-2-fatal-erroor
 *
 * rather than including mantis/core.php here is a copy of this simple function
 *
 *
 * Checks to see if script was queried through the HTTPS protocol
 * @return boolean True if protocol is HTTPS
 */
function http_is_protocol_https() {
	if( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
		return strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https';
	}

	if( !empty( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) != 'off' ) ) {
		return true;
	}

	return false;
}

function siteURL()
{

   #$hostname =  Tools::isWindowsServer() ? php_uname('n') : getHostName();
   #$port = ':'.$_SERVER['SERVER_PORT'];

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName;
}

// ================ MAIN =================
$originPage = "install_step2.php";
$default_path_mantis           = dirname(BASE_PATH).DIRECTORY_SEPARATOR."mantis"; // "/var/www/html/mantis";

$default_url_mantis            = siteURL().dirname(dirname(dirname($_SERVER['REQUEST_URI']))).'/mantis';
$default_url_codevtt           = siteURL().dirname(dirname($_SERVER['REQUEST_URI']));

$filename_config_inc           = "config_inc.php";
$filename_strings              = "strings_english.txt";
$filename_custom_strings       = "custom_strings_inc.php";
$filename_custom_constants     = "custom_constants_inc.php";
$filename_custom_relationships = "custom_relationships_inc.php";

$path_mantis = filter_input(INPUT_POST, 'path_mantis');
if (NULL == $path_mantis) { $path_mantis = $default_path_mantis; }

$url_mantis = filter_input(INPUT_POST, 'url_mantis');
if (NULL == $url_mantis) { $url_mantis = $default_url_mantis; }

$url_codevtt = filter_input(INPUT_POST, 'url_codevtt');
if (NULL == $url_codevtt) { $url_codevtt = $default_url_codevtt; }

$action = filter_input(INPUT_POST, 'action');

#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayForm($originPage, $path_mantis, stripslashes($url_mantis), stripslashes($url_codevtt));

if ("proceedStep2" == $action) {

   $errMsg = '';

   if(!file_exists($path_mantis)) {
      echo "<span class='error_font'>Path to mantis does not exist : ". $path_mantis." </span><br>";
      exit;
   } else {
      if (!is_writable($path_mantis)) {
         $errMsg .= 'Path to mantis is not writable : '. $path_mantis.'<br>';
      }
   }

   // --- check mantis version (config files have been moved in v1.3)
   if (is_dir($path_mantis.DIRECTORY_SEPARATOR.'config')) {
      // mantis v1.3 or higher
      $path_mantis_config = $path_mantis.DIRECTORY_SEPARATOR.'config';
   } else {
      // mantis 1.2
      $path_mantis_config = $path_mantis;
   }

   if (!is_writable($path_mantis_config)) {
      $errMsg .= 'Path to mantis config is not writable : '. $path_mantis_config.'<br>';
   }

   $path_mantis_plugins = $path_mantis.DIRECTORY_SEPARATOR.'plugins';
   if (!is_writable($path_mantis_plugins)) {
      $errMsg .= 'Path to mantis plugins is not writable : '. $path_mantis_plugins.'<br>';
   }

   // --- check mantis core files
   $path_core_constant_inc = $path_mantis.DIRECTORY_SEPARATOR."core".DIRECTORY_SEPARATOR."constant_inc.php";
   if (!file_exists($path_core_constant_inc)) {
      $errMsg .= 'File not found : '. $path_core_constant_inc.'<br>';
   }
   $path_config_defaults_inc = $path_mantis.DIRECTORY_SEPARATOR."config_defaults_inc.php";
   if (!file_exists($path_config_defaults_inc)) {
      $errMsg .= 'File not found : '. $path_config_defaults_inc.'<br>';
   }
   $path_core_strings = $path_mantis.DIRECTORY_SEPARATOR."lang".DIRECTORY_SEPARATOR.$filename_strings;
   if (!file_exists($path_core_strings)) {
      $errMsg .= 'File not found : '. $path_core_strings.'<br>';
   }

   // if config_inc.php does not exist, then mantis is not installed...
   $path_mantis_config_inc=$path_mantis_config.DIRECTORY_SEPARATOR.$filename_config_inc;
   if(!file_exists($path_mantis_config_inc)) {
      $errMsg .= 'File not found : '. $path_mantis_config_inc.'<br>';
   } else {
      if (!is_writable($path_mantis_config_inc)) {
         $errMsg .= 'File not writable : '. $path_mantis_config_inc.'<br>';
      }
   }

   // --- check custom files that will be modified
   $path_custom_constants = $path_mantis_config.DIRECTORY_SEPARATOR.$filename_custom_constants;
   if (file_exists($path_custom_constants)) {
      if (!is_writable($path_custom_constants)) {
         $errMsg .= 'File not writable : '. $path_custom_constants.'<br>';
      }
   }
   $path_custom_relationships = $path_mantis_config.DIRECTORY_SEPARATOR.$filename_custom_relationships;
   if (file_exists($path_custom_relationships)) {
      if (!is_writable($path_custom_relationships)) {
         $errMsg .= 'File not writable : '. $path_custom_relationships.'<br>';
      }
   }
   $path_custom_strings = $path_mantis_config.DIRECTORY_SEPARATOR.$filename_custom_strings;
   if (file_exists($path_custom_strings)) {
      if (!is_writable($path_custom_strings)) {
         $errMsg .= 'File not writable : '. $path_custom_strings.'<br>';
      }
   }

   // === consistency check !
   if ('' !== $errMsg) {
      echo '<script type="text/javascript">';
      echo '  document.getElementById("divErrMsg").style.display = "block";';
      echo "  document.getElementById(\"errorMsg\").innerHTML=\"".$errMsg."\";";
      echo '</script>';
      exit;
   }

   // === let's do the job ...

   // --- load mantis configuration files to get default values
   include_once($path_core_constant_inc);
   include_once($path_custom_constants);
   include_once($path_config_defaults_inc);
   include_once($path_core_strings);

   // --- check & load mantis custom files (override default values)
   if (file_exists($path_custom_strings)) {
      include_once($path_custom_strings);
   }

   include_once($path_mantis_config_inc);



   global $s_status_enum_string;
   global $s_priority_enum_string;
   global $s_severity_enum_string;
   global $s_resolution_enum_string;

   // get information from mantis config files
   $status_enum_string = isset($g_status_enum_string) ? $g_status_enum_string : $s_status_enum_string;
   $priority_enum_string = isset($g_priority_enum_string) ? $g_priority_enum_string : $s_priority_enum_string;
   $severity_enum_string = isset($g_severity_enum_string) ? $g_severity_enum_string : $s_severity_enum_string;
   $resolution_enum_string = isset($g_resolution_enum_string) ? $g_resolution_enum_string : $s_resolution_enum_string;

   if (0 != count($g_status_enum_workflow)) {
      $status_enum_workflow = $g_status_enum_workflow;
   } else {
      // set mantis default workflow (see config_defaults_inc.php)
      echo '<script type="text/javascript">console.warn("WARNING g_status_enum_workflow not defined in config_inc.php: set to mantis default !");</script>';
      $status_enum_workflow[NEW_]='20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved';
      $status_enum_workflow[FEEDBACK] ='10:new,30:acknowledged,40:confirmed,50:assigned,80:resolved';
      $status_enum_workflow[ACKNOWLEDGED] ='20:feedback,40:confirmed,50:assigned,80:resolved';
      $status_enum_workflow[CONFIRMED] ='20:feedback,50:assigned,80:resolved';
      $status_enum_workflow[ASSIGNED] ='20:feedback,80:resolved,90:closed';
      $status_enum_workflow[RESOLVED] ='50:assigned,90:closed';
      $status_enum_workflow[CLOSED] ='50:assigned';
   }
   // and set codev Config variables

   echo "<script type=\"text/javascript\">console.log(\"DEBUG add statusNames\");</script>";
   Constants::$statusNames = Tools::doubleExplode(':', ',', $status_enum_string);

   echo "<script type=\"text/javascript\">console.log(\"DEBUG add priorityNames\");</script>";
   Constants::$priority_names = Tools::doubleExplode(':', ',', $priority_enum_string);

   echo "<script type=\"text/javascript\">console.log(\"DEBUG add severityNames\");</script>";
   Constants::$severity_names = Tools::doubleExplode(':', ',', $severity_enum_string);

   echo "<script type=\"text/javascript\">console.log(\"DEBUG add resolutionNames\");</script>";
   Constants::$resolution_names = Tools::doubleExplode(':', ',', $resolution_enum_string);

   $bug_resolved_status_threshold = isset($g_bug_resolved_status_threshold) ? $g_bug_resolved_status_threshold : constant("RESOLVED");
   echo "<script type=\"text/javascript\">console.log(\"DEBUG add bug_resolved_status_threshold = $g_bug_resolved_status_threshold\");</script>";
   Constants::$bug_resolved_status_threshold = $bug_resolved_status_threshold;

   echo '<script type="text/javascript">console.log(\'DEBUG add status_enum_workflow: '.json_encode($status_enum_workflow).'\');</script>';
   Constants::$status_enum_workflow = $status_enum_workflow;
   if (!is_array(Constants::$status_enum_workflow)) {
      $errStr .= "Could not retrieve status_enum_workflow form Mantis config files<br>";
   }

   echo "<script type=\"text/javascript\">console.log(\"DEBUG create ".str_replace('\\', '/', Constants::$config_file)."\");</script>";
   $errStr .= createConstantsFile($path_mantis, $url_mantis, $url_codevtt);
   if (NULL != $errStr) {
      echo '<script type="text/javascript">';
      echo '  document.getElementById("divErrMsg").style.display = "block";';
      echo "  document.getElementById(\"errorMsg\").innerHTML=\"".$errStr."\";";
      echo '</script>';
      exit;
   }

   // Note: config.ini is needed on step3

   // everything went fine, goto step3
   echo ("<script type='text/javascript'> parent.location.replace('install_step3.php'); </script>");
}

