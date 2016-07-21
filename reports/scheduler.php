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
         

         // Get scheduler task provider list
         $schedulerManager = new SchedulerManager();
         $taskProviderList = $schedulerManager->getSchedulerTaskProviderList();
         $taskProviderDescriptionList = null;
         foreach($taskProviderList as $taskProviderName)
         {
            $taskProviderReflection = new ReflectionClass($taskProviderName);
            $taskProviderDescriptionList[$taskProviderName] = $taskProviderReflection->newInstance()->getShortDesc();
         }
         // Get selected scheduler task provider 
         $selectedTaskProviderName = SchedulerManager::getUserOption(SchedulerManager::OPTION_taskProvider, $_SESSION['userid'], $_SESSION['teamid']);
         if(!in_array($selectedTaskProviderName, $taskProviderList))
         {
            $selectedTaskProviderName = $taskProviderList[0];
         }
         
         
         
         $taskIdList = SmartyTools::getSmartyArray($taskIdList, null);
         $this->smartyHelper->assign("scheduler_taskList", $taskIdList);
         
         $userList = $team->getActiveMembers();
         $userList = SmartyTools::getSmartyArray($userList, null);
         $this->smartyHelper->assign("scheduler_userList", $userList);
         
         $taskProviderDescriptionList = SmartyTools::getSmartyArray($taskProviderDescriptionList, $selectedTaskProviderName);
         $this->smartyHelper->assign("scheduler_taskProviderList", $taskProviderDescriptionList);
      }
   }
   
   
}

// ========== MAIN ===========
SchedulerController::staticInit();
$controller = new SchedulerController('../', 'Timeline scheduler','Scheduler');
$controller->execute();