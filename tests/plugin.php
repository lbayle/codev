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
			$session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
			if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
				$plugins = Plugin::getPlugins();
				$this->smartyHelper->assign('plugins', $plugins);			
			} else {
				$this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
			}
		} else {
			$this->smartyHelper->assign('error',T_('Sorry, you need to be in the admin-team to access this page.'));
		}
	}
}

PluginDashboardController::staticInit();
$controller = new PluginDashboardController('../', 'Plugin Dashboard','Plugin');
$controller->execute();

?>