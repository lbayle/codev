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

class SetHolidaysController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("set_holidays");
   }

   protected function display() {
      if (isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         // if first call to this page
         if (!isset($_POST['nextForm'])) {
            $lTeamList = $session_user->getLeadedTeamList();
            if (0 != count($lTeamList)) {
               // User is TeamLeader, let him choose the user he wants to manage
               $this->smartyHelper->assign('users', $this->getUsers($session_user));
            } else {
               // developper & manager can add timeTracks
               $mTeamList = $session_user->getDevTeamList();
               $managedTeamList = $session_user->getManagedTeamList();
               $teamList = $mTeamList + $managedTeamList;

               if (0 != count($teamList)) {
                  $_POST['userid'] = $session_user->id;
                  $_POST['nextForm'] = "addHolidaysForm";
               }
            }
         }

         if ($_POST['nextForm'] == "addHolidaysForm") {
            $userid = Tools::getSecurePOSTIntValue('userid',$session_user->id);

            $managed_user = UserCache::getInstance()->getUser($userid);

            // dates
            $startdate = Tools::getSecurePOSTStringValue('startdate',date("Y-m-d"));

            $enddate = Tools::getSecurePOSTStringValue('enddate','');

            $defaultBugid = Tools::getSecurePOSTIntValue('bugid',0);

            $action = Tools::getSecurePOSTStringValue('action','');
            if ("addHolidays" == $action) {
               // TODO add tracks !
               $job = Tools::getSecurePOSTStringValue('job');

               $holydays = Holidays::getInstance();

               $startTimestamp = Tools::date2timestamp($startdate);
               $endTimestamp = Tools::date2timestamp($enddate);

               // save to DB
               $timestamp = $startTimestamp;
               while ($timestamp <= $endTimestamp) {
                  // check if not a fixed holiday
                  if (!$holydays->isHoliday($timestamp)) {

                     // check existing timetracks on $timestamp and adjust duration
                     $duration = $managed_user->getAvailableTime($timestamp);
                     if ($duration > 0) {
                        self::$logger->debug(date("Y-m-d", $timestamp)." duration $duration job $job");
                        TimeTrack::create($managed_user->id, $defaultBugid, $job, $timestamp, $duration);
                     }
                  }
                  $timestamp = strtotime("+1 day",$timestamp);;
               }
               // We redirect to holidays report, so the user can verify his holidays
               header('Location:holidays_report.php');
            }

            $this->smartyHelper->assign('startDate', $startdate);
            $this->smartyHelper->assign('endDate', $enddate);

            if($session_user->id != $managed_user->id) {
               $this->smartyHelper->assign('otherrealname', $managed_user->getRealname());
            }

            // SideTasks Project List
            $devProjList = $managed_user->getProjectList($managed_user->getDevTeamList());
            $managedProjList = $managed_user->getProjectList($managed_user->getManagedTeamList());
            $projList = $devProjList + $managedProjList;

            foreach ($projList as $pid => $pname) {
               // we want only SideTasks projects
               $tmpPrj = ProjectCache::getInstance()->getProject($pid);
               try {
                  if (!$tmpPrj->isSideTasksProject()) {
                     unset($projList[$pid]);
                  }
               } catch (Exception $e) {
                  self::$logger->error("project $pid: ".$e->getMessage());
               }
            }

            $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
            $extProj = ProjectCache::getInstance()->getProject($extproj_id);
            $projList[$extproj_id] = $extProj->name;

            $defaultProjectid  = Tools::getSecurePOSTIntValue('projectid',0);
            if($defaultBugid != 0 && $action == 'setBugId') {
               // find ProjectId to update categories
               $issue = IssueCache::getInstance()->getIssue($defaultBugid);
               $defaultProjectid  = $issue->projectId;
            }

            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));
            $this->smartyHelper->assign('issues', $this->getIssues($defaultProjectid, $projList, $extproj_id, $defaultBugid));
            $this->smartyHelper->assign('jobs', $this->getJobs($defaultProjectid, $projList));

            $this->smartyHelper->assign('userid', $managed_user->id);
         }
      }
   }

   /**
    * Get users of teams I lead
    * @param User $session_user The current user
    * @return mixed[] of users
    */
   private function getUsers($session_user) {
      $accessLevel_dev = Team::accessLevel_dev;
      $accessLevel_manager = Team::accessLevel_manager;

      $teamList = $session_user->getLeadedTeamList();

      // separate list elements with ', '
      $formatedTeamString = implode( ', ', array_keys($teamList));

      // show only users from the teams that I lead.
      $query = "SELECT DISTINCT mantis_user_table.id, mantis_user_table.username, mantis_user_table.realname ".
         "FROM `mantis_user_table`, `codev_team_user_table` ".
         "WHERE codev_team_user_table.user_id = mantis_user_table.id ".
         "AND codev_team_user_table.team_id IN ($formatedTeamString) ".
         "AND codev_team_user_table.access_level IN ($accessLevel_dev, $accessLevel_manager) ".
         "ORDER BY mantis_user_table.username";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return NULL;
      }

      $users = array();
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $users[$row->id] = $row->username;
      }

      return SmartyTools::getSmartyArray($users, $session_user->id);
   }

   /**
    * Get issues
    * @param int $defaultProjectid
    * @param string[] $projList
    * @param int $extproj_id
    * @param int $defaultBugid
    * @return mixed[]
    */
   private function getIssues($defaultProjectid, $projList, $extproj_id, $defaultBugid) {
      // Task list
      if (0 != $defaultProjectid) {
         $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);
         $issueList = $project1->getIssues();
      } else {
         // no project specified: show all tasks
         $issueList = Project::getProjectIssues(array_keys($projList));
      }

      $issues = NULL;
      foreach ($issueList as $issue) {
         try  {
            if (($issue->isVacation()) || ($extproj_id == $issue->projectId)) {
               $issues[$issue->bugId] = array(
                  'tcId' => $issue->tcId,
                  'summary' => $issue->summary,
                  'selected' => $issue->bugId == $defaultBugid);
            }
         } catch (Exception $e) {
            self::$logger->error("getIssues(): issue $issue->bugId: ".$e->getMessage());
         }
      }

      return $issues;
   }

   /**
    * Get jobs
    * @param int $defaultProjectid
    * @param array $projList
    * @return mixed[]
    */
   private function getJobs($defaultProjectid, $projList) {
      // Job list
      if (0 != $defaultProjectid) {
         $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);
         $jobList = $project1->getJobList();
      } else {
         $jobList = array();
         foreach ($projList as $pid2 => $pname) {
            $tmpPrj1 = ProjectCache::getInstance()->getProject($pid2);
            $jobList += $tmpPrj1->getJobList();
         }
      }
      // do not display selector if only one Job
      if (1 == count($jobList)) {
         reset($jobList);
         return key($jobList);
      } else {
         return $jobList;
      }
   }

}

// ========== MAIN ===========
SetHolidaysController::staticInit();
$controller = new SetHolidaysController('Add Holidays','Holiday');
$controller->execute();

?>
