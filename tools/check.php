<?php 
if (!isset($_SESSION)) { 
	$tokens = explode('/', $_SERVER['PHP_SELF'], 3);
	$sname = str_replace('.', '_', $tokens[1]);
	session_name($sname); 
	session_start(); 
	header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); 
} 
?>
<?php

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

include_once('consistency_check.class.php');
include_once('user.class.php');

/**
 * Get consistency errors
 * @param int User's id
 */
function getConsistencyErrors($userid) {
    $sessionUser = new User($userid);

    global $statusNames;
    
    // get projects i'm involved in (dev, Leader, Manager)
    $devTeamList = $sessionUser->getDevTeamList();
    $leadedTeamList = $sessionUser->getLeadedTeamList();
    $managedTeamList = $sessionUser->getManagedTeamList();
    $teamList = $devTeamList + $leadedTeamList + $managedTeamList;
    $projectList = $sessionUser->getProjectList($teamList);

    $ccheck = new ConsistencyCheck($projectList);
    $cerrList = $ccheck->check();

    if (count($cerrList) > 0) {
        global $count;
        $count = count($cerrList);
        foreach ($cerrList as $cerr) {
            $user = new User($cerr->userId);
            $issue = new Issue($cerr->bugId);
            
            $cerrs[] = array('userName' => $user->getName(), 
                             'mantisIssueURL' => mantisIssueURL($cerr->bugId, $issue->summary),
                             'date' => date("Y-m-d", $cerr->timestamp),
                             'status' => $statusNames[$cerr->status],
                             'severity' => $cerr->severity,
                             'project' => $issue->getProjectName(),
            		         'desc' => $cerr->desc);
        }
    
        return $cerrs;
    }
}

// ================ MAIN =================

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Consistency Check'));

// Consistency errors
if (isset($_SESSION['userid'])) {
    $consistencyErrors = getConsistencyErrors($_SESSION['userid']);
    $smartyHelper->assign('count', $count);
    if(isset($consistencyErrors)) {
        $smartyHelper->assign('consistencyErrors', $consistencyErrors);
    }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
