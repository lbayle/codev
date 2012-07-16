<?php
include_once('../include/session.inc.php');

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

include_once '../path.inc.php';

// ---------------------------------------------------------------
function exportManagedIssuesToCSV($teamid, $startTimestamp, $endTimestamp, $myFile) {

   global $logger;

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


   // ---
   $projList   = Team::getProjectList($teamid);
   $formatedProjList = implode( ', ', array_keys($projList));

   // Note: if you filter on TeamMembers, you won't have issues temporarily affected to other teams
   //$memberList = Team::getMemberList($teamid);
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
            if (NULL != ($d = $issue->getStartTimestamp())) {
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
                          $issue->elapsed.$sepChar.
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
    if (NULL != ($d = $issue->getStartTimestamp())) {
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
        $issue->elapsed.$sepChar.
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



// ------------------------------------------------
/**
 * creates for each project a table with the following fields:
 * TaskName | RAF | <Jobs>

 * @param unknown_type $timeTracking
 * @param unknown_type $myFile
 */
function exportProjectActivityToCSV($timeTracking, $myFile) {

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

// ---------------------------------------------
/**
 * creates for each project a table with the following fields:
 * id | TC | startDate | endDate | status | total elapsed | elapsed + Remaining | elapsed in period | Remaining
 * TOTAL
 * @param unknown_type $timeTracking
 * @param unknown_type $myFile
 */
function exportProjectMonthlyActivityToCSV($timeTracking, $myFile) {
  $sepChar=';';

  $totalEffortEstim   = 0;
  $totalElapsed       = 0;
  $totalElapsedPeriod = 0;
  $totalRemaining     = 0;

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
         $stringData .= $issue->elapsed.$sepChar;
         $stringData .= ($issue->elapsed + $issue->remaining).$sepChar;

         // sum all job durations
         $elapsedInPeriod = 0;
         foreach($jobs as $jobId => $duration) {
            $elapsedInPeriod += $duration;
         }
         $stringData .= $elapsedInPeriod.$sepChar;

         $stringData .= $issue->remaining.$sepChar;
         $stringData .="\n";

         $totalEffortEstim   += ($issue->effortEstim + $issue->effortAdd);
         $totalElapsed       += $issue->elapsed;
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



// ---------------------------------------------
// format: nom;prenom;trigramme;date de debut;date de fin;nb jours
// format date: "jj/mm/aa"
function exportHolidaystoCSV($month, $year, $teamid, $teamName, $path="") {

  global $logger;
  $sepChar=';';

  $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
  $nbDaysInMonth = date("t", $monthTimestamp);
  $startT = mktime(0, 0, 0, $month, 1, $year);
  $endT   = mktime(23, 59, 59, $month, $nbDaysInMonth, $year);

   // create filename & open file
   $myFile = $path.DIRECTORY_SEPARATOR.$teamName."_Holidays_".formatdate("%Y%m", $monthTimestamp).".csv";
   $fh = fopen($myFile, 'w');

  // USER
  $query = "SELECT codev_team_user_table.user_id, mantis_user_table.username, mantis_user_table.realname ".
    "FROM  `codev_team_user_table`, `mantis_user_table` ".
    "WHERE  codev_team_user_table.team_id = $teamid ".
    "AND    codev_team_user_table.user_id = mantis_user_table.id ".
    "ORDER BY mantis_user_table.username";


  $result = SqlWrapper::getInstance()->sql_query($query);
  if (!$result) {
     echo "<span style='color:red'>ERROR: Query FAILED</span>";
     exit;
  }
  while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
  {
      $user1 = UserCache::getInstance()->getUser($row->user_id);

      // if user was working on the project within the timestamp
      if (($user1->isTeamDeveloper($teamid, $startT, $endT)) ||
          ($user1->isTeamManager($teamid, $startT, $endT))) {

         $daysOf = $user1->getDaysOfInPeriod($startT, $endT);

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



?>
