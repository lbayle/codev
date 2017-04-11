<?php

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/


class UserDailyCost {
   private static $logger;

   /**
    * UDCs for $this teamid only !
    * @var array array[userid][timestamp] = { 'id', 'timestamp', 'udr', 'currency', 'description' }
    */
   private $userDailyCosts;
   
   private $teamid;
   private $teamCurrency;
   private $teamADC;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   /**
    * instanciated by the Team class
    *
    * @param type $teamid
    * @throws Exception
    */
   public function __construct($teamid) {

      $this->teamid = $teamid;

      $query0 = "SELECT currency, average_daily_cost FROM codev_team_table WHERE id = ".$this->teamid;
      $result0 = SqlWrapper::getInstance()->sql_query($query0);
      if (!$result0) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if(SqlWrapper::getInstance()->sql_num_rows($result0) == 1) {
         $row = SqlWrapper::getInstance()->sql_fetch_object($result0);

         $this->teamCurrency = $row->currency;

         if (NULL !== $row->average_daily_cost) {
            $this->teamADC = floatval($row->average_daily_cost) / 100;
         }

      } else {
         // team should exist, as UserDailyCost is instanciated by the Team class
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception('The team '.$this->id." doesn't exist !");
         self::$logger->error("EXCEPTION UserDailyCost constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      // ----------
      $this->userDailyCosts = array();

      $query1 = "SELECT id, user_id, start_date, daily_rate, currency, description FROM codev_userdailycost_table ".
                " WHERE team_id = ".$this->teamid.
                " ORDER BY user_id, start_date DESC"; // ORDER BY start_date DESC is very important !
      $result1 = SqlWrapper::getInstance()->sql_query($query1);
      if (!$result1) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result1)) {
         
         //convert UDC 12350 => 123.5 (2 decimals)
         $udr = floatval($row->daily_rate) / 100;
         
         $values = array(
             'id'       => $row->id,
             'timestamp' => $row->start_date,
             'udr'       => $udr,
             'currency'  => $row->currency,
             'description' => $row->description,
         );
         
         // NOTE: append to table: start_date order is very important !
         $this->userDailyCosts[$row->user_id][$row->start_date] = $values;
      }
      #self::$logger->error($this->userDailyCosts);
      
   }

   /**
    *
    * @return array[userid][timestamp] = { 'id', 'timestamp', 'udr', 'currency', 'description' }
    */
   public function getUserDailyCosts() {
      return $this->userDailyCosts;
   }

   /**
    * 
    * @param int $userid
    * @param int $timestamp
    * @param float $userDailyCost
    * @param string $currency if unset, use teamCurrency
    * @param string $description
    * @return id of inserted row or FALSE on error
    */
   public function setUserDailyCost($userid, $timestamp, $userDailyCost, $currency = NULL, $description = NULL) {

      // TODO: if UDC already exists for this timestamp, then do nothing, return FALSE (no update)

      if (NULL === $currency) { $currency = $this->teamCurrency; }

      // TODO check floatval errors !
      // convert float to int (ex: 123.5 => 12350 (2 decimals)
      $udr = round(floatval($userDailyCost), 2) * 100;
      
      $query = "INSERT INTO `codev_userdailycost_table`  (`user_id`, `team_id`, `start_date`, `daily_rate`, `currency`, `description`) "
              . "VALUES ('".$userid."','".$this->teamid."','".$timestamp."','".$udr."','".$currency."','".$description."');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $id = SqlWrapper::getInstance()->sql_insert_id();

      // update $this->userDailyCosts
      // WARN: order (DESC) is important !!
      $values = array(
          'id'        => $id,
          'timestamp' => $timestamp,
          'udr'       => $userDailyCost,
          'currency'  => $currency,
          'description' => $description,
      );
      $myDailyRates = $this->userDailyCosts[$userid];
      $myDailyRates[$timestamp] = $values;
      krsort($myDailyRates); // order (DESC) is important !!
      $this->userDailyCosts[$userid] = $myDailyRates;


      // === update existing timetracks
      // PERF: initialy, it was planned to store the cost directly in the timetrack to avoid heavy computing,
      //       but it turns out that it is not necessary, and it is much easier to handle if UDC/ADC changes.
      // => nothing to do !

      //self::$logger->error($this->userDailyCosts);

      return $id;
   }

   /**
    * delete by id
    * @param int $id
    */
   public function deleteUserDailyCost($id) {

      $query = "DELETE FROM `codev_userdailycost_table` WHERE id = $id ;";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return true;
   }

