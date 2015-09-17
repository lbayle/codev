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

class TimetrackInfoController extends Controller {

   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         // Admins only
         if ($this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {

            $this->smartyHelper->assign('isGranted', TRUE);

            // default dates
            $weekDates = Tools::week_dates(date('W'),date('Y'));
            $startdate = Tools::getSecurePOSTStringValue("startdate",Tools::formatDate("%Y-%m-%d",$weekDates[1]));
            $this->smartyHelper->assign('startDate', $startdate);

            $enddate = Tools::getSecurePOSTStringValue("enddate",Tools::formatDate("%Y-%m-%d",$weekDates[5]));
            $this->smartyHelper->assign('endDate', $enddate);

            if ('displayTimetracksInfo' == $_POST['action']) {

               // get dateRangeSelector data
               $startTimestamp = Tools::date2timestamp($startdate);
               $endTimestamp = Tools::date2timestamp($enddate);
               $endTimestamp = mktime(23, 59, 59, date('m', $endTimestamp), date('d',$endTimestamp), date('Y', $endTimestamp));
               
               $members = TeamCache::getInstance()->getTeam($this->teamid)->getMembers();
               $memberIdList = array_keys($members);
               $formatedMembers = implode( ', ', $memberIdList);

               $query = "SELECT * FROM `codev_timetracking_table` " .
                        "WHERE date >= $startTimestamp AND date <= $endTimestamp " .
                        "AND userid IN ($formatedMembers)" .
                        "ORDER BY date;";

               #self::$logger->error($query);

               $result = SqlWrapper::getInstance()->sql_query($query);
               if (!$result) {
                  echo "<span style='color:red'>ERROR: Query FAILED</span>";
                  exit;
               }

               $jobs = new Jobs();
               $timetracks = array();
               while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
                  $tt = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);

                  $user = UserCache::getInstance()->getUser($tt->getUserId());
                  $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());

                  if(!is_null($tt->getCommitterId())) {
                     $committer = UserCache::getInstance()->getUser($tt->getCommitterId());
                     $committer_name = $committer->getName();
                     $commit_date = date('Y-m-d H:i:s', $tt->getCommitDate());
                  } else {
                     $committer_name = ''; // this info does not exist before v1.0.4
                     $commit_date = '';
                  }

                  $timetracks[$row->id] = array(
                     #'id' => $row->id,
                     'user' => $user->getName(),
                     'date' => date('Y-m-d', $tt->getDate()),
                     'job' => $jobs->getJobName($tt->getJobId()),
                     'duration' => $tt->getDuration(),
                     'committer' => $committer_name,
                     'commit_date' => $commit_date,
                     'task_id' => $issue->getId(),
                     'task_extRef' => $issue->getTcId(),
                     'task_summary' => $issue->getSummary(),
                  );

               }
               $this->smartyHelper->assign('timetracks', $timetracks);
  



            }



         } else {
            $this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
         }
      } else {
         $this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
      }
   }

}

// ========== MAIN ===========
TimetrackInfoController::staticInit();
$controller = new TimetrackInfoController('../', 'Timetracks info','Admin');
$controller->execute();


