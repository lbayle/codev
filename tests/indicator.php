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

require ('../path.inc.php');

class indicatorController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         $user = UserCache::getInstance()->getUser($_SESSION['userid']);

         #$cmd = new Command(19);
         #$issueSel = $cmd->getIssueSelection();

         $project = new Project(18, NULL);
         $issueSel = $project->getIssueSelection();



         $startTT = $issueSel->getFirstTimetrack();
         if ((NULL != $startTT) && (0 != $startTT->getDate())) {
            $startTimestamp = $startTT->getDate();
         } else {
            $startTimestamp = $cmd->getStartDate();
            #echo "cmd getStartDate ".date("Y-m-d", $startTimestamp).'<br>';
            if (0 == $startTimestamp) {
               $team = TeamCache::getInstance()->getTeam($cmd->getTeamid());
               $startTimestamp = $team->getDate();
               #echo "team Date ".date("Y-m-d", $startTimestamp).'<br>';
            }
         }

         $endTimestamp =  time();

         #echo "cmd StartDate ".date("Y-m-d", $startTimestamp).'<br>';
         #echo "cmd EndDate ".date("Y-m-d", $endTimestamp).'<br>';

         $params = array(
            'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
            'endTimestamp' => $endTimestamp,
            'interval' => 10
         );

         $statusHistoryIndicator = new StatusHistoryIndicator();
         $statusHistoryIndicator->execute($issueSel, $params);
         $smartyobj = $statusHistoryIndicator->getSmartyObject();
         foreach ($smartyobj as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }

      }
   }

}

// ========== MAIN ===========
indicatorController::staticInit();
$controller = new indicatorController('Test: Status History Indicator', 'MENU_NAME');
$controller->execute();
?>