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

class GanttController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;
   
   
   // internal
   protected $execData;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(Tools::isConnectedUser()) {
         // only teamMembers & observers can access this page
         if ((0 == $this->teamid) || ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {
            $this->smartyHelper->assign('windowStartDate', date('Y-m-d'));
            
            // warn: endDate may hide some tasks if too short
            $this->smartyHelper->assign('windowEndDate', date('Y-m-d', strtotime("+1 year", mktime(0, 0, 0))));


         }
      }
   }
   
   
}

// ========== MAIN ===========
GanttController::staticInit();
$controller = new GanttController('../', 'Gantt','Planning');
$controller->execute();