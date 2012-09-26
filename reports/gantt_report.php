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

class GanttReportController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $teamList = $session_user->getTeamList();
         if (count($teamList) > 0) {
            if(isset($_POST['teamid']) && array_key_exists($_POST['teamid'],$teamList)) {
               $teamid = Tools::getSecurePOSTIntValue('teamid');
               $_SESSION['teamid'] = $teamid;
            }
            else if(isset($_SESSION['teamid']) && array_key_exists($_SESSION['teamid'],$teamList)) {
               $teamid = $_SESSION['teamid'];
            }
            else {
               $teamsid = array_keys($teamList);
               $teamid = $teamsid[0];
            }
            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));

            $projects[0] = T_('All projects');
            $projects += TeamCache::getInstance()->getTeam($teamid)->getProjects(false);

            $projectid = 0;
            if(isset($_POST['projectid']) && array_key_exists($_POST['projectid'],$projects)) {
               $projectid = Tools::getSecurePOSTIntValue('projectid');
               $_SESSION['projectid'] = $_POST['projectid'];
            }
            else if(isset($_SESSION['projectid']) && array_key_exists($_SESSION['projectid'],$projects)) {
               $projectid = $_SESSION['projectid'];
            }
            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($projects,$projectid));

            // The first day of the current week
            $weekDates = Tools::week_dates(date('W'),date('Y'));
            $startdate = Tools::getSecurePOSTStringValue('startdate', Tools::formatDate("%Y-%m-%d",$weekDates[1]));
            $this->smartyHelper->assign('startDate', $startdate);

            // The current date plus one year
            $enddate = Tools::getSecurePOSTStringValue('enddate', Tools::formatDate("%Y-%m-%d", strtotime('+1 year')));
            $this->smartyHelper->assign('endDate', $enddate);

            if (isset($_POST['teamid']) && 0 != $teamid) {
               $startT = Tools::date2timestamp($startdate);
               $endT = Tools::date2timestamp($enddate);
               #$endT += 24 * 60 * 60 -1; // + 1 day -1 sec.

               // draw graph
               $this->smartyHelper->assign('urlGraph', 'teamid='.$teamid.'&projects='.$projectid.'&startT='.$startT.'&endT='.$endT);
            }
         }
      }
   }

}

// ========== MAIN ===========
GanttReportController::staticInit();
$controller = new GanttReportController('Gantt Chart','Gantt');
$controller->execute();

?>
