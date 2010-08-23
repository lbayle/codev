<?php

// CALCULATE PERIOD STATS Reports --
// Status & Issue classes

include_once "period_stats.class.php";
//require_once "../Artichow/LinePlot.class.php";
//require_once "../Artichow/Graph.class.php";

class PeriodStatsReport {
  var $start_year;
  var $start_day;
  var $periodStatsList;

  public function PeriodStatsReport($start_year) {
    $this->start_year = $start_year;
    $this->start_day = 1;
    $this->periodStatsList = array();
  }

  // Compute monthly reports for the complete year
  public function computeReport() {
    $now = time();

    for ($start_month=1; $start_month<13; $start_month++) {
      $startTimestamp = mktime(0, 0, 1, $start_month, $this->start_day, $this->start_year);
      $endTimestamp   = mktime(0, 0, 1, ($start_month + 1), $this->start_day, $this->start_year);

      if ($startTimestamp > $now) { break;}

      $periodStats = new PeriodStats($startTimestamp, $endTimestamp);
      $periodStats->computeStats();

      $this->periodStatsList[$startTimestamp] = $periodStats;
    }
  }

  function displayHTMLReport() {
    echo "<table>\n";
    echo "<caption title='Bilan mensuel SAUF SuiviOp.'>Bilan mensuel (nbre de fiches / status &agrave; la fin du mois)</caption>";
    echo "<tr>\n";
    echo "<th>Date</th>\n";
    echo "<th title='Nbre de fiches cr&eacute;&eacute;es SAUF SuiviOp.'>Nb soumissions</th>\n";
    echo "<th>New</th>\n";
    echo "<th>Acknowledge</th>\n";
    echo "<th>Feedback</th>\n";
    echo "<th>Analyze</th>\n";
    echo "<th>Accepted</th>\n";
    echo "<th>Openned</th>\n";
    echo "<th>Resolved</th>\n";
    echo "<th>Closed</th>\n";
    echo "<th title='total d&eacute;rives sur les Resolved/Closed SAUF SuiviOp.' >D&eacute;rive</th>\n";
    echo "</tr>\n";
    foreach ($this->periodStatsList as $date => $ps) {
      echo $ps->displayOneLineHtmlTable();
    }
    echo "</table>\n";
  }

} // end class PeriodStatsReport

?>


