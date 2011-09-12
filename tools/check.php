<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
<?php /*
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
*/ ?>

<?php include_once '../path.inc.php'; ?>

<?php
include_once 'i18n.inc.php';
include_once "tools.php";
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");

  exit;
}
?>

<?php
   $_POST['page_name'] = T_("Consistency Check");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<div id="content">
<?php

include_once 'consistency_check.class.php';
include_once 'user.class.php';


// ================ MAIN =================

$userid = $_SESSION['userid'];
$sessionUser = new User($userid);

// get projects i'm involved in (dev, Leader, Manager)
$devTeamList = $sessionUser->getDevTeamList();
$leadedTeamList = $sessionUser->getLeadedTeamList();
$managedTeamList = $sessionUser->getManagedTeamList();
$teamList = $devTeamList + $leadedTeamList + $managedTeamList;
$projectList = $sessionUser->getProjectList($teamList);

$ccheck = new ConsistencyCheck($projectList);

$cerrList = $ccheck->check();

   if (0 == count($cerrList)) {
   	echo T_("No Error.")."</br>\n";
   } else {

	   echo "<div align='left'>\n";
	   echo "<table>\n";
	   echo "<caption>".count($cerrList).T_(" Error(s) in Mantis Tasks")."</caption>\n";
	   echo "<tr>\n";
	   echo "<th>".T_("User")."</th>\n";
	   echo "<th>".T_("Task")."</th>\n";
	   echo "<th title='".T_("last modification date")."'>Date</th>\n";
	   echo "<th>".T_("Status")."</th>\n";
      echo "<th>".T_("Level")."</th>\n";
	   echo "<th>".T_("Error Description")."</th>\n";
	   echo "</tr>\n";
	   foreach ($cerrList as $cerr) {

	   	 $user = new User($cerr->userId);
          $issue = new Issue($cerr->bugId);
	   	 echo "<tr>\n";
	       echo "<td>".$user->getName()."</td>\n";
	       echo "<td>".mantisIssueURL($cerr->bugId, $issue->summary)."</td>\n";
	       echo "<td>".date("Y-m-d", $cerr->timestamp)."</td>\n";
	       echo "<td>".$statusNames[$cerr->status]."</td>\n";
          echo "<td>$cerr->severity</td>\n";
	       echo "<td>$cerr->desc</td>\n";
	       echo "</tr>\n";
	   }
      echo "</table>\n";
      echo "</div>\n";
   }




?>
</div>

<?php include 'footer.inc.php'; ?>
