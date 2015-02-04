
<?php
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

/**
 * Description of HelloWorldIndicator
 *
 * @author lob
 */
class HelloWorldIndicator extends IndicatorPluginAbstract {

   const OPTION_IS_DATE_DISPLAYED = 'isDateDisplayed';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   //private $inputIssueSel;
   private $startTimestamp;

   // config options from Dashboard
   private $isDateDisplayed;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_COMMAND,
         self::DOMAIN_TEAM,
         self::DOMAIN_USER,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
         self::DOMAIN_ADMIN,
         self::DOMAIN_IMPORT_EXPORT,
      );
      self::$categories = array (
         self::CATEGORY_QUALITY
      );
   }

   public static function getName() {
      return T_('Hello World');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('A simple HelloWorld plugin');
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = time();
      }

      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->isDateDisplayed = true;

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("checkParams() startTimestamp=".$this->startTimestamp);
      }
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_IS_DATE_DISPLAYED, $pluginSettings)) {
            $this->isDateDisplayed = $pluginSettings[self::OPTION_IS_DATE_DISPLAYED];
         }
      }
   }


  /**
    *
    */
   public function execute() {

      $greetings = "Hello world!";

      $this->execData = array (
         'greetings' => $greetings,
         'startTimestamp' => $this->startTimestamp,
         );
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables = array(
         'helloWorldIndicator_greetings' => $this->execData['greetings'],
         'helloWorldIndicator_startDate' => Tools::formatDate("%Y-%m-%d", $this->execData['startTimestamp']),

         // add pluginSettings (if needed by smarty)
         'helloWorldIndicator_'.self::OPTION_IS_DATE_DISPLAYED => $this->isDateDisplayed,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['helloWorldIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['helloWorldIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
HelloWorldIndicator::staticInit();
