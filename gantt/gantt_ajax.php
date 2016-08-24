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

$schedAjaxLogger = Logger::getLogger("gantt_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');

   switch ($action) {
      case 'getGanttTasks':
         try {
            $schedulerManager = new SchedulerManager($_SESSION['userid'], $_SESSION['teamid']);
            $data = $schedulerManager->execute();
            $taskDates = $schedulerManager->getComputedTaskDates();

            // TODO get tasks dependencies
            // TODO convert $taskDates to $dxhtmlGanttTasks

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

