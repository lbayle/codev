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

class GanttGraphView {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function execute() {
      if (Tools::isConnectedUser()) {
         $teamid = Tools::getSecureGETIntValue('teamid');
         $startTimestamp = Tools::getSecureGETStringValue('startT');
         $endTimestamp = Tools::getSecureGETStringValue('endT');
         $projectIds = Tools::getSecureGETIntValue('projects', 0);
         if(0 != $projectIds) {
            $projectIds = explode(':', $projectIds);
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("team <$teamid> projects = <$projectIds>");
            }
         } else {
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("team <$teamid> display all projects");
            }
            $projectIds = array();
         }

         /* INFO: the following 1 line are MANDATORY and fix the following error:
          * “The image <name> cannot be displayed because it contains errors”
          * Can't call ob_end_clean() if zlib.output_compression is ON
          */
         if(!ini_get('zlib.output_compression')) {
            ob_end_clean();
         }

         $graph = $this->getGanttGraph($teamid, $startTimestamp, $endTimestamp, $projectIds);

         // display graph
         $graph->Stroke();

         SqlWrapper::getInstance()->logStats();
      } else {
         Tools::sendForbiddenAccess();
      }
   }

   /**
    * @param int $teamid
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param int[] $projectIds
    * @return GanttGraph
    */
   private function getGanttGraph($teamid, $startTimestamp, $endTimestamp, array $projectIds) {
      $graph = new GanttGraph();

      // set graph title
      $team = TeamCache::getInstance()->getTeam($teamid);
      if (0 != count($projectIds)) {
         $pnameList = "";
         foreach ($projectIds as $pid) {
            if ("" != $pnameList) { $pnameList .=","; }
            $project = ProjectCache::getInstance()->getProject($pid);
            $pnameList .= $project->getName();
         }
         $graph->title->Set(T_('Team').' '.$team->getName().'    '.T_('Project(s)').': '.$pnameList);
      } else {
         $graph->title->Set(T_('Team').' '.$team->getName().'    ('.T_('All projects').')');
      }

      // Setup scale
      $graph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);
      $graph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAYWNBR);

      $gantManager = new GanttManager($teamid, $startTimestamp, $endTimestamp);

      $teamActivities = $gantManager->getTeamActivities();

      // mapping to ease constrains building
      // Note: $issueActivityMapping must be completed before calling $a->getJPGraphBar()
      $issueActivityMapping = array();
      $activityIdx = 0;
      foreach($teamActivities as $a) {
         $a->setActivityIdx($activityIdx);
         $issueActivityMapping[$a->bugid] = $activityIdx;
         ++$activityIdx;
      }

      // Add the specified activities
      foreach($teamActivities as $a) {

         // FILTER on projects
         if ((NULL != $projectIds) && (0 != sizeof($projectIds))) {
            $issue = IssueCache::getInstance()->getIssue($a->bugid);
            if (!in_array($issue->getProjectId(), $projectIds)) {
               // skip activity indexing
               continue;
            }
         }

         $filterTeamActivities[] = $a;

         // Shorten bar depending on gantt startDate
         if (NULL != $startTimestamp && $a->startTimestamp < $startTimestamp) {
            // leave one day to insert prefixBar
            $newStartTimestamp = $startTimestamp + (60*60*24);

            if ($newStartTimestamp > $a->endTimestamp) {
               // there is not enough space for a prefixBar
               $newStartTimestamp = $startTimestamp;
               self::$logger->debug("bugid=".$a->bugid.": Shorten bar to Gantt start date");
            } else {
               $formattedStartDate = date('Y-m-d', $startTimestamp);
               $prefixBar = new GanttBar($a->activityIdx, "", $formattedStartDate, $formattedStartDate, "", 10);
               $prefixBar->SetBreakStyle(true,'dotted',1);
               $graph->Add($prefixBar);
               self::$logger->debug("bugid=".$a->bugid.": Shorten bar & add prefixBar");
            }
            self::$logger->debug("bugid=".$a->bugid.": Shorten bar from ".date('Y-m-d', $a->startTimestamp)." to ".date('Y-m-d', $newStartTimestamp));
            $a->startTimestamp = $newStartTimestamp;
         }

         $bar = $a->getJPGraphBar($issueActivityMapping);
         $graph->Add($bar);
      }
      return $graph;
   }

}

// ========== MAIN ===========
GanttGraphView::staticInit();
$view = new GanttGraphView();
$view->execute();

?>
