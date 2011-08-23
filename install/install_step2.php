<?php if (!isset($_SESSION)) { session_start(); } ?>
<?php /*
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
*/ ?>

<?php include_once '../path.inc.php'; ?>
<?php include_once 'i18n.inc.php'; ?>

<?php
   $_POST['page_name'] = T_("Install - Step 2");
   include 'install_header.inc.php';

   include_once "mysql_connect.inc.php";

   include_once "config.class.php";
   Config::getInstance()->setQuiet(true);

   include_once "internal_config.inc.php";

   include 'install_menu.inc.php';

?>

<script language="JavaScript">

function proceedStep2() {

     document.forms["form2"].action.value="proceedStep2";
     document.forms["form2"].submit();
}

</script>

<div id="content">


<?php

include_once 'install.class.php';

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
   echo "<li>Create constants.php</li>";
   echo "<li></li>";
   echo "</ul>\n";
   echo "";
}


function displayForm($originPage,
                      $filename_strings, $filename_custom_strings, $path_mantis) {

   echo "<form id='form2' name='form2' method='post' action='$originPage' >\n";
   echo "<hr align='left' width='20%'/>\n";
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

$filename_strings        = isset($_POST['filename_strings']) ? $_POST['filename_strings'] : $default_filename_strings;
$filename_custom_strings = isset($_POST['filename_custom_strings']) ? $_POST['filename_custom_strings'] : $default_filename_custom_strings;
$path_mantis             = isset($_POST['path_mantis']) ? $_POST['path_mantis'] : $default_path_mantis;

$action      = isset($_POST['action']) ? $_POST['action'] : '';


displayStepInfo();

displayForm($originPage,
            $filename_strings, $filename_custom_strings, $path_mantis);


if ("proceedStep2" == $action) {

    echo "DEBUG add filename_strings<br/>";
    $desc = T_("Path to mantis config file: strings_english.txt");
    Config::getInstance()->setValue(Config::id_mantisFile_strings, $filename_strings, Config::configType_string , $desc);

    echo "DEBUG add filename_custom_strings<br/>";
    $desc = T_("Path to mantis config file: custom_strings_inc.php");
    Config::getInstance()->setValue(Config::id_mantisFile_custom_strings, $filename_custom_strings, Config::configType_string , $desc);

    echo "DEBUG add path_mantis<br/>";
    $desc = T_("Path to mantis");
    Config::getInstance()->setValue(Config::id_mantisPath, $path_mantis, Config::configType_string , $desc);

    // ---- load mantis configuration files to extract the information
    $filename_constant_inc = "$path_mantis/core/constant_inc.php";
    if (file_exists($filename_constant_inc)) {
       include_once $filename_constant_inc;
    } else {
    	echo "File not loaded: $filename_constant_inc<br/>";
    }

    $filename_config_defaults_inc = "$path_mantis/config_defaults_inc.php";
    if (file_exists($filename_config_defaults_inc)) {
       include_once $filename_config_defaults_inc;
    } else {
    	echo "File not loaded: $filename_config_defaults_inc<br/>";
    }

    $path_strings = "$path_mantis/lang/$filename_strings";
    if (file_exists($path_strings)) {
       include_once $path_strings;
    } else {
    	echo "File not loaded: $path_strings<br/>";
    }


    $path_custom_strings = "$path_mantis/$filename_custom_strings";
    if (file_exists($path_custom_strings)) {
       include_once $path_custom_strings;
    } else {
    	echo "File not loaded: $path_custom_strings<br/>";
    }


    $filename_config_inc = "$path_mantis/config_inc.php";
    if (file_exists($filename_config_inc)) {
       include_once $filename_config_inc;
    } else {
    	echo "File not loaded: $filename_config_inc<br/>";
    }

    // --- get information and set codev Config variables
    $status_enum_string     = isset($g_status_enum_string) ? $g_status_enum_string : $s_status_enum_string;
    $priority_enum_string   = isset($g_priority_enum_string) ? $g_priority_enum_string : $s_priority_enum_string;
    $resolution_enum_string = isset($g_resolution_enum_string) ? $g_resolution_enum_string : $s_resolution_enum_string;

    echo "DEBUG add statusNames<br/>";
    $desc = T_("status Names as defined in Mantis (status_enum_string)");
    Config::getInstance()->setValue(Config::id_statusNames, $status_enum_string, Config::configType_keyValue , $desc);

    echo "DEBUG add priorityNames<br/>";
    $desc = T_("priority Names as defined in Mantis (priority_enum_string)");
    $formatedString = str_replace("'", " ", $priority_enum_string);
    Config::getInstance()->setValue(Config::id_priorityNames, $formatedString, Config::configType_keyValue , $desc);

    echo "DEBUG add resolutionNames<br/>";
    $desc = T_("resolution Names as defined in Mantis (resolution_enum_string)");
    $formatedString = str_replace("'", " ", $resolution_enum_string);
    Config::getInstance()->setValue(Config::id_resolutionNames, $formatedString, Config::configType_keyValue , $desc);

    echo "DEBUG add bug_resolved_status_threshold<br/>";
    $bug_resolved_status_threshold = isset($g_bug_resolved_status_threshold) ? $g_bug_resolved_status_threshold : constant("RESOLVED");
    $desc = T_("bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)");
    Config::getInstance()->setValue(Config::id_bugResolvedStatusThreshold, "$bug_resolved_status_threshold", Config::configType_int , $desc);

    echo "DEBUG create constants.php<br/>";
    $install = new Install();
    $install->createConstantsFile();

}

?>

