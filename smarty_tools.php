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
 *
 *
 * @param $teamList
 * @return array
 */
function getTeams($teamList, $selectedTeamId) {
    foreach ($teamList as $tid => $tname) {
        $teams[] = array(
            'id' => $tid,
            'name' => $tname,
            'selected' => ($tid == $selectedTeamId)
        );
    }
    return $teams;
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
