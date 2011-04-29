<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php
// ==============================================================
class IssueCache {
   
    // instance de la classe
    private static $instance;
    private static $objects;
    private static $callCount;
    private static $cacheName;

    // Un constructeur privé ; empêche la création directe d'objet
    private function __construct() 
    {
        self::$objects = array();
        self::$callCount = array();
        
        self::$cacheName = __CLASS__;
        #echo "DEBUG: Cache ready<br/>";
    }

    // La méthode singleton
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
    public function getIssue($bugId)
    {
        $issue = self::$objects[$bugId];
        
        if (NULL == $issue) {
            self::$objects[$bugId] = new Issue($bugId);
            $issue = self::$objects[$bugId];
            
            #echo "DEBUG: IssueCache add $bugId<br/>";
        } else {
            self::$callCount[$bugId] += 1;
        	   #echo "DEBUG: IssueCache called $bugId<br/>";
        }
        return $issue;
    }
    
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
        
    
} // class Cache

?>