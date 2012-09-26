<?php
include_once('include/session.inc.php');

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

require('path.inc.php');

try {
   if (Tools::isConnectedUser() && isset($_SESSION['teamid'])) {
      $user =  UserCache::getInstance()->getUser($_SESSION['userid']);
      $user->setDefaultTeam($_SESSION['teamid']);
      $user->setDefaultLanguage($_SESSION['locale']);
      $user->setDefaultProject($_SESSION['projectid']);
   }
} catch (Exception $e) {
   #$logger->debug("could not set defaultTeam for user ".$_SESSION['userid']);
}

unset($_SESSION['userid']);
unset($_SESSION['username']);
unset($_SESSION['realname']);

session_destroy();

// load homepage
header('Location: '.$_SERVER['HTTP_REFERER']);
exit;

?>
