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

   private $issueInfoFilters;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * we want:
    * - the projects of this team
    * - the projects from all other teams I'm involved in
    * - apply user preferences
    */
   private function getProjectList($isApplyFilters=true) {

      //$myTeamList = $this->teamList; // from Controller class
      $myTeamList = $this->session_user->getDevTeamList() + $this->session_user->getManagedTeamList();

      if ((!$isApplyFilters) || (false == $this->issueInfoFilters['isHideObservedTeams'])) {
         $myTeamList += $this->session_user->getObservedTeamList();
      }
      $projList = array ();
      if (count($myTeamList) > 0) {
         $projList[0] = T_('(all)');
         $projList += $this->session_user->getProjectList($myTeamList, true, false);
      }
      asort($projList);
      return $projList;
   }

   /*
    * List of tasks to be displayed in the combobox
    * - depends on selected project
    * - apply user preferences
    */
   private function getIssueList($projectIdList) {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $isHideResolved = $this->issueInfoFilters['isHideResolved'];
      $hideStatusAndAbove = ($this->issueInfoFilters['isHideClosed']) ? Constants::$status_closed : 0;
      $handler_id = ($this->issueInfoFilters['isOnlyAssignedToMe']) ? $this->session_userid : 0;

      $issueList = array();
      foreach ($projectIdList as $prjId) {
         if (0 != $prjId) {
            $project = ProjectCache::getInstance()->getProject($prjId);
            $prjType = $team->getProjectType($prjId);
            if ((Project::type_sideTaskProject == $prjType) ||
                ($project->isExternalTasksProject())) {
               $issueList += $project->getIssues(0, false, false);
            } else {
               $issueList += $project->getIssues($handler_id, $isHideResolved, $hideStatusAndAbove);
            }
         }
      }
      return $issueList;
   }

   private function getSmartyIssueList($issueList, $selectedBugId = 0) {
      $bugs = array();
      foreach ($issueList as $issue) {
         $summary = "";
         if($issue->getSummary()) {
            $summary = ' : '.$issue->getSummary();
         }
         $bugs[$issue->getId()] = array(
            'id' => $issue->getId(),
            'name' => $issue->getFormattedIds().$summary,
            'selected' => ($issue->getId() == $selectedBugId),
            'projectid' => $issue->getProjectId()
         );
      }
      return $bugs;
   }

   protected function display() {
      if(Tools::isConnectedUser() && (0 != $this->teamid)) {
         /* @var $user User */
         $user = $this->session_user;
         $currentTeam = TeamCache::getInstance()->getTeam($this->teamid);

         $this->issueInfoFilters = $this->session_user->getIssueInfoFilters();

         if (count($this->teamList) > 0) {
            // --- define the list of tasks the user can display
            $projList = $this->getProjectList(); // list of projects to be displayed in combobox

            // if 'support' is set in the URL, display graphs for 'with/without Support'
            $displaySupport = filter_input(INPUT_GET, 'support') ? true : false;
            if($displaySupport) {
               $this->smartyHelper->assign('support', $displaySupport);
            }

            if(filter_input(INPUT_GET, 'bugid')) {
               $bug_id = Tools::getSecureGETIntValue('bugid', 0);
            }
            else if(isset($_SESSION['bugid'])) {
               $bug_id = $_SESSION['bugid'];
            } else {
               $bug_id = 0;
               unset($_SESSION['bugid']);
            }

            // check if user is allowed to display this issue
            if ((0 !== $bug_id) && (Issue::exists($bug_id))) {
               $issue = IssueCache::getInstance()->getIssue($bug_id);
               $fullProjList = $this->getProjectList(false);
               if (!array_key_exists($issue->getProjectId(), $fullProjList)) {
                  $this->smartyHelper->assign('errorMessage', T_("You do not have permissions to display task :").$bug_id);
                  $bug_id = 0;
               } else {
                  if (!array_key_exists($issue->getProjectId(), $projList)) {
                     // user filters may hide this project, be sure to display it
                     $projList[$issue->getProjectId()] = $fullProjList[$issue->getProjectId()];
                  }
               }
            } else {
               unset($_SESSION['bugid']);
               if (0 !== $bug_id) {
                  $this->smartyHelper->assign('errorMessage', T_("Task not found in Mantis DB :").$bug_id);
                  $bug_id = 0;
               }
            }

            if (0 === $bug_id) {
               // if 0 == bugid, then issueList depends on selected project
               if((isset($_SESSION['projectid'])) && (0 != $_SESSION['projectid']) && Project::exists($_SESSION['projectid'])) {
                  $defaultProjectid = $_SESSION['projectid'];
                  $issueList = $this->getIssueList(array($defaultProjectid));
               } else {
                  $defaultProjectid = 0;
                  $issueList = $this->getIssueList(array_keys($projList));
               }
            } else {
               try {
                  $defaultProjectid = $issue->getProjectId();
                  $issueList = $this->getIssueList(array($defaultProjectid));

                  // if I made it up to here, then user is allowed to display the issue,
                  // nevertheless, we may need to bypass issueInfoFilters
                  if (!array_key_exists($bug_id,$issueList)) {
                     $issueList[$bug_id] = $issue;
                  }

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

                  $this->smartyHelper->assign('isManager', $user->isTeamManager($this->teamid));
                  $this->smartyHelper->assign('isObserver', $user->isTeamObserver($this->teamid));

                  $managedTeamList = $user->getManagedTeamList();
                  $managedProjList = count($managedTeamList) > 0 ? $user->getProjectList($managedTeamList, true, false) : array();
                  $oTeamList = $user->getObservedTeamList();
                  $observedProjList = count($oTeamList) > 0 ? $user->getProjectList($oTeamList, true, false) : array();
                  $isManagerView = (array_key_exists($issue->getProjectId(), $managedProjList)) ? true : false;
                  $isObserverView = (array_key_exists($issue->getProjectId(), $observedProjList)) ? true : false;
                  $this->smartyHelper->assign('issueGeneralInfo', IssueInfoTools::getIssueGeneralInfo($issue, ($isManagerView || $isObserverView)));

                  // do not display users of other teams if externalTasksProject
                  $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
                  if ($extProjId == $issue->getProjectId()) {
                     $displayedTeamid = $this->teamid;
                     $involvedUserids = array_keys($currentTeam->getMembers());
                  } else {
                     // now depending on user settings, display only team users
                     // or all users having worked on this task
                     $displayedTeamid = NULL; // $this->teamid; // NULL for all users
                     $involvedUserids = NULL;
                  }

                  $timeTracks = $issue->getTimeTracks($involvedUserids);
                  $this->smartyHelper->assign('jobDetails', $this->getJobDetails($timeTracks));
                  $this->smartyHelper->assign('timeDrift', IssueInfoTools::getTimeDrift($issue));

                  $firstTT = $issue->getFirstTimetrack();
                  $latestTT = $issue->getLatestTimetrack();

                  if (NULL != $firstTT) {
                     $firstTimetrackTimestamp = $firstTT->getDate();
                     $this->smartyHelper->assign('firstTimetrackDate', date('Y-m-d', $firstTimetrackTimestamp));
                  }
                  if (NULL != $latestTT) {
                     $latestTimetrackTimestamp = $latestTT->getDate();
                     $this->smartyHelper->assign('latestTimetrackDate', date('Y-m-d', $latestTimetrackTimestamp));
                  }

                  // IMPORTANT displaying all timetracks at once can overload the server
                  // so we need to find a way to reduce the number of month displayed
                  // I tested that +1000 timetracks fails (deprends on server config...)
                  $displayedTimetracks = $timeTracks;
                  if (count($timeTracks) > Constants::$issueInfoMaxTimetracksDisplayed) {
                     $timetracks_startTimestamp = $latestTimetrackTimestamp;
                     do {
                        $timetracks_startTimestamp = strtotime("-1 months", $timetracks_startTimestamp);
                        $displayedTimetracks = $issue->getTimeTracks($involvedUserids, $timetracks_startTimestamp);

                     } while (count($displayedTimetracks) < Constants::$issueInfoMaxTimetracksDisplayed);
                     $this->smartyHelper->assign('isTimetracksTruncated', true);
                  }

                  $this->smartyHelper->assign('months', $this->getCalendar($issue,$displayedTimetracks, $displayedTeamid));

                  // set Commands I belong to
                  $parentCmds = $this->getParentCommands($issue);
                  $this->smartyHelper->assign('parentCommands', $parentCmds);
                  $this->smartyHelper->assign('nbParentCommands', count($parentCmds));

                  $_SESSION['projectid'] = $defaultProjectid;
                  $_SESSION['bugid'] = $bug_id;

                  // Dashboard
                  IssueInfoTools::dashboardSettings($this->smartyHelper, $issue, $this->session_userid, $this->teamid);

               } catch (Exception $e) {
                  self::$logger->warn("issue $bug_id not found in mantis DB !");
                  unset($_SESSION['bugid']);
               }

            }
            $bugs = $this->getSmartyIssueList($issueList, $bug_id);
            $projects = SmartyTools::getSmartyArray($projList,$defaultProjectid);
            $this->smartyHelper->assign('bugs', $bugs);
            $this->smartyHelper->assign('projects', $projects);

            $this->smartyHelper->assign('filter_isOnlyAssignedToMe', $this->issueInfoFilters['isOnlyAssignedToMe']);
            $this->smartyHelper->assign('filter_isHideResolved', $this->issueInfoFilters['isHideResolved']);
            $this->smartyHelper->assign('filter_isHideClosed', $this->issueInfoFilters['isHideClosed']);
            $this->smartyHelper->assign('filter_isHideObservedTeams', $this->issueInfoFilters['isHideObservedTeams']);
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
    * Get info (color & name) for the jobs found in timetracks
    * @param TimeTrack[] $timeTracks
    * @return mixed[]
    */
   private function getJobDetails(array $timeTracks) {
      $jobs = new Jobs();

      $jobDetails = array();
      foreach ($timeTracks as $tt) {
         $jobid = $tt->getJobId();
         if(!array_key_exists($jobid,$jobDetails)) {
            $jobDetails[$jobid] = array(
               "jobColor" => $jobs->getJobColor($jobid),
               "jobName" => $jobs->getJobName($jobid),
            );
         }
      }
      return $jobDetails;
   }

   /**
    * Get the calendar of an issue
    * @param Issue $issue The issue
    * @param TimeTrack[] $trackList
    * @return mixed[]
    */
   private function getCalendar(Issue $issue, array $trackList, $teamid = NULL) {
      $months = NULL;

      if (!empty($trackList)) {
         $firstTT = $issue->getFirstTimetrack();
         $startTimestamp = $firstTT->getDate();
         $endTimestamp = $issue->getLatestTimetrack()->getDate();

         for ($y = date('Y', $startTimestamp); $y <= date('Y', $endTimestamp); $y++) {
            for ($m = 1; $m <= 12; $m++) {
               $monthsValue = $this->getMonth($m, $y, $issue, $trackList, $teamid);
               if ($monthsValue != NULL) {
                  $months[] = $monthsValue;
               }
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
   private function getMonth($month, $year, Issue $issue, array $trackList, $teamid = NULL) {
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
         $months[] = sprintf("%02d", $i);
      }

      $jobs = new Jobs();
      $userList = $issue->getInvolvedUsers($teamid);
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
               $midnightTimestamp = mktime(0, 0, 0, date('m', $date), date('d', $date), date('Y', $date));
               if(array_key_exists($midnightTimestamp,$durationByDate)) {
                  $durationByDate[$midnightTimestamp] += $tt->getDuration();
               } else {
                  $durationByDate[$midnightTimestamp] = $tt->getDuration();
               }
               $jobColorByDate[$midnightTimestamp] = $jobs->getJobColor($tt->getJobId());
            }
         }

         $usersDetails = NULL;
         $isUserThisMonth = false; // hide user if he has no timetrack this month
         for ($i = 1; $i <= $nbDaysInMonth; $i++) {
            $todayTimestamp = mktime(0, 0, 0, $month, $i, $year);

            if (array_key_exists($todayTimestamp,$durationByDate)) {

               $userTotalDuration += $durationByDate[$todayTimestamp];

               $usersDetails[] = array(
                  "jobColor" => $jobColorByDate[$todayTimestamp],
                  "jobDuration" => $durationByDate[$todayTimestamp]
               );
               $isUserThisMonth = true;
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
            "isDisplayed" => $isUserThisMonth,
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
}

// ========== MAIN ===========
IssueInfoController::staticInit();
$controller = new IssueInfoController('../', 'Task Info','IssueInfo');
$controller->execute();


