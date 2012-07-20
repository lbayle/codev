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

include_once('classes/issue_cache.class.php');
include_once('classes/issue_selection.class.php');
include_once('classes/jobs.class.php');
include_once('classes/project.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/team_cache.class.php');

require_once('tools.php');

class ProductivityReportTools {

   /**
    * @param TimeTracking $timeTracking
    * @param int $projectId
    * @return mixed[]
    */
   public static function getProjectDetails(TimeTracking $timeTracking, $projectId) {
      $durationPerCategory = array();
      $formatedBugsPerCategory = array();

      $durPerCat = $timeTracking->getProjectDetails($projectId);
      foreach ($durPerCat as $catName => $bugList) {
         foreach ($bugList as $bugid => $duration) {
            $durationPerCategory[$catName] += $duration;

            if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
            $issue = IssueCache::getInstance()->getIssue($bugid);
            $formatedBugsPerCategory[$catName] .= Tools::issueInfoURL($bugid, '['.$issue->getProjectName().'] '.$issue->summary);
         }
      }

      return self::getProjectDetail($durationPerCategory, $formatedBugsPerCategory);
   }

   /**
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   public static function getSideTasksProjectDetails(TimeTracking $timeTracking) {
      // find all sideTasksProjects (type = 1)
      $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);
      $projectIds = $team->getSpecificTypedProjectIds(Project::type_sideTaskProject);

      $durationPerCategory = array();
      $formatedBugsPerCategory = array();
      foreach($projectIds as $projectId) {
         $durPerCat = $timeTracking->getProjectDetails($projectId);
         foreach ($durPerCat as $catName => $bugList) {
            foreach ($bugList as $bugid => $duration) {
               $durationPerCategory[$catName] += $duration;

               if ($formatedBugsPerCategory[$catName] != "") { $formatedBugsPerCategory[$catName] .= ', '; }
               $issue = IssueCache::getInstance()->getIssue($bugid);
               $formatedBugsPerCategory[$catName] .= Tools::issueInfoURL($bugid, '['.$issue->getProjectName().'] '.$issue->summary);
            }
         }
      }

      return self::getProjectDetail($durationPerCategory, $formatedBugsPerCategory);
   }

   /**
    * @param int[] $durationPerCategory
    * @param string[] $formatedBugsPerCategory
    * @return mixed[]
    */
   public static function getProjectDetail(array $durationPerCategory, array $formatedBugsPerCategory) {
      $projectDetails = NULL;
      foreach ($durationPerCategory as $catName => $duration) {
         $projectDetails[] = array(
            'catName' => $catName,
            'duration' => $duration,
            'formatedBugsPerCategory' => $formatedBugsPerCategory[$catName]
         );
      }

      return $projectDetails;
   }

   /**
    * @param array[] $projectDetails
    * @return string
    */
   public static function getProjectDetailsUrl(array $projectDetails) {
      $formatedValues = NULL;
      $formatedLegends = NULL;
      foreach ($projectDetails as $projectDetail) {
         if (0 != $projectDetail['duration']) {
            if (NULL != $formatedValues) {
               $formatedValues .= ":"; $formatedLegends .= ":";
            }
            $formatedValues .= $projectDetail['duration'];
            $formatedLegends .= $projectDetail['catName'];
         }
      }

      if (NULL != $formatedValues) {
         return Tools::SmartUrlEncode("legends=$formatedLegends&values=$formatedValues");
      }
      return NULL;
   }

   /**
    * @param int $teamid
    * @return mixed[]
    */
   public static function getTeamProjects($teamid) {
      $team = TeamCache::getInstance()->getTeam($teamid);
      $projList = $team->getProjects();
      $projList[0] = T_("All sideTasks Projects");
      return $projList;
   }

   /**
    * Get the reopened taks for Smarty
    * @param TimeTracking $timeTracking
    * @return string[]
    */
   public static function getFormattedReopenedTaks(TimeTracking $timeTracking) {
      $formatedTasks = NULL;
      foreach ($timeTracking->getReopened() as $bug_id) {
         $issue = IssueCache::getInstance()->getIssue($bug_id);
         $formatedTasks[] = Tools::issueInfoURL($issue->bugId, '['.$issue->getProjectName().'] '.$issue->summary);
      }
      return $formatedTasks;
   }

   /**
    * @param TimeTracking $timeTracking
    * @return string
    */
   public static function getProductionDaysUrl(TimeTracking $timeTracking) {
      $managementDay = $timeTracking->getManagementDays();
      $formatedValues = $timeTracking->getProdDays().':'.$managementDay.':'.($timeTracking->getProdDaysSideTasks(false) - $managementDay);
      $formatedLegends = T_('Projects').':'.T_('Project Management').':'.T_('Other SideTasks');
      $colors = '#92C5FC'.':'.'#FFC16B'.':'.'#FFF494';
      return Tools::SmartUrlEncode('colors='.$colors.'&legends='.$formatedLegends.'&values='.$formatedValues);
   }

