<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php include '../header.inc.php'; ?>

<?php include '../login.inc.php'; ?>
<?php include '../menu.inc.php'; ?>

<h1>Pr&eacute;vision cong&eacute;s</h1>

<script language="JavaScript">
 function submitForm() {
   document.forms["form1"].teamid.value = document.getElementById('teamidSelector').value;
   document.forms["form1"].action.value = "displayHolidays";
   document.forms["form1"].submit();
 }
</script>

<div id="content" class="center">

<?php 

include_once "../constants.php";
include_once "../tools.php";
include_once "holidays.class.php";

function  displayHolidaysReportForm($teamid) {
  echo "<form id='form1' name='form1' method='post' action='holidays_report.php'>\n";

  echo "Team: \n";
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
   
  echo "<input type=hidden name=teamid  value=1>\n";
   
  echo "<input type=hidden name=action       value=noAction>\n";
  echo "<input type=hidden name=currentForm  value=displayHolidays>\n";
  echo "<input type=hidden name=nextForm     value=displayHolidays>\n";
  echo "</form>\n";  
  echo "<br/>";
}

function displayHolidaysMonth($month, $year, $teamid) {
  global $globalHolidaysList;
        
  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $monthFormated = date("F Y", $monthTimestamp); 
  $nbDaysInMonth = date("t", $monthTimestamp);

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
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";   
   
  $result = mysql_query($query) or die("Query failed: $query");
  while($row = mysql_fetch_object($result))
  {
    $holidays = new Holidays($row->user_id, $year);
    $daysOf = $holidays->getDaysOfInMonth($month);
              
    echo "<tr>\n";
    echo "<td>$row->username</td>\n";
              
    for ($i = 1; $i <= $nbDaysInMonth; $i++) {        
      if (NULL != $daysOf[$i]) {
      	
        echo "<td style='background-color: #A8FFBD; text-align: center;'>".$daysOf[$i]."</td>\n";
      } else {
        $timestamp = mktime(0, 0, 0, $month, $i, $year);
        $dayOfWeek = date("N", $timestamp);
                        
        // If weekend or holiday, display gray
        if (($dayOfWeek > 5) || 
            (in_array(date("Y-m-d", $timestamp), $globalHolidaysList))) { 
          echo "<td style='background-color: #d8d8d8;'></td>\n";
        } else {
          echo "<td></td>\n";
        }
      }
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
  echo "<br/><br/>\n";
  echo "<div>\n";
}

// ================ MAIN =================
$year = date('Y');
$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;

$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) 
  or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;

displayHolidaysReportForm($teamid);

for ($i = 1; $i <= 12; $i++) {
  displayHolidaysMonth($i, $year, $teamid);
}

?>

</div>

<?php include '../footer.inc.php'; ?>
