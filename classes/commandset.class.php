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
if (NULL == Logger::getConfigurationFile()) {
      Logger::configure(dirname(__FILE__).'/../log4php.xml');
      $logger = Logger::getLogger("default");
      $logger->info("LOG activated !");
   }


include_once "issue_selection.class.php";
include_once "team.class.php";



/**
 * Un commandset (ennonce de presta) est un ensemble de taches que l'on veut
 * piloter a l'aide d'indicateurs (cout, delai, qualite, avancement)
 *
 * un commandset peut contenir des taches précises (mantis)
 * mais également définir des objectifs d'ordre global ou non
 * liés au dev.
 *
 * un commandset est provisionné d'un certain budget, négocié avec le client.
 * le cout de l'ensemble des taches devrait etre a l'equilibre avec ce budget.
 */
class CommandtSet {

   const cmdType_dev   = 1;    // in table codev_commandset_cmd_table
   const cmdType_mngt  = 2;    // in table codev_commandset_cmd_table


   private $logger;

   // codev_commandset_table
   private $id;
   private $name;
   private $description;
   private $date;
   private $teamid;
   private $serviceContractId;
   private $cost;
   private $currency;
   private $budget_days;

   // list of commands, ordered by type
   // engByTypeList[type][cmdid]
   private $cmdByTypeList;


   function __construct($id) {

      $this->logger = Logger::getLogger(__CLASS__);

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating an CommandSet with id=0 is not allowed.");
         $this->logger->error("EXCEPTION CommandSet constructor: ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize();
   }

   private function initialize() {
      // ---
      $query  = "SELECT * FROM `codev_commandset_table` WHERE id=$this->id ";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = mysql_fetch_object($result);
      $this->name        = $row->name;
      $this->description = $row->description;
      $this->date        = $row->date;
      $this->teamid      = $row->team_id;
      $this->budget_days = $row->budget_days;
      $this->cost        = $row->budget;
      $this->currency    = $row->currency;

      // ---
      $this->engByTypeList = array();
      $query  = "SELECT * FROM `codev_commandset_cmd_table` ".
                "WHERE commandset_id=$this->id ";
                "ORDER BY type ASC, command_id ASC";

      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = mysql_fetch_object($result))
      {
         if (NULL == $this->engByTypeList["$row->type"]) {
            $this->engByTypeList["$row->type"] = array();
         }
          $this->engByTypeList["$row->type"][] = $row->command_id;
      }
   }

   public function getId() {
      return $this->id;
   }

