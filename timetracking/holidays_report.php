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

<?php
   include_once 'i18n.inc.php';

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}

   $_POST['page_name'] = T_("Holidays Report");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>
<br/>
<?php include 'menu_holidays.inc.php'; ?>


<script language="JavaScript">
 function submitForm() {
   document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
   document.forms["form1"].year.value = document.getElementById('yearSelector').value;
   document.forms["form1"].action.value = "displayHolidays";
   document.forms["form1"].submit();
 }
</script>

<div id="content" class="center">

<?php

include_once "user.class.php";
include_once "holidays.class.php";

// ---------------------------------------------

function  displayHolidaysReportForm($teamid, $curYear) {
  echo "<form id='form1' name='form1' method='post' action='holidays_report.php'>\n";

  echo T_("Team").": \n";
  echo "<select id='teamidSelector' name='teamidSelector' onchange='javascript: submitForm()'>\n";
  $query = "SELECT id, name FROM `codev_team_table` ORDER BY name";
  $result = mysql_query($query) or die("Query failed: $query");

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

  echo "<input type=hidden name=teamid  value=1>\n";
  echo "<input type=hidden name=year    value=2010>\n";

  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=currentForm  value=displayHolidays>\n";
  echo "<input type=hidden name=nextForm     value=displayHolidays>\n";
  echo "</form>\n";
  echo "<br/>";
}

// ---------------------------------------------
function displayHolidaysMonth($month, $year, $teamid) {

  $holidays = Holidays::getInstance();
  $green="A8FFBD";
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

  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
	  	$user1 = UserCache::getInstance()->getUser($row->user_id);

	   // if user was working on the project within the timestamp
	   if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
          ($user1->isTeamManager($teamid, $startT, $endT))) {

		   $daysOf = $user1->getDaysOfInMonth($startT, $endT);

		   $astreintes = $user1->getAstreintesInMonth($startT, $endT);

		    echo "<tr>\n";
		    echo "<td title='$row->realname'>$row->username</td>\n";

		    for ($i = 1; $i <= $nbDaysInMonth; $i++) {


            if (NULL != $astreintes[$i]) {
              echo "<td style='background-color: #$yellow; text-align: center;' title='".T_("OnDuty")."'>".$daysOf[$i]."</td>\n";

            } elseif (NULL != $daysOf[$i]) {

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


// ---------------------------------------------
// format: nom;prenom;trigramme;date de debut;date de fin;nb jours
// format date: "jj/mm/aa"
function exportHolidaystoCSV($month, $year, $teamid, $path="") {

  $sepChar=';';

  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $nbDaysInMonth = date("t", $monthTimestamp);
  $startT = mktime(0, 0, 0, $month, 1, $year);
  $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

   // create filename & open file
   $query = "SELECT name FROM `codev_team_table` WHERE id = $teamid";
   $result = mysql_query($query) or die(T_("Query failed:")." $query");
   $teamName  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : $teamid;

   $myFile = $path.DIRECTORY_SEPARATOR."holidays_".$teamName."_".date("Ym", $monthTimestamp).".csv";
   $fh = fopen($myFile, 'w');

  // USER
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";


  $result = mysql_query($query) or die(T_("Query failed:")." $query");
  while($row = mysql_fetch_object($result))
  {
      $user1 = UserCache::getInstance()->getUser($row->user_id);

      // if user was working on the project within the timestamp
      if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
          ($user1->isTeamManager($teamid, $startT, $endT))) {

         $daysOf = $user1->getDaysOfInMonth($startT, $endT);

           // concatenate days
         $startBlockTimestamp = 0;
         $endBlockTimestamp = 0;
         $blockSize = 0;

         for ($i = 1; $i <= $nbDaysInMonth; $i++) {
            if (NULL != $daysOf[$i]) {

               $evtTimestamp = mktime(0, 0, 0, $month, $i, $year);

               if (1 == $daysOf[$i]) {
               	// do not write, concatenate evt to block
                  if (0 == $startBlockTimestamp) {$startBlockTimestamp = $evtTimestamp; }
               	$blockSize += 1;
                  $endBlockTimestamp = $evtTimestamp;

               } else {
               	// write previous block if exist
                  if (0 != $blockSize) {
                     $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";
                     fwrite($fh, $stringData);
                     $startBlockTimestamp = 0;
                     $endBlockTimestamp = 0;
                     $blockSize = 0;
                  }

                  // write current line ( < 1)
                  $evtDate      = date("d/m/y", $evtTimestamp);
                  $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             $evtDate.$sepChar.
                             $evtDate.$sepChar.
                             $daysOf[$i]."\n";
                  fwrite($fh, $stringData);
               }


            } else {
                  // write previous block if exist
            	if (0 != $blockSize) {
                  $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";
                  fwrite($fh, $stringData);
                  $startBlockTimestamp = 0;
                  $endBlockTimestamp = 0;
                  $blockSize = 0;
               }

            }
          }
          if (0 != $blockSize) {
                 $stringData = $user1->getFirstname().$sepChar.$user1->getLastname().$sepChar.$user1->getShortName().$sepChar.
                             date("d/m/y", $startBlockTimestamp).$sepChar.
                             date("d/m/y", $endBlockTimestamp).$sepChar.
                             $blockSize."\n";
                  fwrite($fh, $stringData);
                  $startBlockTimestamp = 0;
                  $endBlockTimestamp = 0;
                  $blockSize = 0;
          }
      }
  }
  fclose($fh);
}

// ================ MAIN =================
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;

global $codevReportsDir;

$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;

displayHolidaysReportForm($teamid, $year);
$_POST['year'] = $year;

for ($i = 1; $i <= 12; $i++) {
  displayHolidaysMonth($i, $year, $teamid);
}
/*
for ($i = 1; $i <= 12; $i++) {
  exportHolidaystoCSV($i, $year, $teamid, $codevReportsDir);
}
*/
?>

</div>

<?php include 'footer.inc.php'; ?>
