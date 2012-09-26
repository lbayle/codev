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

class DeadLine {
   public $date;
   public $nbDaysToDeadLine;
   public $isOnTime;    // true: ALL issues are on time
   public $issueList;
   public $isMonitored; // true: deadLine concerns only Monitored issues

   /**
    * @param $date
    * @param $nbDaysToDeadLine
    * @param bool $isOnTime
    * @param bool $isMonitored
    */
   public function __construct($date, $nbDaysToDeadLine, $isOnTime, $isMonitored) {
      $this->date = $date;
      $this->nbDaysToDeadLine = $nbDaysToDeadLine;
      $this->isOnTime = $isOnTime;
      $this->isMonitored = $isMonitored;
      $this->issueList = array();
   }

   /**
    * @param int $bugId
    */
   public function addIssue($bugId) {
      $this->issueList[] = $bugId;
   }

   /**
    * @param bool $isOnTime
    */
   public function setIsOnTime($isOnTime) {
      // if already exists and not on time, do not overwrite.
      if ((NULL == $isOnTime) || (TRUE == $isOnTime)) {
         $this->isOnTime = $isOnTime;
      }
   }

   /**
    * @param $isMonitored
    */
   public function setIsMonitored($isMonitored) {
      // non Monitored tasks have priority on deadLine status

      // if not a monitored task, do not overwrite.
      if (TRUE == $this->isMonitored) {
         $this->isMonitored = $isMonitored;
      }
   }

   /**
    * @return string
    */
   public function toString() {
      $string = date("d/m/Y", $this->date)." (+$this->nbDaysToDeadLine days)  ".T_("Tasks").": ";
      $string .= implode(', ', $this->issueList);
      return $string;
   }

   /***
    * depending on $isOnTime, $isMonitored returns
    * the path to the arrow image to be displayed (blue, red, grey)
    * @return string The image URL
    */
   public function getImageURL() {
      if (!$this->isOnTime) {
         return "images/arrow_down_red.png";
      } elseif ($this->isMonitored) {
         return "images/arrow_down_grey.png";
      } else {
         return "images/arrow_down_blue.png";
      }
   }
}

class PlanningReportController extends Controller {

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
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamList = $session_user->getTeamList();

         if (count($teamList) > 0) {
            // use the teamid set in the form, if not defined (first page call) use session teamid
            $teamid = 0;
            if (isset($_POST['teamid'])) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
               $_SESSION['teamid'] = $teamid;
            } elseif(isset($_SESSION['teamid'])) {
               $teamid = $_SESSION['teamid'];
            }

            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

            $pageWidth = Tools::getSecurePOSTIntValue('width',Tools::getSecureGETIntValue('width',0));
            $this->smartyHelper->assign('width', $pageWidth);

