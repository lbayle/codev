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

require_once('Logger.php');

/**
 * @author LoB
 * @date 18 Feb 2011
 */
class Holiday {

   /**
    * @var Logger The logger
    */
   private $logger;

   /**
    * @var int The id
    */
   var $id;

   /**
    * @var int The timestamp
    */
   var $timestamp;

   /**
    * @var string The description
    */
   var $description;

   /**
    * @var string The color
    */
   var $color;

   /**
    * @param int $id
    * @param int $timestamp
    * @param string $description
    * @param string $color
    */
   public function Holiday($id, $timestamp, $description="", $color="D8D8D8") {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->id = $id;
      $this->timestamp = $timestamp;
      $this->description = $description;
      $this->color = $color;

      $this->logger->debug("Holiday $this->id - ".date("d M Y", $this->timestamp)." $this->description $this->color");
   }
}

class Holidays {

   /**
    * @var Logger The logger
    */
   private $logger;

   // class instances
   private static $instance;

   /**
    * @var Holiday[int] The holiday list
    */
   private static $HolidayList;

   /**
    * @var string The default color
    */
   public static $defaultColor="D8D8D8";

   /**
    * Private constructor to respect the singleton pattern
    */
   private function __construct() {
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
      while($row = mysql_fetch_object($result)) {
         self::$HolidayList[$row->id] = new Holiday($row->id, $row->date, $row->description, $row->color);
      }
   }

   /**
    * The singleton pattern
    * @static
    * @return Holidays
    */
   public static function getInstance() {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }

   /**
    * @param int $timestamp
    * @return Holiday
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

   /**
    * returns a Holiday instance or NULL
    * @param int $timestamp
    * @return Holiday
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
    * @param int $startT
    * @param int $endT
    * @return int
    */
   public function getNbHolidays($startT, $endT) {
      $nbHolidays = 0;

      $timestamp = mktime(0, 0, 0, date("m", $startT), date("d", $startT), date("Y", $startT));

      while ($timestamp <= $endT) {
         $h = $this->isHoliday($timestamp);
         if (NULL != $h) {
            $nbHolidays++;
         }

         $timestamp = strtotime("+1 day",$timestamp);;
      }

      $this->logger->debug("nbHolidays = $nbHolidays");
      return $nbHolidays;
   }

}

?>
