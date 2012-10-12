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

         $project = new Project(30, NULL);
         $issueSel = $project->getIssueSelection();

         // ----------------------
         // Filter only BUGS
         //$params = array('filterCriteria' => array(IssueCodevTypeFilter::tag_Bug));
         $bugFilter = new IssueCodevTypeFilter('bugFilter');
         $bugFilter->addFilterCriteria(IssueCodevTypeFilter::tag_Bug);
         $outputList = $bugFilter->execute($issueSel, $params);

         if (empty($outputList)) {
            echo "TYPE not found !<br>";
            return;
         }

         $bugSel = $outputList[IssueCodevTypeFilter::tag_Bug];

         // Filter only NoExtRef
         $extIdFilter = new IssueExtIdFilter('extIdFilter');
         $extIdFilter->addFilterCriteria(IssueExtIdFilter::tag_no_extRef);
         $outputList2 = $extIdFilter->execute($bugSel);

         if (empty($outputList2)) {
            echo "noExtRef not found !<br>";
            return array();
         }
         $issueSel = $outputList2[IssueExtIdFilter::tag_no_extRef];


         // ----------------------

         $startTimestamp = 1322175600; // 2011-11-25
         $endTimestamp =  time();

         #echo "StartDate ".date("Y-m-d", $startTimestamp).'<br>';
         #echo "EndDate ".date("Y-m-d", $endTimestamp).'<br>';

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