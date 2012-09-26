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

function execCmd($uxCommand) {
   #$status = system($command, $retCode);
   $status = exec($uxCommand, $output, $retCode);
   //if (0 != $retCode) {
   //   echo "FAILED (err $retCode) could not exec mysql commands from file: $sqlFile</br>";
   //}
   return $status;
}

// ================ MAIN =================
$logger = Logger::getLogger("chmod");

if (Tools::isConnectedUser()) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
      // /tmp/codevtt/logs
      $pos = strrpos (Constants::$codevtt_logfile, '/');
      $tmp = substr(Constants::$codevtt_logfile, 0, $pos);

      $uxCommand = "chmod -R a+r $tmp";
      execCmd($uxCommand);
      $logger->info($uxCommand);
      echo "$uxCommand<br>";

      // ---- /tmp/codevtt
      $pos = strrpos ($tmp, '/');
      $tmp = substr(Constants::$codevtt_logfile, 0, $pos);

      $uxCommand = "chmod a+r $tmp";
      execCmd($uxCommand);
      $logger->info($uxCommand);
      echo "$uxCommand<br>";
   }
}

?>