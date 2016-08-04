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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if(Tools::isConnectedUser() && (isset($_GET['action']) || isset($_POST['action']))) {

	$logger = Logger::getLogger("EditTimeTrackingAjax");

   $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   $session_user = $_SESSION['userid'];

   $action = Tools::getSecurePOSTStringValue('action');
   $timetracksId = Tools::getSecurePOSTStringValue('timetrackId');
   
   if(isset($action)) {
      $smartyHelper = new SmartyHelper();
      
      switch ($action) {
         case "getEditableValue":
            echo getEditableValue($teamid, $timetracksId);
            break;
         case "updateTimetrack":
            updateTimetrack($teamid);
            break;
      }
   }
   else {
      Tools::sendUnauthorizedAccess();
   }
}

function getEditableValue($teamid, $timetracksId) {
   $team = TeamCache::getInstance()->getTeam($teamid);
         
   $note = TimeTrack::getNote($timetracksId);
         
   $durationsList = TimeTrackingTools::getDurationList($teamid);
                 
   // return data
   $data = array(
      'note' => $note,
      'durationsList' => $durationsList,
   );
   $jsonData = json_encode($data);
   // return data
   return $jsonData;
}

function updateTimetrack($teamid) {
   $team = TeamCache::getInstance()->getTeam($teamid);
   
   $logger = Logger::getLogger("EditTimeTrackingAjax");
   
   $timetracksId = Tools::getSecurePOSTStringValue('timetrackId');
   $date = Tools::getSecurePOSTStringValue('date');
   $duration = Tools::getSecurePOSTStringValue('duration');
   $timetrack = TimeTrackCache::getInstance()->getTimeTrack($timetracksId);
   if (1 == $team->getGeneralPreference('useTrackNote')) {
      $note = Tools::getSecurePOSTStringValue('note');
      $updateDone = $timetrack->update($date, $duration, $note );
   }
   else{
      $updateDone = $timetrack->update($date, $duration);
   }
   if(!$updateDone){
     $logger->error("error");
   }
}
