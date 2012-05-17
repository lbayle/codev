<?php
include_once('../include/session.inc.php');

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

require('../path.inc.php');

require('super_header.inc.php');

include_once "issue.class.php";
include_once "project.class.php";
include_once "time_track.class.php";
include_once "user.class.php";
include_once "jobs.class.php";
include_once "holidays.class.php";

/**
 * Get projects
 * @param int $defaultProjectid
 * @param array $projectList
 * @return array
 */
function getProjects($defaultProjectid, $projectList) {
   foreach ($projectList as $pid => $pname) {
      $projects[] = array('id' => $pid,
                          'name' => $pname,
                          'selected' => $pid == $defaultProjectid
      );
   }
   return $projects;
}

/**
 * Get versions overview
 * @param Project $project
 * @return array
 */
function getVersionsOverview($project) {
   $projectVersionList = $project->getVersionList();
   foreach ($projectVersionList as $version => $pv) {
      if (NULL == $pv) {
         continue;
      }

      $valuesMgr = $pv->getDriftMgr();

      $driftMgrColor = IssueSelection::getDriftColor($valuesMgr['percent']);
      $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $values = $pv->getDrift();
      $driftColor = IssueSelection::getDriftColor($values['percent']);
      $formatteddriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";

      $vdate =  $pv->getVersionDate();
      $date = "";
      if (is_numeric($vdate)) {
         $date = date("Y-m-d",$vdate);
      }

      $versionsOverview[] = array('name' => $pv->name,
                                  'date' => $date,
                                  'progressMgr' => round(100 * $pv->getProgressMgr()),
                                  'progress' => round(100 * $pv->getProgress()),
                                  'driftMgrColor' => $formatteddriftMgrColor,
                                  'driftMgr' => round(100 * $valuesMgr['percent']),
                                  'driftColor' => $formatteddriftColor,
                                  'drift' => round(100 * $values['percent'])
      );
   }

   $driftMgr = $project->getDriftMgr();
   $driftMgrColor = IssueSelection::getDriftColor($driftMgr['percent']);
   $formattedDriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

   $drift = $project->getDrift();
   $driftColor = IssueSelection::getDriftColor($drift['percent']);
   $formattedDriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";

   $versionsOverview[] = array('name' => T_("Total"),
                               'date' => '',
                               'progressMgr' => round(100 * $project->getProgressMgr()),
                               'progress' => round(100 * $project->getProgress()),
                               'driftMgrColor' => $formattedDriftMgrColor,
                               'driftMgr' => round(100 * $driftMgr['percent']),
                               'driftColor' => $formattedDriftColor,
                               'drift' => round(100 * $drift['percent'])
   );

   return $versionsOverview;
}

/**
 * Get detailed mgr versions
 * @param array $projectVersionList
 * @return array
 */
function getVersionsDetailedMgr($projectVersionList) {
   $totalEffortEstimMgr = 0;
   $totalElapsed = 0;
   $totalRemainingMgr = 0;
   $totalReestimatedMgr = 0;
   $totalDriftMgr = 0;
   foreach ($projectVersionList as $version => $pv) {
      $totalEffortEstimMgr += $pv->mgrEffortEstim;
      $totalElapsed += $pv->elapsed;
      $totalRemainingMgr += $pv->remainingMgr;
      $totalReestimatedMgr += $pv->remainingMgr;
      //$formatedList  = implode( ',', array_keys($pv->getIssueList()));

      $valuesMgr = $pv->getDriftMgr();
      $totalDriftMgr += $valuesMgr['nbDays'];

      $driftMgrColor = IssueSelection::getDriftColor($valuesMgr['percent']);
      $formatteddriftMgrColor = (NULL == $driftMgrColor) ? "" : "style='background-color: #".$driftMgrColor.";' ";

      $versionsDetailedMgr[] = array('name' => $pv->name,
                                     //'progress' => round(100 * $pv->getProgress()),
                                     'effortEstim' => $pv->mgrEffortEstim,
                                     'reestimated' => ($pv->remainingMgr + $pv->elapsed),
                                     'elapsed' => $pv->elapsed,
                                     'remaining' => $pv->remainingMgr,
                                     'driftColor' => $formatteddriftMgrColor,
                                     'drift' => round($valuesMgr['nbDays'],2)
      );
   }

   $versionsDetailedMgr[] = array('name' => T_("Total"),
                                  //'progress' => round(100 * $totalProgress),
                                  'effortEstim' => $totalEffortEstimMgr,
                                  'reestimated' => ($totalRemainingMgr + $totalElapsed),
                                  'elapsed' => $totalElapsed,
                                  'remaining' => $totalRemainingMgr,
                                  'driftColor' => '',
                                  'drift' => round($totalDriftMgr,2)
   );

   return $versionsDetailedMgr;
}

/**
 * Get detailed versions
 * @param array $projectVersionList
 * @return array
 */
