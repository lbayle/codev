
<?php 

// MANTIS CoDev Reports

// -- CALCULATE PERIOD STATS Reports --


// LoB 10 Jun 2010

include_once "periodStats.class.php";
require_once "../Artichow/LinePlot.class.php";
require_once "../Artichow/Graph.class.php";

// Connect DB
$link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass) or die("Impossible de se connecter");
mysql_select_db($db_mantis_database) or die("Could not select database");

$start_year = date('Y');
#$periodStatsReport = new PeriodStatsReport($start_year);

echo "TAMERE<br>";
#$graph = $periodStatsReport->displayGraphReport();
#$graph->draw();

?>


