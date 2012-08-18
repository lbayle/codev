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

require('include/super_header.inc.php');

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
function createConstantsFile() {
   #echo "DEBUG: create file ".self::FILENAME_CONSTANTS."<br/>";

   $today = Tools::date2timestamp(date("Y-m-d"));

   // create/overwrite file
   $fp = fopen(Install::FILENAME_CONSTANTS, 'w');

   if (!$fp) {
      return "ERROR creating file " . Install::FILENAME_CONSTANTS . " (current dir=" . getcwd() . ")";
   }

   $stringData = "<?php\n";
   $stringData .= "// This file is part of CoDev-Timetracking.\n";
   $stringData .= "// - The Variables in here can be customized to your needs\n";
   $stringData .= "// - This file has been generated during install on " . date("D d M Y H:i") . "\n";
   $stringData .= "\n";
   $stringData .= "include_once('classes/config.class.php');\n";
   $stringData .= "\n";
   $stringData .= "class Constants {\n";
   $stringData .= "\n";
   $stringData .= "   public static \$codevInstall_timestamp;\n";
   $stringData .= "\n";
   $stringData .= "   public static \$mantisURL;\n";
   $stringData .= "\n";
   $stringData .= "   // log file as defined in log4php.xml\n";
   $stringData .= "   public static \$codevtt_logfile;\n";
   $stringData .= "\n";
   $stringData .= "   public static \$homepage_title;\n";
   $stringData .= "\n";
   $stringData .= "   public static \$codevRootDir;\n";
   $stringData .= "\n";
   $stringData .= "   // --- RESOLUTION ---\n";
   $stringData .= "   # WARNING: watch out for i18n ! special chars may break PHP code and/or DB values\n";
   $stringData .= "   # INFO: the values depend on what you defined in codev_config_table.resolutionNames\n";
   $stringData .= "   public static \$resolution_fixed;\n";
   $stringData .= "   public static \$resolution_reopened;\n";
   $stringData .= "\n";

   $stringData .= "   // --- STATUS ---\n";
   $stringData .= "   # WARNING: CodevTT uses some global variables for status.\n";
   $stringData .= "   #          Some of these variables are used in the code, so if they are not defined\n";
   $stringData .= "   #          in the mantis workflow, they need to be created. The mandatory variables are:\n";
   $stringData .= "   #           \$status_new, \$status_feedback, \$status_acknowledged,\n";
   $stringData .= "   #           \$status_open, \$status_closed\n";
   $stringData .= "\n";
   $stringData .= "   public static \$statusNames;\n";
   $stringData .= "\n";

   $statusList = Config::getInstance()->getValue(Config::id_statusNames);
   foreach ($statusList as $s_name) {
      // TODO stringFormat s_name
      $stringData .= "   public static \$status_".$s_name.";\n";
   }

   $stringData .= "   // TODO add equivalences for all mandatory status not present in workflow (see mantis 131)\n";
   $stringData .= "   // ex: \$status_open = \$status_assigned;\n";

   $stringData .= "\n";
   $stringData .= "   public static function staticInit() {\n";
   $stringData .= "\n";
   $stringData .= "      self::\$codevInstall_timestamp = ".$today.";\n";
   $stringData .= "\n";
   $stringData .= "      self::\$mantisURL = 'http://'.\$_SERVER['HTTP_HOST'].'/mantis';\n";
   $stringData .= "\n";
   $stringData .= "      self::\$codevtt_logfile = '/tmp/codevtt/logs/codevtt.log';\n";
   $stringData .= "\n";
   $stringData .= "      self::\$homepage_title = 'Welcome';\n";
   $stringData .= "\n";
   $stringData .= "      self::\$codevRootDir = dirname(__FILE__);\n";
   $stringData .= "\n";
   $stringData .= "      self::\$resolution_fixed = array_search('fixed', Config::getInstance()->getValue(Config::id_resolutionNames));  # 20\n";
   $stringData .= "      self::\$resolution_reopened = array_search('reopened', Config::getInstance()->getValue(Config::id_resolutionNames));  # 30;\n";
   $stringData .= "\n";
   $stringData .= "      self::\$statusNames = Config::getInstance()->getValue(Config::id_statusNames);\n";
   $stringData .= "\n";
   foreach ($statusList as $s_name) {
      // TODO stringFormat s_name
      $stringData .= "   self::\$status_".$s_name." = array_search('".$s_name."', self::\$statusNames);\n";
   }
   $stringData .= "\n";
   $stringData .= "      date_default_timezone_set('Europe/Paris');\n";
   $stringData .= "\n";
   // Constrains
   $stringData .= "      # Custom Relationships\n";
   $stringData .= "      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY', 2500 );\n";
   $stringData .= "      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS', 2501 );\n";
   $stringData .= "   }\n";
   $stringData .= "\n";
   $stringData .= "}\n";
   $stringData .= "\n";
   $stringData .= "Constants::staticInit();\n";
   $stringData .= "\n";

   $stringData .= "?>\n";
   fwrite($fp, $stringData);
   fclose($fp);

   return NULL;
}

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Step 1 finished.</li>";
   echo "<li>Mantis costomization done. (Status, Workflow, Threshold, etc)</li>";
   echo "<li>Apache has write access to codev directory</li>";
   echo "</ul>\n";
   echo "<br/>";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Set statusNames as defined in Mantis      (status_enum_string)</li>";
   echo "<li>Set priority Names as defined in Mantis   (priority_enum_string)</li>";
   echo "<li>Set resolution Names as defined in Mantis (resolution_enum_string)</li>";
   echo "<li>Set bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)</li>";
   echo "<li>Genereate constants.php</li>";
   echo "<li>Add 'CodevTT' to Mantis menu </li>";
   echo "<li></li>";
   echo "</ul>\n";
   echo "";
}


