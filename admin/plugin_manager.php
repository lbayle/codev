<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

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

require('../path.inc.php');

class PluginManagerController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      $this->smartyHelper->assign('activeGlobalMenuItem', 'Admin');

      if(Tools::isConnectedUser()) {

         if (!$this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {

            $action = Tools::getSecurePOSTStringValue('action', 'display');
            $pm = PluginManager::getInstance();

            // === ACTIONS =====================================================
            if ('enablePlugin' == $action) {
               $pluginName = Tools::getSecurePOSTStringValue('pluginName');
               $pm->enablePlugin($pluginName);

            } else if ('disablePlugin' == $action) {
               $pluginName = Tools::getSecurePOSTStringValue('pluginName');
               $pm->disablePlugin($pluginName);

            } else if ('discoverNewPlugins' == $action) {
               try {
                  Tools::createClassMap();
                  $pm->discoverNewPlugins();
                  //$this->smartyHelper->assign('infoMsg', T_('Found xx new plugins !'));
               } catch (Exception $e) {
                  $this->smartyHelper->assign('errorMsg', T_('Could not create classmap: ').$e->getMessage());
               }
            }
            
            // === DISPLAY =====================================================

            // set values to display plugin table
            $plugins = $pm->getPlugins();
            $formattedPlugins = array();
            foreach ($plugins as $plugin) {

               $className = $plugin['className'];

               $formated_domains = array();
               foreach ($plugin['domains'] as $domName) {
                  array_push($formated_domains, T_($domName));
               }
               //sort($formated_domains);

               $formated_categories = array();
               foreach ($plugin['categories'] as $catName) {
                  array_push($formated_categories, T_($catName));
               }
               //sort($formated_categories);

               $formattedPlugins[$className] = array(
               'name' => $plugin['displayedName'],
               'status' => $plugin['status'],
               'statusName' => pluginManager::getStatusName($plugin['status']),
               'domains' => implode(',<br>', $formated_domains),
               'categories' => implode(',<br>', $formated_categories),
               'version' => $plugin['version'],
               'description' => $plugin['description'],
               );
            }
            $this->smartyHelper->assign('availablePlugins', $formattedPlugins);
         
         
            
         }
      }
   }

}

// ========== MAIN ===========
PluginManagerController::staticInit();
$controller = new PluginManagerController('../', 'Plugin Manager','Admin');
$controller->execute();

?>
