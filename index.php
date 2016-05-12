<?php
require('include/session.inc.php');

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

require('path.inc.php');

// check if INSTALL needed
if (!file_exists(Constants::$config_file)) {
   header('Location: install/install.php');
   exit;
}

class IndexController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      // Drifted tasks
      if(Tools::isConnectedUser()) {

         $isAdmin = $this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId));
         $isManager = $this->session_user->isTeamManager($this->teamid);

         // check codevtt version
         if (1 == Constants::$isCheckLatestVersion) {
            try {
               if ($isAdmin || ($isManager && (date('d') < 4))) {
                  $latestVersionInfo = Tools::getLatestVersionInfo(3);
                  if (FALSE !== $latestVersionInfo) {
                     if ( Config::codevVersion != $latestVersionInfo['version'] ) {
                        $this->smartyHelper->assign('latestVersionInfo', $latestVersionInfo);
                     }
                  }
               }
            } catch (Exception $e) {
               // version check should never break CodevTT usage...
               // (no log, even logs could raise errors)
            }
         }
         
         // if CodevTT installed since at least 6 month,
         // then display FairPlay message every 3 month (mar, jun, sep, dec) during 3 days.
         if (($isManager || $isAdmin) && 
             (0 == date('m') % 3) && (date('d') > 27) &&
             (time() - Constants::$codevInstall_timestamp > (60*60*24 * 180))    
            ) {
            $this->smartyHelper->assign('displayFairPlay', true);
            $this->smartyHelper->assign('codevInstall_date', date('Y-m-d', Constants::$codevInstall_timestamp));
         }
         
         if ($isAdmin) {
            // check global configuration
            $cerrList = ConsistencyCheck2::checkMantisDefaultProjectWorkflow();
            // add more checks here
            if (count($cerrList) > 0) {
               $systemConsistencyErrors = array();
               foreach ($cerrList as $cerr) {
                  $systemConsistencyErrors[] = array('severity' => $cerr->getLiteralSeverity(),
                                                     'desc' => $cerr->desc);
               }
               $this->smartyHelper->assign('systemConsistencyErrors', $systemConsistencyErrors);
            }
         }

         // Consistency errors
         $consistencyErrors = $this->getConsistencyErrors();

         if(count($consistencyErrors) > 0) {
            $this->smartyHelper->assign('consistencyErrorsTitle', count($consistencyErrors).' '.T_("Errors in your Tasks"));
            $this->smartyHelper->assign('consistencyErrors', $consistencyErrors);
         }

         if (0 != $this->teamid) {
           // homepage dashboard configuration
            $this->setDashboard();
         }
      }

   }

   /**
    * Get consistency errors
    * @return mixed[]
    */
   private function getConsistencyErrors() {
      $consistencyErrors = array(); // if null, array_merge fails !

      if (0 != $this->teamid) {

         // only this team's projects
         #$teamList = $this->teamList;
         $teamList = array($this->teamid => $this->teamList[$this->teamid]);

         // except disabled projects
         $projList = $this->session_user->getProjectList($teamList, true, false);

         $issueList = $this->session_user->getAssignedIssues($projList, true);

         $ccheck = new ConsistencyCheck2($issueList, $this->teamid);

         $cerrList = $ccheck->check();

         if (count($cerrList) > 0) {
            foreach ($cerrList as $cerr) {
               if ($this->session_user->getId() == $cerr->userId) {
                  $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
                  $titleAttr = array(
                      T_('Project') => $issue->getProjectName(),
                      T_('Summary') => $issue->getSummary(),
                  );
                  $consistencyErrors[] = array('issueURL' => Tools::issueInfoURL($cerr->bugId, $titleAttr),
                     'status' => Constants::$statusNames[$cerr->status],
                     'desc' => $cerr->desc);
               }
            }
         }
      }

      return $consistencyErrors;
   }

   private function setDashboard() {

      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $projList = $team->getProjects(false, false, false);
      $issueList = $this->session_user->getAssignedIssues($projList, false);
      $issueSel = new IssueSelection('userAssigned');
      $issueSel->addIssueList($issueList);

      // feed the PluginDataProvider
      $pluginDataProvider = PluginDataProvider::getInstance();
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $this->session_userid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $this->teamid);
      $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $issueSel);

      $dashboardName = 'homepage'.$this->teamid;

      // save the DataProvider for Ajax calls
      $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

      // create the Dashboard
      $dashboard = new Dashboard($dashboardName);
      $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_HOMEPAGE);
      $dashboard->setCategories(array(
          IndicatorPluginInterface::CATEGORY_QUALITY,
          IndicatorPluginInterface::CATEGORY_ACTIVITY,
          IndicatorPluginInterface::CATEGORY_ROADMAP,
          IndicatorPluginInterface::CATEGORY_PLANNING,
          IndicatorPluginInterface::CATEGORY_RISK,
          IndicatorPluginInterface::CATEGORY_TEAM,
          IndicatorPluginInterface::CATEGORY_ADMIN,
          IndicatorPluginInterface::CATEGORY_INTERNAL,
         ));
      $dashboard->setTeamid($this->teamid);
      $dashboard->setUserid($this->session_userid);

      $data = $dashboard->getSmartyVariables($this->smartyHelper);
      foreach ($data as $smartyKey => $smartyVariable) {
         $this->smartyHelper->assign($smartyKey, $smartyVariable);
      }

   }

}

// ========== MAIN ===========
IndexController::staticInit();
$controller = new IndexController('./', Constants::$homepage_title,'index');
$controller->execute();


