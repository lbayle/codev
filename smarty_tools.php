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

include_once('tools.php');

include_once('classes/issue_cache.class.php');
include_once('classes/issue_selection.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

class SmartyTools {

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

   /**
    * @param int $value
    * @param bool $allowNull
    * @return int $value
    * @throws Exception
    * @deprecated Use Tools::getSecurePOSTIntValue($key, $defaultValue)
    */
   public static function checkNumericValue($value, $allowNull = FALSE) {
      if ((NULL == $value) && (TRUE == $allowNull)) { return NULL; }

      $formattedValue = SqlWrapper::sql_real_escape_string($value);
      if (!is_numeric($formattedValue)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($value)");
         self::$logger->fatal("EXCEPTION: ".$e->getMessage());
         self::$logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }
      return $formattedValue;
   }

   /**
    * Convert an array in smarty array
    * @param string[] $list
    * @param int $selected
    * @return mixed[]
    */
   public static function getSmartyArray(array $list, $selected) {
      if ($list != NULL) {
         $smartyList = array();
         foreach ($list as $id => $name) {
            $smartyList[$id] = array(
               'id' => $id,
               'name' => $name,
               'selected' => $id == $selected
            );
         }
         return $smartyList;
      } else {
         return NULL;
      }
   }

   /**
    * @param int $projectid
    * @param int $defaultBugid
    * @param array $projList
    * @return mixed[]
    */
   public static function getBugs($projectid = 0, $defaultBugid = 0, array $projList = NULL) {
      // Task list
      if (0 != $projectid) {
         $project1 = ProjectCache::getInstance()->getProject($projectid);
         $issueList = $project1->getIssues();
      } else {
         // no project specified: show all tasks
         $issueList = Project::getProjectIssues(array_keys($projList));
      }

      $bugs = NULL;
      foreach ($issueList as $issue) {
         $externalId = "";
         if(!empty($issue->tcId)) {
            $externalId = ' / '.$issue->tcId;
         }
         $summary = "";
         if(!empty($issue->summary)) {
            $summary = ' : '.$issue->summary;
         }
         $bugs[$issue->bugId] = array('id' => $issue->bugId,
            'name' => $issue->bugId.$externalId.$summary,
            'selected' => $issue->bugId == $defaultBugid,
            'projectid' => $issue->projectId
         );
      }

      return $bugs;
   }

   /**
    * Get the list of weeks of a specific year in Smarty comprehensible array
    * @param int $weekid The selected week
    * @param int $year The specific year
    * @return array The result
    */
   public static function getWeeks($weekid, $year) {
      $weeks = array();
      for ($i = 1; $i <= 53; $i++) {
         $wDates = Tools::week_dates($i,$year);
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
   public static function getYears($year, $offset = 1) {
      $years = array();
      for ($y = ($year-$offset); $y <= ($year+$offset); $y++) {
         $years[] = array('id' => $y,
            'selected' => $y == $year);
      }
      return $years;
   }

   /**
    * Get the list of years in [startYear;now] in Smarty comprehensible array
    * @param int $startYear The start year
    * @param int $curYear The actual year
    * @return array The years
    */
   public static function getYearsToNow($startYear, $curYear) {
      $years = array();
      for ($y = $startYear; $y <= date('Y'); $y++) {
         $years[] = array('id' => $y,
            'selected' => $y == $curYear);
      }
      return $years;
   }

   /**
    * Get detailed mgr
    * @param IssueSelection $issueSelection
    * @return mixed[]
    */
   public static function getIssueSelectionDetailedMgr(IssueSelection $issueSelection) {
      //$formatedList  = implode( ',', array_keys($issueSelection->getIssueList()));

      $valuesMgr = $issueSelection->getDriftMgr();

      $driftMgrColor = IssueSelection::getDriftColor($valuesMgr['percent']);
      $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $selectionDetailedMgr = array('name' => $issueSelection->name,
         //'progress' => round(100 * $pv->getProgress()),
         'effortEstim' => $issueSelection->mgrEffortEstim,
         'reestimated' => $issueSelection->getReestimatedMgr(),
         'elapsed' => $issueSelection->elapsed,
         'backlog' => $issueSelection->durationMgr,
         'driftColor' => $formatteddriftMgrColor,
         'drift' => round($valuesMgr['nbDays'],2),
         'progress' => round(100 * $issueSelection->getProgressMgr(),2),

      );
      return $selectionDetailedMgr;
   }

   /**
    * get issues attributes
    * @param IssueSelection $issueSelection
    * @return mixed[]
    */
   public static function getIssueListInfo(IssueSelection $issueSelection) {
      $issueArray = array();

      $issues = $issueSelection->getIssueList();
      foreach ($issues as $id => $issue) {
         $driftMgr = $issue->getDriftMgr();
         $driftMgrColor = $issue->getDriftColor($driftMgr);

         $issueArray[$id] = array(
            "mantisLink" => Tools::mantisIssueURL($issue->bugId, NULL, TRUE),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->bugId)),
            "project" => $issue->getProjectName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "progress" => round(100 * $issue->getProgress()),
            "effortEstim" => $issue->mgrEffortEstim,
            "elapsed" => $issue->getElapsed(),
            "driftMgr" => $driftMgr,
            "driftMgrColor" => (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ",
            "durationMgr" => $issue->getDurationMgr(),
            "summary" => $issue->summary,
            "category" => $issue->getCategoryName()
         );
      }
      return $issueArray;
   }

}

SmartyTools::staticInit();

?>
