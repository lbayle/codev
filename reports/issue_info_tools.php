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

require_once('lib/log4php/Logger.php');

include_once('classes/config.class.php');
include_once('classes/issue.class.php');
include_once('classes/timetrack_cache.class.php');
include_once('classes/user_cache.class.php');

$logger = Logger::getLogger("issue_info_tools");

/**
 * Get general info of an issue
 * @param Issue $issue The issue
 * @param bool $isManager if true: show MgrEffortEstim column
 * @param bool $displaySupport If true, display support
 * @return mixed[string]
 */
function getIssueGeneralInfo(Issue $issue, $isManager=false, $displaySupport=false) {
   $withSupport = true;  // include support in elapsed & Drift

   $drift = $issue->getDrift($withSupport);
   $issueGeneralInfo = array(
      "issueId" => $issue->bugId,
      "issueSummary" => htmlspecialchars($issue->summary),
      "issueExtRef" => $issue->tcId,
      'mantisURL'=> mantisIssueURL($issue->bugId, NULL, true),
      'issueURL' => mantisIssueURL($issue->bugId),
      'statusName'=> $issue->getCurrentStatusName(),
      'handlerName'=> UserCache::getInstance()->getUser($issue->handlerId)->getName(),

      "issueEffortTitle" => $issue->effortEstim.' + '.$issue->effortAdd,
      "issueEffort" => $issue->effortEstim + $issue->effortAdd,
      "issueReestimated" => $issue->getReestimated(),
      "issueRemaining" => $issue->remaining,
      "issueDriftColor" => $issue->getDriftColor($drift),
      "issueDrift" => round($drift, 2),
      "progress" => round(100 * $issue->getProgress())
   );
   if($isManager) {
      $issueGeneralInfo['issueMgrEffortEstim'] = $issue->mgrEffortEstim;
      $issueGeneralInfo['issueReestimatedMgr'] = $issue->getReestimatedMgr();
      $driftMgr = $issue->getDriftMgr($withSupport);
      $issueGeneralInfo['issueDriftMgrColor'] = $issue->getDriftColor($driftMgr);
      $issueGeneralInfo['issueDriftMgr'] = round($driftMgr, 2);
   }
   if ($withSupport) {
      $issueGeneralInfo['issueElapsed'] = $issue->elapsed;
   } else {
      $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
      $issueGeneralInfo['issueElapsed'] = $issue->elapsed - $issue->getElapsed($job_support);
   }
   if ($displaySupport) {
      if ($isManager) {
         $driftMgr = $issue->getDriftMgr(!$withSupport);
         $issueGeneralInfo['issueDriftMgrSupportColor'] = $issue->getDriftColor($driftMgr);
         $issueGeneralInfo['issueDriftMgrSupport'] = round($driftMgr, 2);
      }
      $drift = $issue->getDrift(!$withSupport);
      $issueGeneralInfo['issueDriftSupportColor'] = $issue->getDriftColor($drift);
      $issueGeneralInfo['issueDriftSupport'] = round($drift, 2);
   }

   return $issueGeneralInfo;
}

/**
 * @param Command $cmd
 * @param int $selectedCmdsetId
 * @return type
 */
function getParentCommands(Issue $issue) {
   $commands = array();

   $cmdList = $issue->getCommandList();
   if($cmdList != NULL) {
      // TODO return URL for 'name' ?
      foreach ($cmdList as $id => $cmdName) {
         $commands[] = array(
            'id' => $id,
            'name' => $cmdName,
            #'reference' => ,
         );
      }
   }
   return $commands;
}


/**
 * Get job details of an issue
 * @param array $timeTracks
 * @return mixed[string]
 */
function getJobDetails(array $timeTracks) {
   $durationByJob = array();
   $jobs = new Jobs();
   $totalDuration = 0;
   foreach ($timeTracks as $tid => $tdate) {
      $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
      $durationByJob[$tt->jobId] += $tt->duration;
      $totalDuration += $tt->duration;
   }

   $jobDetails = NULL;
   foreach ($durationByJob as $jid => $duration) {
      $jobDetails[] = array(
         "jobColor" => $jobs->getJobColor($jid),
         "jobName" => $jobs->getJobName($jid),
         "duration" => $duration,
         "durationRate" => round(($duration*100 / $totalDuration))
      );
   }

   return $jobDetails;
}

/**
 * Get time drift of an issue
 * @param Issue $issue The issue
 * @return mixed[string]
 */
