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

/**
 * @author LoB
 * @date 18 Feb 2011
 */
class Holiday {

   private $logger;

   var $id;
	var $timestamp;
	var $description;
	var $color;

   // ---------------------------------------
	public function Holiday($id, $timestamp, $description="", $color="D8D8D8") {

	   $this->logger = Logger::getLogger(__CLASS__);

   	$this->id    = $id;
      $this->timestamp  = $timestamp;
      $this->description  = $description;
      $this->color = $color;

      $this->logger->debug("Holiday $this->id - ".date("d M Y", $this->timestamp)." $this->description $this->color");
    }
}

// =======================================
class Holidays {


   private $logger;

   // instance de la classe
   private static $instance;

    private static $HolidayList;
    public  static $defaultColor="D8D8D8";

      // Un constructeur prive ; empeche la creation directe d'objet
    private function __construct()
    {
      $this->logger = Logger::getLogger(__CLASS__);

      self::$HolidayList = array();

      $query = "SELECT * FROM `codev_holidays_table`";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
      while($row = mysql_fetch_object($result))
      {
         $h = new Holiday($row->id, $row->date, $row->description, $row->color);
         self::$HolidayList[$row->id] = $h;
      }
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

	// ---------------------------------------

   /**
    *
    * @param unknown_type $timestamp
    */
   private function getHoliday($timestamp) {

   	foreach (self::$HolidayList as $h) {
   		if ($h->timestamp == $timestamp) {
            $this->logger->trace("Holiday found  ".date("d M Y", $h->timestamp)."  - ".date("d M Y", $timestamp)."  $h->description");
   			return $h;
   		}
   	}
      $this->logger->trace("No Holiday defined for on: ".date("d M Y", $timestamp)."   $timestamp");
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

   	$this->logger->debug("nbHolidays = $nbHolidays");
   	return $nbHolidays;
   }

} // class

?>