   /**
    * @param TimeTracking $timeTracking
    * @param int $teamid
    * @return mixed[]
    */
   public static function getWorkingDaysPerJob(TimeTracking $timeTracking, $teamid) {
      // find out which jobs must be displayed
      $team = TeamCache::getInstance()->getTeam($teamid);
      $projList = $team->getProjects();
      $jobList  = array();
      foreach ($projList as $id => $pname) {
         $p = ProjectCache::getInstance()->getProject($id);
         $jl = $p->getJobList($team->getProjectType($id));
         $jobList += $jl;
      }

      $jobs = new Jobs();
      $workingDaysPerJob = NULL;
      foreach ($jobList as $id => $jname) {
         if (Jobs::JOB_NA != $id) {
            $workingDaysPerJob[] = array(
               "name" => $jname,
               "nbDays" => $timeTracking->getWorkingDaysPerJob($id),
               "color" => $jobs->getJobColor($id)
            );
         }
      }

      return $workingDaysPerJob;
   }

   /**
    * @param array[] $workingDaysPerJobs
    * @return string
    */
   public static function getWorkingDaysPerJobUrl(array $workingDaysPerJobs) {
      $formatedValues = NULL;
      $formatedLegends = NULL;
      $formatedColors = NULL;

      foreach ($workingDaysPerJobs as $workingDaysPerJob) {
         if (0 != $workingDaysPerJob['nbDays']) {
            if (NULL != $formatedValues) { $formatedValues .= ":"; $formatedLegends .= ":"; $formatedColors .= ":"; }
            $formatedValues .= $workingDaysPerJob['nbDays'];
            $formatedLegends .= $workingDaysPerJob['name'];
            $formatedColors .= "#".$workingDaysPerJob['color'];
         }
      }

      if (NULL != $formatedValues) {
         return Tools::SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues.'&colors='.$formatedColors);
      }
      return NULL;
   }

   /**
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   public static function getWorkingDaysPerProject(TimeTracking $timeTracking) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);

      $workingDaysPerProject = NULL;
      $projects = $team->getTrueProjects();
      foreach($projects as $project) {
         $nbDays = $timeTracking->getWorkingDaysPerProject($project->id);
         if ((! $team->isSideTasksProject($project->id)) && (! $team->isNoStatsProject($project->id))) {
            $progress = round(100 * $project->getProgress()).'%';
            $progressMgr = round(100 * $project->getProgressMgr()).'%';
         } else {
            $progress = '';
            $progressMgr = '';
         }

         $workingDaysPerProject[] = array(
            'name' => $project->name,
            'nbDays' => $nbDays,
            'progress' => $progress,
            'progressMgr' => $progressMgr
         );
      }

      return $workingDaysPerProject;
   }

   /**
    * @param array $workingDaysPerProject
    * @return string
    */
   public static function getWorkingDaysPerProjectUrl(array $workingDaysPerProject) {
      $formatedValues = NULL;
      $formatedLegends = NULL;
      foreach ($workingDaysPerProject as $workingDays) {
         if (0 != $workingDays['nbDays']) {
            if (NULL != $formatedValues) {
               $formatedValues .= ":"; $formatedLegends .= ":";
            }
            $formatedValues .= $workingDays['nbDays'];
            $formatedLegends .= $workingDays['name'];
         }
      }

      if (NULL != $formatedValues) {
         return Tools::SmartUrlEncode('legends='.$formatedLegends.'&values='.$formatedValues);
      } else {
         return NULL;
      }
   }

   /**
    * display Drifts for Issues that have been marked as 'Resolved' durung the timestamp
    * @param Issue[] $issueList
    * @param bool $withSupport
    * @return mixed[]
    */
   public static function getResolvedDeviationStats(array $issueList, $withSupport = true) {
      $issueSelection = new IssueSelection("resolved issues");
      $issueSelection->addIssueList($issueList);

      $allDriftMgr = $issueSelection->getDriftMgr();
      $allDrift = $issueSelection->getDrift();

      $deviationGroupsMgr = $issueSelection->getDeviationGroupsMgr(1, $withSupport);
      $deviationGroups    = $issueSelection->getDeviationGroups(1, $withSupport);

      $posDeviationGroupMgr = $deviationGroupsMgr['positive'];
      $posDeviationGroup = $deviationGroups['positive'];
      $posDriftMgr = $posDeviationGroupMgr->getDriftMgr();
      $posDrift    = $posDeviationGroup->getDrift();
      $detailsResolvedDeviationStats[] = array(
         "type" => "Tasks in drift",
         "nbIssuesMgr" => $posDeviationGroupMgr->getNbIssues(),
         "nbDaysMgr" => $posDriftMgr['nbDays'],
         "nbIssues" => $posDeviationGroup->getNbIssues(),
         "nbDays" => $posDrift['nbDays'],
         "formattedIssueListMgr" => $posDeviationGroupMgr->getFormattedIssueList(),
         "formattedIssueList" => $posDeviationGroup->getFormattedIssueList()
      );

      $equalDeviationGroupMgr = $deviationGroupsMgr['equal'];
      $equalDeviationGroup = $deviationGroups['equal'];
      $equalDriftMgr = $equalDeviationGroupMgr->getDriftMgr();
      $equalDrift = $equalDeviationGroup->getDrift();
      $detailsResolvedDeviationStats[] = array(
         "type" => "Tasks in time",
         "nbIssuesMgr" => $equalDeviationGroupMgr->getNbIssues(),
         "nbDaysMgr" => $equalDriftMgr['nbDays'],
         "nbIssues" => $equalDeviationGroup->getNbIssues(),
         "nbDays" => $equalDrift['nbDays'],
         "formattedIssueListMgr" => $equalDeviationGroupMgr->getFormattedIssueList(),
         "formattedIssueList" => $equalDeviationGroup->getFormattedIssueList()
      );

      $negDeviationGroupMgr = $deviationGroupsMgr['negative'];
      $negDeviationGroup = $deviationGroups['negative'];
      $negDriftMgr = $negDeviationGroupMgr->getDriftMgr();
      $negDrift    = $negDeviationGroup->getDrift();
      $detailsResolvedDeviationStats[] = array(
         "type" => "Tasks ahead",
         "nbIssuesMgr" => $negDeviationGroupMgr->getNbIssues(),
         "nbDaysMgr" => $negDriftMgr['nbDays'],
         "nbIssues" => $negDeviationGroup->getNbIssues(),
         "nbDays" => $negDrift['nbDays'],
         "formattedIssueListMgr" => $negDeviationGroupMgr->getFormattedIssueList(),
         "formattedIssueList" => $negDeviationGroup->getFormattedIssueList()
      );

      $resolvedDeviationStats = array(
         "driftMgr" => round($allDriftMgr['nbDays'], 2),
         "drift" => round($allDrift['nbDays'], 2),
         "detailsResolvedDeviationStats" => $detailsResolvedDeviationStats
      );

      return $resolvedDeviationStats;
   }

   /**
    * @param Issue[] $issueList
    * @param bool $isManager
    * @param bool $withSupport
    * @return mixed[]
    */
   public static function getResolvedIssuesInDrift(array $issueList, $isManager=false, $withSupport=true) {
      $resolvedIssuesInDrift = NULL;
      foreach ($issueList as $issue) {
         // TODO: check if issue in team project list ?
         $driftMgrEE = 0;
         if ($isManager) {
            $driftMgrEE = $issue->getDriftMgr($withSupport);
         }
         $driftEE = $issue->getDrift($withSupport);

         if (($isManager && $driftMgrEE > 0) || ($driftEE > 0)) {
            $resolvedIssuesInDrift[] = array(
               "issueURL" => Tools::issueInfoURL($issue->bugId),
               "projectName" => $issue->getProjectName(),
               "driftMgrEE" => $driftMgrEE,
               "driftEE" => $driftEE,
               "currentStatusName" => $issue->getCurrentStatusName(),
               "summary" => $issue->summary
            );
         }
      }

      return $resolvedIssuesInDrift;
   }

   /**
    * @param TimeTracking $timeTracking
    * @return mixed[]
    */
   public static function getCheckWarnings(TimeTracking $timeTracking) {
      $team = TeamCache::getInstance()->getTeam($timeTracking->team_id);
      $warnings = NULL;
      foreach($team->getMembers() as $userid => $username) {
         $incompleteDays = $timeTracking->checkCompleteDays($userid, TRUE);
         foreach ($incompleteDays as $date => $value) {
            if ($value < 1) {
               $warnings[] = array(
                  'user' => $username,
                  'date' => date("Y-m-d", $date),
                  'desc' => T_("incomplete").' ('.T_('missing').' '.(1-$value).' '.T_('day').')',
                  'severity' => T_("Error"),
                  'severityColor' => 'color:red'
               );
            } else {
               $warnings[] = array(
                  'user' => $username,
                  'date' => date("Y-m-d", $date),
                  'desc' => T_("inconsistent").' ('.$value.' '.T_('day').')',
                  'severity' => T_("Error"),
                  'severityColor' => 'color:red'
               );
            }
         }

         $missingDays = $timeTracking->checkMissingDays($userid);
         foreach ($missingDays as $date) {
            $warnings[] = array(
               'user' => $username,
               'date' => date("Y-m-d", $date),
               'desc' => T_("not defined."),
               'severity' => T_("Error"),
               'severityColor' => 'color:red'
            );
         }
      }

      return $warnings;
   }

}

?>
