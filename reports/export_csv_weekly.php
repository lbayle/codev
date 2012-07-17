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

include_once('reports/export_csv_tools.php');

include_once('classes/issue_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/time_tracking.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("export_csv");

/**
 * Export week activity
 * @param int $teamid
 * @param $weekDates
 * @param TimeTracking $timeTracking
 * @param string $myFile
 * @return string
 */
function exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile) {
   $sepChar=';';

   // create filename & open file
   $fh = fopen($myFile, 'w');

   $stringData = T_("Task").$sepChar.
                 T_("Job").$sepChar.
                 T_("Description").$sepChar.
                 T_("Assigned to").$sepChar.
                 Tools::formatDate("%A %d/%m", $weekDates[1]).$sepChar.
                 Tools::formatDate("%A %d/%m", $weekDates[2]).$sepChar.
                 Tools::formatDate("%A %d/%m", $weekDates[3]).$sepChar.
                 Tools::formatDate("%A %d/%m", $weekDates[4]).$sepChar.
                 Tools::formatDate("%A %d/%m", $weekDates[5])."\n";
   fwrite($fh, $stringData);

   $query = "SELECT codev_team_user_table.user_id, mantis_user_table.realname ".
            "FROM  `codev_team_user_table`, `mantis_user_table` ".
            "WHERE  codev_team_user_table.team_id = $teamid ".
            "AND    codev_team_user_table.user_id = mantis_user_table.id ".
            "ORDER BY mantis_user_table.realname";

   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      // if user was working on the project during the timestamp
      $user = UserCache::getInstance()->getUser($row->user_id);
      if (($user->isTeamDeveloper($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp)) ||
         ($user->isTeamManager($teamid, $timeTracking->startTimestamp, $timeTracking->endTimestamp))) {
         exportWeekDetailsToCSV($row->user_id, $timeTracking, $user->getShortname(), $fh);
      }
   }
   fclose($fh);
   return $myFile;
}

/**
 * @param int $userid
 * @param TimeTracking $timeTracking
 * @param string $realname
 * @param resource $fh
 */
function exportWeekDetailsToCSV($userid, TimeTracking $timeTracking, $realname, $fh) {
   global $logger;

   $sepChar=';';

   $weekTracks = $timeTracking->getWeekDetails($userid);
   foreach ($weekTracks as $bugid => $jobList) {

      try {
         $issue = IssueCache::getInstance()->getIssue($bugid);

         // remove sepChar from summary text
         $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

         foreach ($jobList as $jobid => $dayList) {

            $query  = "SELECT name FROM `codev_job_table` WHERE id=$jobid";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            $jobName = SqlWrapper::getInstance()->sql_result($result, 0);
            $stringData = $bugid.$sepChar.
                        $jobName.$sepChar.
                        $formatedSummary.$sepChar.
                        $realname.$sepChar;
            for ($i = 1; $i <= 4; $i++) {
               $stringData .= $dayList[$i].$sepChar;
            }
            $stringData .= $dayList[5]."\n";
            fwrite($fh, $stringData);
         }
      } catch (Exception $e) {
         $logger->error('exportWeekDetailsToCSV(): issue $bugid not found in mantis DB !');
      }
   }
}

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
         $managedIssuesfile = exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $managedIssuesfile);
         $reports[] = array('file' => basename($managedIssuesfile),
                            'title' => T_('Export Managed Issues'),
                            'subtitle' => T_('Issues form Team projects, including issues assigned to other teams')
         );

         $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $teamid);

         $weekActivityReportfile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_CRA_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
         $weekActivityReportfile = exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $weekActivityReportfile);
         $reports[] = array('file' => basename($weekActivityReportfile),
            'title' => T_('Export Week').' '.$weekid.' '.T_('Member Activity')
         );

         $projectActivityFile = $codevReportsDir.DIRECTORY_SEPARATOR.$formatedteamName."_projects_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
         $projectActivityFile = exportProjectActivityToCSV($timeTracking, $projectActivityFile);
         $reports[] = array('file' => basename($projectActivityFile),
            'title' => T_('Export Week').' '.$weekid.' '.T_('Projects Activity')
         );

         $smartyHelper->assign('reports', $reports);

         $monthsReport = "";
         // reduce scope to enhance speed
         $monthsLineReport = "";
         $startMonth = 1;
         for ($i = $startMonth; $i <= 12; $i++) {
            $myFile = exportHolidaystoCSV($i, $year, $teamid, $formatedteamName, $codevReportsDir);
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
