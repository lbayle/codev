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

/**
 * CALCULATE PERIOD STATS Reports
 * Status & Issue classes
 */
class PeriodStatsReport {

   private $start_year;
   private $start_month;
   private $start_day;
   private $periodStatsList = array();

   private $teamid;

   public function __construct($start_year, $start_month, $start_say, $teamid) {
      $this->start_year = $start_year;
      $this->start_month = $start_month;
      $this->start_day = $start_say;

      $this->teamid = $teamid;
   }

   /**
    * Compute monthly reports for the complete year
    * @return PeriodStats[]
    */
   public function computeReport() {
      $now = time();
      $startM = $this->start_month;
      $startD = $this->start_day;
      $sql = AdodbWrapper::getInstance();

      for ($y = $this->start_year; $y <= date('Y'); $y++) {

         for ($month=$startM; $month<13; $month++) {
            $startTimestamp = mktime(0, 0, 1, $month, $startD, $y);
            $endTimestamp   = mktime(0, 0, 1, ($month + 1), $startD, $y);

            if ($startTimestamp > $now) { break; }

            $periodStats = new PeriodStats($startTimestamp, $endTimestamp);

            $projectList = array();

            // only projects for specified team, except excluded projects
            $query = "SELECT project_id FROM codev_team_project_table ".
               "WHERE team_id = ".$sql->db_param().
               "AND codev_team_project_table.type <> ".$sql->db_param();

            $result = $sql->sql_query($query, array($this->teamid, Project::type_noStatsProject));
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            while($row = $sql->fetchObject($result)) {
               $projectList[] = $row->project_id;
            }

            $periodStats->projectList = $projectList;
            $periodStats->computeStats();
            $this->periodStatsList[$startTimestamp] = $periodStats;
            $startD = 1;
         }
         $startM = 1;
      }

      return $this->periodStatsList;
   }

   public function getStatus($status) {
      $sub = array();

      foreach ($this->periodStatsList as $date => $ps) {
         $sub[$date] = $ps->statusCountList[$status];

      }
      return $sub;
   }

}

