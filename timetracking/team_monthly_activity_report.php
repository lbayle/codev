<?php
include_once('../include/session.inc.php');

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

require('super_header.inc.php');

include_once "user_cache.class.php";
include_once "issue_cache.class.php";
include_once "issue.class.php";
include_once "team.class.php";
include_once "time_tracking.class.php";

$logger = Logger::getLogger("team_monthly");













// =========== MAIN ==========

require('display.inc.php');

$threshold = 0.5; // for Deviation filters

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Team Monthly Activity'));
$smartyHelper->assign('menu2', "menu/team_activity_menu.html");

if (isset($_SESSION['userid'])) {

   // use the teamid set in the form, if not defined (first page call) use session teamid
   if (isset($_POST['teamid'])) {
      $teamid = $_POST['teamid'];
   } else {
      $teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
   }
   $_SESSION['teamid'] = $teamid;

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);




}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
