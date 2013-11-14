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

class WBSEditorController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {
         // the root WBSElement (Folder) has been created at Command creation.
         //$root_id = WBSElement2::create(NULL, NULL, NULL, NULL, "root_".date('Ymd'));
         $root_id = 56;

         $this->smartyHelper->assign('wbsRootId', $root_id);
      }
   }

}

// ========== MAIN ===========
WBSEditorController::staticInit();
$controller = new WBSEditorController('../', 'Dynatree TEST','');
$controller->execute();

?>
