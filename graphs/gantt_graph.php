<?php
require('../include/session.inc.php');
# WARNING: Never ever put an 'echo' in this file, the graph won't be displayed !

/*
   This file is part of CoDevTT.

   CoDevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

require('i18n/i18n.inc.php');

$logger = Logger::getLogger('gantt_graph');

/**
 * @param int $teamid
 * @param int $startTimestamp
 * @param int $endTimestamp
 * @param int[] $projectIds
 * @return GanttGraph
 */
function getGanttGraph($teamid, $startTimestamp, $endTimestamp, array $projectIds) {
   global $logger;

   $graph = new GanttGraph();

   // set graph title
   $team = TeamCache::getInstance()->getTeam($teamid);
   if ( (NULL != $projectIds) && (0 != sizeof($projectIds))) {
      $pnameList = "";
      foreach ($projectIds as $pid) {
         if ("" != $pnameList) { $pnameList .=","; }
         $project = ProjectCache::getInstance()->getProject($pid);
         $pnameList .= $project->name;
      }
      $graph->title->Set(T_('Team').' '.$team->name.'    '.T_('Project(s)').': '.$pnameList);
   } else {
      $graph->title->Set(T_('Team').' '.$team->name.'    ('.T_('All projects').')');
   }

   // Setup scale
   $graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);
   $graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAYWNBR);

   $gantManager = new GanttManager($teamid, $startTimestamp, $endTimestamp);

   $teamActivities = $gantManager->getTeamActivities();

   // mapping to ease constrains building
   $issueActivityMapping = array();

   // set activityIdx
   $activityIdx = 0;

   // Add the specified activities
   foreach($teamActivities as $a) {
      $a->setActivityIdx($activityIdx);

      // FILTER on projects
      if ((NULL != $projectIds) && (0 != sizeof($projectIds))) {
         $issue = IssueCache::getInstance()->getIssue($a->bugid);
         if (!in_array($issue->projectId, $projectIds)) {
            // skip activity indexing
            continue;
         }
      }

      $issueActivityMapping[$a->bugid] = $activityIdx;
      $filterTeamActivities[] = $a;
      ++$activityIdx;

      // Shorten bar depending on gantt startDate
      if (NULL != $startTimestamp && $a->startTimestamp < $startTimestamp) {
         // leave one day to insert prefixBar
         $newStartTimestamp = $startTimestamp + (60*60*24);

         if ($newStartTimestamp > $a->endTimestamp) {
            // there is not enough space for a prefixBar
            $newStartTimestamp = $startTimestamp;
            $logger->debug("bugid=".$a->bugid.": Shorten bar to Gantt start date");
         } else {
            $formattedStartDate = date('Y-m-d', $startTimestamp);
            $prefixBar = new GanttBar($a->activityIdx, "", $formattedStartDate, $formattedStartDate, "", 10);
            $prefixBar->SetBreakStyle(true,'dotted',1);
            $graph->Add($prefixBar);
            $logger->debug("bugid=".$a->bugid.": Shorten bar & add prefixBar");
         }
         $logger->debug("bugid=".$a->bugid.": Shorten bar from ".date('Y-m-d', $a->startTimestamp)." to ".date('Y-m-d', $newStartTimestamp));
         $a->startTimestamp = $newStartTimestamp;
      }

      $bar = $a->getJPGraphBar($issueActivityMapping);
      $graph->Add($bar);
   }
   return $graph;
}

// ========== MAIN ===========
if (isset($_SESSION['userid'])) {
   $teamid = Tools::getSecureGETIntValue('teamid');
   $startTimestamp = Tools::getSecureGETStringValue('startT');
   $endTimestamp = Tools::getSecureGETStringValue('endT');
   $projectIds = Tools::getSecureGETIntValue('projects', 0);
   if(0 != $projectIds) {
      $projectIds = explode(':', $projectIds);
      $logger->debug("team <$teamid> projects = <$projectIds>");
   } else {
      $logger->debug("team <$teamid> display all projects");
      $projectIds = NULL;
   }

   // INFO: the following 1 line are MANDATORY and fix the following error:
   // “The image <name> cannot be displayed because it contains errors”
   ob_end_clean();

   $graph = getGanttGraph($teamid, $startTimestamp, $endTimestamp, $projectIds);

   // display graph
   $graph->Stroke();

   SqlWrapper::getInstance()->logStats();
}

?>
