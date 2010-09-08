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
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_resolved;
    global $status_delivered;
    global $status_closed;
  	
    echo "<table>\n";
    echo "<caption title='Bilan mensuel SAUF SuiviOp.'>Bilan mensuel (nbre de fiches / status &agrave; la fin du mois)</caption>";
    echo "<tr>\n";
    echo "<th>Date</th>\n";
    echo "<th title='Nbre de fiches cr&eacute;&eacute;es SAUF SuiviOp.'>Nb soumissions</th>\n";
    echo "<th>New</th>\n";
    echo "<th>Acknowledge</th>\n";
    echo "<th>Feedback</th>\n";
    echo "<th>Analyzed</th>\n";
    echo "<th>Accepted</th>\n";
    echo "<th>Openned</th>\n";
    echo "<th>Resolved</th>\n";
    echo "<th>Delivered</th>\n";
    echo "<th>Closed</th>\n";
    echo "</tr>\n";
    foreach ($this->periodStatsList as $date => $ps) {
      
	    $tableLine = "<tr>\n";
	    $tableLine .= "<td>".date("F Y", $ps->startTimestamp)."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList["submitted"]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_new]."</td>\n"; // TODO new
	    $tableLine .= "<td>".$ps->statusCountList[$status_ack]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_feedback]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_analyzed]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_accepted]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_openned]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_resolved]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_delivered]."</td>\n";
	    $tableLine .= "<td>".$ps->statusCountList[$status_closed]."</td>\n";
	    $tableLine .= "</tr>\n";
	    echo "$tableLine";
    }
    echo "</table>\n";
  }

} // end class PeriodStatsReport

?>


