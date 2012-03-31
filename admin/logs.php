<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 

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

    $nbLinesToDisplay = 40;

    $logs = array();
    
    $lines = file($codevtt_logfile);
    
    if (count($lines) > $nbLinesToDisplay) {
    	$offset = count($lines) - $nbLinesToDisplay;
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
