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

class ExportCSVWeeklyController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("check");

      $reportsDir = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports';

      if (!is_dir($reportsDir)) {
         $retCode = mkdir($reportsDir, 0755);
         if (FALSE == $retCode) {
            self::$logger->error("could not create reports directory:".$reportsDir);
            echo "ERROR could not create reports directory:".$reportsDir;
         }
      }

   }

   protected function display() {
      if(Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            $weekid = isset($_POST['weekid']) ? $_POST['weekid'] : date('W');
            $year = isset($_POST['year']) ? $_POST['year'] : date('Y');
            $this->smartyHelper->assign('weeks', SmartyTools::getWeeks($weekid, $year));

            $this->smartyHelper->assign('years', SmartyTools::getYears($year,2));

            if ('computeCsvWeekly' == $_POST['action']) {
               $formatedteamName = TeamCache::getInstance()->getTeam($this->teamid)->getName();

               $weekDates      = Tools::week_dates($weekid,$year);
               $startTimestamp = $weekDates[1];
               $endTimestamp   = mktime(23, 59, 59, date("m", $weekDates[5]), date("d", $weekDates[5]), date("Y", $weekDates[5]));

               $reports = "";

               $managedIssuesfile = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$formatedteamName."_Mantis_".Tools::formatDate("%Y%m%d",time()).".csv";
               $managedIssuesfile = ExportCsvTools::exportManagedIssuesToCSV($this->teamid, $startTimestamp, $endTimestamp, $managedIssuesfile);
               $reports[] = array('file' => basename($managedIssuesfile),
                  'title' => T_('Export Managed Issues'),
                  'subtitle' => T_('Issues form Team projects, including issues assigned to other teams')
               );

               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

               $weekActivityReportfile = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$formatedteamName."_CRA_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
               $weekActivityReportfile = $this->exportWeekActivityReportToCSV($this->teamid, $weekDates, $timeTracking, $weekActivityReportfile);
               $reports[] = array('file' => basename($weekActivityReportfile),
                  'title' => T_('Export Week').' '.$weekid.' '.T_('Member Activity')
               );

               $projectActivityFile = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports'.DIRECTORY_SEPARATOR.$formatedteamName."_projects_".Tools::formatDate("%Y_W%W", $startTimestamp).".csv";
               $projectActivityFile = $this->exportProjectActivityToCSV($timeTracking, $projectActivityFile);
               $reports[] = array('file' => basename($projectActivityFile),
                  'title' => T_('Export Week').' '.$weekid.' '.T_('Projects Activity')
               );

               $this->smartyHelper->assign('reports', $reports);

               //$this->smartyHelper->assign('reportsDir', Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports');
            }
         }
      }
   }

   /**
    * creates for each project a table with the following fields:
    * TaskName | RAF | <Jobs>
    * @param TimeTracking $timeTracking
    * @param string $myFile
    * @return string
    */
   private function exportProjectActivityToCSV(TimeTracking $timeTracking, $myFile) {
      $sepChar=';';
      $team = TeamCache::getInstance()->getTeam($timeTracking->getTeamid());


      if (!is_dir(Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports')) {
         mkdir(Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports', 0755);
      }

      $fh = fopen($myFile, 'w');

      // $projectTracks[projectid][bugid][jobid] = duration
      $projectTracks = $timeTracking->getProjectTracks();

      foreach ($projectTracks as $projectId => $bugList) {
         // write table header
         $project = ProjectCache::getInstance()->getProject($projectId);
         $stringData = $project->getName()."\n";

         $stringData .= T_("Task").$sepChar;
         $stringData .= T_("BL").$sepChar;
         $jobList = $project->getJobList($team->getProjectType($projectId));
         foreach($jobList as $jobName) {
            $stringData .= $jobName.$sepChar;
         }
         $stringData .="\n";

         // write table content (by bugid)
         foreach ($bugList as $bugid => $jobs) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            // remove sepChar from summary text
            $formatedSummary = str_replace($sepChar, " ", $issue->getSummary());

            $stringData .= "$bugid / ".$issue->getTcId()." : ".$formatedSummary.$sepChar;
            $stringData .= $issue->getBacklog().$sepChar;
            foreach($jobList as $jobId => $jobName) {
               $stringData .= $jobs[$jobId].$sepChar;
            }
            $stringData .="\n";
         }
         $stringData .="\n";
         fwrite($fh, $stringData);
      }
      fclose($fh);
      return $myFile;
   }

   /**
    * Export week activity
    * @param int $teamid
    * @param $weekDates
    * @param TimeTracking $timeTracking
    * @param string $myFile
    * @return string
    */
   private function exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile) {
      $sepChar=';';
      $sql = AdodbWrapper::getInstance();

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

      $query = "SELECT codev_team_user_table.user_id, {user}.realname ".
         "FROM  codev_team_user_table, {user} ".
         "WHERE  codev_team_user_table.team_id =".$sql->db_param().
         " AND    codev_team_user_table.user_id = {user}.id ".
         "ORDER BY {user}.realname";

      $result = $sql->sql_query($query, array($teamid));

      while($row = $sql->fetchObject($result)) {
         // if user was working on the project during the timestamp
         $user = UserCache::getInstance()->getUser($row->user_id);
         if (($user->isTeamDeveloper($teamid, $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp())) ||
            ($user->isTeamManager($teamid, $timeTracking->getStartTimestamp(), $timeTracking->getEndTimestamp()))) {
            $this->exportWeekDetailsToCSV($row->user_id, $timeTracking, $user->getShortname(), $fh);
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
   private function exportWeekDetailsToCSV($userid, TimeTracking $timeTracking, $realname, $fh) {
      $sepChar=';';
      $sql = AdodbWrapper::getInstance();

      $weekTracks = $timeTracking->getWeekDetails($userid);
      foreach ($weekTracks as $bugid => $jobList) {
         try {
            $issue = IssueCache::getInstance()->getIssue($bugid);

            // remove sepChar from summary text
            $formatedSummary = str_replace($sepChar, " ", $issue->getSummary());

            foreach ($jobList as $jobid => $dayList) {
               $query  = "SELECT name FROM codev_job_table WHERE id=".$sql->db_param();
               $result = $sql->sql_query($query, array($jobid));

               $jobName = $sql->sql_result($result, 0);
               $stringData = $bugid.$sepChar.
                  $jobName.$sepChar.
                  $formatedSummary.$sepChar.
                  $realname.$sepChar;
               for ($i = 1; $i <= 4; $i++) {
                  if(array_key_exists($i, $dayList)) {
                     $stringData .= $dayList[$i];
                  }
                  $stringData .= $sepChar;
               }
               if(array_key_exists(5,$dayList)) {
                  $stringData .= $dayList[5];
               }
               $stringData .= "\n";
               fwrite($fh, $stringData);
            }
         } catch (Exception $e) {
            self::$logger->error('exportWeekDetailsToCSV(): issue $bugid not found in mantis DB !');
         }
      }
   }

}

// ========== MAIN ===========
ExportCSVWeeklyController::staticInit();
$controller = new ExportCSVWeeklyController('../', 'CSV Report','ImportExport');
$controller->execute();