            if (array_key_exists($teamid,$teamList)) {
               $this->smartyHelper->assign('consistencyErrors', $this->getConsistencyErrors($teamid));

               $today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
               $graphSize = ("undefined" != $pageWidth) ? $pageWidth - 150 : 800;

               $allTasksLists = array();
               $workloads = array();
               $teamMembers = TeamCache::getInstance()->getTeam($teamid)->getUsers();

               $nbDaysToDisplay = 0;
               foreach ($teamMembers as $user) {
                  $workload = 0;

                  // show only developper's & manager's tasks
                  if ((!$user->isTeamDeveloper($teamid)) &&
                     (!$user->isTeamManager($teamid))) {

                     if(self::$logger->isDebugEnabled()) {
                        self::$logger->debug("user ".$user->getId()." excluded from scheduled users on team $teamid");
                     }
                     continue;
                  }

                  if (NULL != ($user->getDepartureDate()) && ($user->getDepartureDate() < $today)) { continue; }

                  $scheduledTaskList = ScheduledTask::scheduleUser($user, $today, TRUE);

                  foreach($scheduledTaskList as $scheduledTask) {
                     $workload += $scheduledTask->getDuration();
                  }
                  $nbDaysToDisplay = ($nbDaysToDisplay < $workload) ? $workload : $nbDaysToDisplay;

                  $allTasksLists[$user->getName()] = $scheduledTaskList;
                  $workloads[$user->getName()] = $workload;
               }

               $dayPixSize = (0 != $nbDaysToDisplay) ? ($graphSize / $nbDaysToDisplay) : 0;
               $dayPixSize = round($dayPixSize);
               #echo "DEBUG dayPixSize = $dayPixSize<br/>\n";

               $this->smartyHelper->assign('planning', $this->getPlanning($nbDaysToDisplay, $dayPixSize, $allTasksLists, $workloads, $teamid));
               $this->smartyHelper->assign('colors', array(
                  "green" => T_("onTime"),
                  "red"   => T_("NOT onTime"),
                  "blue"  => T_("no deadLine"),
                  "grey"  => T_("monitored")
               ));
               $this->smartyHelper->assign('dayPixSize', $dayPixSize-1);
            }
         }
      }
   }

   /**
    * Get the consistency errors
    * @param int $teamid
    * @return mixed[][]
    */
   private function getConsistencyErrors($teamid) {
      $issueList = TeamCache::getInstance()->getTeam($teamid)->getTeamIssueList(TRUE);
      $ccheck = new ConsistencyCheck2($issueList);

      $cerrList  = $ccheck->checkBadBacklog();
      $cerrList2 = $ccheck->checkUnassignedTasks();

      $consistencyErrors = NULL;
      if (count($cerrList) > 0 || count($cerrList2) > 0) {
         $consistencyErrors = array();
         foreach ($cerrList as $cerr) {
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);

            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->getSummary()),
               'issueStatus' => Constants::$statusNames[$cerr->status],
               'date' => date("Y-m-d", $cerr->timestamp),
               'user' => $user->getName(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc
            );
         }
         if (0 != count($cerrList2)) {
            $consistencyErrors[] = array(
               'issueURL' => '',
               'issueStatus' => '-',
               'date' => '-',
               'user' => '('.T_('unknown').')',
               'severity' => T_('Warning'),
               'severityColor' => 'color:orange',
               'desc' => count($cerrList2).' '.T_('tasks are not assigned to anybody.')
            );
         }
      }

      return $consistencyErrors;
   }

   /**
    * Get the planning
    * @param int $nbDaysToDisplay
    * @param int $dayPixSize
    * @param mixed[] $allTasksLists
    * @param int[] $workloads
    * @param int $teamid
    * @return mixed[][][] The planning
    */
   private function getPlanning($nbDaysToDisplay, $dayPixSize, array $allTasksLists, array $workloads, $teamid) {
      $days = array();
      for ($i = 0; $i < $nbDaysToDisplay; $i++) {
         $days[] = $dayPixSize - 1;
      }

      $taks = array();
      foreach ($allTasksLists as $userName => $scheduledTaskList) {
         $taks[] = array(
            "workload" => $workloads[$userName],
            "username" => $userName,
            "deadlines" => $this->getUserDeadLines($userName, $dayPixSize, $scheduledTaskList),
            "scheduledTasks" => $this->getScheduledTasks($userName, $dayPixSize, $scheduledTaskList, $teamid)
         );
      }

      return array(
         "days" => $days,
         "tasks" => $taks
      );
   }

   /**
    * @param string $userName
    * @param int $dayPixSize
    * @param ScheduledTask[] $scheduledTaskList
    * @return mixed[][]
    */
   private function getUserDeadLines($userName, $dayPixSize, array $scheduledTaskList) {
      $deadLineTriggerWidth = 10;
      $deadLines = array();

      // remove duplicate deadLines & set color
      foreach($scheduledTaskList as $scheduledTask) {
         if (NULL != $scheduledTask->getDeadline()) {
            if (!array_key_exists($scheduledTask->getDeadline(), $deadLines)) {
               $dline = new DeadLine($scheduledTask->getDeadline(),
                  $scheduledTask->getNbDaysToDeadLine(),
                  $scheduledTask->isOnTime(),
                  $scheduledTask->isMonitored());
               $dline->addIssue($scheduledTask->getIssueId());
               $deadLines[$scheduledTask->getDeadline()] = $dline;
            } else {
               $dline = $deadLines[$scheduledTask->getDeadline()];
               $dline->setIsOnTime($scheduledTask->isOnTime());
               $dline->addIssue($scheduledTask->getIssueId());
               $dline->setIsMonitored($scheduledTask->isMonitored());
            }
         }
      }

      // well if no deadLines, ...
      if (0 == count($deadLines)) {
         return array();
      }

      // sort deadLines by date ASC
      ksort($deadLines);

      // because the 'size' of the arrow, the first scheduledTask has been shifted
      // we need to check if the $nbDays of the first deadLine = 0
      $dline = reset($deadLines);
      $isDeadline = 0 != $dline->nbDaysToDeadLine;

      // display deadLines
      $curPos=0;
      $deadline = array();
      foreach($deadLines as $date => $dline) {
         $offset = $dline->nbDaysToDeadLine;

         if ($offset >= 0) {
            $deadline[$date] = array(
               "user" => $userName,
               "date" => date(T_("Y-m-d"), $date),
               "url" => $dline->getImageURL(),
               "title" => $dline->toString(),
               "nbDaysToDeadLine" => $dline->nbDaysToDeadLine,
               "deadlineIssues" => implode(', ', $dline->issueList),
               "imgUrl" => Tools::getServerRootURL().'/images/tooltip_white_arrow_130.png'
            );

            if ($offset > 0) {
               // draw timeLine
               $timeLineSize = ($offset * $dayPixSize) - ($deadLineTriggerWidth/2) - $curPos;

               $deadline[$date]["width"] = $timeLineSize;
               $curPos += $timeLineSize + $deadLineTriggerWidth;
            } else {
               $curPos += $deadLineTriggerWidth/2;
            }
         }
      }

      return array(
         "isDeadline" => $isDeadline,
         "deadline" => $deadline,
      );
   }

   /**
    * @param string $userName
    * @param int $dayPixSize
    * @param ScheduledTask[] $scheduledTaskList
    * @param int $teamid
    * @return mixed[][]
    */
   private function getScheduledTasks($userName, $dayPixSize, array $scheduledTaskList, $teamid) {
      $totalPix = 0;

      $projList = TeamCache::getInstance()->getTeam($teamid)->getProjects();

      $scheduledTasks = array();
      foreach($scheduledTaskList as $scheduledTask) {
         $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
         $totalPix += $taskPixSize;

         // set color
         if (NULL != $scheduledTask->getDeadline()) {
            if (!$scheduledTask->isOnTime()) {
               $color = "red";
            } else {
               $color = ($scheduledTask->isMonitored()) ? "grey" : "green";
            }
         } else {
            $color = ($scheduledTask->isMonitored()) ? "grey" : "blue";
         }

         // hide tasks not in team projects
         $issue = IssueCache::getInstance()->getIssue($scheduledTask->getIssueId());

         $taskTitle = $scheduledTask->getDescription();
         $formatedTitle = str_replace("'", ' ', $taskTitle);
         $formatedTitle = str_replace('"', ' ', $formatedTitle);

         $drawnTaskPixSize = $taskPixSize - 1;

         $sTask = array(
            "user" => $userName,
            "bugid" => $scheduledTask->getIssueId(),
            "title" => $formatedTitle,
            "width" => $drawnTaskPixSize,
            "color" => $color,
            "strike" => !array_key_exists($issue->getProjectId(),$projList),
            "duration" => $scheduledTask->getDuration(),
            "priorityName" => $scheduledTask->getPriorityName(),
            "severityName" => $scheduledTask->getSeverityName(),
            "statusName" => $scheduledTask->getStatusName(),
            "projectName" => $scheduledTask->getProjectName(),
            "summary" => $scheduledTask->getSummary(),
            "imgUrl" => Tools::getServerRootURL().'/images/tooltip_white_arrow_big.png'
         );
         if ($scheduledTask->isMonitored()) {
            $sTask["handlerName"] = $scheduledTask->getHandlerName();
         }
         if ($scheduledTask->getDeadline() > 0) {
            $sTask["deadLine"] = date(T_("Y-m-d"), $scheduledTask->getDeadline());
         }
         $scheduledTasks[$scheduledTask->getIssueId()] = $sTask;

      }

      return $scheduledTasks;
   }

}

// ========== MAIN ===========
PlanningReportController::staticInit();
$controller = new PlanningReportController('Planning','Planning');
$controller->execute();

?>
