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
         

         // LoadPerJobIndicator
         $params = array(
            //'startTimestamp' => $startTimestamp, // $cmd->getStartDate(),
            //'endTimestamp' => $endTimestamp,
            'teamid' => $teamid // ASF_OVA_Internet
         );

         $cmd = CommandCache::getInstance()->getCommand($cmdid);

         $indicator = new LoadPerJobIndicator();
         $indicator->execute($cmd->getIssueSelection(), $params);

         $data = $indicator->getSmartyObject();
         foreach ($data as $smartyKey => $smartyVariable) {
            $this->smartyHelper->assign($smartyKey, $smartyVariable);
         }
         $html = $this->smartyHelper->fetch(LoadPerJobIndicator::getSmartyFilename());


         $LoadPerJobWidget = array(
            'id' => get_class($indicator), // WARN: not unique if inserted twice !
            'color' => 'color-white',
            'title' => $indicator->getDesc(),
            'content' => $html,
         );

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