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


#require('../lib/adodb/adodb.inc.php');
require('../path.inc.php');

class AdodbController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

	private function bug_get_bugnote_count($id) {
		
      $sql = AdodbWrapper::getInstance();
      $query = 'SELECT COUNT(1) FROM {mantis_bugnote_table} WHERE bug_id =' . $sql->db_param();
      $result = $sql->sql_query($query, array($id));
      return $sql->sql_result($result);
   }
   
   protected function display() {
      if (Tools::isConnectedUser()) {
         $bugid = 1;
         $count = $this->bug_get_bugnote_count($bugid);
         $this->smartyHelper->assign('bugid', $bugid);
         $this->smartyHelper->assign('bugnote_count', $count);
      }
   }

}

// ========== MAIN ===========
AdodbController::staticInit();
$controller = new AdodbController('../', 'TEST AdodbController', 'Tests');
$controller->execute();
