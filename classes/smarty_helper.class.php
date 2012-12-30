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

require_once('i18n/i18n.inc.php');

/**
 * Smarty helper : Construct a smarty objet for templating engine
 * @author NSV
 * @date 17 Mar 2012
 */
class SmartyHelper {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * @var Smarty The smarty instance
    */
   private $smarty;

   /**
    * Constructor
    */
   public function __construct() {
      $this->smarty = new Smarty();
      $this->smarty->caching = Smarty::CACHING_OFF;
      $this->smarty->setTemplateDir(BASE_PATH . '/tpl/');
      $this->smarty->setCacheDir('/tmp/codevtt/cache/');
      $this->smarty->setCompileDir('/tmp/codevtt/template_c/');

      // Method for smarty translation
      function smarty_translate($params, $content, $smarty, &$repeat) {
         if (isset($content)) {
            return T_($content);
         } else {
            return NULL;
         }
      }

      // Method for smarty escape quotes
      function smarty_modifier_escape_all_quotes($string) {
         return strtr($string, array("'" => "\'",'"' => '&quot;'));
      }

      // register with smarty
      $this->smarty->registerPlugin("block", "t", "smarty_translate");
      $this->smarty->registerPlugin("modifier", "escape_all_quotes", "smarty_modifier_escape_all_quotes");
      
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * Assign the key to value
    * @param string $key The key
    * @param string $value The value
    */
   public function assign($key, $value) {
      $this->smarty->assign($key, $value);
   }

   /**
    * Display the template
    * @param string $template the template to be displayed
    */
   public function display($template) {
      if (self::$logger->isEnabledFor(LoggerLevel::getLevelInfo())) {
         $generatedTime = round(microtime(true) - $this->smarty->start_time, 3);

         $peakMemAlloc = Tools::bytesToSize1024(memory_get_peak_usage(true));
         $memUsage     = Tools::bytesToSize1024(memory_get_usage(true));

         self::$logger->info('PERF: ' . $generatedTime . 'sec | Mem ' . $memUsage . ' | Peak ' . $peakMemAlloc . ' --- ' . $_SERVER['PHP_SELF'] . ' --- ');
      }
      SqlWrapper::getInstance()->logStats();
      /*
            IssueCache::getInstance()->logStats();
            ProjectCache::getInstance()->logStats();
            UserCache::getInstance()->logStats();
            TimeTrackCache::getInstance()->logStats();
      */

      if (!headers_sent()) {
         header("Content-type: text/html; charset=UTF-8");
      } else {
         self::$logger->error("Headers already sent");
      }

      if (Tools::endsWith($template, '.html')) {
         $this->smarty->display($template);
      } else {
         $this->smarty->display($template . '.html');
      }
   }

   /**
    * Display the default template
    */
   public function displayTemplate() {
      $this->smarty->assign("year", date("Y"));
      $this->smarty->assign("codevVersion", Config::codevVersion);
      if(Tools::isConnectedUser()) {
         $this->smarty->assign("username", $_SESSION['username']);
         $this->smarty->assign("realname", $_SESSION['realname']);
      }
      $this->smarty->assign('page', $_SERVER['PHP_SELF']);
      $this->smarty->assign('ajaxPage', str_replace('.php', '', $_SERVER['PHP_SELF']).'_ajax.php');
      $this->smarty->assign('tpl_name', str_replace('.php', '', substr(strrchr($_SERVER['PHP_SELF'], '/'), 1)));
      $this->smarty->assign('mantisURL', Constants::$mantisURL);
      $this->smarty->assign('rootWebSite', Tools::getServerRootURL() . '/');
      $this->smarty->assign('locale', $_SESSION['locale']);
      $this->smarty->assign('usingBrowserIE', Tools::usingBrowserIE());

      // Tabs: apply jQuery CSS before construction to avoid ugly flash on tabs construction (FOC)
      // Note: this may change if jQuery version changes.
      $this->smarty->assign('ui_tabs_jquery', 'ui-tabs ui-widget ui-widget-content ui-corner-all');
      $this->smarty->assign('ui_tabs_jquery_ul', 'ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all');
      $this->smarty->assign('ui_tabs_jquery_li', 'ui-state-default ui-corner-top');


      $this->display('template');
   }

}

?>
