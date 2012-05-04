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

include_once('user.class.php');

// ================ MAIN =================
global $codevtt_logfile;

if (isset($_SESSION['userid'])) {

    // Admins only
    $session_user = new User($_SESSION['userid']);

    require('display.inc.php');

    if (!$session_user->isTeamMember($admin_teamid)) {
        echo T_("Sorry, you need to be in the admin-team to access this page.");
        exit;
    }

    $smartyHelper = new SmartyHelper();
    $smartyHelper->assign('pageName', T_('CodevTT Logs'));
    $smartyHelper->assign('menu2', "menu/admin_menu.html");

    $nbLinesToDisplay = 40;

    $logs = array();

    if ( (NULL == $codevtt_logfile) || (!file_exists($codevtt_logfile))) {
       echo T_("Sorry, logfile not found:")." [".$codevtt_logfile."]";
       exit;
    }

    $lines = file($codevtt_logfile);

    if (count($lines) > $nbLinesToDisplay) {
    	$offset = count($lines) - $nbLinesToDisplay;
    } else {
       $offset = 0;
    }

	#foreach ($lines as $line_num => $line) {
	for ($i = $offset; $i <= ($offset+$nbLinesToDisplay); $i++) {

		$logs["$i"] = htmlspecialchars($lines[$i], ENT_QUOTES, "UTF-8");

		#echo "DEBUG $line_num - ".$logs[$line_num]."<br>";
	}

	$smartyHelper->assign('logs', $logs);
}


$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
