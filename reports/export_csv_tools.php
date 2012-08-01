<?php
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

include_once('classes/issue_cache.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/sqlwrapper.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class ExportCsvTools {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @param int $teamid
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $myFile
    * @return string
    */
   public static function exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile) {
      $sepChar=';';

      $fh = fopen($myFile, 'w');

      // write header
      // WARNING i18n: translations with HTML chars (&eacute;) include ';' which is the CSV separation char !
      $stringData = T_("Project").$sepChar.
         T_("m_id").$sepChar.
         T_("ExtRef").$sepChar.
         T_("Summary").$sepChar.
         T_("Status").$sepChar.
         T_("Submitted").$sepChar.
         T_("Start date").$sepChar.
         T_("DeadLine").$sepChar.
         T_("Product Version").$sepChar.
         T_("Priority").$sepChar.
         "Category".$sepChar.
         T_("Resolution").$sepChar.
         T_("MgrEffortEstim").$sepChar.
         T_("BI").$sepChar.
         T_("BS").$sepChar.
         "Elapsed".$sepChar.
         T_("RAF").$sepChar.
         T_("Progress").$sepChar.
         T_("Delivery Date").$sepChar.
         T_("Delivery Sheet").$sepChar.
         T_("Assigned to").$sepChar.
         "\n";
      fwrite($fh, $stringData);

      $projList = TeamCache::getInstance()->getTeam($teamid)->getProjects();
      $formatedProjList = implode( ', ', array_keys($projList));

      // Note: if you filter on TeamMembers, you won't have issues temporarily affected to other teams
      //$memberList = TeamCache::getInstance()->getTeam($teamid)->getMembers();
      //$formatedMemberList = implode( ', ', array_keys($memberList));

      // for all issues with status !=  {resolved, closed}

      $query = "SELECT * FROM `mantis_bug_table` ".
         "WHERE status < get_project_resolved_status_threshold(project_id) ".
         "AND project_id IN ($formatedProjList) ".
         //"AND handler_id IN ($formatedMemberList) ".
         "ORDER BY id DESC";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $user = UserCache::getInstance()->getUser($issue->handlerId);

         $deadLine = "";
         if (NULL != $issue->getDeadLine()) {
            $deadLine = date("d/m/Y", $issue->getDeadLine());
         }
         $deliveryDate = "";
         if (NULL != $issue->deliveryDate) {
            $deliveryDate = date("d/m/Y", $issue->deliveryDate);
         }

         // remove sepChar from summary text
         $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

         $startDate="";
         if (NULL != ($d = $issue->startDate())) {
            $startDate = date("d/m/Y", $d);
         }

         // write data
         $stringData = $issue->getProjectName().$sepChar.
            $issue->bugId.$sepChar.
            $issue->getTC().$sepChar.
            $formatedSummary.$sepChar.
            $issue->getCurrentStatusName().$sepChar.
            date("d/m/Y", $issue->dateSubmission).$sepChar.
            $startDate.$sepChar.
            $deadLine.$sepChar.
            $issue->version.$sepChar.
            $issue->getPriorityName().$sepChar.
            $issue->getCategoryName().$sepChar.
            $issue->getResolutionName().$sepChar.
            $issue->mgrEffortEstim.$sepChar.
            $issue->effortEstim.$sepChar.
            $issue->effortAdd.$sepChar.
            $issue->getElapsed().$sepChar.
            $issue->remaining.$sepChar.
            round(100 * $issue->getProgress())."%".$sepChar.
            $deliveryDate.$sepChar.
            $issue->deliveryId.$sepChar.
            $user->getShortname().
            "\n";
         fwrite($fh, $stringData);

      }

      // Add resolved issues modified into the period
      $query = "SELECT * FROM `mantis_bug_table` ".
         "WHERE status >= get_project_resolved_status_threshold(project_id) ".
         "AND project_id IN ($formatedProjList) ".
         //"AND handler_id IN ($formatedMemberList) ".
         "AND last_updated > $startTimestamp ".
         "AND last_updated < $endTimestamp ".
         "ORDER BY id DESC";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

         $issue = IssueCache::getInstance()->getIssue($row->id, $row);
         $user = UserCache::getInstance()->getUser($issue->handlerId);

         $deliveryDate = "";
         if (NULL != $issue->deliveryDate) {
            $deliveryDate = date("d/m/Y", $issue->deliveryDate);
         }

         // remove sepChar from summary text
         $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

         $startDate="";
         if (NULL != ($d = $issue->startDate())) {
            $startDate = date("d/m/Y", $d);
         }

         // write data
         $stringData = $issue->getProjectName().$sepChar.
            $issue->bugId.$sepChar.
            $issue->getTC().$sepChar.
            $formatedSummary.$sepChar.
            $issue->getCurrentStatusName().$sepChar.
            date("d/m/Y", $issue->dateSubmission).$sepChar.
            $startDate.$sepChar.
            $deadLine.$sepChar.
            $issue->version.$sepChar.
            $issue->getPriorityName().$sepChar.
            $issue->getCategoryName().$sepChar.
            $issue->getResolutionName().$sepChar.
            $issue->mgrEffortEstim.$sepChar.
            $issue->effortEstim.$sepChar.
            $issue->effortAdd.$sepChar.
            $issue->getElapsed().$sepChar.
            $issue->remaining.$sepChar.
            $deliveryDate.$sepChar.
            $issue->deliveryId.$sepChar.
            $user->getShortname().
            "\n";
         fwrite($fh, $stringData);
      }

      fclose($fh);
      return $myFile;
   }

   /**
    * creates for each project a table with the following fields:
    * TaskName | RAF | <Jobs>
    * @param TimeTracking $timeTracking
    * @param string $myFile
    * @return string
    */
   public static function exportProjectActivityToCSV(TimeTracking $timeTracking, $myFile) {
      $sepChar=';';
      $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);

      $fh = fopen($myFile, 'w');

      // $projectTracks[projectid][bugid][jobid] = duration
      $projectTracks = $timeTracking->getProjectTracks();

      foreach ($projectTracks as $projectId => $bugList) {

         // write table header
         $project = ProjectCache::getInstance()->getProject($projectId);
         $stringData = $project->name."\n";

         $stringData .=T_("Task").$sepChar;
         $stringData .=T_("RAF").$sepChar;
         $jobList = $project->getJobList($team->getProjectType($projectId));
         foreach($jobList as $jobId => $jobName) {
            $stringData .= $jobName.$sepChar;
         }
         $stringData .="\n";

         // write table content (by bugid)
         foreach ($bugList as $bugid => $jobs) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            // remove sepChar from summary text
            $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

            $stringData .= "$bugid / ".$issue->tcId." : ".$formatedSummary.$sepChar;
            $stringData .= $issue->remaining.$sepChar;
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
    * creates for each project a table with the following fields:
    * id | TC | startDate | endDate | status | total elapsed | elapsed + Remaining | elapsed in period | Remaining
    * TOTAL
    * @param TimeTracking $timeTracking
    * @param string $myFile
    * @return string
    */
   public static function exportProjectMonthlyActivityToCSV(TimeTracking $timeTracking, $myFile) {
      $sepChar=';';

      $totalEffortEstim   = 0;

      $fh = fopen($myFile, 'w');

      // returns : $projectTracks[projectid][bugid][jobid] = duration
      $projectTracks = $timeTracking->getProjectTracks();

      foreach ($projectTracks as $projectId => $bugList) {

         $totalElapsed = 0;
         $totalRemaining = 0;
         $totalElapsedPeriod = 0;

         // write table header
         $project = ProjectCache::getInstance()->getProject($projectId);
         $stringData = $project->name."\n";

         // WARNING i18n: HTML translation like french accents (eacute;) add an unwanted column sepChar (;)
         $stringData .=("ID").$sepChar;
         $stringData .=("Task").$sepChar;
         $stringData .=("Ext.ID").$sepChar;
         $stringData .=("Start date").$sepChar;
         $stringData .=("End date").$sepChar;
         $stringData .=("Status").$sepChar;
         $stringData .=("Total EffortEstim").$sepChar;
         $stringData .=("Total elapsed").$sepChar;
         $stringData .=("elapsed + Remaining").$sepChar;
         $stringData .=("elapsed in period").$sepChar;
         $stringData .=("RAF").$sepChar;
         $stringData .="\n";

         // write table content (by bugid)
         foreach ($bugList as $bugid => $jobs) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            // remove sepChar from summary text
            $formatedSummary = str_replace("$sepChar", " ", $issue->summary);

            $stringData .= $bugid.$sepChar;
            $stringData .= $formatedSummary.$sepChar;
            $stringData .= $issue->tcId.$sepChar;
            $stringData .= date("d/m/Y", $issue->startDate()).$sepChar;
            $stringData .= date("d/m/Y", $issue->endDate()).$sepChar;
            $stringData .= $issue->getCurrentStatusName().$sepChar;
            $stringData .= ($issue->effortEstim + $issue->effortAdd).$sepChar;
            $stringData .= $issue->getElapsed().$sepChar;
            $stringData .= ($issue->getElapsed() + $issue->remaining).$sepChar;

            // sum all job durations
            $elapsedInPeriod = 0;
            foreach($jobs as $jobId => $duration) {
               $elapsedInPeriod += $duration;
            }
            $stringData .= $elapsedInPeriod.$sepChar;

            $stringData .= $issue->remaining.$sepChar;
            $stringData .="\n";

            $totalEffortEstim   += ($issue->effortEstim + $issue->effortAdd);
            $totalElapsed       += $issue->getElapsed();
            $totalRemaining     += $issue->remaining;
            $totalElapsedPeriod += $elapsedInPeriod;
         }

         // total per project
         $stringData .= ("TOTAL").$sepChar.$sepChar.$sepChar.$sepChar.$sepChar.$sepChar;
         $stringData .= $totalEffortEstim.$sepChar;
         $stringData .= $totalElapsed.$sepChar;
         $stringData .= ($totalElapsed + $totalRemaining).$sepChar;
         $stringData .= $totalElapsedPeriod.$sepChar;
         $stringData .= $totalRemaining.$sepChar;
         $stringData .= "\n";

         $stringData .="\n";
         fwrite($fh, $stringData);
      } // project
      fclose($fh);
      return $myFile;

   }

   /**
    * format: nom;prenom;trigramme;date de debut;date de fin;nb jours
    * format date: "jj/mm/aa"
    * @param int $month
    * @param int $year
    * @param int $teamid
    * @param string $teamName
    * @param string $path
    * @return string
    */
   public static function exportHolidaystoCSV($month, $year, $teamid, $teamName, $path="") {
      $sepChar=';';

      $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
      $nbDaysInMonth = date("t", $monthTimestamp);
      $startT = mktime(0, 0, 0, $month, 1, $year);
      $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

      // create filename & open file
      $myFile = $path.DIRECTORY_SEPARATOR.$teamName."_Holidays_".Tools::formatdate("%Y%m", $monthTimestamp).".csv";
      $fh = fopen($myFile, 'w');

      $team = TeamCache::getInstance()->getTeam($teamid);
      foreach($team->getMembers() as $userid => $username) {
         $user1 = UserCache::getInstance()->getUser($userid);

         // if user was working on the project within the timestamp
         if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
            ($user1->isTeamManager($teamid, $startT, $endT))) {

            
            $timeTracks = $user1->getTimeTracks($startT, $endT);
            $issueIds = array();
            foreach ($timeTracks as $timeTrack) {
               $issueIds[] = $timeTrack->bugId;
            }
            
            $daysOf = $user1->getDaysOfInPeriod($timeTracks, $issueIds);

            // concatenate days
            $startBlockTimestamp = 0;
            $endBlockTimestamp = 0;
            $blockSize = 0;

            for ($i = 1; $i <= $nbDaysInMonth; $i++) {
               if (NULL != $daysOf["$evtTimestamp"]) {

                  $evtTimestamp = mktime(0, 0, 0, $month, $i, $year);

                  if (1 == $daysOf["$evtTimestamp"]) {
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
                        $daysOf["$evtTimestamp"]."\n";
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
   public static function exportWeekActivityReportToCSV($teamid, $weekDates, $timeTracking, $myFile) {
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
            self::exportWeekDetailsToCSV($row->user_id, $timeTracking, $user->getShortname(), $fh);
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
   public static function exportWeekDetailsToCSV($userid, TimeTracking $timeTracking, $realname, $fh) {
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
            self::$logger->error('exportWeekDetailsToCSV(): issue $bugid not found in mantis DB !');
         }
      }
   }

}

// Initialize complex static variables
ExportCsvTools::staticInit();

?>
