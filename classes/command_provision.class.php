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

/**
 * Description of CommandProvision
 *
 */
class CommandProvision {

   /**
    * @var Logger The logger
    */
   private static $logger;

   const provision_mngt = 2;
   const provision_risk = 3;
   const provision_guarantee = 4;
   const provision_quality = 5;
   const provision_other = 99;

   // TODO i18n for constants
   public static $provisionNames = array(
      self::provision_mngt => "Management",
      self::provision_risk => "Risk",
      self::provision_guarantee => "Guarantee",
      self::provision_quality => "Quality",
      self::provision_other => "Other",
   );

   
   private $id;
   private $date;
   private $command_id;
   private $type;
   private $budget_days;
   private $budget;
   private $currency;
   private $average_daily_rate;
   private $summary;
   private $description;
   private $isInCheckBudget;



   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function __construct($id, $row = NULL) {

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a CommandProvision with id=0 is not allowed.");
         self::$logger->error("EXCEPTION CommandProvision constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize($row);
   }

   public function initialize($row) {

      if(is_null($row)) {
         $query  = "SELECT * FROM `codev_command_provision_table` WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            //$e = new Exception("Query FAILED: $query");
            //throw $e;
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);

         if (FALSE == $row) {
            $e = new Exception("Unknown CommandProvision id ".$this->id);
            throw $e;
         }
      }

      $this->command_id = $row->command_id;
      $this->date = $row->date;
      $this->type = $row->type;
      $this->budget_days = $row->budget_days;
      $this->budget = $row->budget;
      $this->currency = $row->currency;
      $this->average_daily_rate = $row->average_daily_rate;
      $this->summary = $row->summary;
      $this->description = $row->description;
      $this->isInCheckBudget = (1 == $row->is_in_check_budget);

   }

   public static function create($command_id, $timestamp, $type, $summary, $budget_days, $budget, $average_daily_rate, $isInCheckBudget, $currency = 'EUR', $description = NULL) {

      $budget_cent = floatval($budget) * 100;
      $budgetDays_cent = floatval($budget_days) * 100; // store 1.15 days in an int
      $adr_cent = floatval($average_daily_rate) * 100;
      $formattedIsInCheckBudget = $isInCheckBudget ? 1 : 0;
      $query = "INSERT INTO `codev_command_provision_table` ".
              " (`command_id`, `date`, `type`, `budget_days`, `budget`, `average_daily_rate`, `currency`, `summary`, `is_in_check_budget` ";
      if(!is_null($description)) { $query .= ", `description`"; }
      $query .= ") VALUES ($command_id, $timestamp, $type, $budgetDays_cent, $budget_cent, $adr_cent, '$currency', '$summary', '$formattedIsInCheckBudget' ";
      if(!is_null($description)) { $query .= ", '$description'"; }
      $query .= ");";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_insert_id();
   }

   public static function delete($id) {
      $query = "DELETE FROM `codev_command_provision_table` WHERE `id` = $id";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function getDate() {
      return $this->date;
   }

   public function setDate($timestamp) {
      if($this->date != $timestamp) {
         $this->date = $timestamp;
         $query = "UPDATE `codev_command_table` SET start_date = '$timestamp' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    * 
    * @return int
    */
   public function getCommandId() {
      return $this->command_id;
   }

   public function getCommandName() {
      $cmd = CommandCache::getInstance()->getCommand($this->command_id);
      return $cmd->getName();
   }

   /**
    *
    * @param int $value
    */
   public function setCommandId($value) {
      if($this->command_id != $value) {
         $this->command_id = $value;
         $query = "UPDATE `codev_command_provision_table` SET command_id = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return int
    */
   public function getType() {
      return $this->type;
   }

   /**
    *
    * @param int $value
    */
   public function setType($value) {

      if($this->type != $value) {

         // TODO check $value in array_keys(self::$provisionNames)

         $this->type = $value;
         $query = "UPDATE `codev_command_provision_table` SET type = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return int
    */
   public function getProvisionDays() {
      return ($this->budget_days / 100);
   }

   /**
    *
    * @param int $value
    */
   public function setBudgetDays($value) {
      if($this->budget_days != floatval($value) * 100) {
         $this->budget_days = floatval($value) * 100;
         $query = "UPDATE `codev_command_provision_table` SET budget_days = '$this->budget_days' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return float
    */
   public function getProvisionBudget() {
      return ($this->budget / 100);
   }

   /**
    *
    * @param float $value
    */
   public function setBudget($value) {
      if($this->budget_days != floatval($value) * 100) {
         $this->budget_days = floatval($value) * 100;
         $query = "UPDATE `codev_command_provision_table` SET budget = '$this->budget_days' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return int
    */
   public function getAverageDailyRate() {
      return ($this->average_daily_rate / 100);
   }

   /**
    *
    * @param int $value
    */
   public function setAverageDailyRate($value) {
      if($this->average_daily_rate != floatval($value) * 100) {
         $this->average_daily_rate = floatval($value) * 100;
         $query = "UPDATE `codev_command_provision_table` SET average_daily_rate = '$this->average_daily_rate' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return int
    */
   public function getCurrency() {
      return $this->currency;
   }

   /**
    *
    * @param int $value
    */
   public function setCurrency($value = 'EUR') {
      if($this->currency != $value) {
         $this->currency = $value;
         $query = "UPDATE `codev_command_provision_table` SET currency = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    *
    * @return int
    */
   public function getSummary() {
      return $this->summary;
   }

   /**
    *
    * @param int $value
    */
   public function setSummary($value) {
      if($this->summary != $value) {
         $this->summary = $value;
         $query = "UPDATE `codev_command_provision_table` SET summary = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    * @return bool isEnabled
    */
   public function isInCheckBudget() {
      return $this->isInCheckBudget;
   }

   /**
    * @param bool $isEnabled
    */
   public function setIsInCheckBudget($isInCheckBudget) {
      $this->isInCheckBudget = $isInCheckBudget;

      $value = ($isInCheckBudget) ? '1' : '0';

      $query = "UPDATE `codev_command_provision_table` SET is_in_check_budget = '$value' WHERE id = ".$this->id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }


   /**
    *
    * @return int
    */
   public function getDescription() {
      return $this->description;
   }

   /**
    *
    * @param int $value
    */
   public function setDescription($value) {
      if($this->description != $value) {
         $this->description = $value;
         $query = "UPDATE `codev_command_provision_table` SET description = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

}

// Initialize complex static variables
CommandProvision::staticInit();
?>