function getVersionsDetailed($projectVersionList) {
   $totalDrift = 0;
   $totalEffortEstim = 0;
   $totalElapsed = 0;
   $totalRemaining = 0;
   foreach ($projectVersionList as $version => $pv) {
      $totalEffortEstim += $pv->effortEstim + $pv->effortAdd;
      $totalElapsed += $pv->elapsed;
      $totalRemaining += $pv->remaining;
      //$formatedList  = implode( ',', array_keys($pv->getIssueList()));

      $values = $pv->getDrift();
      $totalDrift += $values['nbDays'];
      $driftColor = IssueSelection::getDriftColor($values['percent']);
      $formatteddriftColor = (NULL == $driftColor) ? "" : "style='background-color: #".$driftColor.";' ";

      $versionsDetailed[] = array('name' => $pv->name,
         //'progress' => round(100 * $pv->getProgress()),
         'title' => 'title="'.($pv->effortEstim + $pv->effortAdd).'"',
         'effortEstim' => ($pv->effortEstim + $pv->effortAdd),
         'reestimated' => ($pv->remaining + $pv->elapsed),
         'elapsed' => $pv->elapsed,
         'remaining' => $pv->remaining,
         'driftColor' => $formatteddriftColor,
         'drift' => round($values['nbDays'],2)
      );
   }

   $versionsDetailed[] = array('name' => T_("Total"),
      'title' => '',
      'effortEstim' => $totalEffortEstim,
      'reestimated' => ($totalRemaining + $totalElapsed),
      'elapsed' => $totalElapsed,
      'remaining' => $totalRemaining,
      'driftColor' => '',
      'drift' => $totalDrift
   );

   return $versionsDetailed;
}

/**
 * Get version issues
 * @param array $projectVersionList
 * @return array
 */
function getVersionsIssues($projectVersionList) {
   global $status_new;

   $totalElapsed = 0;
   $totalRemaining = 0;
   foreach ($projectVersionList as $version => $pv) {
      $totalElapsed += $pv->elapsed;
      $totalRemaining += $pv->remaining;
      //$formatedList  = implode( ',', array_keys($pv->getIssueList()));

      // format Issues list
      $formatedResolvedList = "";
      $formatedOpenList = "";
      $formatedNewList = "";
      foreach ($pv->getIssueList() as $bugid => $issue) {

         if ($status_new == $issue->currentStatus) {
            if ("" != $formatedNewList) {
               $formatedNewList .= ', ';
            }
            $formatedNewList .= issueInfoURL($bugid, $issue->summary);

         } elseif ($issue->currentStatus >= $issue->bug_resolved_status_threshold) {
            if ("" != $formatedResolvedList) {
               $formatedResolvedList .= ', ';
            }
            $title = "(".$issue->getDrift().") $issue->summary";
            $formatedResolvedList .= issueInfoURL($bugid, $title);
         } else {
            if ("" != $formatedOpenList) {
               $formatedOpenList .= ', ';
            }
            $title = "(".$issue->getDrift().", ".$issue->getCurrentStatusName().") $issue->summary";
            $formatedOpenList .= issueInfoURL($bugid, $title);
         }
      }

      $versionsIssues[] = array('name' => $pv->name,
                                'newList' => $formatedNewList,
                                'openList' => $formatedOpenList,
                                'resolvedList' => $formatedResolvedList
      );
   }

   /*
   // compute total progress
   if (0 == $totalRemaining) {
      $totalProgress = 1;  // if no Remaining, then Project is 100% done.
   } elseif (0 == $totalElapsed) {
      $totalProgress = 0;  // if no time spent, then no work done.
   } else {
      $totalProgress = $totalElapsed / ($totalElapsed + $totalRemaining);
   }
   */

   return $versionsIssues;
}

/**
 * Get all "non-resolved" issues that are in drift (ordered by version)
 * @param array $projectVersionList
 * @param boolean $isManager
 * @param boolean $withSupport
 * @return array
 */
function getCurrentIssuesInDrift($projectVersionList, $isManager, $withSupport = true) {
   foreach ($projectVersionList as $version => $pv) {
      foreach ($pv->getIssueList() as $bugid => $issue) {

         if ($issue->isResolved()) {
            // skip resolved issues
            continue;
         }

         $driftPrelEE = ($isManager) ? $issue->getDriftMgrEE($withSupport) : 0;
         $driftEE = $issue->getDrift($withSupport);

         if (($driftPrelEE > 0) || ($driftEE > 0)) {
            if ($isManager) {
               $driftMgrColor = "";
               if ($driftPrelEE < -1) {
                  $driftMgrColor = "style='background-color: #61ed66;'";
               } else if ($driftPrelEE > 1) {
                  $driftMgrColor = "style='background-color: #fcbdbd;'";
               }
               $driftMgr = round($driftPrelEE, 2);
            }
            $driftColor = "";
            if ($driftEE < -1) {
               $driftColor = "style='background-color: #61ed66;'";
            } else if ($driftEE > 1) {
               $driftColor = "style='background-color: #fcbdbd;'";
            }

            $currentIssuesInDrift[] = array('issueURL' => issueInfoURL($issue->bugId),
                                            'projectName' => $issue->getProjectName(),
                                            'targetVersion' => $issue->getTargetVersion(),
                                            'driftMgrColor' => $driftMgrColor,
                                            'driftMgr' => $driftMgr,
                                            'driftColor' => $driftColor,
                                            'drift' => round($driftEE, 2),
                                            'remaining' => $issue->remaining,
                                            'progress' => round(100 * $issue->getProgress()),
                                            'currentStatusName' => $issue->getCurrentStatusName(),
                                            'summary' => $issue->summary
            );
         }
      }
   }

   return $currentIssuesInDrift;
}

