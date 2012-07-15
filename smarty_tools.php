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

SmartyTools::staticInit();

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
      self::$logger = Logger::getLogger("tools");
   }

   /**
    * @param int $value
    * @return int $value
    * @throws Exception
    * @deprecated Use Tools::getSecurePOSTIntValue($key, $defaultValue)
    */
   public static function checkNumericValue($value, $allowNull = false) {
      if ((NULL == $value) && (true == $allowNull)) { return NULL; }

      $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($value);
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
    * Convert a teamList in a Smarty comprehensible array
    * @param string[] $teamList The teams
    * @param int $selectedTeamId The selected team
    * @return mixed[]
    * @deprecated Use SmartyTools::getSmartyArray($list, $selected)
    */
   public static function getTeams(array $teamList, $selectedTeamId = NULL) {
      return self::getSmartyArray($teamList, $selectedTeamId);
   }

   /**
    * Convert a projectList in a Smarty comprehensible array
    * @param string[] $projectList The projects
    * @param int $selectedProjectId The selected project
    * @return mixed[] The projects
    * @deprecated Use SmartyTools::getSmartyArray($list, $selected)
    */
   public static function getProjects(array $projectList, $selectedProjectId = NULL) {
      return self::getSmartyArray($projectList, $selectedProjectId);
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
         $issueList = $project1->getIssueList();
      } else {
         // no project specified: show all tasks
         $issueList = array();
         $formatedProjList = implode( ', ', array_keys($projList));

         $query  = "SELECT id ".
            "FROM `mantis_bug_table` ".
            "WHERE project_id IN ($formatedProjList) ".
            "ORDER BY id DESC";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         if (0 != SqlWrapper::getInstance()->sql_num_rows($result)) {
            while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
               $issueList[] = $row->id;
            }
         }
      }

      $bugs = NULL;
      foreach ($issueList as $bugid) {
         $issue = IssueCache::getInstance()->getIssue($bugid);
         $externalId = "";
         if(!empty($issue->tcId)) {
            $externalId = ' / '.$issue->tcId;
         }
         $summary = "";
         if(!empty($issue->summary)) {
            $summary = ' : '.$issue->summary;
         }
         $bugs[$bugid] = array('id' => $bugid,
            'name' => $bugid.$externalId.$summary,
            'selected' => $bugid == $defaultBugid,
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
         'remaining' => $issueSelection->durationMgr,
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
            "mantisLink" => Tools::mantisIssueURL($issue->bugId, NULL, true),
            "bugid" => Tools::issueInfoURL(sprintf("%07d\n", $issue->bugId)),
            "project" => $issue->getProjectName(),
            "target" => $issue->getTargetVersion(),
            "status" => $issue->getCurrentStatusName(),
            "progress" => round(100 * $issue->getProgress()),
            "effortEstim" => $issue->mgrEffortEstim,
            "elapsed" => $issue->elapsed,
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

/**
 * @param int $value
 * @return int $value
 * @throws Exception
 * @deprecated Use SmartyTools::checkNumericValue($value, $allowNull)
 */
function checkNumericValue($value, $allowNull = false) {
   return SmartyTools::checkNumericValue($value, $allowNull);
}

/**
 * Convert a teamList in a Smarty comprehensible array
 * @param array $teamList The teams
 * @param int $selectedTeamId The selected team
 * @return array
 * @deprecated Use SmartyTools::getTeams($teamList, $selectedTeamId)
 */
function getTeams($teamList, $selectedTeamId = NULL) {
   return SmartyTools::getTeams($teamList, $selectedTeamId);
}

/**
 * Convert a projectList in a Smarty comprehensible array
 * @param array $projectList The projects
 * @param int $selectedProjectId The selected project
 * @return array The projects
 * @deprecated Use SmartyTools::getProjects($projectList, $selectedProjectId)
 */
function getProjects($projectList, $selectedProjectId = NULL) {
    return SmartyTools::getProjects($projectList, $selectedProjectId);
}

/**
 * Convert an array in smarty array
 * @param array $list
 * @param int $selected
 * @return mixed[string]
 * @deprecated Use SmartyTools::getSmartyArray($list, $selected)
 */
function getSmartyArray($list, $selected) {
   return SmartyTools::getSmartyArray($list, $selected);
}

/**
 * @param int $projectid
 * @param int $defaultBugid
 * @param array $projList
 * @return array
 * @deprecated Use SmartyTools::getBugs($projectid, $defaultBugid, $projList)
 */
function getBugs($projectid = 0, $defaultBugid = 0, array $projList = NULL) {
   return SmartyTools::getBugs($projectid, $defaultBugid, $projList);
}

/**
 * Get the list of weeks of a specific year in Smarty comprehensible array
 * @param int $weekid The selected week
 * @param int $year The specific year
 * @return array The result
 * @deprecated Use SmartyTools::getWeeks($weekid, $year)
 */
function getWeeks($weekid, $year) {
   return SmartyTools::getWeeks($weekid, $year);
}

/**
 * Get the list of years in [year-offset;year+offset] in Smarty comprehensible array
 * @param int $year The actual year
 * @param int $offset The offset
 * @return array The years
 * @deprecated Use SmartyTools::getYears($year, $offset)
 */
function getYears($year, $offset = 1) {
   return SmartyTools::getYears($year, $offset);
}

/**
 * Get the list of years in [startYear;now] in Smarty comprehensible array
 * @param int $startYear The start year
 * @param int $curYear The actual year
 * @return array The years
 * @deprecated Use SmartyTools::getYearsToNow($startYear, $curYear)
 */
function getYearsToNow($startYear, $curYear) {
   return SmartyTools::getYearsToNow($startYear, $curYear);
}

/**
 * Get detailed mgr
 * @param array $issueSelection
 * @return array
 * @deprecated Use SmartyTools::getIssueSelectionDetailedMgr($issueSelection)
 */
function getIssueSelectionDetailedMgr(IssueSelection $issueSelection) {
   return SmartyTools::getIssueSelectionDetailedMgr($issueSelection);
}

/**
 * get issues attributes
 * @param IssueSelection $issueSelection
 * @return array
 * @deprecated Use SmartyTools::getIssueListInfo($issueSelection)
 */
function getIssueListInfo(IssueSelection $issueSelection) {
   return SmartyTools::getIssueListInfo($issueSelection);
}

?>
