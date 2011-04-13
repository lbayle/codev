<?php

// ==============================================================


/**
 * Constants Singleton class
 * contains CoDev settings
 * @author lbayle
 *
 */

class ConfigItem {
   
   public $id;
   public $value;
   public $type;

	public function __construct($id, $value, $type) 
   {
   	$this->id    = $id;
      $this->type  = $type;
      
      switch ($type) {
      	case Config::configType_keyValue :
      		$this->value = doubleExplode(':', ',', $value);
      		break;
         case Config::configType_array :
         	$this->value = explode(',', $value);
            break;
         default:
         	$this->value = $value;
      }
   }
	
}

class Config {
   
   const  configType_int      = 1;
   const  configType_string   = 2;
   const  configType_keyValue = 3;
   const  configType_array    = 4;
   
   private static $instance;    // singleton instance
   private static $configItems;
    
   // --------------------------------------
   private function __construct() 
   {
      self::$configItems = array();
    	
      $query = "SELECT * FROM `codev_config_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
      	#echo "DEBUG: Config:: $row->config_id<br/>";
      	self::$configItems["$row->config_id"] = new ConfigItem($row->config_id, $row->value, $row->type);
      }
    
        #echo "DEBUG: Config ready<br/>";
   }

   // --------------------------------------
   /**
    * get Singleton instance
    */
   public static function getInstance() 
   {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
   }
   
   // --------------------------------------
   public static function getValue($id) {
    	
    	$value == NULL;
    	$item = self::$configItems[$id];
    	
    	if (NULL != $item) {	
         $value = $item->value;
    	} else {
    		echo "DEBUG: Config::getValue($id): item not found !<br/>";
    	}
    	return $value;
   }
    
   // --------------------------------------
   public static function getType($id) {
      
      $type == NULL;
      $item = self::$configItems[$id];
      
      if (NULL != $item) { 
         $type = $item->type; 
      } else {
         echo "DEBUG: Config::getType($id): item not found !<br/>";
      }
      return $value;
   }
} // class

?>