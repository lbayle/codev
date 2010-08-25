<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include '../header.inc.php'; ?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<h1>Indicateurs de production</h1>


<script language="JavaScript">
  function submitForm() {
    document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
    document.forms["form1"].action.value = "timeTrackingReport";
    document.forms["form1"].submit();
 }
</script>

<div id="content">

<?php

include_once "../constants.php";
include_once "../tools.php";
include_once "../reports/period_stats.class.php";

include_once "time_tracking.class.php";
require_once('calendar/classes/tc_calendar.php');

function setInfoForm($teamid, $defaultDate1, $defaultDate2) {
  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate1);
           
  $myCalendar1 = new tc_calendar("date1", true, false);
  $myCalendar1->setIcon("calendar/images/iconCalendar.gif");
  $myCalendar1->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar1->setPath("calendar/");
  $myCalendar1->setYearInterval(2010, 2015);
  $myCalendar1->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar1->setDateFormat('Y-m-d');
  $myCalendar1->startMonday(true);

  list($defaultYear, $defaultMonth, $defaultDay) = explode('-', $defaultDate2);
        
  $myCalendar2 = new tc_calendar("date2", true, false);
  $myCalendar2->setIcon("calendar/images/iconCalendar.gif");
  $myCalendar2->setDate($defaultDay, $defaultMonth, $defaultYear);
  $myCalendar2->setPath("calendar/");
  $myCalendar2->setYearInterval(2010, 2015);
  $myCalendar2->dateAllow('2010-01-01', '2015-12-31');
  $myCalendar2->setDateFormat('Y-m-d');
  $myCalendar2->startMonday(true);

  // Create form
  if (isset($_GET['debug'])) {
      echo "<form id='form1' name='form1' method='post' action='time_tracking_report.php?debug'>\n";
  } else {
  	   echo "<form id='form1' name='form1' method='post' action='time_tracking_report.php'>\n";
  }
  
  echo "Team: <select id='teamidSelector' name='teamidSelector'>\n";
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

  echo "&nbsp;Date d&eacute;but: "; $myCalendar1->writeScript();

  echo "&nbsp;Date fin (inclu): "; $myCalendar2->writeScript();

  echo "&nbsp;<input type=button value='Envoyer' onClick='javascript: submitForm()'>\n";

  echo "<input type=hidden name=teamid  value=1>\n";
        
  echo "<input type=hidden name=currentAction value=setInfoForm>\n";
  echo "<input type=hidden name=nextAction    value=timeTrackingReport>\n";

  echo "</form>\n";
}

function displayRates ($timeTracking) {
  global $status_resolved;
  global $status_closed;
         
  $prodDays                = $timeTracking->getProdDays();
  $sideProdDays            = $timeTracking->getProdDaysSideTasks();
  $productivityRate        = $timeTracking->getProductivityRate();
  $efficiencyRate          = $timeTracking->getEfficiencyRate();
  $systemDisponibilityRate = $timeTracking->getSystemDisponibilityRate();
  $productionDaysForecast  = $timeTracking->getProductionDaysForecast();
        
  $periodStats = new PeriodStats($timeTracking->startTimestamp, $timeTracking->endTimestamp);
        
  $derive = $periodStats->getDrift($status_resolved) + $periodStats->getDrift($status_closed);
  $derive = - $derive;
        
  echo "<table>\n";
  echo "<caption>Indicateurs de productivit&eacute;</caption>\n";
  echo "<tr>\n";
  echo "<th>Indicateur</th>\n";
  echo "<th>Valeur</th>\n";
  echo "<th>Description</th>\n";
  echo "<th>Formule</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>Production Days : FDJ</td>\n";
  echo "<td>$prodDays</td>\n";
  echo "<td>nombre de jours pass&eacute;s sur les projets FDJ</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>Production Days : SuiviOp</td>\n";
  echo "<td>$sideProdDays</td>\n";
  echo "<td>nombre de jours pass&eacute;s sur les taches annexes</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>Production Days : total</td>\n";
  echo "<td>".($sideProdDays + $prodDays)."</td>\n";
  echo "<td>nombre de jours factur&eacute;s</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='Production Days : forecast'>Capacit&eacute; de production</td>\n";
  echo "<td>".$productionDaysForecast."</td>\n";
  echo "<td>pr&eacute;vision de capacit&eacute; (en fonction des cong&eacute;s)</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='ResolvedIssues * IssueDifficulty / prodDaysFDJ'>Productivity Rate</td>\n";
  echo "<td>".number_format($productivityRate, 2)."</td>\n";
  echo "<td>nombre de bugs resolus par jour. Les bugs réouverts ne sont pas comptabilis&eacute;s</td>\n";
  echo "<td>nbResolvedIssues * IssueDifficulty / prodDaysFDJ</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='Days spend on FDJ projects / total prodDays * 100'>Efficiency Rate</td>\n";
  echo "<td>".number_format($efficiencyRate, 2)."%</td>\n";
  echo "<td>temps quotidien pass&eacute; &agrave; la resolution de bugs</td>\n";
  echo "<td>prodDaysFDJ / total prodDays * 100</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td>System Disponibility</td>\n";
  echo "<td>".number_format($systemDisponibilityRate, 3)."%</td>\n";
  echo "<td>disponibilit&eacute; de la plateforme de develomppement</td>\n";
  echo "<td>100 - (nb breakdown days / prodDays)</td>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  echo "<td title='si n&eacute;gatif, nous sommes en avance sur le planing'>D&eacute;rive</td>\n";
  echo "<td>".number_format($derive, 1)."</td>\n";
  echo "<td title='si n&eacute;gatif, nous sommes en avance sur le planing'>nb jours de d&eacute;passement sur les fiches Resolved/Closed</td>\n";
  echo "<td></td>\n";
  echo "</tr>\n";
        
  echo "</table>\n";

  //echo "<br/>SideTasks<br/>";
  //echo "Nb Production Days  : $sideProdDays<br/>";
  //echo "ProductivityRate    : ".$sideProductivityRate."<br/>\n";
}

