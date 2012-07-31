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

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

require('reports/export_csv_tools.php');

include_once('classes/team_cache.class.php');
include_once('classes/time_tracking.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

// =========== MAIN ==========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CSV Report');
$smartyHelper->assign('activeGlobalMenuItem', 'ImportExport');

global $codevReportsDir;

if(isset($_SESSION['userid'])) {
   $user = UserCache::getInstance()->getUser($_SESSION['userid']);

   // teams
   $lTeamList = $user->getLeadedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $mTeamList = $user->getDevTeamList();
   $teamList = $mTeamList + $lTeamList + $managedTeamList;

   if (count($teamList) > 0) {
      $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      $teamid = Tools::getSecurePOSTIntValue('teamid', $defaultTeam);
      $_SESSION['teamid'] = $teamid;

      $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

      $team = TeamCache::getInstance()->getTeam($teamid);
      $formatedteamName = str_replace(" ", "_", $team->name);

      // dates
      $month = date('m');
      $year = date('Y');

      // The first day of the current month
      $startdate = Tools::getSecurePOSTStringValue("startdate", Tools::formatDate("%Y-%m-%d",mktime(0, 0, 0, $month, 1, $year)));
      $smartyHelper->assign('startDate', $startdate);
      $startTimestamp = Tools::date2timestamp($startdate);

      // The current date plus one year
      $nbDaysInMonth  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $enddate = Tools::getSecurePOSTStringValue("enddate", Tools::formatDate("%Y-%m-%d", mktime(23, 59, 59, $month, $nbDaysInMonth, $year)));
      $smartyHelper->assign('endDate', $enddate);
      $endTimestamp = Tools::date2timestamp($enddate);
      $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.

      if (isset($_POST['teamid']) && 0 != $teamid) {
         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_Mantis_".date("Ymd").".csv";

         ExportCsvTools::exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile);
         $smartyHelper->assign('managedIssuesToCSV', basename($myFile));

         $myFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_Projects_".date("Ymd", $timeTracking->startTimestamp)."-".date("Ymd", $timeTracking->endTimestamp).".csv";

         ExportCsvTools::exportProjectMonthlyActivityToCSV($timeTracking, $myFile);
         $smartyHelper->assign('projectMonthlyActivityToCSV', basename($myFile));

         // reduce scope to enhance speed
         $reports = array();
         for ($i = 1; $i <= 12; $i++) {
            $reports[] = basename(ExportCsvTools::exportHolidaystoCSV($i, $year, $teamid, $formatedteamName, $codevReportsDir));
         }
         $smartyHelper->assign('reports', $reports);

         $smartyHelper->assign('reportsDir', $codevReportsDir);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
