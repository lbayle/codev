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

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

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
   public function __construct($id, $timestamp, $description="", $color="D8D8D8") {
      $this->id = $id;
      $this->timestamp = $timestamp;
      $this->description = $description;
      $this->color = $color;

   }
}

Holiday::staticInit();

class Holidays {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * @var Holidays The instance
    */
   private static $instance;

   /**
    * @var Holiday[int] The holiday list
    */
   private static $HolidayList;

   /**
    * @var string The default color
    */
   public static $defaultColor = "D8D8D8";

   /**
    * Private constructor to respect the singleton pattern
    */
   private function __construct() {
      self::$HolidayList = array();

      $sql = AdodbWrapper::getInstance();
      $query = 'SELECT * FROM codev_holidays_table';
      $result = $sql->sql_query($query);

      while($row = $sql->fetchObject($result)) {
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
    * Get holiday
    * @param int $timestamp
    * @return Holiday
    */
   private function getHoliday($timestamp) {
      foreach (self::$HolidayList as $h) {
         if ($h->timestamp == $timestamp) {
            if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
               self::$logger->trace("Holiday found  ".date("d M Y", $h->timestamp)."  - ".date("d M Y", $timestamp)."  $h->description");
            }
            return $h;
         }
      }
      if(self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
         self::$logger->trace("No Holiday defined for on: ".date("d M Y", $timestamp)."   $timestamp");
      }
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
    * Get number of holidays
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

         $timestamp = strtotime("+1 day",$timestamp);
      }

      return $nbHolidays;
   }

   /**
    * Get the number of workdays
    * @param int $startT The included start day
    * @param int $endT The included end day
    * @return int Number of workdays in the period
    */
   public function getWorkdays($startT, $endT) {

      #$nbDays = floor(strtotime("+1 day",$endT - $startT) / (60 * 60 * 24));
      $date1 = new DateTime(date("Y-m-d", $startT));
      $date2 = new DateTime(date("Y-m-d", $endT));
      $nbDays = $date2->diff($date1)->format("%a") + 1;

      $nbHolidays = $this->getNbHolidays($startT, $endT);
       return $nbDays - $nbHolidays;
   }

   /**
    * Get holidays
    * @static
    * @return mixed[int]
    */
   public static function getHolidays() {
      $sql = AdodbWrapper::getInstance();
      $query = 'SELECT * FROM codev_holidays_table ORDER BY date DESC';
      try {
         $result = $sql->sql_query($query);
      } catch (Exception $e) {
         return NULL;
      }

      $holidays = array();
      while($row = $sql->fetchObject($result)) {
         $holidays[$row->id] = array(
            "date" => Tools::formatDate("%d %b %Y (%a)", $row->date),
            "desc" => $row->description,
            "color" => $row->color
         );
      }

      return $holidays;
   }

   /**
    * Save to DB
    * @static
    * @param string $timestamp
    * @param string $hol_desc
    * @param string $hol_color
    * @return resource
    */
   public static function save($timestamp, $hol_desc, $hol_color) {
      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO codev_holidays_table (date, description, color)".
               " VALUES (".$sql->db_param().",".$sql->db_param().",".$sql->db_param().")";
      return $sql->sql_query($query, array($timestamp, $hol_desc, $hol_color));
   }

   /**
    * Delete from DB
    * @static
    * @param int $hol_id
    * @return resource
    */
   public static function delete($hol_id) {
      $sql = AdodbWrapper::getInstance();
      $query = 'DELETE FROM codev_holidays_table WHERE id='.$sql->db_param().';';
      $result = $sql->sql_query($query, array($hol_id));
      return $result;
   }

}

Holidays::staticInit();