// --------------------------------
function displayWorkingDaysPerJob($timeTracking) {
  echo "<table width='300'>\n";
  echo "<caption>Charge par poste</caption>\n";
  echo "<tr>\n";
  echo "<th>Poste</th>\n";
  echo "<th>Nb jours</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  $query     = "SELECT id, name FROM `codev_job_table`";
  $result    = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    echo "<tr>\n";
    echo "<td>$row->name</td>\n";
    echo "<td>".$timeTracking->getWorkingDaysPerJob($row->id)."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

function displayWorkingDaysPerProject($timeTracking) {
  echo "<table width='300'>\n";
  echo "<caption>Charge par projet</caption>\n";
  echo "<tr>\n";
  echo "<th>Projet</th>\n";
  echo "<th>Nb jours</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  $query     = "SELECT id, name FROM `mantis_project_table` ORDER BY name";
  $result    = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    echo "<tr>\n";
    echo "<td>$row->name</td>\n";
    echo "<td>".$timeTracking->getWorkingDaysPerProject($row->id)."</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

function displayProjectDetails($timeTracking) {
  echo "<table width='300'>\n";
  echo "<caption>Detail Suivi Op.</caption>\n";
  echo "<tr>\n";
  echo "<th>Projet</th>\n";
  echo "<th>Nb jours</th>\n";
  echo "</tr>\n";

  echo "<tr>\n";
  // TODO '11' must be replaced by a query in codev_team_project_table to
  // find the proj with type=1 that is associated to that team. 
  $durationPerCategory = $timeTracking->getProjectDetails(11);  // 11 = Suivi Op.
  foreach ($durationPerCategory as $catName => $duration)
  {
    echo "<tr bgcolor='white'>\n";
    echo "<td>$catName</td>\n";
    echo "<td>$duration</td>\n";
    echo "</tr>\n";
  }
  echo "</table>\n";
}

function displayCheckWarnings($timeTracking) {
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $timeTracking->team_id ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";   
   
  $result = mysql_query($query) or die("Query failed: $query");
   
  echo "<p style='color:red'>\n";
   
  while($row = mysql_fetch_object($result))
  {
    $incompleteDays = $timeTracking->checkCompleteDays($row->user_id, TRUE);
    foreach ($incompleteDays as $date => $value) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate incomplet ($value jour).\n";
    }
                   
    $missingDays = $timeTracking->checkMissingDays($row->user_id);
    foreach ($missingDays as $date) {
      $formatedDate = date("Y-m-d", $date);
      echo "<br/>$row->username: $formatedDate non d&eacute;finie.\n";
    }
  }
  echo "</p>\n";
}

// =========== MAIN ==========
$year = date('Y');

// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$weekDates      = week_dates(date('W'),$year);

$teamid = isset($_POST[teamid]) ? $_POST[teamid] : 1;
$date1  = isset($_REQUEST["date1"]) ? $_REQUEST["date1"] : date("Y-m-d", $weekDates[1]);
$date2  = isset($_REQUEST["date2"]) ? $_REQUEST["date2"] : date("Y-m-d", $weekDates[5]);

$startTimestamp = date2timestamp($date1);
$endTimestamp = date2timestamp($date2);

$endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

//echo "DEBUG startTimestamp $startTimestamp  ".date("Y-m-d H:i:s", $startTimestamp)."<br/>";
//echo "DEBUG endTimestamp $endTimestamp  ".date("Y-m-d H:i:s", $endTimestamp)."<br/>";

$timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);
        
setInfoForm($teamid, $date1, $date2);

echo "<br/>";
echo "du ".date("Y-m-d  -  H:i:s", $startTimestamp)."&nbsp;";
echo "au ".date("Y-m-d  -  H:i:s", $endTimestamp)."<br/>";
echo "<br/>";
   
echo "<br/>";
displayWorkingDaysPerJob($timeTracking);

echo "<br/>";
echo "<br/>";
displayWorkingDaysPerProject($timeTracking);

echo "<br/>";
echo "<br/>";
displayProjectDetails($timeTracking);

echo "<br/>";
echo "<br/>";
displayRates($timeTracking);
        
echo "<br/>";
echo "<br/>";
displayCheckWarnings($timeTracking);

?>

</div>

<?php include '../footer.inc.php'; ?>