function getTimeDrift(Issue $issue) {
   $timeDriftSmarty = array();

   if (NULL != $issue->getDeadLine()) {
      $timeDriftSmarty["deadLine"] = formatDate("%d %b %Y", $issue->getDeadLine());
   }

   if (NULL != $issue->deliveryDate) {
      $timeDriftSmarty["deliveryDate"] = formatDate("%d %b %Y", $issue->deliveryDate);
   }

   $timeDrift = $issue->getTimeDrift();
   if (!is_string($timeDrift)) {
      $timeDriftSmarty["driftColor"] = $issue->getDriftColor($timeDrift);
      $timeDriftSmarty["drift"] = round($timeDrift);
   }

   return $timeDriftSmarty;
}

/**
 * Get the calendar of an issue
 * @param Issue $issue The issue
 * @param array $trackList
 * @return array
 */
function getCalendar(Issue $issue, array $trackList) {
   $months = NULL;
   for ($y = date('Y', $issue->dateSubmission); $y <= date('Y'); $y++) {
      for ($m = 1; $m <= 12; $m++) {
         $monthsValue = getMonth($m, $y, $issue, $trackList);
         if ($monthsValue != NULL) {
            $months[] = $monthsValue;
         }
      }
   }
   return $months;
}

/**
 * @param int $month
 * @param int $year
 * @param Issue $issue The issue
 * @param array $trackList
 * @return mixed[string]
 */
function getMonth($month, $year, Issue $issue, array $trackList) {
   $totalDuration = 0;

   // if no work done this month, do not display month
   $found = 0;
   foreach ($trackList as $tid => $tdate) {
      if (($month == date('m', $tdate)) &&
         ($year  == date('Y', $tdate))) {
         $found += 1;

         $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
         $totalDuration += $tt->duration;
      }
   }
   if (0 == $found) { return NULL; }

   $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
   $monthFormated = formatDate("%B %Y", $monthTimestamp);
   $nbDaysInMonth = date("t", $monthTimestamp);

   $months = array();
   for ($i = 1; $i <= $nbDaysInMonth; $i++) {
      if ($i < 10 ) {
         $months[] = "0".$i;
      }
      else {
         $months[] = $i;
      }
   }

   $jobs = new Jobs();
   $userList = $issue->getInvolvedUsers();
   $users = NULL;
   foreach ($userList as $uid => $username) {
      // build $durationByDate[] for this user
      $userTimeTracks = $issue->getTimeTracks($uid);
      $durationByDate = array();
      $jobColorByDate = array();
      foreach ($userTimeTracks as $tid => $tdate) {
         $tt = TimeTrackCache::getInstance()->getTimeTrack($tid);
         $durationByDate[$tdate] += $tt->duration;
         $jobColorByDate[$tdate] = $jobs->getJobColor($tt->jobId);
      }

      $usersDetails = NULL;
      for ($i = 1; $i <= $nbDaysInMonth; $i++) {
         $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);

         if (NULL != $durationByDate[$todayTimestamp]) {
            $usersDetails[] = array(
               "jobColor" => $jobColorByDate[$todayTimestamp],
               "jobDuration" => $durationByDate[$todayTimestamp]
            );
         } else {
            // if weekend or holiday, display gray
            $holidays = Holidays::getInstance();
            $h = $holidays->isHoliday($todayTimestamp);
            if (NULL != $h) {
               $usersDetails[] = array(
                  "jobColor" => Holidays::$defaultColor,
                  "jobDescription" => $h->description
               );
            } else {
               $usersDetails[] = array();
            }
         }
      }

      $users[] = array(
         "username" => $username,
         "jobs" => $usersDetails
      );
   }

   return array(
      "monthFormated" => $monthFormated,
      "totalDuration" => $totalDuration,
      "months" => $months,
      "users" => $users
   );
}

/**
 * Table Repartition du temps par status
 * @param Issue $issue The issue
 * @return mixed[string]
 */
function getDurationsByStatus(Issue $issue) {
   global $logger;
   global $statusNames;

   # WARN: use of FDJ custom
   //$issue = new IssueFDJ($issue_->bugId);

   $issue->computeDurationsPerStatus();

   $statusNamesSmarty = NULL;
   foreach($issue->statusList as $status_id => $status) {
      $statusNamesSmarty[] = $statusNames[$status_id];
   }

   // REM do not display SuiviOp tasks
   $durations = NULL;
   try {
      if (!$issue->isSideTaskIssue()) {
         foreach($issue->statusList as $status_id => $status) {
            $durations[] = getDurationLiteral($status->duration);
         }
      }
   } catch (Exception $e) {
      $logger->error("displayDurationsByStatus(): issue $issue->bugId: ".$e->getMessage());
   }

   return array(
      "statusNames" => $statusNamesSmarty,
      "durations" => $durations
   );
}

?>
