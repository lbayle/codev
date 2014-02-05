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
      if(Tools::isConnectedUser()) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $teamList = $user->getTeamList();

         if (count($teamList) > 0) {
            // --- define the list of tasks the user can display
            // All projects from teams where I'm a Developper or Manager AND Observer
            $allProject[0] = T_('(all)');
            $dTeamList = $user->getDevTeamList();
            $devProjList = count($dTeamList) > 0 ? $user->getProjectList($dTeamList, true, false) : array();
            $managedTeamList = $user->getManagedTeamList();
            $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList, true, false) : array();
            $oTeamList = $user->getObservedTeamList();
            $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList, true, false) : array();
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

                  $defaultProjectid = $issue->getProjectId();
                  $bugs = SmartyTools::getBugs($defaultProjectid, $bug_id);

                  if ((array_key_exists($defaultProjectid,$projList)) && 
                      (array_key_exists($bug_id,$bugs))) {
                     
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

                     $isManager = (array_key_exists($issue->getProjectId(), $managedProjList)) ? true : false;
                     $isObserver = (array_key_exists($issue->getProjectId(), $observedProjList)) ? true : false;
                     $this->smartyHelper->assign('isManager', ($isManager || $isObserver));
                     $this->smartyHelper->assign('issueGeneralInfo', IssueInfoTools::getIssueGeneralInfo($issue, ($isManager || $isObserver), $displaySupport));
                     
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
                     $data = $this->getBacklogGraph($issue);
                     foreach ($data as $smartyKey => $smartyVariable) {
                        $this->smartyHelper->assign($smartyKey, $smartyVariable);
                     }

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
         foreach ($cmdList as $id => $name) {
            $commands[] = array(
               'id' => $id,
               'name' => $name,
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
         $duration = $tt->getDuration();
         $jobid = $tt->getJobId();
         if(array_key_exists($jobid,$durationByJob)) {
            $durationByJob[$jobid] += $duration;
         } else {
            $durationByJob[$jobid] = $duration;
         }
         $totalDuration += $duration;
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

      $deadline = $issue->getDeadLine();
      if (!is_null($deadline) && (0 != $deadline)) {
         $timeDriftSmarty["deadLine"] = Tools::formatDate("%d %b %Y", $deadline);
      }

      if (NULL != $issue->getDeliveryDate()) {
         $timeDriftSmarty["deliveryDate"] = Tools::formatDate("%d %b %Y", $issue->getDeliveryDate());
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
      for ($y = date('Y', $issue->getDateSubmission()); $y <= date('Y'); $y++) {
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
         if (($month == date('m', $tt->getDate())) &&
            ($year  == date('Y', $tt->getDate()))) {
            $found += 1;

            $totalDuration += $tt->getDuration();
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

         $userTotalDuration = 0;

         // build $durationByDate[] for this user
         $durationByDate = array();
         $jobColorByDate = array();
         foreach ($timeTracks as $tt) {
            if($tt->getUserId() == $uid) {
               $date = $tt->getDate();
               if(array_key_exists($date,$durationByDate)) {
                  $durationByDate[$date] += $tt->getDuration();
               } else {
                  $durationByDate[$date] = $tt->getDuration();
               }
               $jobColorByDate[$date] = $jobs->getJobColor($tt->getJobId());
            }
         }

         $usersDetails = NULL;
         for ($i = 1; $i <= $nbDaysInMonth; $i++) {
            $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);

            if (array_key_exists($todayTimestamp,$durationByDate)) {

               $userTotalDuration += $durationByDate[$todayTimestamp];

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
            "jobs" => $usersDetails,
            'totalDuration' => (0 == $userTotalDuration ? '' : $userTotalDuration)
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
      foreach($issue->getStatusList() as $status_id => $status) {
         if(array_key_exists($status_id,Constants::$statusNames)) {
            $statusNamesSmarty[] = Constants::$statusNames[$status_id];
         } else {
            self::$logger->error("Status ".$status_id." not found in statusNames constants");
         }
      }

      // REM do not display SuiviOp tasks
      $durations = NULL;
      try {
         if (!$issue->isSideTaskIssue()) {
            foreach($issue->getStatusList() as $status) {
               $durations[] = Tools::getDurationLiteral($status->duration);
            }
         }
      } catch (Exception $e) {
         self::$logger->error("displayDurationsByStatus(): issue ".$issue->getId().": ".$e->getMessage());
      }

      return array(
         "statusNames" => $statusNamesSmarty,
         "durations" => $durations
      );
   }

   /**
    * @param Issue $issue
    * @return string
    */
   private function getBacklogGraph(Issue $issue) {

      $backlogList = $issue->getBacklogHistory();

      $formattedBlList = array();
      foreach ($backlogList as $t => $b) {
         $formattedBlList[Tools::formatDate("%Y-%m-%d", $t)] = $b;
      }

      // Graph start/stop dates
      reset($formattedBlList);
      $plotMinDate = key($formattedBlList);
      end($formattedBlList);
      $plotMaxDate = key($formattedBlList);

      // Calculate a nice week interval
      $minTimestamp = Tools::date2timestamp($plotMinDate);
      $maxTimestamp = Tools::date2timestamp($plotMaxDate);
      $nbWeeks = ($maxTimestamp - $minTimestamp) / 60 / 60 / 24 / 7;
      $interval = ceil($nbWeeks / 10);

      $jqplotData = Tools::array2plot($formattedBlList);

      return array(
         'backlog_interval'         => $interval,
         'backlog_plotMinDate'      => $plotMinDate,
         'backlog_plotMaxDate'      => $plotMaxDate,
         'backlog_jqplotTitle'      => T_('Backlog variation'),
         'backlog_jqplotYaxisLabel' => T_('Backlog (days)'),
         'backlog_jqplotData'       => $jqplotData,
      );



   }

}

// ========== MAIN ===========
IssueInfoController::staticInit();
$controller = new IssueInfoController('../', 'Task Info','IssueInfo');
$controller->execute();

?>
