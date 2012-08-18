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

class MantisReports extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $mTeamList = $session_user->getDevTeamList();
         $lTeamList = $session_user->getLeadedTeamList();
         $oTeamList = $session_user->getObservedTeamList();
         $managedTeamList = $session_user->getManagedTeamList();
         $teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

         $defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
         $teamid = Tools::getSecureGETIntValue('teamid', $defaultTeam);
         $_SESSION['teamid'] = $teamid;

         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

         // if current team is not in allowed list, do not display
         if (array_key_exists($teamid, $teamList)) {
            $team = TeamCache::getInstance()->getTeam($teamid);
            $start_year = date("Y", $team->date);
            $start_month = date("m", $team->date);
            $start_day = date("d", $team->date);

            $statusNames = Config::getInstance()->getValue("statusNames");
            ksort($statusNames);

            $this->smartyHelper->assign('statusNames', $statusNames);

            $periodStatsReport = new PeriodStatsReport($start_year, $start_month, $start_day, $teamid);
            $periodStatsList = $periodStatsReport->computeReport();

            $periods = array();
            foreach ($periodStatsList as $date => $ps) {
               $status = array();
               foreach ($statusNames as $s => $sname) {
                  $status[$s] = $ps->statusCountList[$s];
               }
               $periods[Tools::formatDate("%B %Y", $date)] = $status;
            }
            $this->smartyHelper->assign('periods', $periods);
         }
      }
   }

}

// ========== MAIN ===========
MantisReports::staticInit();
$controller = new MantisReports('Suivi des fiches Mantis');
$controller->execute();

?>
