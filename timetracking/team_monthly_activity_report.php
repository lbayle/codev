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

class TeamMonthlyActivityReportController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (isset($_SESSION['userid'])) {
         $threshold = 0.5; // for Deviation filters

         // use the teamid set in the form, if not defined (first page call) use session teamid
         if (isset($_POST['teamid'])) {
            $teamid = Tools::getSecurePOSTIntValue('teamid');
            $_SESSION['teamid'] = $teamid;
         } else {
            $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
         }
      }
   }

}

// ========== MAIN ===========
TeamMonthlyActivityReportController::staticInit();
$controller = new TeamMonthlyActivityReportController('Team Monthly Activity');
$controller->execute();

?>
