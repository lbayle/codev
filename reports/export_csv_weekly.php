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

if(isset($_SESSION['userid'])) {
   $userid = $_SESSION['userid'];

   // team
   $session_user = UserCache::getInstance()->getUser($userid);
   $mTeamList = $session_user->getDevTeamList();
   $lTeamList = $session_user->getLeadedTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $mTeamList + $lTeamList + $managedTeamList;

   if (0 != count($teamList)) {
      $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
      $teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
      $_SESSION['teamid'] = $teamid;

      $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

      $weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');
      $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
      $smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));

      $smartyHelper->assign('years', SmartyTools::getYears($year,2));

      if (isset($_POST['teamid']) && 0 != $teamid) {
         global $codevReportsDir;
         $formatedteamName = TeamCache::getInstance()->getTeam($teamid)->getName();

         $weekDates      = Tools::week_dates($weekid,$year);
         $startTimestamp = $weekDates[1];
         $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5]));

         $reports = "";

         $managedIssuesfile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_Mantis_".Tools::formatDate("%Y%m%d",time()).".csv";
         $managedIssuesfile = ExportCsvTools::exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $managedIssuesfile);
         $reports[] = array('file' => basename($managedIssuesfile),
                            'title' => T_('Export Managed Issues'),
                            'subtitle' => T_('Issues form Team projects, including issues assigned to other teams')
         );

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $weekActivityReportfile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_CRA_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
         $weekActivityReportfile = ExportCsvTools::exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $weekActivityReportfile);
         $reports[] = array('file' => basename($weekActivityReportfile),
            'title' => T_('Export Week').' '.$weekid.' '.T_('Member Activity')
         );

         $projectActivityFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_projects_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
         $projectActivityFile = ExportCsvTools::exportProjectActivityToCSV($timeTracking, $projectActivityFile);
         $reports[] = array('file' => basename($projectActivityFile),
            'title' => T_('Export Week').' '.$weekid.' '.T_('Projects Activity')
         );

         $smartyHelper->assign('reports', $reports);

         $monthsReport = "";
         // reduce scope to enhance speed
         $monthsLineReport = "";
         $startMonth = 1;
         for ($i = $startMonth; $i <= 12; $i++) {
            $myFile = ExportCsvTools::exportHolidaystoCSV($i, $year, $teamid, $formatedteamName, $codevReportsDir);
            $monthsLineReport[] = array('file' => basename($myFile));
         }

         $monthsReport['title'] = T_('Export Holidays').' '.$year;
         $monthsReport['line'] = $monthsLineReport;
         $smartyHelper->assign('monthsReport', $monthsReport);

         $smartyHelper->assign('reportsDir', $codevReportsDir);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
