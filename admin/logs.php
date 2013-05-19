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

class LogsController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         // Admins only
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            if ( (NULL != Constants::$codevtt_logfile) && (file_exists(Constants::$codevtt_logfile))) {
               $nbLinesToDisplay = 1500;

               $lines = file(Constants::$codevtt_logfile);

               if (count($lines) > $nbLinesToDisplay) {
                  $offset = count($lines) - $nbLinesToDisplay;
               } else {
                  $offset = 0;
               }

               $logs = array();
               for ($i = $offset; $i <= ($offset+$nbLinesToDisplay), $i < count($lines) ; $i++) {
                  $logs[$i+1] = htmlspecialchars($lines[$i], ENT_QUOTES, "UTF-8");
                  #echo "DEBUG $line_num - ".$logs[$line_num]."<br>";
               }

               $this->smartyHelper->assign('logs', $logs);
            } else {
               $this->smartyHelper->assign('error',T_('Sorry, logfile not found:').' ['.Constants::$codevtt_logfile.']');
            }
         } else {
            $this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
         }
      } else {
         $this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
      }
   }

}

// ========== MAIN ===========
LogsController::staticInit();
$controller = new LogsController('../', 'CodevTT Logs','Admin');
$controller->execute();

?>
