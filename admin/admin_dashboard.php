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

class AdminDashboardController extends Controller {
	
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

         // feed the PluginDataProvider
         $pluginDataProvider = PluginDataProvider::getInstance();
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID, $this->session_userid);
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $this->teamid);

         $weekDates = Tools::week_dates(date('W'),date('Y'));
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $weekDates[1]);
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $weekDates[5]);

         $dashboardName = 'Admin'.$this->teamid;

         // save the DataProvider for Ajax calls
         $_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardName] = serialize($pluginDataProvider);

         // create the Dashboard
         $dashboard = new Dashboard($dashboardName);
         $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_ADMIN);
         $dashboard->setCategories(array(
             IndicatorPluginInterface::CATEGORY_QUALITY,
             IndicatorPluginInterface::CATEGORY_ACTIVITY,
             IndicatorPluginInterface::CATEGORY_ROADMAP,
             IndicatorPluginInterface::CATEGORY_PLANNING,
             IndicatorPluginInterface::CATEGORY_RISK,
             IndicatorPluginInterface::CATEGORY_ADMIN,
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

AdminDashboardController::staticInit();
$controller = new AdminDashboardController('../', 'Admin tools','Admin');
$controller->execute();

