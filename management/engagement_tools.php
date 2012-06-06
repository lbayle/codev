<?php
/*
  This file is part of CodevTT.

  CodevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CodevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
 */



/**
 *
 */
function getEngagementIssues($engagement) {

   $issueArray = array();

   $issues = $engagement->getIssueSelection()->getIssueList();
   foreach ($issues as $id => $issue) {

      $issueInfo = array();

      $issueInfo["bugid"] = issueInfoURL(sprintf("%07d\n",   $issue->bugId));
      $issueInfo["project"] = $issue->getProjectName();
      $issueInfo["target"] = $issue->getTargetVersion();
      $issueInfo["status"] = $issue->getCurrentStatusName();
      $issueInfo["progress"] = round(100 * $issue->getProgress());
      $issueInfo["effortEstim"] = $issue->mgrEffortEstim;
      $issueInfo["elapsed"] = $issue->elapsed;
      $issueInfo["driftMgr"] = $issue->getDriftMgr();
      $issueInfo["durationMgr"] = $issue->getDurationMgr();
      $issueInfo["summary"] = $issue->summary;

      $issueArray[$id] = $issueInfo;
   }
   return $issueArray;
}


function displayEngagement($smartyHelper, $eng) {

   $smartyHelper->assign('engid', $eng->getId());
   $smartyHelper->assign('engName', $eng->getName());
   $smartyHelper->assign('engDesc', $eng->getDesc());
   $smartyHelper->assign('engBudjetDev', $eng->getBudjetDev());
   $smartyHelper->assign('engBudjetMngt', $eng->getBudjetMngt());
   $smartyHelper->assign('engBudjetGarantie', $eng->getBudjetGarantie());
   $smartyHelper->assign('engBudjetTotal', $eng->getBudjetDev() + $eng->getBudjetMngt() + $eng->getBudjetGarantie());
   $smartyHelper->assign('engStartDate', date("Y-m-d", $eng->getStartDate()));
   $smartyHelper->assign('engDeadline', date("Y-m-d", $eng->getDeadline()));
   $smartyHelper->assign('engAverageDailyRate', $eng->getAverageDailyRate());

   // set Eng Details
   $engIssueSel = $eng->getIssueSelection();
   $engDetailedMgr = getIssueSelectionDetailedMgr($engIssueSel);
   $smartyHelper->assign('engDetailedMgr', $engDetailedMgr);
   $smartyHelper->assign('engNbIssues', $engIssueSel->getNbIssues());
   $smartyHelper->assign('engShortIssueList', $engIssueSel->getFormattedIssueList());

   $issueList = getEngagementIssues($eng);
   $smartyHelper->assign('engIssues', $issueList);

   
   $smartyHelper->assign('engStats', "ok");


}


?>
