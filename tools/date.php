<?php
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

function setCalendarToDateForm($defaultDate1) {
   echo "<div class=left>";
   // Create form
   echo '<link type="text/css" href="lib/jquery/css/Aristo/Aristo.css" rel="Stylesheet" />';
   echo '<script type="text/javascript" src="lib/jquery/jquery.min.js"></script>';
   echo '<script type="text/javascript" src="lib/jquery/js/jquery.bgiframe-2.1.2.js"></script>';
   echo '<script type="text/javascript" src="lib/jquery.bgiframe/jquery.bgiframe.min.js"></script>';
   echo '<script type="text/javascript" src="js_min/datepicker.min.js"></script>';
   echo "<form id='form1' name='form1' method='post' action='$_SERVER[PHP_SELF]'>\n";
   echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
   echo '<script type="text/javascript">';
   echo 'jQuery(document).ready(function() {';
   echo 'jQuery("#datepicker").datepicker("setDate" ,"'.$defaultDate1.'");';
   echo '});';
   echo '</script>';
   echo '<input type="text" id="datepicker" class="datepicker" name="date" maxlength="10" size="10" title="Date" />';
   echo "&nbsp;<input type=submit value='Convert to Timestamp' />\n";
   echo "</form>\n";
   echo "</div>";
}

function setTimestampToDateForm($timestamp) {
   echo "<div class=left>";
   // Create form
   echo "<form id='form2' name='form2' method='post' action='$_SERVER[PHP_SELF]'>\n";
   echo("Timestamp: <input name='timestamp' type='text' id='timestamp' value='$timestamp'>\n");
   echo "&nbsp;<input type=submit value='Convert to Date'>\n";
   echo "</form>\n";
   echo "</div>";
}

// =========== MAIN ==========
echo '<html><head><base href="'.Tools::getServerRootURL().'/" /></head><body>';
$date1 = Tools::getSecurePOSTStringValue("date",date("Y-m-d", time()));
setCalendarToDateForm($date1);
echo "<br/>";

$timestamp = Tools::getSecurePOSTIntValue("timestamp",0);
setTimestampToDateForm($timestamp);

if (isset($_POST["date"])) {
   $timestamp = Tools::date2timestamp($date1);
   echo "<br/>$formatedDate => $timestamp<br/>";
} elseif (isset($_POST["timestamp"])) {
   echo "<br/>$timestamp => ".date("Y-m-d H:i:s", $timestamp)."<br/>";
}

?>
