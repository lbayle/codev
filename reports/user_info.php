<?php
require('../include/session.inc.php');

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

class UserInfoController extends Controller {

   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }


   protected function display() {
      if(Tools::isConnectedUser()) {

         // only teamMembers & observers can access this page
         $now = time();
         $midnightTimestamp = mktime(0, 0, 0, date('m', $now), date('d', $now), date('Y', $now));
         if ((0 == $this->teamid) ||
             ($this->session_user->isTeamCustomer($this->teamid)) ||
             (!$this->session_user->isTeamMember($this->teamid, NULL, $midnightTimestamp, $midnightTimestamp))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {
            $action = filter_input(INPUT_POST, 'action');
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            $managedUserid = Tools::getSecurePOSTIntValue('userid',$this->session_userid);
            $managedUser = UserCache::getInstance()->getUser($managedUserid);

            if ($this->session_user->isTeamManager($this->teamid)) {
               // session_user is Manager, let him choose the teamMember he wants to manage
               $teamMembers = $team->getActiveMembers(NULL, NULL, TRUE);
               $this->smartyHelper->assign('users', $teamMembers);
               $this->smartyHelper->assign('selectedUser', $managedUserid);
               $this->smartyHelper->assign("isManager", true);
            }
            $this->smartyHelper->assign('managedUser_realname', $managedUser->getRealname());
            $this->smartyHelper->assign('managedUserid', $managedUserid);

            $teamMemberData = $team->getTeamMemberData($managedUserid);
            $this->smartyHelper->assign('managedUser_login', $managedUser->getName());
            $this->smartyHelper->assign('managedUser_accessLevel',  $teamMemberData['accessLevel']);
            $this->smartyHelper->assign('managedUser_teamArrivalDate', $teamMemberData['arrivalDate']);
            //$this->smartyHelper->assign('managedUser_teamDepartureDate', $teamMemberData['departureDate']);

            if ('setDateRange' === $action) {
               $startdate = filter_input(INPUT_POST, 'startdate');
               $startTimestamp = Tools::date2timestamp($startdate);

               $enddate = filter_input(INPUT_POST, 'enddate');
               $endTimestamp = Tools::date2timestamp($enddate);
               $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.
            } else {
               $startTimestamp = strtotime("first day of this month");
               $startTimestamp = mktime(0, 0, 0, date('m', $startTimestamp), date('d', $startTimestamp), date('Y', $startTimestamp));

               $endTimestamp = strtotime("last day of this month");
               $endTimestamp = mktime(0, 0, 0, date('m', $endTimestamp), date('d', $endTimestamp), date('Y', $endTimestamp));
            }

            $this->smartyHelper->assign('startDate', date("Y-m-d", $startTimestamp));
            $this->smartyHelper->assign('endDate', date("Y-m-d", $endTimestamp));

            // create issueSelection with issues from team projects
            // Note: yes, we want all the tasks because it will not be possible to update this ilst later
            $teamIssues = $team->getTeamIssueList(true, true); // with disabledProjects ?
            $teamIssueSelection = new IssueSelection('Team'.$this->teamid.'ISel');
            $teamIssueSelection->addIssueList($teamIssues);

            // feed the PluginDataProvider
            $pluginDataProvider = PluginDataProvider::getInstance();
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $teamIssueSelection);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $this->teamid);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $this->session_userid);
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_MANAGED_USER_ID, $managedUserid);

            $dashboardName = 'User'.$this->session_userid;
            $dashboardDomain = IndicatorPluginInterface::DOMAIN_USER;
            $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_DOMAIN, $dashboardDomain);

            // save the DataProvider for Ajax calls
            $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

            // create the Dashboard
            $dashboard = new Dashboard($dashboardName);
            $dashboard->setDomain($dashboardDomain);
            $dashboard->setCategories(array(
               IndicatorPluginInterface::CATEGORY_QUALITY,
               IndicatorPluginInterface::CATEGORY_ACTIVITY,
               IndicatorPluginInterface::CATEGORY_ROADMAP,
               IndicatorPluginInterface::CATEGORY_PLANNING,
               IndicatorPluginInterface::CATEGORY_RISK,
               IndicatorPluginInterface::CATEGORY_FINANCIAL,
               ));
            $dashboard->setTeamid($this->teamid);
            $dashboard->setUserid($this->session_userid);

            $data = $dashboard->getSmartyVariables($this->smartyHelper);
            foreach ($data as $smartyKey => $smartyVariable) {
               $this->smartyHelper->assign($smartyKey, $smartyVariable);
            }
         }
      }
   }


}

// ========== MAIN ===========
UserInfoController::staticInit();
$controller = new UserInfoController('../', 'User statistics','ProdReports');
$controller->execute();

