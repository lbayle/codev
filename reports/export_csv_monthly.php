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

require('super_header.inc.php');

require('../smarty_tools.php');

include_once "period_stats.class.php";
include_once "project.class.php";
include_once 'export_csv_tools.php';

include_once "time_tracking.class.php";

$logger = Logger::getLogger("export_csv");

// =========== MAIN ==========
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CSV Report');

global $codevReportsDir;

if(isset($_SESSION['userid'])) {
   $userid = $_SESSION['userid'];

   // team
   $user = UserCache::getInstance()->getUser($userid);
   $lTeamList = $user->getLeadedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $mTeamList = $user->getDevTeamList();
   $teamList = $mTeamList + $lTeamList + $managedTeamList;

   if (0 == count($teamList)) {
      echo "<div id='content'' class='center'>";
      echo T_("Sorry, you do NOT have access to this page.");
      echo "</div>";
   } else {
      $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      $teamid = getSecurePOSTIntValue('teamid', $defaultTeam);
      $_SESSION['teamid'] = $teamid;

      $smartyHelper->assign('teams', getTeams($teamList, $teamid));

      $query = "SELECT name FROM `codev_team_table` WHERE id = $teamid";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      
      $teamName  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : $teamid;
      $formatedteamName = str_replace(" ", "_", $teamName);

      $action = isset($_POST['action']) ? $_POST['action'] : '';

      // dates
      $month = date('m');
      $year = date('Y');

      // The first day of the current month
      $startdate = isset($_POST["startdate"]) ? $_POST["startdate"] : formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year));
      $smartyHelper->assign('startDate', $startdate);
      $startTimestamp = date2timestamp($startdate);

      // The current date plus one year
      $nbDaysInMonth  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $enddate = isset($_POST["enddate"]) ? $_POST["enddate"] : formatDate("%Y-%m-%d", mktime(23, 59, 59, $month, $nbDaysInMonth, $year));
      $smartyHelper->assign('endDate', $enddate);
      $endTimestamp = date2timestamp($enddate);
      $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

      if ("exportPeriod" == $action && 0 != $teamid) {

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_Mantis_".date("Ymd").".csv";

         exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile);
         $smartyHelper->assign('managedIssuesToCSV', basename($myFile));

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_Projects_".date("Ymd", $timeTracking->startTimestamp)."-".date("Ymd", $timeTracking->endTimestamp).".csv";

         exportProjectMonthlyActivityToCSV($timeTracking, $myFile);
         $smartyHelper->assign('projectMonthlyActivityToCSV', basename($myFile));

         // reduce scope to enhance speed
         $reports = array();
         $startMonth = 1;
         for ($i = $startMonth; $i <= 12; $i++) {
            $reports[] = basename(exportHolidaystoCSV($i, $year, $teamid, $formatedteamName, $codevReportsDir));
         }
         $smartyHelper->assign('reports', $reports);

         $smartyHelper->assign('reportsDir', $codevReportsDir);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
