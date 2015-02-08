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

class TeamDashboardController extends Controller {
	
	/**
	 * @var Logger The logger
	 */
	private static $logger;	
	
	public static function staticInit() {
		self::$logger = Logger::getLogger(__CLASS__);
	}
	
	protected function display() {
		if (Tools::isConnectedUser()) {

         $team = TeamCache::getInstance()->getTeam($this->teamid);
         
         $action = filter_input(INPUT_GET, 'action');
         if ('setDateRange' === $action) {
            $startdate = filter_input(INPUT_GET, 'startdate');
            $startTimestamp = Tools::date2timestamp($startdate);
            
            $enddate = filter_input(INPUT_GET, 'enddate');
            $endTimestamp = Tools::date2timestamp($enddate);
            $endTimestamp += 24 * 60 * 60 -1; // + 1 day -1 sec.
         } else {
            //$startTimestamp = $team->getDate(); // creationDate
            //$endTimestamp = time();
            $startTimestamp = strtotime("first day of this month");
            $endTimestamp = strtotime("last day of this month");
         }
         $this->smartyHelper->assign('startDate', date("Y-m-d", $startTimestamp));
         $this->smartyHelper->assign('endDate', date("Y-m-d", $endTimestamp));

         // create issueSelection with issues from team projects
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

         // save the DataProvider for Ajax calls
         $_SESSION[PluginDataProviderInterface::SESSION_ID] = serialize($pluginDataProvider);

         // create the Dashboard
         $dashboard = new Dashboard('Team'.$this->teamid);
         $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_TEAM);
         $dashboard->setCategories(array(
             IndicatorPluginInterface::CATEGORY_QUALITY,
             IndicatorPluginInterface::CATEGORY_ACTIVITY,
             IndicatorPluginInterface::CATEGORY_ROADMAP,
             IndicatorPluginInterface::CATEGORY_PLANNING,
             IndicatorPluginInterface::CATEGORY_RISK,
             IndicatorPluginInterface::CATEGORY_TEAM,
            ));
         $dashboard->setTeamid($this->teamid);
         $dashboard->setUserid($this->session_userid);

         $data = $dashboard->getSmartyVariables($this->smartyHelper);
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
		} else {
			$this->smartyHelper->assign('error',T_('Sorry, you need to be identified.'));
		}
	}
}

TeamDashboardController::staticInit();
$controller = new TeamDashboardController('../', 'Team Indicators','ProdReports');
$controller->execute();

?>