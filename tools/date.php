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

<?php

require_once('tc_calendar.php');

   $_POST[page_name] = "Date converstion"; 
   include 'header.inc.php'; 

   include 'menu.inc.php';
   
   
?>

<script language="JavaScript">
  function submitForm1() {
    document.forms["form1"].action.value = "dateToTimestamp";
    document.forms["form1"].submit();
  }

  function submitForm2() {
     document.forms["form2"].action.value = "timestampToDate";
     document.forms["form2"].submit();
   }
  
</script>


<?php

function setCalendarToDateForm($defaultDate1) {
	
  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate1);
           
  $myCalendar1 = new tc_calendar("date1", true, false);
  $myCalendar1->setIcon("../calendar/images/iconCalendar.gif");
  $myCalendar1->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar1->setPath("../calendar/");
  $myCalendar1->setYearInterval(2010, 2025);
  $myCalendar1->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar1->setDateFormat('Y-m-d');
  $myCalendar1->startMonday(true);

  echo "<div class=left>";
  // Create form
  echo "<form id='form1' name='form1' method='post' action='date.php'>\n";
  
  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; 
  $myCalendar1->writeScript();

  echo "&nbsp;<input type=button value='Convert to Timestamp' onClick='javascript: submitForm1()'>\n";
  
  echo "<input type=hidden name=action      value=noAction>\n";
  
  echo "</form>\n";
  echo "</div>";
}

function setTimestampToDateForm($timestamp) {
	
  echo "<div class=left>";
  // Create form
  echo "<form id='form2' name='form2' method='post' action='date.php'>\n";
  

  echo("Timestamp: <input name='timestamp' type='text' id='timestamp' value='$timestamp'>\n");
  echo "&nbsp;<input type=button value='Convert to Date' onClick='javascript: submitForm2()'>\n";
    
  echo "<input type=hidden name=action      value=noAction>\n";
    
  echo "</form>\n";
  echo "</div>";
}



// =========== MAIN ==========

$date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", time());
$timestamp    = $_POST[timestamp];

echo "<br/>";
echo "<br/>";
echo "<br/>";
echo "<br/>";
setCalendarToDateForm($date1);

$formatedDate = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : NULL;

echo "<br/>";
setTimestampToDateForm($timestamp);


$action = $_POST[action];
if ("dateToTimestamp" == $action) {

	if (NULL != $formatedDate) {
	   $timestamp = date2timestamp($formatedDate);
	   echo "<br/>$formatedDate => $timestamp<br/>";
	   
	   $_POST[timestamp] = $timestamp;
	}
	
} elseif ("timestampToDate" == $action) {
	echo "<br/>$timestamp => ".date("Y-m-d H:i:s", $timestamp)."<br/>";
}






?>