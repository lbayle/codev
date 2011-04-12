<?php if (!isset($_SESSION)) { session_start(); } ?>

<?php
// ==============================================================
class ProjectCache {
   
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
    public function getProject($id)
    {
        $object = self::$objects[$id];
        
        if (NULL == $object) {
            self::$objects[$id] = new Project($id);
            $object = self::$objects[$id];
        } else {
            self::$callCount[$id] += 1;
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
         echo "ratio               = 1:".number_format($nbCalls/$nbObj, 0)."<br/>\n";
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