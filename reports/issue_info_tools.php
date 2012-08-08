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

require_once('tools.php');

require_once('lib/log4php/Logger.php');

class IssueInfoTools {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("tools");
   }

   /**
    * Get general info of an issue
    * @param Issue $issue The issue
    * @param bool $isManager if true: show MgrEffortEstim column
    * @param bool $displaySupport If true, display support
    * @return mixed[]
    */
   public static function getIssueGeneralInfo(Issue $issue, $isManager=false, $displaySupport=false) {
      $withSupport = true;  // include support in elapsed & Drift

      $drift = $issue->getDrift($withSupport);
      $issueGeneralInfo = array(
         "issueId" => $issue->bugId,
         "issueSummary" => htmlspecialchars($issue->summary),
         "issueExtRef" => $issue->tcId,
         'mantisURL'=> Tools::mantisIssueURL($issue->bugId, NULL, true),
         'issueURL' => Tools::mantisIssueURL($issue->bugId),
         'statusName'=> $issue->getCurrentStatusName(),
         'handlerName'=> UserCache::getInstance()->getUser($issue->handlerId)->getName(),

         "issueEffortTitle" => $issue->effortEstim.' + '.$issue->effortAdd,
         "issueEffort" => $issue->effortEstim + $issue->effortAdd,
         "issueReestimated" => $issue->getReestimated(),
         "issueBacklog" => $issue->backlog,
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
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed();
      } else {
         $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed() - $issue->getElapsed($job_support);
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
    * @param Issue $issue The issue
    * @return mixed[] Commands
    */
   public static function getParentCommands(Issue $issue) {
      $commands = array();

      $cmdList = $issue->getCommandList();
      if($cmdList != NULL) {
         // TODO return URL for 'name' ?
         foreach ($cmdList as $cmd) {
            $commands[] = array(
               'id' => $cmd->getId(),
               'name' => $cmd->getName(),
               #'reference' => ,
            );
         }
      }
      return $commands;
   }

   /**
    * Get job details of an issue
    * @param TimeTrack[] $timeTracks
    * @return mixed[]
    */
   public static function getJobDetails(array $timeTracks) {
      $durationByJob = array();
      $jobs = new Jobs();
      $totalDuration = 0;
      foreach ($timeTracks as $tt) {
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
    * @return mixed[]
    */
   public static function getTimeDrift(Issue $issue) {
      $timeDriftSmarty = array();

      if (NULL != $issue->getDeadLine()) {
         $timeDriftSmarty["deadLine"] = Tools::formatDate("%d %b %Y", $issue->getDeadLine());
      }

      if (NULL != $issue->deliveryDate) {
         $timeDriftSmarty["deliveryDate"] = Tools::formatDate("%d %b %Y", $issue->deliveryDate);
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
    * @param TimeTrack[] $trackList
    * @return mixed[]
    */
   public static function getCalendar(Issue $issue, array $trackList) {
      $months = NULL;
      for ($y = date('Y', $issue->dateSubmission); $y <= date('Y'); $y++) {
         for ($m = 1; $m <= 12; $m++) {
            $monthsValue = self::getMonth($m, $y, $issue, $trackList);
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
    * @param TimeTrack[] $trackList
    * @return mixed[]
    */
   public static function getMonth($month, $year, Issue $issue, array $trackList) {
      $totalDuration = 0;

      // if no work done this month, do not display month
      $found = 0;
      foreach ($trackList as $tt) {
         if (($month == date('m', $tt->date)) &&
            ($year  == date('Y', $tt->date))) {
            $found += 1;

            $totalDuration += $tt->duration;
         }
      }
      if (0 == $found) { return NULL; }

      $monthTimestamp = mktime(0, 0, 0, $month, 1, $year);
      $monthFormated = Tools::formatDate("%B %Y", $monthTimestamp);
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
      $timeTracks = $issue->getTimeTracks();
      foreach ($userList as $uid => $username) {
         // build $durationByDate[] for this user
         $durationByDate = array();
         $jobColorByDate = array();
         foreach ($timeTracks as $tt) {
            if($tt->userId == $uid) {
               $durationByDate[$tt->date] += $tt->duration;
               $jobColorByDate[$tt->date] = $jobs->getJobColor($tt->jobId);
            }
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
    * @return mixed[]
    */
   public static function getDurationsByStatus(Issue $issue) {
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
            foreach($issue->statusList as $status) {
               $durations[] = Tools::getDurationLiteral($status->duration);
            }
         }
      } catch (Exception $e) {
         self::$logger->error("displayDurationsByStatus(): issue $issue->bugId: ".$e->getMessage());
      }

      return array(
         "statusNames" => $statusNamesSmarty,
         "durations" => $durations
      );
   }


   public static function getTimetrackDates(Issue $issue) {
      $timestamps = array();
      $timeTracks = $issue->getTimeTracks();
      foreach ($timeTracks as $tt) {
         $timestamp = mktime(23, 59, 59, date('m', $tt->date), date('d', $tt->date), date('Y', $tt->date));
         if (!in_array($timestamp, $timestamps)) {
            $timestamps[] = $timestamp;
            #echo "createTimestampList() timestamp = ".date("Y-m-d H:i:s", $timestamp)."<br>";
         }
      }
      return $timestamps;
   }

   /**
    * @static
    * @param Issue $issue
    * @param int[] $timestampList
    * @return string
    */
   public static function getBacklogGraph(Issue $issue, array $timestampList) {
      $test = NULL;
      if($timestampList != NULL && count($timestampList) > 0) {
         $backlogList = array();
         foreach ($timestampList as $timestamp) {
            $backlog = $issue->getBacklog($timestamp);
            if(!is_numeric($backlog)) {
               $backlog = $issue->mgrEffortEstim;
            }

            $backlogList[Tools::formatDate("%Y-%m-%d", $timestamp)] = (NULL == $backlog) ? $issue->mgrEffortEstim : $backlog;
         }

         $test = "";
         foreach($backlogList as $id => $val) {
            if($test != NULL) {
               $test .= ',';
            }
            $test .= '["'.$id.'", '.$val.']';
         }
         $test = '['.$test.']';
      }
      
      return $test;
   }

}

// Initialize complex static variables
IssueInfoTools::staticInit();

?>
