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

class IssueInfoTools {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   /**
    * Get general info of an issue
    * @param Issue $issue The issue
    * @param bool $isManager if true: show MgrEffortEstim column
    * @param bool $displaySupport If true, display support
    * @return mixed[]
    */
   public static function getIssueGeneralInfo(Issue $issue, $isManager=false, $displaySupport=false) {
      $withSupport = true;  // include support in elapsed & Drift

      $drift = $issue->getDrift($withSupport);
      $issueGeneralInfo = array(
         "issueId" => $issue->getId(),
         "issueSummary" => htmlspecialchars(preg_replace('![\t\r\n]+!',' ',$issue->getSummary())),
         "issueType" => $issue->getType(),
         "issueDescription" => htmlspecialchars($issue->getDescription()),
         "projectName" => $issue->getProjectName(),
         "categoryName" => $issue->getCategoryName(),
         "issueExtRef" => $issue->getTcId(),
         'mantisURL'=> Tools::mantisIssueURL($issue->getId(), NULL, true),
         'issueURL' => Tools::mantisIssueURL($issue->getId()),
         'statusName'=> $issue->getCurrentStatusName(),
         'priorityName'=> $issue->getPriorityName(),
         'severityName'=> $issue->getSeverityName(),
         'targetVersion'=> $issue->getTargetVersion(),
         'handlerName'=> UserCache::getInstance()->getUser($issue->getHandlerId())->getName(),

         "issueEffortTitle" => $issue->getEffortEstim().' + '.$issue->getEffortAdd(),
         "issueEffort" => $issue->getEffortEstim() + $issue->getEffortAdd(),
         "issueReestimated" => $issue->getReestimated(),
         "issueBacklog" => $issue->getBacklog(),
         "issueDriftColor" => $issue->getDriftColor($drift),
         "issueDrift" => round($drift, 2),
         "progress" => round(100 * $issue->getProgress()),
         'relationships' => self::getFormattedRelationshipsInfo($issue),
      	);
      if($isManager) {
         $issueGeneralInfo['issueMgrEffortEstim'] = $issue->getMgrEffortEstim();
         $driftMgr = $issue->getDriftMgr($withSupport);
         $issueGeneralInfo['issueDriftMgrColor'] = $issue->getDriftColor($driftMgr);
         $issueGeneralInfo['issueDriftMgr'] = round($driftMgr, 2);
      }
      if ($withSupport) {
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed();
      } else {
         $issueGeneralInfo['issueElapsed'] = $issue->getElapsed() - $issue->getElapsed(Jobs::JOB_SUPPORT);
      }
      if ($displaySupport) {
         if ($isManager) {
            $driftMgr = $issue->getDriftMgr(!$withSupport);
            $issueGeneralInfo['issueDriftMgrSupportColor'] = $issue->getDriftColor($driftMgr);
            $issueGeneralInfo['issueDriftMgrSupport'] = round($driftMgr, 2);
         }
         $drift = $issue->getDrift(!$withSupport);
         $issueGeneralInfo['issueDriftSupportColor'] = $issue->getDriftColor($drift);
         $issueGeneralInfo['issueDriftSupport'] = round($drift, 2);
      }

      return $issueGeneralInfo;
   }

   /**
    *
    * @param type $relationshipList
    */
   private static function getFormattedRelationshipsInfo($issue) {
      
      $relationships = $issue->getRelationships();

      $relationshipsInfo = array();
      foreach ($relationships as $relType => $bugids) {
         $typeLabel = Issue::getRelationshipLabel($relType);

         foreach ($bugids as $bugid) {
            $relatedIssue = IssueCache::getInstance()->getIssue($bugid);
            $summary = htmlspecialchars(preg_replace('![\t\r\n]+!',' ',$relatedIssue->getSummary()));
            $relationshipsInfo["$bugid"] = array('url' => Tools::issueInfoURL($bugid),
                                                 'relationship' => $typeLabel,
                                                 'status' => $relatedIssue->getCurrentStatusName(),
                                                 'progress' => round(100 * $relatedIssue->getProgress()),
                                                 'summary' => $summary
                                                 );
         }
      }
      ksort($relationshipsInfo);
      return $relationshipsInfo;
   }

   /**
    *
    * @param SmartyHelper $smartyHelper
    * @param Issue $issue
    * @param int $userid
    * @param int $teamid
    */
   public static function dashboardSettings(SmartyHelper $smartyHelper, Issue $issue, $userid, $teamid) {

      $isel = new IssueSelection();
      $isel->addIssue($issue->getId());

      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $isel);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $teamid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $userid);

      // start date is min(1st_timetrack, issue_creation_date)
      $startT = $issue->getDateSubmission();
      $firstTT = $issue->getFirstTimetrack();
      if (NULL != $firstTT) {
         $startT = min(array($issue->getDateSubmission(), $firstTT->getDate()));
      }

      // end date is last_timetrack or now if none
      $eTs = (NULL == $firstTT) ? time() : $issue->getLatestTimetrack()->getDate();
      $endT = mktime(23, 59, 59, date('m', $eTs), date('d', $eTs), date('Y', $eTs));

      //echo "start $startT end $endT<br>";

      // Calculate a nice day interval
      $nbWeeks = ($endT - $startT) / 60 / 60 / 24;
      $interval = ceil($nbWeeks / 20);

      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startT);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endT);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_INTERVAL, $interval);

      $dashboardName = 'Tasks_prj'.$issue->getProjectId();

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard($dashboardName); // settings are common all tasks of a project
      $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_TASK);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
         ));
      $dashboard->setTeamid($teamid);
      $dashboard->setUserid($userid);

      $data = $dashboard->getSmartyVariables($smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $smartyHelper->assign($smartyKey, $smartyVariable);
      }
   }

}

// Initialize complex static variables
IssueInfoTools::staticInit();

