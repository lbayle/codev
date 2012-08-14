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

class IssueInfoController extends Controller {

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

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $teamList = $user->getTeamList();

         if (count($teamList) > 0) {
            // --- define the list of tasks the user can display
            // All projects from teams where I'm a Developper or Manager AND Observer
            $allProject[0] = T_('(all)');
            $dTeamList = $user->getDevTeamList();
            $devProjList = count($dTeamList) > 0 ? $user->getProjectList($dTeamList) : array();
            $managedTeamList = $user->getManagedTeamList();
            $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList) : array();
            $oTeamList = $user->getObservedTeamList();
            $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList) : array();
            $projList = $allProject + $devProjList + $managedProjList + $observedProjList;

            // if 'support' is set in the URL, display graphs for 'with/without Support'
            $displaySupport = isset($_GET['support']) ? true : false;
            if($displaySupport) {
               $this->smartyHelper->assign('support', $displaySupport);
            }

            $bug_id = Tools::getSecureGETIntValue('bugid', 0);
            $bugs = NULL;
            $projects = NULL;
            if($bug_id != 0) {
               try {
                  $issue = IssueCache::getInstance()->getIssue($bug_id);

                  $defaultProjectid = $issue->projectId;
                  $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id);
                  if (array_key_exists($bug_id,$bugs)) {
                     $consistencyErrors = NULL;
                     $ccheck = new ConsistencyCheck2(array($issue));
                     $cerrList = $ccheck->check();
                     if (0 != count($cerrList)) {
                        foreach ($cerrList as $cerr) {
                           $consistencyErrors[] = array(
                              'severity' => $cerr->getLiteralSeverity(),
                              'severityColor' => $cerr->getSeverityColor(),
                              'desc' => $cerr->desc
                           );
                        }
                        $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                        $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
                        $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                     }

                     $isManager = (array_key_exists($issue->projectId, $managedProjList)) ? true : false;
                     $this->smartyHelper->assign('isManager', $isManager);
                     $this->smartyHelper->assign('issueGeneralInfo', IssueInfoTools::getIssueGeneralInfo($issue, $isManager, $displaySupport));
                     $timeTracks = $issue->getTimeTracks();
                     $this->smartyHelper->assign('jobDetails', $this->getJobDetails($timeTracks));
                     $this->smartyHelper->assign('timeDrift', $this->getTimeDrift($issue));

                     $this->smartyHelper->assign('months', $this->getCalendar($issue,$timeTracks));
                     $this->smartyHelper->assign('durationsByStatus', $this->getDurationsByStatus($issue));

                     // set Commands I belong to
                     $parentCmds = $this->getParentCommands($issue);
                     $this->smartyHelper->assign('parentCommands', $parentCmds);
                     $this->smartyHelper->assign('nbParentCommands', count($parentCmds));

                     // get Backlog history
                     $timestampList = $this->getTimetrackDates($issue);
                     /*
                     $plotMinDate = date('Y-m-d', $issue->getFirstTimetrack()->date);
                     $plotMaxDate = date('Y-m-d', $issue->getLatestTimetrack()->date);
                     $smartyHelper->assign('plotMinDate', $plotMinDate);
                     $smartyHelper->assign('plotMaxDate', $plotMaxDate);
                     */
                     $this->smartyHelper->assign('jqplotTitle',      'Backlog variation');
                     $this->smartyHelper->assign('jqplotYaxisLabel', 'Backlog (days)');
                     $this->smartyHelper->assign('jqplotData', $this->getBacklogGraph($issue, $timestampList));

                  }
                  $projects = SmartyTools::getSmartyArray($projList,$defaultProjectid);
                  $_SESSION['projectid'] = $defaultProjectid;

               } catch (Exception $e) {
                  // TODO display ERROR "issue not found in mantis DB !"
               }

            } else {
               $defaultProjectid = 0;
               if((isset($_SESSION['projectid'])) && (0 != $_SESSION['projectid'])) {
                  $defaultProjectid = $_SESSION['projectid'];
                  $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id);
               } else {
                  $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id, $projList);
               }
               $projects = SmartyTools::getSmartyArray($projList,$defaultProjectid);
            }
            $this->smartyHelper->assign('bugs', $bugs);
            $this->smartyHelper->assign('projects', $projects);
         }
      }
   }

   /**
    * @param Issue $issue The issue
    * @return mixed[] Commands
    */
   private function getParentCommands(Issue $issue) {
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
   private function getJobDetails(array $timeTracks) {
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
   private function getTimeDrift(Issue $issue) {
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
   private function getCalendar(Issue $issue, array $trackList) {
      $months = NULL;
      for ($y = date('Y', $issue->dateSubmission); $y <= date('Y'); $y++) {
         for ($m = 1; $m <= 12; $m++) {
            $monthsValue = $this->getMonth($m, $y, $issue, $trackList);
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
   private function getMonth($month, $year, Issue $issue, array $trackList) {
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
   private function getDurationsByStatus(Issue $issue) {
      # WARN: use of FDJ custom
      //$issue = new IssueFDJ($issue_->bugId);

      $issue->computeDurationsPerStatus();

      $statusNamesSmarty = NULL;
      foreach($issue->statusList as $status_id => $status) {
         $statusNamesSmarty[] = Constants::$statusNames[$status_id];
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

   /**
    * @param Issue $issue
    * @return int[]
    */
   private function getTimetrackDates(Issue $issue) {
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
    * @param Issue $issue
    * @param int[] $timestampList
    * @return string
    */
   private function getBacklogGraph(Issue $issue, array $timestampList) {
      $backlogList = array();
      foreach ($timestampList as $timestamp) {
         $backlog = $issue->getBacklog($timestamp);
         if(!is_numeric($backlog)) {
            $backlog = $issue->mgrEffortEstim;
         }
         $backlogList[Tools::formatDate("%Y-%m-%d", $timestamp)] = (NULL == $backlog) ? $issue->mgrEffortEstim : $backlog;
      }

      return Tools::array2plot($backlogList);
   }

}

// ========== MAIN ===========
IssueInfoController::staticInit();
$controller = new IssueInfoController('Task Info','IssueInfo');
$controller->execute();

?>
