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

class PluginDashboardController extends Controller {
	
	/**
	 * @var Logger The logger
	 */
	private static $logger;	
	
	public static function staticInit() {
		self::$logger = Logger::getLogger(__CLASS__);
	}
	
	protected function display() {
		if (Tools::isConnectedUser()) {
			// Admins only
         $userid = $_SESSION['userid'];
			//$session_user = UserCache::getInstance()->getUser($userid);

         $teamid = 9; // ASF_OVA_Internet
         $cmdid = 16; // ASF Commande Internet

         $cmd = CommandCache::getInstance()->getCommand($cmdid);


         // ------ START TESTS
         //if (FALSE == Tools::createClassMap()) { echo "ERROR createClassMap";}
         $pm = PluginManager::getInstance();
         $pm->discoverNewPlugins();

         // ------ END TESTS

         // feed the PluginDataProvider
         $pluginDataProvider = PluginDataProvider::getInstance();
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION, $cmd->getIssueSelection());
         $pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_TEAM_ID, $teamid);
         //$pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP, $startTimestamp);
         //$pluginDataProvider->setParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP, $endTimestamp);

         // save the DataProvider for Ajax calls
         $_SESSION['pluginDataProvider_xxx'] = serialize($pluginDataProvider);

         // create the Dashboard
         $dashboard = new Dashboard('myDashboardId');
         $dashboard->setDomain(IndicatorPluginInterface::DOMAIN_COMMAND);
         $dashboard->setCategories(array(
             IndicatorPluginInterface::CATEGORY_QUALITY,
             IndicatorPluginInterface::CATEGORY_ACTIVITY));
         $dashboard->setTeamid($teamid);
         $dashboard->setUserid($userid);

         $data = $dashboard->getSmartyVariables($this->smartyHelper);
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
         

		} else {
			$this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
		}
	}
}

PluginDashboardController::staticInit();
$controller = new PluginDashboardController('../', 'Dashboard Test','Plugin');
$controller->execute();

?>