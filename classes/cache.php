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

require_once('lib/log4php/Logger.php');

/**
 * A cache pattern (singleton / factory)
 */
abstract class Cache {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * @var Cache class instances
    */
   private static $instance = array();

   /**
    * @var mixed[] The list
    */
   private $objects;

   /**
    * @var int Number of calls for each object
    */
   private $callCount;

   /**
    * @var string The cache name
    */
   private $cacheName;

   /**
    * Private constructor to respect the singleton pattern
    * @param string $cacheName The cache name
    */
   private function __construct($cacheName) {
      $this->objects = array();
      $this->callCount = array();
      $this->cacheName = $cacheName;

      self::$logger = Logger::getLogger("cache"); // common logger for all cache classes

      #echo "DEBUG: Cache ready<br/>";
   }

   /**
    * The singleton pattern
    * @static
    * @param mixed $cache The cache type
    * @return Cache
    */
   protected static function createInstance($cache) {
      if (!array_key_exists($cache,self::$instance)) {
         $c = $cache;
         self::$instance[$cache] = new $c($cache);
      }
      return self::$instance[$cache];
   }

   /**
    * Get object class instance
    * @param int $id The id
    * @return mixed The object attached to the id
    */
   protected function get($id) {
      $obj = isset($this->objects[$id]) ? $this->objects[$id] : NULL;

      if (NULL == $obj) {
         $this->objects[$id] = $this->create($id);
         $obj = $this->objects[$id];
         #echo "DEBUG: CommandCache add $cmdid<br/>";
      } else {
         if (isset($this->callCount[$id])) {
            $this->callCount[$id] += 1;
         } else {
            $this->callCount[$id] = 1;
         }
         #echo "DEBUG: CommandCache called $cmdid<br/>";
      }

      return $obj;
   }

   /**
    * Create object
    * Must be implement by the children classes
    * @abstract
    * @param int $id The id
    * @return mixed The object
    */
   protected abstract function create($id);

   /**
    * Display stats
    * @param bool $verbose
    */
   public function displayStats($verbose = FALSE) {
      $nbObj = count($this->callCount);
      $nbCalls = array_sum($this->callCount);

      echo '=== '.$this->cacheName." Statistics ===<br/>\n";
      echo 'nb objects in cache = '.$nbObj."<br/>\n";
      echo 'nb cache calls      = '.$nbCalls."<br/>\n";
      if (0 != $nbObj) {
         echo 'ratio               = 1:'.round($nbCalls/$nbObj)."<br/>\n";
      }
      echo "<br/>\n";
      if ($verbose) {
         foreach($this->callCount as $id => $count) {
            echo 'cache['.$id.'] = '.$count."<br/>\n";
         }
      }
   }

   /**
    * Log stats
    */
   public function logStats() {
      if (self::$logger->isDebugEnabled()) {
         $nbObj = count($this->callCount);
         $nbCalls = array_sum($this->callCount);
         $ratio = (0 != $nbObj) ? '1:'.round($nbCalls/$nbObj) : '';

         self::$logger->debug($this->cacheName.' Statistics : nbObj='.$nbObj.' nbCalls='.$nbCalls.' ratio='.$ratio);
      }
   }

}
