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
 * FDJ UO
 */
class UniteOeuvre {

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
    * @static
    * @param int $timeTrackId
    * @param float $value
    * @return int
    */
   public static function create($timeTrackId, $value) {
      if ((0 == $timeTrackId) ||
          (0 == $value)) {
         self::$logger->error("create UO : timeTrackId = $timeTrackId, value = $value");
         return 0;
         }

      $query = "INSERT INTO `codev_uo_table`  (`timeTrackId`, `value`) VALUES ('$timeTrackId','$value');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: UO Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * @static
    * Remove the current track
    * @return bool True if the track is removed
    */
   public static function remove($trackId) {
      $query = "DELETE FROM `codev_uo_table` WHERE timetrackid=$trackId;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         return false;
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("UO deleted");
         }
         return true;
      }
   }

   /**
    * @static
    * @param type $trackid
    * @return string
    */
   public static function getUO($trackid) {
      $query = "SELECT `value` FROM `codev_uo_table` WHERE timetrackid=$trackid;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      if (0 == SqlWrapper::getInstance()->sql_num_rows($result)) {
         //$e = new Exception("UO not defined for timetrack_id = $trackid");
         //self::$logger->error("EXCEPTION UO::getUO(): ".$e->getMessage());
         //self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         //throw $e;
         return "";
      }
      $value = SqlWrapper::getInstance()->sql_result($result, 0);
      return $value;
   }

   /**
    * @static
    * @param type $trackid
    * @param type $UOValue
    */
   public static function setUO($trackid, $UOValue) {
      $query = "UPDATE `codev_uo_table` SET `value` = $UOValue WHERE `codev_uo_table`.`timetrackid` = $trackid;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }
   
   
   public static function getDriftColor($bugResolvedStatusThreshold, $currentStatus ,$drift = NULL) {
      if (0 < $drift) {
         if ($currentStatus < $bugResolvedStatusThreshold) {
            $color = "#ff6a6e";
         } else {
            $color = "#fcbdbd";
         }
      } elseif (0 > $drift) {
         if ($currentStatus < $bugResolvedStatusThreshold) {
            $color = "#61ed66";
         } else {
            $color = "#bdfcbd";
         }
      } else {
         $color = NULL;
      }

      return $color;
   }
   
}

UniteOeuvre::staticInit();
