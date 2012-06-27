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

require_once('issue.class.php');

class IssueCache {

   private static $logger;

   // class instances
   private static $instance;

   private static $objects;
   private static $callCount;
   private static $cacheName;

   /**
    * Private constructor to respect the singleton pattern
    */
   private function __construct() {
      self::$objects = array();
      self::$callCount = array();

      self::$cacheName = __CLASS__;

      self::$logger = Logger::getLogger("cache"); // common logger for all cache classes

      #echo "DEBUG: Cache ready<br/>";
   }

   /**
    * The singleton pattern
    * @static
    * @return IssueCache
    */
   public static function getInstance() {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }

   /**
    * Get Issue class instance
    * @param int $bugId The issue id
    * @return Issue The issue attached to the id
    */
   public function getIssue($bugId) {
      $issue = isset(self::$objects[$bugId]) ? self::$objects[$bugId] : NULL;

      if (NULL == $issue) {
         if (Issue::exists($bugId)) {
            self::$objects[$bugId] = new Issue($bugId);
            $issue = self::$objects[$bugId];
            #echo "DEBUG: IssueCache add $bugId<br/>";
         } else {
            $e = new Exception("IssueCache.getIssue($bugId) : Issue not found in Mantis DB.");
            throw $e;
         }
      } else {
         if (isset(self::$callCount[$bugId])) {
            self::$callCount[$bugId] += 1;
         } else {
            self::$callCount[$bugId] = 1;
         }
         #echo "DEBUG: IssueCache called $bugId<br/>";
      }
      return $issue;
   }

   /**
    * Display stats
    * @param bool $verbose
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

   /**
    * Log stats
    */
   public function logStats() {
      if (self::$logger->isDebugEnabled()) {
         $nbObj   = count(self::$callCount);
         $nbCalls = array_sum(self::$callCount);
         $ratio = (0 != $nbObj) ? "1:".round($nbCalls/$nbObj) : '';

         self::$logger->debug(self::$cacheName." Statistics : nbObj=$nbObj nbCalls=$nbCalls ratio=$ratio");
      }
   }

}

?>
