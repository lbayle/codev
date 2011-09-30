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
if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}
?>

<?php
   $_POST['page_name'] = T_("Gantt Chart");
   include 'header.inc.php';
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>


<div id="content">

<?php
require_once ('jpgraph.php');
require_once ('jpgraph_gantt.php');

include_once "issue.class.php";
include_once "user.class.php";
include_once "time_tracking.class.php";
include_once "gantt_manager.class.php";


$originPage = "gantt.php";

$userid = $_SESSION['userid'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

$defaultTeam = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
$teamid = isset($_POST['teamid']) ? $_POST['teamid'] : $defaultTeam;
$_SESSION['teamid'] = $teamid;

$session_user = UserCache::getInstance()->getUser($userid);
$mTeamList = $session_user->getTeamList();
$lTeamList = $session_user->getLeadedTeamList();
$oTeamList = $session_user->getObservedTeamList();
$managedTeamList = $session_user->getManagedTeamList();
$teamList = $mTeamList + $lTeamList + $oTeamList + $managedTeamList;

if (0 == count($teamList)) {
   echo "<div id='content'' class='center'>";
   echo T_("Sorry, you do NOT have access to this page.");
   echo "</div>";

} else {
   $startT = date2timestamp("2011-08-01");
   $endT   = date2timestamp("2011-12-30");

   //echo "AA teamid = $teamid<br/>";
   //$gantManager = new GanttManager($teamid, $startT, $endT);
   //$graph = $gantManager->getGanttGraph();

   //echo "<img='data:image/png;base64,".$graph->Stroke()."' />";

   // draw graph
   $graphURL = getServerRootURL()."/graphs/gantt_graph.php?teamid=$teamid&startT=$startT&endT=$endT";
   $graphURL = SmartUrlEncode($graphURL);
   echo "<img src='$graphURL'/>";



   echo "<br/>\n";
   echo "<br/>\n";

   $graphURL = getServerRootURL()."/graphs/jpgantt.php";
   $graphURL = SmartUrlEncode($graphURL);
   echo "    <img src='$graphURL'/>";

   echo "<br/>\n";


}



?>

</div>

<?php include 'footer.inc.php'; ?>

