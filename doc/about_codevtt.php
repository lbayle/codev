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

class AboutController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }



   protected function display() {

      // get Latest info from http://codevtt.org
      if (1 == Constants::$isCheckLatestVersion) {
         $latestVersionInfo = Tools::getLatestVersionInfo();
         if (FALSE !== $latestVersionInfo) {
            $this->smartyHelper->assign('latestVersionInfo', $latestVersionInfo);
         }
      }      
   }

}

// ========== MAIN ===========
AboutController::staticInit();
$controller = new AboutController('../', 'About CodevTT','Doc');
$controller->execute();


