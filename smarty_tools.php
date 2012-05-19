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
 * Convert a teamList in a Smarty comprehensible array
 * @param int $teamList The teams
 * @param int $selectedTeamId The selected team
 * @return array
 */
function getTeams($teamList, $selectedTeamId) {
    foreach ($teamList as $tid => $tname) {
        $teams[] = array('id' => $tid,
                         'name' => $tname,
                         'selected' => ($tid == $selectedTeamId)
        );
    }
    return $teams;
}

/**
 * Get the list of weeks of a specific year in Smarty comprehensible array
 * @param int $weekid The selected week
 * @param int $year The specific year
 * @return array The result
 */
function getWeeks($weekid, $year) {
   for ($i = 1; $i <= 53; $i++) {
      $wDates = week_dates($i,$year);
      $monday = strftime(T_('W').'%U | %d %b', strtotime("Monday",$wDates[1]));
      $friday = strftime("%d %b", strtotime("Friday",$wDates[1]));
      $weeks[] = array('id' => $i,
                       'value' => utf8_encode(ucwords($monday)." - ".ucwords($friday)),
                       'selected' => $i == $weekid);
   }

   return $weeks;
}

/**
 * Get the list of years in [year-offset;year+offset] in Smarty comprehensible array
 * @param int $year The actual year
 * @param int $offset The offset
 * @return array The years
 */
function getYears($year,$offset = 1) {
   for ($y = ($year-$offset); $y <= ($year+offset); $y++) {
      $years[] = array('id' => $y,
                       'selected' => $y == $year);
   }
   return $years;
}

/**
 * Get detailed mgr
 * @param array $issueSelection
 * @return array
 */
function getIssueSelectionDetailedMgr($issueSelection) {

   //$formatedList  = implode( ',', array_keys($issueSelection->getIssueList()));

   $valuesMgr = $issueSelection->getDriftMgr();

   $driftMgrColor = IssueSelection::getDriftColor($valuesMgr['percent']);
   $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

   $selectionDetailedMgr = array('name' => $issueSelection->name,
      //'progress' => round(100 * $pv->getProgress()),
      'effortEstim' => $issueSelection->mgrEffortEstim,
      'reestimated' => ($issueSelection->remainingMgr + $issueSelection->elapsed),
      'elapsed' => $issueSelection->elapsed,
      'remaining' => $issueSelection->remainingMgr,
      'driftColor' => $formatteddriftMgrColor,
      'drift' => round($valuesMgr['nbDays'],2)
   );
   return $selectionDetailedMgr;
}

?>
