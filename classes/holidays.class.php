<?php

// MANTIS CoDev 

// LoB 18 Feb 2011
// =======================================



// =======================================
class Holiday {
   var $id;
	var $timestamp;
	var $description;
	var $color;
	
   // ---------------------------------------
	public function Holiday($id, $timestamp, $description="", $color="#D8D8D8") {
      $this->id    = $id;
      $this->timestamp  = $timestamp;
      $this->description  = $description;
      $this->color = $color;
      
      #echo "DEBUG Holiday $this->id - ".date("d M Y", $this->timestamp)." $this->description $this->color<br/>";
    }
}

// =======================================
class Holidays {

   var $HolidayList;
   var $defaultColor="#D8D8D8";
	// ---------------------------------------
   public function Holidays() {
      
   	$this->HolidayList = array();
   	
      $query = "SELECT * FROM `codev_holidays_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $h = new Holiday($row->id, $row->date, $row->description, $row->color);
         $this->HolidayList[$row->id] = $h;
      }
   }

   /**
    * 
    * @param unknown_type $timestamp
    */
   private function getHoliday($timestamp) {
   	
   	foreach ($this->HolidayList as $h) {
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
   
   
} // class

?>