   public function getTeamid() {
      return $this->teamid;
   }
   public function setTeamid($value) {

      $this->teamid = $value;
      $query = "UPDATE `codev_commandset_table` SET team_id = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }

   public function getName() {
      return $this->name;
   }
   public function setName($name) {
      $formattedValue = mysql_real_escape_string($name);
      $this->name = $formattedValue;
      $query = "UPDATE `codev_commandset_table` SET name = '$formattedValue' WHERE id='$this->id' ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getDesc() {
      return $this->description;
   }
   public function setDesc($description) {
      $formattedValue = mysql_real_escape_string($description);
      $this->description = $formattedValue;
      $query = "UPDATE `codev_commandset_table` SET description = '$formattedValue' WHERE id='$this->id' ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getDate() {
      return $this->date;
   }
   public function setDate($value) {
      $formattedValue = mysql_real_escape_string($value);
      $this->date = date2timestamp($formattedValue);
      $query = "UPDATE `codev_commandset_table` SET date = '$this->date' WHERE id='$this->id' ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getBudgetDays() {
      return $this->budget_days;
   }
   public function setBudgetDays($value) {

      $this->budget_days = $value;
      $query = "UPDATE `codev_commandset_table` SET budget_days = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }


   public function getCost() {
      return $this->cost;
   }
   public function setCost($value) {

      $this->cost = $value;
      $query = "UPDATE `codev_commandset_table` SET budget = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }


   public function getCurrency() {
      return $this->currency;
   }
   public function setCurrency($value) {

      $this->currency = $value;
      $query = "UPDATE `codev_commandset_table` SET currency = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }








   /**
    *
    * @param int $type
    * @return array cmdid => Command
    */
   public function getCommands($type) {

      $cmdList = array();

      $cmdidList = $this->engByTypeList[$type];

      foreach ($cmdidList as $cmdid) {

         $cmdList[$cmdid] = CommandCache::getInstance()->getCommand($cmdid);
      }

      return $cmdList;
   }


   /**
    * create a new commandset in the DB
    *
    * @return int $id
    */
   public static function create($name, $date, $teamid) {
    $query = "INSERT INTO `codev_commandset_table`  (`name`, `date`, `team_id`) ".
             "VALUES ('$name','$date', '$teamid');";
    $result = mysql_query($query);
    if (!$result) {
       $this->logger->error("Query FAILED: $query");
       $this->logger->error(mysql_error());
       echo "<span style='color:red'>ERROR: Query FAILED</span>";
       exit;
    }
    $id = mysql_insert_id();
    return $id;
   }

   /**
    * add Command to commandset (in DB & current instance)
    *
    * @param int $bugid
    */
   public function addCommand($cmdid, $type) {

      try {
         CommandCache::getInstance()->getCommand($cmdid);
      } catch (Exception $e) {
         $this->logger->error("addCommand($cmdid): Command $cmdid does not exist !");
         echo "<span style='color:red'>ERROR: Command  '$cmdid' does not exist !</span>";
         return NULL;
      }

      $this->logger->debug("Add command $cmdid to commandset $this->id");

      if (NULL == $this->engByTypeList["$type"]) {
         $this->engByTypeList["$type"] = array();
      }
      $this->engByTypeList["$type"][] = $cmdid;

      $query = "INSERT INTO `codev_commandset_cmd_table` (`commandset_id`, `command_id`, `type`) VALUES ('$this->id', '$cmdid', '$type');";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $id = mysql_insert_id();
      return $id;

   }

   /**
    * remove command from commandset commandList.
    * the Command itself is not deleted.
    *
    * @param int $cmdid
    */
   public function removeCommand($cmdid) {

      $typeList = array_keys($this->engByTypeList);

      foreach ($typeList as $type) {
         if (NULL != $this->engByTypeList[$type][$cmdid]) {
            unset($this->engByTypeList[$type][$cmdid]);
            # break;
         }
      }


      $query = "DELETE FROM `codev_commandset_cmd_table` WHERE commandset_id='$this->id' AND command_id='$cmdid';";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    *
    */
   public function getContractService() {

      if (NULL == $this->serviceContractId) {

         $query  = "SELECT * FROM `codev_servicecontract_srv_table` WHERE commandset_id=$this->id ";
         $result = mysql_query($query);
         if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // can a CommandSet belong to more than one servicecontract ?
         while($row = mysql_fetch_object($result)) {

            $this->serviceContractId = $row->servicecontract_id;
            $this->logger->debug("CommandSet $this->id is in servicecontract $this->serviceContractId");
         }
      }
      return $this->serviceContractId;
   }


   /**
    *
    * @param int $value servicecontractid. if NULL or '0' then remove association
    * @param int $type
    *
    */
   public function setContractService($value, $type = 1) {

      if ((NULL == $value) || (0 == $value)) {

         if (NULL == $this->getContractService()) { return; }
         $query = "DELETE FROM `codev_servicecontract_srv_table` WHERE `commandset_id` = '$this->id' ";

      } else {
         if (NULL == $this->getContractService()) {
            $query = "INSERT INTO `codev_servicecontract_srv_table` (`servicecontract_id`, `commandset_id`, `type`) ".
                     "VALUES ('$value', '$this->id', '$type');";
         } else {
            $query = "UPDATE `codev_servicecontract_srv_table` SET servicecontract_id = '$value' WHERE commandset_id='$this->id' ";
         }
      }

      $result = mysql_query($query);
      if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
      }
      $this->serviceContractId = $value;

   }



}
?>