/**
 * Get all resolved issues that are in drift (ordered by version)
 * @param array $projectVersionList
 * @param boolean $isManager
 * @param boolean $withSupport
 * @return array
 */
function getResolvedIssuesInDrift($projectVersionList, $isManager, $withSupport = true) {
   foreach ($projectVersionList as $version => $pv) {
      foreach ($pv->getIssueList() as $bugid => $issue) {

         if (!$issue->isResolved()) {
            // skip non-resolved issues
            continue;
         }

         $driftPrelEE = ($isManager) ? $issue->getDriftMgrEE($withSupport) : 0;
         $driftEE = $issue->getDrift($withSupport);

         if (($driftPrelEE > 0) || ($driftEE > 0)) {
            if ($isManager) {
               $driftMgrColor = "";
               if ($driftPrelEE < -1) {
                  $driftMgrColor = "style='background-color: #61ed66;'";
               } else if ($driftPrelEE > 1) {
                  $driftMgrColor = "style='background-color: #fcbdbd;'";
               }
               $driftMgr = round($driftPrelEE, 2);
            }
            $driftColor = "";
            if ($driftEE < -1) {
               $driftColor = "style='background-color: #61ed66;'";
            } else if ($driftEE > 1) {
               $driftColor = "style='background-color: #fcbdbd;'";
            }

            $resolvedIssuesInDrift[] = array('issueURL' => issueInfoURL($issue->bugId),
                                             'projectName' => $issue->getProjectName(),
                                             'targetVersion' => $issue->getTargetVersion(),
                                             'driftMgrColor' => $driftMgrColor,
                                             'driftMgr' => $driftMgr,
                                             'driftColor' => $driftColor,
                                             'drift' => round($driftEE, 2),
                                             'remaining' => $issue->remaining,
                                             'progress' => round(100 * $issue->getProgress()),
                                             'currentStatusName' => $issue->getCurrentStatusName(),
                                             'summary' => $issue->summary
            );
         }
      }
   }

   return $resolvedIssuesInDrift;
}

// ================ MAIN =================
require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('Project Info'));

if(isset($_SESSION['userid'])) {
   $session_userid = $_SESSION['userid'];
   $user = UserCache::getInstance()->getUser($session_userid);

   $dTeamList = $user->getDevTeamList();
   $lTeamList = $user->getLeadedTeamList();
   $oTeamList = $user->getObservedTeamList();
   $managedTeamList = $user->getManagedTeamList();
   $teamList = $dTeamList + $lTeamList + $oTeamList + $managedTeamList;

   if (0 != count($teamList)) {
      $projectid = 0;
      if(isset($_GET['projectid'])) {
         $projectid = $_GET['projectid'];
      } else if(isset($_SESSION['projectid'])) {
         $projectid = $_SESSION['projectid'];
      }
      $_SESSION['projectid'] = $projectid;

      // --- define the list of tasks the user can display
      // All projects from teams where I'm a Developper or Manager AND Observers
      $devProjList      = (0 == count($dTeamList))       ? array() : $user->getProjectList($dTeamList);
      $managedProjList  = (0 == count($managedTeamList)) ? array() : $user->getProjectList($managedTeamList);
      $observedProjList = (0 == count($oTeamList))       ? array() : $user->getProjectList($oTeamList);
      $projList = $devProjList + $managedProjList + $observedProjList;

      $smartyHelper->assign('projects', getProjects($projectid, $projList));

      if (in_array($projectid, array_keys($projList))) {
         $isManager = true; // TODO
         $smartyHelper->assign("isManager", $isManager);

         $project = ProjectCache::getInstance()->getProject($projectid);

         $smartyHelper->assign("versionsOverview", getVersionsOverview($project));

         if ($isManager) {
            $smartyHelper->assign("versionsDetailedMgr", getVersionsDetailedMgr($project->getVersionList()));
         }

         $projectVersionList = $project->getVersionList();
         $smartyHelper->assign("versionsDetailed", getVersionsDetailed($projectVersionList));

         $smartyHelper->assign("versionsIssues", getVersionsIssues($projectVersionList));

         $smartyHelper->assign("currentIssuesInDrift", getCurrentIssuesInDrift($projectVersionList, $isManager));

         $smartyHelper->assign("resolvedIssuesInDrift", getResolvedIssuesInDrift($projectVersionList, $isManager));
      } else if ($projectid) {
         $smartyHelper->assign("error", T_("Sorry, you are not allowed to view the details of this project"));
      }
   } else {
      $smartyHelper->assign("error", T_("Sorry, you need to be member of a Team to access this page."));
   }

   // log stats
   IssueCache::getInstance()->logStats();
   ProjectCache::getInstance()->logStats();
   UserCache::getInstance()->logStats();
   TimeTrackCache::getInstance()->logStats();
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
