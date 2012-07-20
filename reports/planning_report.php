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

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

include_once('classes/consistency_check2.class.php');
include_once('classes/issue_cache.class.php');
include_once('classes/scheduler.class.php');
include_once('classes/team_cache.class.php');
include_once('classes/user_cache.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("planning_report");

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

      $count = 0;
      foreach($this->issueList as $i) {
         $count++;
         $string .= "$i";
         if($count != count($this->issueList)) {
            $string .= ",";
         }
      }
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

/**
 * Get the consistency errors
 * @param int $teamid
 * @return mixed[][]
 */
function getConsistencyErrors($teamid) {
   global $statusNames;

   $issueList = TeamCache::getInstance()->getTeam($teamid)->getTeamIssueList(TRUE);
   $ccheck = new ConsistencyCheck2($issueList);

   $cerrList  = $ccheck->checkBadRemaining();
   $cerrList2 = $ccheck->checkUnassignedTasks();

   $consistencyErrors = NULL;
   if (count($cerrList) > 0 || count($cerrList2) > 0) {
      $consistencyErrors = array();
      foreach ($cerrList as $cerr) {
         $user = UserCache::getInstance()->getUser($cerr->userId);
         $issue = IssueCache::getInstance()->getIssue($cerr->bugId);

         $consistencyErrors[] = array(
            'issueURL' => Tools::issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
            'issueStatus' => $statusNames[$cerr->status],
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
function getPlanning($nbDaysToDisplay, $dayPixSize, array $allTasksLists, array $workloads, $teamid) {
   $days = array();
   for ($i = 0; $i < $nbDaysToDisplay; $i++) {
      $days[] = $dayPixSize - 1;
   }

   $deadLineTriggerWidth = 10;
   $taks = array();
   foreach ($allTasksLists as $userName => $scheduledTaskList) {
      $taks[] = array(
         "workload" => $workloads[$userName],
         "username" => $userName,
         "deadlines" => getUserDeadLines($dayPixSize, $scheduledTaskList, $deadLineTriggerWidth),
         "userSchedule" => getUserSchedule($dayPixSize, $scheduledTaskList, $teamid)
      );
   }

   return array(
      "width" => $deadLineTriggerWidth / 2,
      "height" => 1,
      "days" => $days,
      "tasks" => $taks
   );
}

/**
 * @param int $dayPixSize
 * @param ScheduledTask[] $scheduledTaskList
 * @param int $deadLineTriggerWidth
 * @return mixed[][]
 */
function getUserDeadLines($dayPixSize, array $scheduledTaskList, $deadLineTriggerWidth) {
   $deadLines = array();

   // remove duplicate deadLines & set color
   foreach($scheduledTaskList as $scheduledTask) {
      if (NULL != $scheduledTask->deadLine) {
         if (!array_key_exists($scheduledTask->deadLine, $deadLines)) {
            $dline = new DeadLine($scheduledTask->deadLine,
               $scheduledTask->nbDaysToDeadLine,
               $scheduledTask->isOnTime,
               $scheduledTask->isMonitored);
            $dline->addIssue($scheduledTask->bugId);
            $deadLines[$scheduledTask->deadLine] = $dline;
         } else {
            $dline = $deadLines[$scheduledTask->deadLine];
            $dline->setIsOnTime($scheduledTask->isOnTime);
            $dline->addIssue($scheduledTask->bugId);
            $dline->setIsMonitored($scheduledTask->isMonitored);
         }
      }
   }

   // well if no deadLines, ...
   if (0 == count($deadLines)) { return array(); }

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
            "url" => $dline->getImageURL(),
            "title" => $dline->toString()
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
      "height" => 7,
      "deadline" => $deadline,
   );
}

/**
 * @param int $dayPixSize
 * @param ScheduledTask[] $scheduledTaskList
 * @param int $teamid
 * @return mixed[][]
 */
function getUserSchedule($dayPixSize, array $scheduledTaskList, $teamid) {
   $totalPix = 0;
   $sepWidth = 1;

   $projList = TeamCache::getInstance()->getTeam($teamid)->getProjects();

   $scheduledTasks = array();
   foreach($scheduledTaskList as $scheduledTask) {

      $taskPixSize = $scheduledTask->getPixSize($dayPixSize);
      $totalPix += $taskPixSize;

      // set color
      if (NULL != $scheduledTask->deadLine) {
         if (!$scheduledTask->isOnTime) {
            $color = "red";
         } else {
            $color = ($scheduledTask->isMonitored) ? "grey" : "green";
         }
      } else {
         $color = ($scheduledTask->isMonitored) ? "grey" : "blue";
      }

      // hide tasks not in team projects
      $issue = IssueCache::getInstance()->getIssue($scheduledTask->bugId);

      $taskTitle = $scheduledTask->getDescription();
      $formatedTitle = str_replace("'", ' ', $taskTitle);
      $formatedTitle = str_replace('"', ' ', $formatedTitle);

      $drawnTaskPixSize = $taskPixSize - $sepWidth;

      $scheduledTasks[] = array(
         "bugid" => $scheduledTask->bugId,
         "title" => $formatedTitle,
         "width" => $drawnTaskPixSize,
         "color" => $color,
         "strike" => NULL == $projList[$issue->projectId]
      );
   }

   return array(
      "height" => 20,
      "sepWidth" => $sepWidth,
      "scheduledTasks" => $scheduledTasks,
   );
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'Planning');

if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $teamList = $session_user->getTeamList();

   if (count($teamList) > 0) {
      // use the teamid set in the form, if not defined (first page call) use session teamid
      if (isset($_POST['teamid'])) {
         $teamid = Tools::getSecurePOSTIntValue('teamid');
         $_SESSION['teamid'] = $teamid;
      } elseif(isset($_SESSION['teamid'])) {
         $teamid = $_SESSION['teamid'];
      }

      $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

      $pageWidth = Tools::getSecurePOSTIntValue('width',Tools::getSecureGETIntValue('width',0));
      $smartyHelper->assign('width', $pageWidth);

      if (array_key_exists($teamid,$teamList)) {
         $smartyHelper->assign('consistencyErrors', getConsistencyErrors($teamid));

         $today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
         $graphSize = ("undefined" != $pageWidth) ? $pageWidth - 150 : 800;

         $scheduler = new Scheduler();
         $allTasksLists = array();
         $workloads = array();
         $teamMembers = TeamCache::getInstance()->getTeam($teamid)->getUsers();

         $nbDaysToDisplay = 0;
         foreach ($teamMembers as $user) {
            $workload = 0;

            // show only developper's & manager's tasks
            if ((!$user->isTeamDeveloper($teamid)) &&
               (!$user->isTeamManager($teamid))) {

               $logger->debug("user $user->id excluded from scheduled users on team $teamid");
               continue;
            }

            if (NULL != ($user->getDepartureDate()) && ($user->getDepartureDate() < $today)) { continue; }

            $scheduledTaskList = $scheduler->scheduleUser($user, $today, TRUE);

            foreach($scheduledTaskList as $key => $scheduledTask) {
               $workload += $scheduledTask->duration;
            }
            $nbDaysToDisplay = ($nbDaysToDisplay < $workload) ? $workload : $nbDaysToDisplay;

            $allTasksLists[$user->getName()] = $scheduledTaskList;
            $workloads[$user->getName()] = $workload;
         }

         $dayPixSize = (0 != $nbDaysToDisplay) ? ($graphSize / $nbDaysToDisplay) : 0;
         $dayPixSize = round($dayPixSize);
         #echo "DEBUG dayPixSize = $dayPixSize<br/>\n";

         $smartyHelper->assign('planning', getPlanning($nbDaysToDisplay, $dayPixSize, $allTasksLists, $workloads, $teamid));
         $smartyHelper->assign('colors', array(
            "green" => "onTime",
            "red"   => "NOT onTime",
            "blue"  => "no deadLine",
            "grey"  => "monitored"
         ));
         $smartyHelper->assign('dayPixSize', $dayPixSize-1);
      }
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
