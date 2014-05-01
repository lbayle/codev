<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This class provides information to the IndicatorPlugins.
 *
 * It is capable of retrieving general information from CodevTT/Mantis,
 * and some specific information may be add by the controler of the displayed page. 
 * 
 * The PluginDataProvider should be instanciated by the page controler.
 * The Controler does not know which Plugins are loaded
 * 
 * 
 * IndicatorPlugins will receive a reference to the PluginDataProvider and will
 * NOT get information from any other way: The PluginDataProvider is the only
 * interface between the CodevTT kernel and the plugins.
 * 
 * The PluginDataProvider class is part of the CodevTT kernel (GPL v3),
 * while the IndicatorPlugins may be under other licenses (including non-open-source
 * licenses). A PluginDataProviderInterface has an MIT license so that non-open-source
 * plugins are not under the GPL license of the PluginDataProvider.
 * 
 * 
 * @author lbayle
 */
class PluginDataProvider implements PluginDataProviderInterface {

   /**
    * Singleton
    * @var PluginDataProvider
    */
   private static $instance;

   private static $logger;

   /**
    *
    * @var array
    */
   private $params;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * The singleton pattern
    * @static
    * @return IssueCache
    */
   public static function getInstance() {
      if (NULL == self::$instance) {
         self::$instance = new PluginDataProvider();


      }
      return self::$instance;
   }

   /**
    * Private constructor to respect the singleton pattern
    * @param string $cacheName The cache name
    */
   private function __construct() {

      self::$logger = Logger::getLogger(__CLASS__); // common logger for all cache classes

      #echo "DEBUG: Cache ready<br/>";
   }



   /**
    * used by plugins
    * 
    * @return String
    */
   public function getCodevVersion() {
      return Config::codevVersion;
   }
   
   /**
    * used by plugins
    * returns the value or NULL if not found
    * 
    * @param String $key
    * @return int
    */
   public function getParam($key) {
      return $this->params[$key];
   }

   /**
    * used by the page Controler
    * must NOT be used by plugins
    * 
    * Note: The Controler should not compute/provide all the data that *may* be used
    * by plugins as it does not know which data will be used.
    * so this method should only be used by the Controler if:
    * - this data will certainly be used
    * - providing the data is not time/CPU consuming
    * ex: CommandName
    * 
    * The controler should provide methods that will get the data on demand,
    * and these methods will be called by the PluginDataProvider.
    * 
    * @param type $key
    * @param type $value
    * @return \IndicatorPluginManager
    */
   public function setParam($key, $value) {
      $this->params[$key] = $value;
      return $this;
   }
   
}

// Initialize complex static variables
PluginDataProvider::staticInit();