function displayForm($originPage,
                     $filename_strings, $filename_custom_strings, $path_mantis) {

   echo "<form id='form2' name='form2' method='post' action='$originPage' >\n";
   echo "<h2>".T_("Get Mantis customizations")."</h2>\n";

   echo "<table class='invisible'>\n";

   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Path to mantis")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='path_mantis'  id='path_mantis' value='$path_mantis'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Strings file")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='filename_strings'  id='filename_strings' value='$filename_strings'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Custom Strings file")."</td>\n";
   echo "    <td><input size='50' type='text' style='font-family: sans-serif' name='filename_custom_strings'  id='filename_custom_strings' value='$filename_custom_strings'></td>\n";
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

$default_path_mantis                = "/var/www/html/mantis";
$default_filename_strings           = "strings_english.txt";
$default_filename_custom_strings    = "custom_strings_inc.php";

$filename_strings = Tools::getSecurePOSTStringValue('filename_strings', $default_filename_strings);
$filename_custom_strings = Tools::getSecurePOSTStringValue('filename_custom_strings', $default_filename_custom_strings);
$path_mantis = Tools::getSecurePOSTStringValue('path_mantis', $default_path_mantis);

$action = Tools::getSecurePOSTStringValue('action', '');

#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayForm($originPage, $filename_strings, $filename_custom_strings, stripslashes($path_mantis));

if ("proceedStep2" == $action) {
   if(!file_exists($path_mantis)) {
      die("Path to mantis ". $path_mantis." doesn't exist");
   }

   // ---- load mantis configuration files to extract the information
   $filename_constant_inc = $path_mantis."/core/constant_inc.php";
   if (file_exists($filename_constant_inc)) {
      include_once($filename_constant_inc);
   } else {
      echo "File not loaded: $filename_constant_inc<br />";
   }

   $filename_config_defaults_inc = $path_mantis."/config_defaults_inc.php";
   if (file_exists($filename_config_defaults_inc)) {
      include_once($filename_config_defaults_inc);
   } else {
      echo "File not loaded: $filename_config_defaults_inc<br />";
   }

   $path_strings = $path_mantis."/lang/".$filename_strings;
   if (file_exists($path_strings)) {
      include_once($path_strings);
   } else {
      echo "File not loaded: $path_strings<br />";
   }

   $path_custom_strings = $path_mantis."/".$filename_custom_strings;
   if (file_exists($path_custom_strings)) {
      include_once($path_custom_strings);
   } else {
      echo "File not loaded: $path_custom_strings<br />";
   }

   $filename_config_inc = $path_mantis."/config_inc.php";
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
   echo "DEBUG 1/8 add filename_strings<br/>";
   $desc = T_("Path to mantis config file: strings_english.txt");
   Config::getInstance()->setValue(Config::id_mantisFile_strings, $filename_strings, Config::configType_string , $desc);

   echo "DEBUG 2/8 add filename_custom_strings<br/>";
   $desc = T_("Path to mantis config file: custom_strings_inc.php");
   Config::getInstance()->setValue(Config::id_mantisFile_custom_strings, $filename_custom_strings, Config::configType_string , $desc);

   echo "DEBUG 3/8 add path_mantis<br/>";
   $desc = T_("Path to mantis");
   Config::getInstance()->setValue(Config::id_mantisPath, $path_mantis, Config::configType_string , $desc);

   echo "DEBUG 4/8 add statusNames<br/>";
   $desc = T_("status Names as defined in Mantis (status_enum_string)");
   Config::getInstance()->setValue(Config::id_statusNames, $status_enum_string, Config::configType_keyValue , $desc);

   echo "DEBUG 5/8 add priorityNames<br/>";
   $desc = T_("priority Names as defined in Mantis (priority_enum_string)");
   $formatedString = str_replace("'", " ", $priority_enum_string);
   Config::getInstance()->setValue(Config::id_priorityNames, $formatedString, Config::configType_keyValue , $desc);

   echo "DEBUG 5.1/8 add severityNames<br/>";
   $desc = T_("severity Names as defined in Mantis (severity_enum_string)");
   $formatedString = str_replace("'", " ", $severity_enum_string);
   Config::getInstance()->setValue(Config::id_severityNames, $formatedString, Config::configType_keyValue , $desc);

   echo "DEBUG 6/8 add resolutionNames<br/>";
   $desc = T_("resolution Names as defined in Mantis (resolution_enum_string)");
   $formatedString = str_replace("'", " ", $resolution_enum_string);
   Config::getInstance()->setValue(Config::id_resolutionNames, $formatedString, Config::configType_keyValue , $desc);

   echo "DEBUG 7/8 add bug_resolved_status_threshold<br/>";
   $bug_resolved_status_threshold = isset($g_bug_resolved_status_threshold) ? $g_bug_resolved_status_threshold : constant("RESOLVED");
   $desc = T_("bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)");
   Config::getInstance()->setValue(Config::id_bugResolvedStatusThreshold, "$bug_resolved_status_threshold", Config::configType_int , $desc, "0");

   echo "DEBUG 8/8 create constants.php<br/>";
   $errStr = createConstantsFile();
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   // Note: constants.php is needed on step3

   // everything went fine, goto step3
   echo ("<script type='text/javascript'> parent.location.replace('install_step3.php'); </script>");
}

?>
