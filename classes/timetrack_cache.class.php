<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
// ==============================================================
class TimeTrackCache {
   
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
    public function getTimeTrack($id)
    {
        $object = self::$objects[$id];
        
        if (NULL == $object) {
            self::$objects[$id] = new TimeTrack($id);
            $object = self::$objects[$id];
        } else {
            self::$callCount[$id] += 1;
        }
        return $object;
    }
    
    /**
     * 
     */
    public function displayStats() {
    	echo "=== ".self::$cacheName." Statistics ===<br/>\n";
    	echo "nb objects in cache : ".count(self::$callCount)."<br/>\n";
      echo "nb cache calls     : ".array_sum(self::$callCount)."<br/>\n";
      echo "<br/>\n";
      foreach(self::$callCount as $id => $count) {
      	echo "cache[$id] = $count<br/>\n";
      }
    	
    }
    
    
} // class Cache

?>