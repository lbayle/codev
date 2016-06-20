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

if(Tools::isConnectedUser() && isset($_POST['action'])) {
      //$smartyHelper = new SmartyHelper();
   switch ($_POST['action']) {
      case 'getTeam':
         getTeam();
          break;
      case 'getOldTimetrack':
          getOldTimetrack();
          break;
      default:
          Tools::sendNotFoundAccess();
          break;
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

function getTeam(){
   $data = array();
   $team_id = $_SESSION['teamid'];
   $mList = TeamCache::getInstance()->getTeam($team_id)->getActiveMembers();
   foreach($mList as $key=>$m){
      $pushdata = array("key"=>"$key", "label"=>"$m");
      array_push($data, $pushdata);
   }
   echo json_encode($data);
}

function getOldTimetrack() {
   $timeTracks = array();
   $allTimetracks = array();
   $team_id = $_SESSION['teamid'];
   $mList = TeamCache::getInstance()->getTeam($team_id)->getActiveMembers();
   $d = new DateTime('2010-01-01 00:00:00');
   $t = $d->getTimestamp();
   foreach($mList as $key=>$m){
      $user = UserCache::getInstance()->getUser($key);
      $timeTracks = $user->getTimeTracks($t, time());
      foreach($timeTracks as $timetrack_id=>$timetrack){
         $issue_id = $timetrack->getIssueId();
         $issue = IssueCache::getInstance()->getIssue($issue_id);
         $issue_name = $issue->getSummary();
         $date = $timetrack->getDate();
         $dateParse = date('Y-m-d H:i:s', $date);
         $endDate = $date + $timetrack->getDuration()*24*60*60;
         $endDateParse = date('Y-m-d H:i:s', $endDate);
         $pushdata = array("text"=>"$issue_name","start_date"=>"$dateParse" ,"end_date"=>"$endDateParse" ,"user_id"=>$key);
         //$pushdata = array("text"=>"$timetrack_id", "user_id"=>$key);
         array_push($allTimetracks, $pushdata);
      }
   }
   echo json_encode($allTimetracks);
}

?>