   /**
    * strict search for an UDC definition at the given timestamp
    *
    * @param type $userid
    * @param type $timestamp
    * @return struct or FALSE if UDC not defined at this date (strictly)
    */
   public function existsUserDailyCost($userid, $timestamp) {
      if (!array_key_exists($userid, $this->userDailyCosts)) {
         return FALSE;
      }
      if (!array_key_exists($timestamp, $this->userDailyCosts[$userid])) {
         return FALSE;
      }
      return $this->userDailyCosts[$userid][$timestamp];
   }

   /**
    * returns the valid UDC struct for a date
    * if no UDC defined, returns NULL
    *
    * valid UDC is the closest start_date <= $timestamp
    *
    * Note: this method is used in admin page to check if an UDC has effectively been created
    *
    * @param int $userid team member
    * @param int $timestamp
    * @return array { 'id', 'timestamp', 'udr', 'currency' } or NULL if not found
    */
   public function getUserDailyCost($userid, $timestamp) {

      if (!array_key_exists($userid, $this->userDailyCosts)) {
         return NULL;
      }
      $userDailyCosts = $this->userDailyCosts[$userid];

      // WARN: keys (timestamp) must be ordered (DESC)
      foreach ($userDailyCosts as $start_date => $values) {
         // as long as the start_date of the UDC is > $timestamp, continue
         if ($start_date <= $timestamp) {
            // the start_date is lower than $timestamp, so this is the correct UDC to apply at this date

            //self::$logger->error("Found UDC at ".date('Y-m-d', $start_date)." applyable to ".date('Y-m-d', $timestamp));
            //self::$logger->error($values);

            unset($values['description']); // TODO remove ?
            return $values;
         }
      }
      // not found
      return NULL;
   }

   /**
    * returns the valid UDC value for a user at a specific date
    * if no UDC defined for this user, result depends on $isRaw
    *  - $isRay == TRUE  return FALSE
    *  - $isRay == FALSE return teamADR
    *
    * if $isRay == FALSE AND no teamADR defined, throw exception
    *
    * Note: this method is used to compute the cost of a timetrack
    *
    * @param int $userid team member
    * @param int $timestamp date of the timetrack
    * @param type $targetCurrency target currency (default: team currency)
    * @param type $isRaw if TRUE do not look for teamADC
    * @return float UDC in the target currency or FALSE if not found
    * @throws Exception
    */
   public function getUdcValue($userid, $timestamp, $targetCurrency=NULL, $isRaw=FALSE) {

      if (NULL === $targetCurrency) { $targetCurrency = $this->teamCurrency; }

      $udcValues = NULL;
      if (array_key_exists($userid, $this->userDailyCosts)) {
         $userDailyCosts = $this->userDailyCosts[$userid];

         // WARN: keys (timestamp) must be ordered (DESC)
         foreach ($userDailyCosts as $start_date => $values) {
            // as long as the start_date of the UDC is > $timestamp, continue
            if ($start_date <= $timestamp) {
               // the start_date is lower than $timestamp, so this is the correct UDC to apply at this date

               //self::$logger->error("Found UDC at ".date('Y-m-d', $start_date)." applyable to ".date('Y-m-d', $timestamp));
               //self::$logger->error($values);

               $udcValues = $values;
               break; // found UDC
            }
         }
      }
      if (NULL === $udcValues) {

         // if isRAW, no failover on teamADR
         if ($isRaw) { return FALSE; }

         // use teamADR if exists, else throw Exception
         if (NULL === $this->teamADC) {
            $e = new Exception("No UDC and no teamADC defined for user $userid on team $this->teamid");
            self::$logger->error("EXCEPTION getUdrValue: ".$e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
            throw $e;
         }
         //self::$logger->error("No UDC for $userid, using teamADR ($this->teamADR)");
         $udcValues = array(
             'udr'       => $this->teamADC,
             'currency'  => $this->teamCurrency
         );
      }

      // convert to target currency
      if ($targetCurrency !== $udcValues['currency']) {
         $newUdc = Currencies::getInstance()->convertValue($udcValues['udr'], $udcValues['currency'], $targetCurrency);
         //self::$logger->error("Converted UDC = ".$newUdc." $targetCurrency");
         return $newUdc;
      } else {
         //self::$logger->error("UDC = ".$udrValues['udr']);
         return $udcValues['udr'];
      }

   }

}
UserDailyCost::staticInit();

