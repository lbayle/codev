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
include_once "commandset_cache.class.php";



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
class CommandSet{

   const type_general = 1; // in codev_servicecontract_cmdset_table

   const state_toBeSent       = 1;
   const state_sent           = 2;
   const state_toBeSigned     = 3;
   const state_signed         = 4;

  // TODO i18n for constants
  public static $stateNames = array(CommandSet::state_toBeSent       => "A émettre",
                                    CommandSet::state_sent           => "Emis",
                                    CommandSet::state_toBeSigned     => "A signer",
                                    CommandSet::state_signed         => "Signé");

   private $logger;

   // codev_commandset_table
   private $id;
   private $name;
   private $description;
   private $state;
   private $date;
   private $teamid;
   private $serviceContractList;
   private $cost;
   private $currency;
   private $budget_days;

   // list of commands, ordered by type
   // cmdByTypeList[type][cmdid]
   private $cmdidByTypeList;

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
      $this->cmdidByTypeList = array();
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
         if (NULL == $this->cmdidByTypeList["$row->type"]) {
            $this->cmdidByTypeList["$row->type"] = array();
         }
          $this->cmdidByTypeList["$row->type"][] = $row->command_id;
      }
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

   public function getState() {
      return $this->state;
   }

   public function setState($value) {

      $this->state = $value;
      $query = "UPDATE `codev_commandset_table` SET state='$value' WHERE id='$this->id' ";
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
    * @param int $type  Command::type_general
    * @return array cmdid => Command
    */
   public function getCommands($type) {

      // TODO: if type==NULL return for all types

      $cmdList = array();

      $cmdidList = $this->cmdidByTypeList["$type"];
      
      if(($cmdidList) && (0 != count($cmdidList))) {
         foreach ($cmdidList as $cmdid) {

            $cmdList[$cmdid] = CommandCache::getInstance()->getCommand($cmdid);
         }
      }
      return $cmdList;
   }

   /**
    * Collect the Issues of all the Commands (of a given type)
    *
    * @param int $type Command::type_general
    *
    * @return IssueSelection
    */
   public function getIssueSelection($type) {

      // TODO: if type==NULL return for all types

      $issueSelection = new IssueSelection();

      $cmdidList = $this->cmdidByTypeList["$type"];

      if(($cmdidList) && (0 != count($cmdidList))) {
         foreach ($cmdidList as $cmdid) {

            $cmd = CommandCache::getInstance()->getCommand($cmdid);

            $mcdIS = $cmd->getIssueSelection();
            $issueSelection->addIssueList($mcdIS->getIssueList());

         }
      }
      return $issueSelection;

   }


   /**
    * add Command to commandset (in DB & current instance)
    *
    * @param type $cmdid
    * @param int $type Command::type_general
    * @return int id in codev_commandset_cmd_table
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

      if (NULL == $this->cmdidByTypeList["$type"]) {
         $this->cmdidByTypeList["$type"] = array();
      }
      $this->cmdidByTypeList["$type"][] = $cmdid;

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

      $typeList = array_keys($this->cmdidByTypeList);
      foreach ($typeList as $type) {
         if (NULL != $this->cmdidByTypeList[$type][$cmdid]) {
            unset($this->cmdidByTypeList[$type][$cmdid]);
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
    * A CommandSet can be included in several ServiceContract from different teams.
    *
    * This returns the list of ServiceContracts where this CommandSet is defined.
    *
    * @return array[servicecontract_id] = servicecontractName
    */
   public function getServiceContractList() {

      if (NULL == $this->serviceContractList) {

         $query  = "SELECT * FROM `codev_servicecontract_cmdset_table` WHERE commandset_id=$this->id ";
         $result = mysql_query($query);
         if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // a Command can belong to more than one commandset
         while($row = mysql_fetch_object($result)) {

            $srvContract = ServiceContractCache::getInstance()->getServiceContract($row->servicecontract_id);

            $this->serviceContractList["$row->servicecontract_id"] = $srvContract->getName();
            $this->logger->debug("CommandSet $this->id is in ServiceContract $row->servicecontract_id (".$srvContract->getName().")");
         }
      }
      return $this->commandSetList;
   }


   /**
    *
    * @return array
    */
   public function getConsistencyErrors() {

      
      $cmdList = $this->getCommands(Command::type_general);

      $csetErrors = array();
      foreach ($cmdList as $cmdid => $cmd) {
         $cmdErrors = $cmd->getConsistencyErrors();
         $csetErrors = array_merge($csetErrors, $cmdErrors);
      }
      return $csetErrors;
   }


}
?>