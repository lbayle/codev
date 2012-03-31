<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 

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

$page_name = T_("Holidays Report");
include 'header.inc.php';

include 'login.inc.php';
include 'menu.inc.php';
?>
<br/>
<?php include 'menu_holidays.inc.php'; ?>

<script language="JavaScript">
 function submitForm() {
   document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
   document.forms["form1"].year.value = document.getElementById('yearSelector').value;
   document.forms["form1"].action.value = "displayHolidays";
   document.forms["form1"].is_modified.value= "true";
   document.forms["form1"].submit();
 }
</script>

<div id="content" class="center">

<?php

include_once "user.class.php";
include_once "holidays.class.php";

$logger = Logger::getLogger("holidays_report");

// ---------------------------------------------

function  displayHolidaysReportForm($teamid, $curYear, $isExternalTasks = false, $is_modified = "false") {
  
  global $logger;
  
  echo "<form id='form1' name='form1' method='post' action='holidays_report.php'>\n";

  echo T_("Team").": \n";
  echo "<select id='teamidSelector' name='teamidSelector' onchange='javascript: submitForm()'>\n";
  $query = "SELECT id, name FROM `codev_team_table` ORDER BY name";
   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

  while($row = mysql_fetch_object($result))
  {
    if ($row->id == $teamid) {
      echo "<option selected value='".$row->id."'>".$row->name."</option>\n";
    } else {
      echo "<option value='".$row->id."'>".$row->name."</option>\n";
    }
  }
  echo "</select>\n";

  echo T_("Year").": \n";
  echo "<select id='yearSelector' name='yearSelector' onchange='javascript: submitForm()'>\n";
  for ($y = ($curYear -2); $y <= ($curYear +2); $y++) {

  	 if ($y == $curYear) {
      echo "<option selected value='".$y."'>".$y."</option>\n";
    } else {
      echo "<option value='".$y."'>".$y."</option>\n";
    }
  }
  echo "</select>\n";

  $isChecked = $isExternalTasks ? "CHECKED" : "";
  echo "&nbsp;<input type=CHECKBOX  $isChecked name='cb_extTasks' id='cb_extTasks' onChange='javascript: submitForm()'>".T_("Show external tasks")."</input>\n";
  
  echo "<input type=hidden name=teamid  value=1>\n";
  echo "<input type=hidden name=year    value=2010>\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=is_modified  value=$is_modified>\n";
  echo "<input type=hidden name=currentForm  value=displayHolidays>\n";
  echo "<input type=hidden name=nextForm     value=displayHolidays>\n";
  echo "</form>\n";
  echo "<br/>";
}

// ---------------------------------------------
function displayHolidaysMonth($month, $year, $teamid, $isExternalTasks = false) {

  global $logger;

  $holidays = Holidays::getInstance();
  $green="A8FFBD";
  $green2="75FFDA";
  $yellow="F8FFA8";
  $orange="FFC466";

  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $monthFormated = date("F Y", $monthTimestamp);
  $nbDaysInMonth = date("t", $monthTimestamp);

  $startT = mktime(0, 0, 0, $month, 1, $year);
  $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

  echo "<div align='center'>\n";
  echo "<table width='80%'>\n";
  echo "<caption>$monthFormated</caption>\n";
  echo "<tr>\n";
  echo "<th></th>\n";
  for ($i = 1; $i <= $nbDaysInMonth; $i++) {
    if ($i < 10 ) {
      echo "<th>0$i</th>\n";
    }
    else {
      echo "<th>$i</th>\n";
    }
  }
  echo "</tr>\n";

  // USER
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";

   $result = mysql_query($query);
   if (!$result) {
      $logger->error("Query FAILED: $query");
      $logger->error(mysql_error());
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }
  while($row = mysql_fetch_object($result))
  {
	  	$user1 = UserCache::getInstance()->getUser($row->user_id);

	   // if user was working on the project within the timestamp
	   if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
           ($user1->isTeamManager($teamid, $startT, $endT))) {

		    $daysOf = $user1->getDaysOfInMonth($startT, $endT);

		    $astreintes = $user1->getAstreintesInMonth($startT, $endT);
		   
			if ($isExternalTasks) {
               $externalTasks = $user1->getExternalTasksInMonth($startT, $endT);
			} else {	
               $externalTasks = array();
			}
			
		    echo "<tr>\n";
		    echo "<td title='$row->realname'>$row->username</td>\n";

		    for ($i = 1; $i <= $nbDaysInMonth; $i++) {


            if (isset($externalTasks["$i"]) && (NULL != $externalTasks["$i"])) {
              echo "<td style='background-color: #$green2; text-align: center;' title='".T_("ExternalTask")."'>".$externalTasks[$i]."</td>\n";

            } elseif (isset($astreintes["$i"]) && (NULL != $astreintes["$i"])) {
              echo "<td style='background-color: #$yellow; text-align: center;' title='".T_("OnDuty")."'>".$daysOf[$i]."</td>\n";

            } elseif (isset($daysOf["$i"]) && (NULL != $daysOf["$i"])) {

		        echo "<td style='background-color: #$green; text-align: center;'>".$daysOf[$i]."</td>\n";
		      } else {

              // If weekend or holiday, display gray
               $timestamp = mktime(0, 0, 0, $month, $i, $year);
		      	$h = $holidays->isHoliday($timestamp);
		         if (NULL != $h) {
                   echo "<td style='background-color: #$h->color;' title='$h->description'></td>\n";
		         } else {
                   echo "<td></td>\n";
		         }
		      }
		    }
		    echo "</tr>\n";
	   }
  }
  echo "</table>\n";
  echo "<br/><br/>\n";
  echo "<div>\n";
}

// ================ MAIN =================
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;

global $codevReportsDir;

$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;

// 'is_modified' is used because it's not possible to make a difference
// between an unchecked checkBox and an unset checkbox variable
$is_modified = isset($_POST['is_modified']) ? $_POST['is_modified'] : "false";
if ("false" == $is_modified) {
   $isExternalTasks = true; // default value
} else {
   $isExternalTasks   = isset($_POST['cb_extTasks']) ? true : false;
}

displayHolidaysReportForm($teamid, $year, $isExternalTasks, $is_modified);
$_POST['year'] = $year;

for ($i = 1; $i <= 12; $i++) {
  displayHolidaysMonth($i, $year, $teamid, $isExternalTasks);
}
?>

</div>

<?php include 'footer.inc.php'; ?>
