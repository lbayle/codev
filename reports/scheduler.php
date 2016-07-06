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

class SchedulerController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;
   
   
   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {
         $team = TeamCache::getInstance()->getTeam($this->teamid);
         $taskList = $team->getTeamIssueList(false, false);
         
         // Get config from BD 
         $timePerUserPerTaskList = SchedulerManager::getTimePerUserPerTaskList($_SESSION['userid'], $_SESSION['teamid']);
         
         // Set task id list
         $taskIdList[null] = T_("Select a task");
         foreach($taskList as $key => $task)
         {
            $statusThreshold = $task->getBugResolvedStatusThreshold();
            $status = $task->getStatus();
            
            if($status < $statusThreshold){
               if(0 < $task->getEffortEstim())
               {
                  $taskIdList[$key] = $task->getSummary();
               }
            }
         }
         
         // Set time Per User Per Task List with libelle
         $timePerUserPerTaskLibelleList = null;
         if(NULL != $timePerUserPerTaskList){
            foreach($timePerUserPerTaskList as $taskIdKey => $timePerUserList)
            {
               $taskSummary = IssueCache::getInstance()->getIssue($taskIdKey)->getSummary();
               foreach($timePerUserList as $userIdKey => $time)
               {
                  $userName = UserCache::getInstance()->getUser($userIdKey)->getName();
                  $timePerUserPerTaskLibelleList[$taskIdKey]['users'][$userName] = $time;
                  $timePerUserPerTaskLibelleList[$taskIdKey]['taskName'] = $taskSummary;
               }
            }
         }

         
         
         
//         self::$logger->error('-------------------------$timePerUserPerTaskLibelleList');
//         self::$logger->error($timePerUserPerTaskLibelleList);
         $this->smartyHelper->assign("scheduler_timePerUserPerTaskLibelleList", $timePerUserPerTaskLibelleList);
         
         $taskIdList = SmartyTools::getSmartyArray($taskIdList, null);
         $this->smartyHelper->assign("scheduler_taskList", $taskIdList);
         
         $userList = $team->getActiveMembers();
         $userList = SmartyTools::getSmartyArray($userList, null);
         $this->smartyHelper->assign("scheduler_userList", $userList);
         
         $this->smartyHelper->assign("scheduler_taskId", json_encode($timePerUserPerTaskLibelleList));
      }
   }
   
   
}

// ========== MAIN ===========
SchedulerController::staticInit();
$controller = new SchedulerController('../', 'Workload scheduler','Scheduler');
$controller->execute();