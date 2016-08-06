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
         // only teamMembers & observers can access this page
         if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $projectList = $team->getProjects(false, false, false);
            $taskList = $team->getTeamIssueList(false, false);
            $userList = $team->getActiveMembers();
            $schedulerManager = new SchedulerManager();

            // TODO use this value to stop scheduler computing if OPTION_nbDaysForecast is too big
            $max_time = ini_get("max_execution_time");
            self::$logger->error('max_execution_time = '.$max_time);

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
            $taskProviderList = $schedulerManager->getSchedulerTaskProviderList();
            $taskProviderDescriptionList = null;
            foreach($taskProviderList as $taskProviderName)
            {
               $taskProviderReflection = new ReflectionClass($taskProviderName);
               $taskProviderDescriptionList[$taskProviderName] = $taskProviderReflection->newInstance()->getShortDesc();
            }
            // Get selected scheduler task provider 
            $selectedTaskProviderName = $schedulerManager->getUserOption(SchedulerManager::OPTION_taskProvider, $_SESSION['userid'], $_SESSION['teamid']);
            if(!in_array($selectedTaskProviderName, $taskProviderList))
            {
               $selectedTaskProviderName = $taskProviderList[0];
            }


            $optDisplayExtRef = $schedulerManager->getUserOption(SchedulerManager::OPTION_isDisplayExtRef, $_SESSION['userid'], $_SESSION['teamid']);
            $this->smartyHelper->assign("isDisplayExtRef", $optDisplayExtRef);

            $projectList = SmartyTools::getSmartyArray($projectList, null);
            $this->smartyHelper->assign("scheduler_projectList", $projectList);

            $taskIdList = SmartyTools::getSmartyArray($taskIdList, null);
            $this->smartyHelper->assign("scheduler_taskList", $taskIdList);

            $userList = SmartyTools::getSmartyArray($userList, null);
            $this->smartyHelper->assign("scheduler_userList", $userList);

            $taskProviderDescriptionList = SmartyTools::getSmartyArray($taskProviderDescriptionList, $selectedTaskProviderName);
            $this->smartyHelper->assign("scheduler_taskProviderList", $taskProviderDescriptionList);

            // start date of the displayed window
            $windowStartDate = date('Y-m-d', strtotime('first day of this week'));
            $this->smartyHelper->assign('scheduler_windowStartDate', $windowStartDate);

            // displayed window : nb days displayed (default 30 days)
            $nbDaysToDisplay = $schedulerManager->getUserOption(SchedulerManager::OPTION_nbDaysToDisplay, $_SESSION['userid'], $_SESSION['teamid']);
            $this->smartyHelper->assign('scheduler_nbDaysToDisplay', $nbDaysToDisplay);

            $nbDaysToDisplayList = SmartyTools::getSmartyArray(array(
                        15 => T_('2 weeks'),
                        30 => T_('1 month'),
                        60 => T_('2 months'),
                        90 => T_('3 months')), $nbDaysToDisplay);
            $this->smartyHelper->assign('nbDaysToDisplayList', $nbDaysToDisplayList);

            // nb days to compute (default 90 days)
            $nbDaysToCompute = $schedulerManager->getUserOption(SchedulerManager::OPTION_nbDaysForecast, $_SESSION['userid'], $_SESSION['teamid']);
            $this->smartyHelper->assign('scheduler_nbDaysToCompute', $nbDaysToCompute);

            $warnThreshold = $schedulerManager->getUserOption(SchedulerManager::OPTION_warnThreshold, $_SESSION['userid'], $_SESSION['teamid']);
            $this->smartyHelper->assign('scheduler_warnThreshold', $warnThreshold);

         }
      }
   }
   
   
}

// ========== MAIN ===========
SchedulerController::staticInit();
$controller = new SchedulerController('../', 'Timeline scheduler','Planning');
$controller->execute();