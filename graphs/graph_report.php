<?php if (!isset($_SESSION)) { session_start(); } ?>
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
   $_POST[page_name] = T_("Statistics"); 
   include 'header.inc.php'; 
?>

<?php include 'login.inc.php'; ?>
<?php include 'menu.inc.php'; ?>

<div id="content">

<?php 
   
include_once "period_stats_report.class.php";
include_once "issue.class.php";


function displaySubmittedResolved($periodStatsReport, $width, $height) {
	
   #$submitted = $periodStatsReport->getStatus("submitted");
   #$resolved  = $periodStatsReport->getStatus("delta_resolved");

   $graph_title="title=".T_("Submitted / Resolved");
   $graph_width="width=$width";
   $graph_height="height=$height";


   #$val1 = array_values($submitted);
   $val1 = array(4, 2, 5, 3, 3, 6, 7, 6, 5, 4, 3, 2);
   $strVal1 = "leg1=Submitted&x1=".implode(':', $val1);

   #$val2 = array_values($resolved);
   $val2 = array(3, 8, 15, 6, 2, 1, 3, 2, 5, 7, 6, 6);
   $strVal2 = "leg2=Resolved&x2=".implode(':', $val2);

   $bottomLabel = array("jan", "feb", "mar", "apr");
   #$bottomLabel = array();
   #foreach ($submitted as $date => $val) {
   #   $bottomLabel[] = date("M y", $date);
   #}
   $strBottomLabel = "bottomLabel=".implode(':', $bottomLabel);

   #$leftLabel = array(0, 10, 20, 30, 40);
   #$strLeftLabel = "leftLabel=".implode(':', $leftLabel);

   // ---------
   echo "<div>\n";
   echo "<h2>".T_("Submitted / Resolved")."</h2>\n";
   
   echo "<div class=\"float\">\n";
   echo "    <img src='".getServerRootURL()."/graphs/two_lines.php?displayPointLabels&$graph_title&$graph_width&$graph_height&$strBottomLabel&$strVal1&$strVal2'/>";
   echo "</div>\n";
/*   
   echo "<div class=\"float\">\n";
   echo "<table>\n";
   echo "<caption title='".T_("Submitted / Resolved")."'</caption>";
   echo "<tr>\n";
   echo "<th>Date</th>\n";
   echo "<th title='".T_("Nbre de fiches cr&eacute;&eacute;es SAUF SuiviOp, FDL")."'>".T_("Nb submissions")."</th>\n";
   echo "<th title='".T_("Nbre de fiches r&eacute;solues SAUF SuiviOp et non reouvertes'").">".T_("Nb Resolved")."</th>\n";
   echo "</tr>\n";
   foreach ($submitted as $date => $val) {
      echo "<tr>\n";
   	echo "<td class=\"right\">".date("F Y", $date)."</td>\n";
      echo "<td class=\"right\">".$val."</td>\n";
      echo "<td class=\"right\">".$resolved[$date]."</td>\n";
      echo "</tr>\n";
   }
   echo "</table>\n";
   echo "</div>\n";
*/   
   echo "</div>\n";
   
   echo "<div class=\"spacer\"> </div>\n";
   
   
   
   
   
   
   
   
   
}




# ====================================

$start_year = date('Y') -1; 

$defaultTeam = isset($_SESSION[teamid]) ? $_SESSION[teamid] : 0;
$teamid = isset($_POST[teamid]) ? $_POST[teamid] : $defaultTeam;
$_SESSION[teamid] = $teamid;


// ---- Submitted / Resolved
$periodStatsReport = new PeriodStatsReport($start_year, $teamid);
#$periodStatsReport->computeReport();
displaySubmittedResolved($periodStatsReport, 1000, 300);

// --------- Drifts

?>

</div>

<?php include 'footer.inc.php'; ?>

