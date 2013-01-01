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

         if (0 != $this->teamid) {

            $projects[0] = T_('All projects');
            $projects += TeamCache::getInstance()->getTeam($this->teamid)->getProjects(false);

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
            $enddate = Tools::getSecurePOSTStringValue('enddate', Tools::formatDate("%Y-%m-%d", strtotime('+6 month')));
            $this->smartyHelper->assign('endDate', $enddate);

            if ('computeGantt' == $_POST['action']) {
               $startT = Tools::date2timestamp($startdate);
               $endT = Tools::date2timestamp($enddate);
               #$endT += 24 * 60 * 60 -1; // + 1 day -1 sec.

               // draw graph
               $this->smartyHelper->assign('urlGraph', 'teamid='.$this->teamid.'&projects='.$projectid.'&startT='.$startT.'&endT='.$endT);
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
