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

class ProjectActivityReportController extends Controller {

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

        // only teamMembers & observers can access this page
        if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
        } else {

            // dates
            $weekDates = Tools::week_dates(date('W'),date('Y'));
            $startdate = Tools::getSecurePOSTStringValue("startdate",Tools::formatDate("%Y-%m-%d",$weekDates[1]));
            $this->smartyHelper->assign('startDate', $startdate);

            $enddate = Tools::getSecurePOSTStringValue("enddate",Tools::formatDate("%Y-%m-%d",$weekDates[5]));
            $this->smartyHelper->assign('endDate', $enddate);

            $isDetailed = Tools::getSecurePOSTIntValue('withJobDetails',0);
            $this->smartyHelper->assign('isJobDetails', $isDetailed);

            $isExtTasksPrj = Tools::getSecurePOSTIntValue('withExtTasksPrj',0);
            $this->smartyHelper->assign('isExtTasksPrj', $isExtTasksPrj);

            $isSideTasksPrj = Tools::getSecurePOSTIntValue('withSideTasksPrj',1);
            $this->smartyHelper->assign('isSideTasksPrj', $isSideTasksPrj);

            if ('computeProjectActivityReport' == $_POST['action']) {

               $startTimestamp = Tools::date2timestamp($startdate);
               $endTimestamp = Tools::date2timestamp($enddate);
               $endTimestamp = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));

               $timeTracking = new TimeTracking($startTimestamp, $endTimestamp, $this->teamid);

               $this->smartyHelper->assign('projectActivityReport', $this->getProjectActivityReport($timeTracking->getProjectTracks(true), $this->teamid, $isDetailed));

               // WorkingDaysPerProjectPerUser
               $data = $timeTracking->getWorkingDaysPerProjectPerUser($isExtTasksPrj, true, $isSideTasksPrj);
               foreach ($data as $smartyKey => $smartyVariable) {
                  $this->smartyHelper->assign($smartyKey, $smartyVariable);
               }
               
               
               $data = $this->getWorkingDaysPerProjectPerUser($startTimestamp, $endTimestamp, $isExtTasksPrj, $isSideTasksPrj);
               foreach ($data as $smartyKey => $smartyVariable) {
                  $this->smartyHelper->assign($smartyKey, $smartyVariable);
               }
            }
         }
      }
   }
   
   
   
   /**
    * Get project activity report
    * @param mixed[][][] $projectTracks
    * @param int $teamid The team id
    * @param boolean $isDetailed
    * @return mixed[]
    */
   private function getProjectActivityReport(array $projectTracks, $teamid, $isDetailed) {
      $team = TeamCache::getInstance()->getTeam($teamid);
      $projectActivityReport = NULL;
      foreach ($projectTracks as $projectId => $bugList) {
         $project = ProjectCache::getInstance()->getProject($projectId);

         $jobList = $project->getJobList($team->getProjectType($projectId));
         $jobTypeList = array();
         if ($isDetailed) {
            foreach($jobList as $jobId => $jobName) {
               $jobTypeList[$jobId] = $jobName;
            }
         }

         // write table content (by bugid)
         $row_id = 0;
         $bugDetailedList = "";
         foreach ($bugList as $bugid => $jobs) {
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $totalTime = 0;
            $tr_class = ($row_id & 1) ? "row_even" : "row_odd";

            $subJobList = array();
            foreach($jobList as $jobId => $jobName) {
               $jobTime = 0;
               if(array_key_exists($jobId, $jobs)) {
                  $jobTime = $jobs[$jobId];
               }
               if ($isDetailed) {
                  $subJobList[$jobId] = $jobTime;
               }
               $totalTime += $jobTime;
            }

            $row_id += 1;

            $bugDetailedList[$bugid] = array(
               'class' => $tr_class,
               'description' => SmartyTools::getIssueDescription($bugid, $issue->getTcId(), $issue->getSummary()),
               'jobList' => $subJobList,
               'targetVersion' => $issue->getTargetVersion(),
               'currentStatusName' => $issue->getCurrentStatusName(),
               'progress' => round(100 * $issue->getProgress()),
               'backlog' => $issue->getBacklog(),
               'totalTime' => $totalTime,
            );
         }

         $projectActivityReport[$projectId] = array(
            'id' => $projectId,
            'name' => $project->getName(),
            'jobList' => $jobTypeList,
            'bugList' => $bugDetailedList
         );
      }

      return $projectActivityReport;
   }

   
   private function getWorkingDaysPerProjectPerUser($startTimestamp, $endTimestamp, $isExtTasksPrj, $isSideTasksPrj) {
      
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $activeMembers = $team->getActiveMembers($startTimestamp, $endTimestamp, TRUE);
      $activeMembersIds = array_keys($activeMembers);
      $usersData = array();
      
      // Time spend by user for each project (depending on the chosen Timestamp)
      foreach($activeMembersIds as $user_id) {
         $user = UserCache::getInstance()->getUser($user_id);
         $timeTracks = $user->getTimeTracks($startTimestamp, $endTimestamp);
         
         $userElapsedPerProject = array();
         foreach($timeTracks as $timeTrack) {
            $userElapsedPerProject[$timeTrack->getProjectId()] += $timeTrack->getDuration();
         }
         $usersData[$user_id] = $userElapsedPerProject;
      }
      
      

      // Check SideTask & ExternalTask
      $projList = $team->getProjects(true, true, $isSideTasksPrj);
      if(!$isExtTasksPrj){
         if (array_key_exists(Config::getInstance()->getValue(Config::id_externalTasksProject),$projList)){
            unset($projList[Config::getInstance()->getValue(Config::id_externalTasksProject)]);
         }
      } 
      
      // Time elapsed per user and per project (plus total per user)
      $usersSmartyData = array();
      foreach($activeMembers as $user_id => $realName) {
         $elapsedPerProject = array();
         $userTotal = 0;
         foreach (array_keys($projList) as $projId){
            $val = $usersData[$user_id][$projId];
            $elapsedPerProject[$projId] = $val;
            $userTotal += $val;
         }
         
      // Formatting for Smarty   
         $usersSmartyData[] = array (
            'id' => $user_id,
            'name' => $realName,
            'elapsedPerProject' => $elapsedPerProject,
            'total' => $userTotal,
         );
      }
      
      // Time elapsed per project plus total for all the projects
      $totalAllProj = 0;
      $totalPerProj = array();
      foreach (array_keys($projList) as $projId) {
         foreach($activeMembersIds as $userId) {
            $totalAllProj += $usersData[$userId][$projId];
            $totalPerProj[$projId] += $usersData[$userId][$projId];
         }
      }
      $totalPerProj['total'] = $totalAllProj;
       
      $data = array(
          'usersData' => $usersSmartyData, 
          'projList' => $projList,
          'totalPerProj' => $totalPerProj,
      );
      return $data;
 }  
}

// ========== MAIN ===========
ProjectActivityReportController::staticInit();
$controller = new ProjectActivityReportController('../', 'Weekly activities','TimeTracking');
$controller->execute();


