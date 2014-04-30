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
			//$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamid = 9; // ASF_OVA_Internet
         $cmdid = 16; // ASF Commande Internet

         $cmd = CommandCache::getInstance()->getCommand($cmdid);


         // feed the PluginManager
         $pluginMgr = PluginManagerFacade::getInstance();
         $pluginMgr->setParam(PluginManagerFacadeInterface::PARAM_ISSUE_SELECTION, $cmd->getIssueSelection());
         $pluginMgr->setParam(PluginManagerFacadeInterface::PARAM_TEAM_ID, $teamid);
         //$pluginMgr->setParam(PluginManagerFacadeInterface::PARAM_START_TIMESTAMP, $startTimestamp);
         //$pluginMgr->setParam(PluginManagerFacadeInterface::PARAM_END_TIMESTAMP, $endTimestamp);

         // Run Indicator
         $indicator = new LoadPerJobIndicator2($pluginMgr);
         $indicator->execute();

         $data = $indicator->getSmartyVariables();
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $html = $this->smartyHelper->fetch(LoadPerJobIndicator::getSmartyFilename());

         // set indicator result in a dashboard widget
         $LoadPerJobWidget = array(
            'id' => LoadPerJobIndicator2::getName(), // WARN: not unique if inserted twice !
            'color' => 'color-white',
            'title' => LoadPerJobIndicator2::getDesc(),
            'content' => $html,
         );

         // prepare dashboard
         $dashboardWidgets = array();
         $dashboardWidgets[] = $LoadPerJobWidget;
         $this->smartyHelper->assign('dashboardWidgets', $dashboardWidgets);
         $this->smartyHelper->assign('dashboardTitle', 'Sample Cmd dashboard');



		} else {
			$this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
		}
	}
}

PluginDashboardController::staticInit();
$controller = new PluginDashboardController('../', 'Dashboard Test','Plugin');
$controller->execute();

?>