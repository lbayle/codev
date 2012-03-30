<?php /*
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
*/ ?>

<?php
// ==============================================================
class UserCache {

    private static $logger;

    // instance de la classe
    private static $instance;
    private static $objects;
    private static $callCount;
    private static $cacheName;

    // Un constructeur prive ; empeche la creation directe d'objet
    private function __construct()
    {
        self::$objects = array();
        self::$callCount = array();

        self::$cacheName = __CLASS__;

        self::$logger = Logger::getLogger("cache"); // common logger for all cache classes

        #echo "DEBUG: Cache ready<br/>";
    }

    // La methode singleton
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    /**
     * get Issue class instance
     * @param $bugId
     */
    public function getUser($id)
    {
        $object = isset(self::$objects[$id]) ? self::$objects[$id]: NULL;

        if (NULL == $object) {
            self::$objects[$id] = new User($id);
            $object = self::$objects[$id];
        } else {
            if (isset(self::$callCount[$id])) {
               self::$callCount[$id] += 1;
            } else {
               self::$callCount[$id] = 1;
            }
        }
        return $object;
    }

    /**
     *
     */
    public function displayStats($verbose = FALSE) {

    	$nbObj   = count(self::$callCount);
    	$nbCalls = array_sum(self::$callCount);

      echo "=== ".self::$cacheName." Statistics ===<br/>\n";
      echo "nb objects in cache = ".$nbObj."<br/>\n";
      echo "nb cache calls      = ".$nbCalls."<br/>\n";
      if (0 != $nbObj) {
         echo "ratio               = 1:".round($nbCalls/$nbObj)."<br/>\n";
      }
      echo "<br/>\n";
      if ($verbose) {
         foreach(self::$callCount as $bugId => $count) {
            echo "cache[$bugId] = $count<br/>\n";
         }
      }
    }

    public function logStats() {
      if (self::$logger->isDebugEnabled()) {
         $nbObj   = count(self::$callCount);
         $nbCalls = array_sum(self::$callCount);
         $ratio = (0 != $nbObj) ? "1:".round($nbCalls/$nbObj) : '';

         self::$logger->debug(self::$cacheName." Statistics : nbObj=$nbObj nbCalls=$nbCalls ratio=$ratio");
      }
    }


} // class Cache

?>