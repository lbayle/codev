<?php if (!isset($_SESSION)) { session_name("codevtt"); session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
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
   $_POST[page_name] = "Suivi des fiches Mantis"; 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<script language="JavaScript">
  function submitTeam() {
    // check fields
    foundError = 0;
    msgString = "Les champs suivants ont &eacute;t&eacute; oubli&eacute;s:\n\n";
        
    if (0 == document.forms["teamSelectForm"].teamid.value)  { msgString += "Team\n"; ++foundError; }
                   
    if (0 == foundError) {
      document.forms["teamSelectForm"].submit();
    } else {
      alert(msgString);    
    }    
  }
</script>

<div id="content">

<?php

//
// MANTIS CoDev History Reports
//
include_once "user.class.php";
include_once "issue.class.php";
include_once "period_stats_report.class.php";


function setTeamForm($originPage, $defaultSelection, $teamList) {
   
  // create form
  echo "<div align=center>\n";
  echo "<form id='teamSelectForm' name='teamSelectForm' method='post' action='$originPage' onchange='javascript: submitTeam()'>\n";

  echo "Team :\n";
  echo "<select name='teamid'>\n";
  echo "<option value='0'></option>\n";

   foreach ($teamList as $tid => $tname) {
  
    if ($tid == $defaultSelection) {
      echo "<option selected value='".$tid."'>".$tname."</option>\n";
    } else {
      echo "<option value='".$tid."'>".$tname."</option>\n";
    }
  }
  echo "</select>\n";

  echo "<input type=hidden name=currentForm value=teamSelectForm>\n";
  echo "<input type=hidden name=nextForm    value=editTeamForm>\n";

  echo "</form>\n";
  echo "</div>\n";
}


// ================ MAIN ================

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;


$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);


$mTeamList = $session_user->getDevTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList; 

// if current team is not in allowed list, do not display
if (NULL == $teamList[$teamid]) {
	#echo "$teamid NOT allowed<br/>";
	$teamid = 0;
}

// ---------- DISPLAY -------------

setTeamForm("mantis_reports.php", $teamid, $teamList);

if (0 != $teamid) {

	$team= new Team($teamid);
   $start_year  = date("Y", $team->date);
   $start_month = date("m", $team->date);
   $start_day   = date("d", $team->date);
	
   echo "<div align='left'>\n";
   echo "<ul>\n";
   echo "   <li><a href='#tagPeriodStats'>Bilan mensuel</a></li>\n";
   echo "</ul><br/>\n";
   echo "</div>\n";
      
      
   echo "<a name='tagPeriodStats'></a>\n";
   echo "<br/>\n";
   echo "<hr/>\n";
   echo "<br/>\n";
   $periodStatsReport = new PeriodStatsReport($start_year, $start_month, $start_day, $teamid);
	$periodStatsReport->computeReport();
	$periodStatsReport->displayHTMLReport();
	
   echo "<br/>\n";
   echo "<br/>\n";
   
}
?>

</div>

<?php include 'footer.inc.php'; ?>
