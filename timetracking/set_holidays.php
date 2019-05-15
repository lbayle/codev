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
      if (Tools::isConnectedUser()) {

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {
             
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $nextForm = Tools::getSecurePOSTStringValue('nextForm','');
            $userid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);

            // if first call to this page
            if ('' === $nextForm) {
               $activeMembers = $team->getActiveMembers(NULL, NULL, TRUE);
               if ($this->session_user->isTeamManager($this->teamid)) {
                  $this->smartyHelper->assign('users', SmartyTools::getSmartyArray($activeMembers, $this->session_userid));
               } else {
                  // developper & manager can add timeTracks
                  if (array_key_exists($this->session_userid, $activeMembers)) {
                     //$_POST['userid'] = $this->session_userid;
                     $nextForm = "addHolidaysForm";
                     //$_POST['days'] = 'dayid';
                  }
               }
            }

            if ("addHolidaysForm" === $nextForm) {

               $managed_user = UserCache::getInstance()->getUser($userid);

               // dates
               $startdate = Tools::getSecurePOSTStringValue('startdate',date("Y-m-d"));

               $enddate = Tools::getSecurePOSTStringValue('enddate','');
			   
               $defaultBugid = Tools::getSecurePOSTIntValue('bugid',0);

               $action = Tools::getSecurePOSTStringValue('action','');
               
               $duration = Tools::getSecurePOSTNumberValue('duree',0);
               
               if ("addHolidays" == $action) {
                  // TODO add tracks !
                  $job = Tools::getSecurePOSTStringValue('job');
                  
                  $duration = Tools::getSecurePOSTNumberValue('duree');

                  $holydays = Holidays::getInstance();

                  $keyvalue = Tools::getSecurePOSTStringValue('checkedDays');
                  $checkedDaysList = Tools::doubleExplode(':', ',', $keyvalue);
               
                  $startTimestamp = Tools::date2timestamp($startdate);
                  $endTimestamp = Tools::date2timestamp($enddate);
                  
                  // save to DB
                  $weekday = date('l', strtotime($startdate));
                  $timestamp = $startTimestamp;
                  
                  while ($timestamp <= $endTimestamp) {
                     // check if not a fixed holiday
                     if (!$holydays->isHoliday($timestamp)) {
      						// check existing timetracks on $timestamp and adjust duration
                        $availabletime = $managed_user->getAvailableTime($timestamp);
                        // not imput more than possible 
                        if ($duration >= $availabletime) {
                        	$imput = $availabletime;
                        } else {
                           $imput = $duration;
                        }
                        // check if weekday checkbox is checked
                        if (1 == $checkedDaysList[$weekday]) {
	                        if ($duration > 0) {
	                           if(self::$logger->isDebugEnabled()) {
	                              self::$logger->debug(date("Y-m-d", $timestamp)." duration $imput job $job");
	                           }
	                           TimeTrack::create($managed_user->getId(), $defaultBugid, $job, $timestamp, $imput, $this->session_userid, $this->teamid);
	                        }
                        }
                     }
                     $timestamp = strtotime("+1 day",$timestamp);
                     $weekday = date('l', strtotime(date("Y-m-d", $timestamp)));      
                  }
                  
                  // We redirect to holidays report, so the user can verify his holidays
                  header('Location:holidays_report.php');
               }

               $this->smartyHelper->assign('startDate', $startdate);
               $this->smartyHelper->assign('endDate', $enddate);
			   
               if($this->session_userid != $managed_user->getId()) {
                  $this->smartyHelper->assign('otherrealname', $managed_user->getRealname());
               }

               // Get Team SideTasks Project List

               $projList = $team->getProjects(true, false);
               foreach ($projList as $pid => $pname) {
                  // we want only SideTasks projects
                  try {
                     if (!$team->isSideTasksProject($pid)) {
                        unset($projList[$pid]);
                     }
                  } catch (Exception $e) {
                     self::$logger->error("project $pid: ".$e->getMessage());
                  }
               }

               $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
               $extProj = ProjectCache::getInstance()->getProject($extproj_id);
               $projList[$extproj_id] = $extProj->getName();

               $defaultProjectid  = Tools::getSecurePOSTIntValue('projectid',0);
               
               if($defaultBugid != 0 && $action == 'setBugId') {
                  // find ProjectId to update categories
                  $issue = IssueCache::getInstance()->getIssue($defaultBugid);
                  $defaultProjectid  = $issue->getProjectId();
               }

               $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projList,$defaultProjectid));
               $this->smartyHelper->assign('issues', $this->getIssues($defaultProjectid, $projList, $extproj_id, $defaultBugid));
               $this->smartyHelper->assign('jobs', $this->getJobs($defaultProjectid, $projList));
               $this->smartyHelper->assign('duration', SmartyTools::getSmartyArray(TimeTrackingTools::getDurationList($team->getId()),$duration));
               
               $this->smartyHelper->assign('userid', $managed_user->getId());
            }
         }
      }
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
            if (($issue->isVacation($this->teamid)) || 
                 $issue->isProjManagement(array($this->teamid)) ||
                 ($extproj_id == $issue->getProjectId())) {
               $issues[$issue->getId()] = array(
                  'tcId' => $issue->getTcId(),
                  'summary' => $issue->getSummary(),
                  'selected' => $issue->getId() == $defaultBugid);
            }
         } catch (Exception $e) {
            self::$logger->error("getIssues(): issue ".$issue->getId().": ".$e->getMessage());
         }
      }

      return $issues;
   }

   /**
    * Get jobs.
    *
    * Note: only sidetaskProjects & externalTasksProject are in the $projList
    *
    * @param int $defaultProjectid
    * @param array $projList
    * @return mixed[]
    */
   private function getJobs($defaultProjectid, $projList) {
      // Job list
      if (0 != $defaultProjectid) {
         $project1 = ProjectCache::getInstance()->getProject($defaultProjectid);
         $jobList = $project1->getJobList(Project::type_sideTaskProject);
      } else {
         $jobList = array();
         foreach ($projList as $pid2 => $pname) {
            $tmpPrj1 = ProjectCache::getInstance()->getProject($pid2);
            $jobList += $tmpPrj1->getJobList(Project::type_sideTaskProject);
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
$controller = new SetHolidaysController('../', 'Add leaves','Holiday');
$controller->execute();

