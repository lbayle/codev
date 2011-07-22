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

// LoB 18 Feb 2011
// =======================================



// =======================================
class Holiday {
   var $id;
	var $timestamp;
	var $description;
	var $color;

   // ---------------------------------------
	public function Holiday($id, $timestamp, $description="", $color="D8D8D8") {
      $this->id    = $id;
      $this->timestamp  = $timestamp;
      $this->description  = $description;
      $this->color = $color;

      #echo "DEBUG Holiday $this->id - ".date("d M Y", $this->timestamp)." $this->description $this->color<br/>";
    }
}

// =======================================
class Holidays {



   // instance de la classe
    private static $instance;
	
    private static $HolidayList;
    public  static $defaultColor="D8D8D8";
   
      // Un constructeur privé ; empêche la création directe d'objet
    private function __construct() 
    {
	  #echo "DEBUG Holiday construct<br/>";
    	
      self::$HolidayList = array();
   	
      $query = "SELECT * FROM `codev_holidays_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $h = new Holiday($row->id, $row->date, $row->description, $row->color);
         self::$HolidayList[$row->id] = $h;
      }
    }

    // La méthode singleton
    public static function getInstance() 
    {
    	#echo "DEBUG Holiday instance<br/>";
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
   
	// ---------------------------------------
 
   /**
    *
    * @param unknown_type $timestamp
    */
   private function getHoliday($timestamp) {
   	
   	foreach (self::$HolidayList as $h) {
   		if ($h->timestamp == $timestamp) {
            #echo "DEBUG Holiday found  ".date("d M Y", $h->timestamp)."  - ".date("d M Y", $timestamp)."  $h->description<br/>";
   			return $h;
   		}
   	}
      #echo "DEBUG Holiday NOT found  ".date("d M Y", $timestamp)."   $timestamp<br/>";
   	return NULL;
   }

   // ---------------------------------------
   /**
    * returns a Holiday instance or NULL
    * @param unknown_type $timestamp
    */
   public function isHoliday($timestamp) {

      // is in fixed holidays table ?
      $h = $this->getHoliday($timestamp);
      if (NULL != $h) {
         return $h;
      }

   	// is saturday or sunday ?
      $dayOfWeek = date("N",$timestamp);
      if ($dayOfWeek > 5) {
      	$h = new Holiday(0, $timestamp);
      	return $h;
      }
      return NULL;
   }

   /**
    *
    * @param $startTimestamp
    * @param $endTimestamp
    */
   public function getNbHolidays($startT, $endT) {
      $nbHolidays = 0;

      $timestamp = mktime(0, 0, 0, date("m", $startT), date("d", $startT), date("Y", $startT));

      while ($timestamp <= $endT) {

         $h = $this->isHoliday($timestamp);
         if (NULL != $h) { $nbHolidays++; }

         $timestamp = strtotime("+1 day",$timestamp);;
      }

   	#echo "DEBUG nbHolidays = $nbHolidays<br/>";
   	return $nbHolidays;
   }

} // class

?>