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
<?php

// CALCULATE PERIOD STATS Reports --
// Status & Issue classes

include_once "period_stats.class.php";

class PeriodStatsReport {
  var $start_year;
  var $start_month;
  var $start_day;
  var $periodStatsList;

  var $teamid;

  // --------------------------------------------
  public function PeriodStatsReport($start_year, $start_month, $start_say, $teamid) {
    $this->start_year = $start_year;
    $this->start_month = $start_month;
    $this->start_day = $start_say;
    $this->periodStatsList = array();

    $this->teamid = $teamid;
  }

  // --------------------------------------------
  // Compute monthly reports for the complete year
  public function computeReport() {

    $periodStatsExcludedProjectList = Config::getInstance()->getValue(Config::id_periodStatsExcludedProjectList);

    $now = time();
    $startM = $this->start_month;
    $startD = $this->start_day;

    for ($y = $this->start_year; $y <= date('Y'); $y++) {

	    for ($month=$startM; $month<13; $month++) {
	      $startTimestamp = mktime(0, 0, 1, $month, $startD, $y);
	      $endTimestamp   = mktime(0, 0, 1, ($month + 1), $startD, $y);

	      if ($startTimestamp > $now) { break; }

	      $periodStats = new PeriodStats($startTimestamp, $endTimestamp);


	      $projectList = array();
	      $query = "SELECT project_id FROM `codev_team_project_table` ".
	               "WHERE team_id = $this->teamid ";

	      // only projects for specified team, except excluded projects
	      if ((NULL != $periodStatsExcludedProjectList) &&
	          (0 != count($periodStatsExcludedProjectList))) {
         	       $formatedExcludedProjects = implode( ', ', $periodStatsExcludedProjectList);
	               $query .= "AND project_id NOT IN ($formatedExcludedProjects)";
	      }
	      $result = mysql_query($query) or die("Query failed: $query");
	      while($row = mysql_fetch_object($result)) {
            $projectList[] = $row->project_id;
	      }

	      $periodStats->projectList = $projectList;
	      $periodStats->computeStats();
	      $this->periodStatsList[$startTimestamp] = $periodStats;
	      $startD = 1;
	    }
	    $startM = 1;
    }
  }


  // --------------------------------------------
  // Compute monthly reports for the complete year
  public function computeSubmittedResolved() {

    $periodStatsExcludedProjectList = Config::getInstance()->getValue(Config::id_periodStatsExcludedProjectList);

    $now = time();
    $startM = $this->start_month;
    $startD = $this->start_day;

    for ($y = $this->start_year; $y <= date('Y'); $y++) {

       for ($month=$startM; $month<13; $month++) {
         $startTimestamp = mktime(0, 0, 0, $month, $startD, $y);
         $nbDaysInMonth = date("t", mktime(0, 0, 0, $month, 1, $y));
         $endTimestamp   = mktime(23, 59, 59, $month, $nbDaysInMonth, $y);
     
          
         if ($startTimestamp > $now) { break; }
             if (isset($_GET['debug'])) {
                 echo "DEBUG computeSubmittedResolved: startTimestamp=".date("Y-m-d H:i:s", $startTimestamp)." endTimestamp=".date("Y-m-d H:i:s", $endTimestamp)."<br/>";
       	
             }
         
         $periodStats = new PeriodStats($startTimestamp, $endTimestamp);

         $projectList = array();
         $query = "SELECT project_id FROM `codev_team_project_table` ".
                  "WHERE team_id = $this->teamid ";

	      // only projects for specified team, except excluded projects
	      if ((NULL != $periodStatsExcludedProjectList) &&
	          (0 != count($periodStatsExcludedProjectList))) {
         	       $formatedExcludedProjects = implode( ', ', $periodStatsExcludedProjectList);
	               $query .= "AND project_id NOT IN ($formatedExcludedProjects)";
	      }

         $result = mysql_query($query) or die("Query failed: $query");
         while($row = mysql_fetch_object($result)) {
            $projectList[] = $row->project_id;
         }

         $periodStats->projectList = $projectList;
         $periodStats->computeSubmittedResolved();
         $this->periodStatsList[$startTimestamp] = $periodStats;
         $startD = 1;
       }
       $startM = 1;
    }
  }


  // --------------------------------------------
  public function getStatus($status) {
      $sub = array();

      foreach ($this->periodStatsList as $date => $ps) {
      	$sub[$date] = $ps->statusCountList[$status];

      }
  	   return $sub;
  }

  // --------------------------------------------
  public function getSubmitted() {
      $sub = array();

      foreach ($this->periodStatsList as $date => $ps) {
         $sub[$date] = $ps->submittedList;

      }
      return $sub;
  }

  // --------------------------------------------
  public function getDeltaResolved() {
      $sub = array();

      foreach ($this->periodStatsList as $date => $ps) {
         $sub[$date] = $ps->deltaResolvedList;

      }
      return $sub;
  }


  // --------------------------------------------
  function displayHTMLReport() {

    $statusNames = Config::getInstance()->getValue("statusNames");
    ksort($statusNames);

    echo "<table>\n";
    echo "<caption title='Bilan mensuel SAUF SuiviOp.'>Bilan mensuel (nbre de fiches / status &agrave; la fin du mois)</caption>";
    echo "<tr>\n";
    echo "<th>Date</th>\n";
    foreach ($statusNames as $s => $sname) {
      echo "<th>$sname</th>\n";
    }
    echo "</tr>\n";

    foreach ($this->periodStatsList as $date => $ps) {

      // Disp
      $tableLine = "<tr>\n";
      $tableLine .= "<td class=\"right\">".date("F Y", $date)."</td>\n";

      foreach ($statusNames as $s => $sname) {
         $tableLine .= "<td class=\"right\">".$ps->statusCountList[$s]."</td>\n";
      }
      $tableLine .= "</tr>\n";
      echo "$tableLine";

    }
    echo "</table>\n";
  }

} // end class PeriodStatsReport

?>


