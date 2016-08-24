<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/
require('../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

$ganttAjaxLogger = Logger::getLogger("gantt_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   switch ($action) {
      case 'getGanttTasks':
         try {
            $schedulerManager = new SchedulerManager($_SESSION['userid'], $_SESSION['teamid']);
            $data = $schedulerManager->execute();
            $taskDates = $schedulerManager->getComputedTaskDates();
            $isExtRef = $schedulerManager->getUserOption(SchedulerManager::OPTION_isDisplayExtRef);

            // TODO get tasks dependencies
            // TODO convert $taskDates to $dxhtmlGanttTasks
            $tasksData = array();
            $bugid_to_idx = array();
            $idx = 1;
            foreach($taskDates as $bugid => $taskDates) {
               // TODO set startDate to first timestrack (if exists)
               $duration = round(($taskDates['endTimestamp'] - $taskDates['startTimestamp']) / 86400, 2); // 24*60*60 (ms -> day);
               $issue = IssueCache::getInstance()->getIssue($bugid);
               
               if ($isExtRef) {
                  $extRef = $issue->getTcId();
                  if (NULL == $extRef) { $extRef = 'm'.$bugid; }
                  $griddText =$extRef.' | '.$issue->getSummary();
                  $barText = $extRef;
               } else {
                  $griddText = $bugid.' | '.$issue->getSummary();
                  $barText = $bugid;
               }
               $tasksData[] = array(
                   'id' => $idx,
                   'text' => $griddText,
                   'start_date' => date('d-m-Y', $taskDates['startTimestamp']), // core
                   'duration' => $duration,
                   'progress' => $issue->getProgress() ,
                   #'open' => true,
                   #'color' => 'lightblue',         // TODO
                   #'textColor' => 'black',         // TODO
                   #'progressColor' => 'blue',      // TODO
                   #'parent' => 1,
                   #'readonly' => true
                   // custom:
                   'barText' => $barText,
                   'tooltipHtml' => 'TODO',        // TODO
                   'assignedTo' => 'toto, titi',   // TODO
               );
               $bugid_to_idx[$bugid] = $idx;
               ++$idx;
            }

            //$ganttAjaxLogger->error($tasksData);

            $tasksLinks = array();
/*
        {id:1, source:1, target:2, type:"1"},
        {id:2, source:1, target:3, type:"1"},
        {id:3, source:3, target:4, type:"1"},
        {id:4, source:4, target:5, type:"0"},
        {id:5, source:5, target:6, type:"0"}
*/
            $dxhtmlGanttTasks = array(
                'data' => $tasksData,
                'links' => $tasksLinks,
            );

            $jsonData = array(
               'statusMsg' => 'SUCCESS',
               'ganttTasks' => $dxhtmlGanttTasks,
            );
         } catch (Exception $e) {
            //$statusMsg = $e->getMessage();
            $jsonData = array(
               'statusMsg' => 'ERROR'
            );
         }
         echo json_encode($jsonData);
         break;
      default:
         Tools::sendNotFoundAccess();
         break;
   }
} else {
   Tools::sendUnauthorizedAccess();
}

