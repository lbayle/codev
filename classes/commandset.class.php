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
class CommandSet extends Model {

   const type_general = 1; // in codev_servicecontract_cmdset_table

   const state_toBeSent = 1;
   const state_sent = 2;
   const state_toBeSigned = 3;
   const state_signed = 4;

   // TODO i18n for constants
   public static $stateNames = array(
      self::state_toBeSent => "A émettre",
      self::state_sent => "Emis",
      self::state_toBeSigned => "A signer",
      self::state_signed => "Signé");

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

   // codev_commandset_table
   private $id;
   private $name;
   private $reference;
   private $description;
   private $state;
   private $date;
   private $teamid;
   private $serviceContractList;
   private $cost;         // DEPRECATED, see UserDailyCost
   private $currency;     // DEPRECATED, see UserDailyCost
   private $budget_days;  // DEPRECATED

   // list of commands, ordered by type
   // cmdByTypeList[type][cmdid]
   private $cmdidByTypeList;

   private $provisionList;

   /**
    * @param int $id The command set id
    * @param resource $details The command set details
    * @throws Exception if $id = 0
    */
   function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating an CommandSet with id=0 is not allowed.");
         self::$logger->error("EXCEPTION CommandSet constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The command set details
    */
   private function initialize($row = NULL) {
      if($row == NULL) {
         $query = "SELECT * FROM `codev_commandset_table` WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }
      $this->name = $row->name;
      $this->reference = $row->reference;
      // Avoid escaped \r\n in the description
      $this->description = str_replace("\\r\\n", "\n", preg_replace("/\\\\+/","\\",$row->description));
      $this->date = $row->date;
      $this->teamid = $row->team_id;
      $this->state = $row->state;
      $this->budget_days = $row->budget_days; // DEPRECATED
      $this->cost = $row->budget;             // DEPRECATED, see UserDailyCost
      $this->currency = $row->currency;       // DEPRECATED, see UserDailyCost
   }

   /**
    * create a new commandset in the DB
    * @static
    * @param string $name
    * @param int $teamid
    * @return int $id
    */
   public static function create($name, $teamid) {

      $query = "SELECT count(*) FROM `codev_commandset_table` WHERE name = '".$name."';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $count = SqlWrapper::getInstance()->sql_result($result);

      if($count == 0) {

         $query = "INSERT INTO `codev_commandset_table` (`name`, `team_id`) " .
                  "VALUES ('$name', $teamid);";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         return SqlWrapper::getInstance()->sql_insert_id();
      } else {
         throw new Exception('Already exists');
      }
   }

   /**
    * delete a commandset
    *
    * @static
    * @param int $id
    * @return bool
    */
   public static function delete($id) {
      $query = "DELETE FROM `codev_servicecontract_cmdset_table` WHERE commandset_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_commandset_cmd_table` WHERE commandset_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_commandset_table` WHERE id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         exit;
      }

      return true;
   }

   public function getId() {
      return $this->id;
   }

   public function getTeamid() {
      return $this->teamid;
   }

   public function setTeamid($value) {
      $this->teamid = $value;
      $query = "UPDATE `codev_commandset_table` SET team_id = $value WHERE id = ".$this->id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function getName() {
      return $this->name;
   }

   public function setName($name) {
      if($this->name != $name) {
         $this->name = $name;
         $query = "UPDATE `codev_commandset_table` SET name = '$name' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getReference() {
      return $this->reference;
   }

   public function setReference($value) {
      if($this->reference != $value) {
         $this->reference = $value;
         $query = "UPDATE `codev_commandset_table` SET reference = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getDesc() {
      return $this->description;
   }

   public function setDesc($description) {
      if($this->description != $description) {
         $this->description = $description;
         $query = "UPDATE `codev_commandset_table` SET description = '$description' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getState() {
      return $this->state;
   }

   public function setState($value) {
      if($this->state != $value) {
         $this->state = $value;
         $query = "UPDATE `codev_commandset_table` SET state = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getDate() {
      return $this->date;
   }

   public function setDate($timestamp) {
      if($this->date != $timestamp) {
         $this->date = $timestamp;
         $query = "UPDATE `codev_commandset_table` SET date = '$this->date' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    * @param int $type  Command::type_general
    * @return Command[] cmdid => Command
    */
   public function getCommands($type) {
      // TODO: if type==NULL return for all types

      $cmdList = array();

      $cmdidList = $this->getCommandIds($type);

      if (($cmdidList) && (0 != count($cmdidList))) {
         foreach ($cmdidList as $cmdid) {
            $cmdList[$cmdid] = CommandCache::getInstance()->getCommand($cmdid);
         }
      }
      return $cmdList;
   }

   private function getCommandIds($type = NULL) {
      if(NULL == $this->cmdidByTypeList) {
         $this->cmdidByTypeList = array();
         $query = "SELECT * FROM `codev_commandset_cmd_table` " .
            "WHERE commandset_id = $this->id ".
            "ORDER BY type ASC, command_id ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            if (!array_key_exists($row->type,$this->cmdidByTypeList)) {
               $this->cmdidByTypeList[$row->type] = array();
            }
            $this->cmdidByTypeList[$row->type][] = $row->command_id;
         }
      }

      if(NULL == $type) {
         return $this->cmdidByTypeList;
      } else {
         return $this->cmdidByTypeList[$type];
      }
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

      $cmdidList = $this->getCommandIds($type);

      if (($cmdidList) && (0 != count($cmdidList))) {
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
    * @param int $cmdid
    * @param int $type Command::type_general
    * @return int id in codev_commandset_cmd_table
    */
   public function addCommand($cmdid, $type) {
      try {
         CommandCache::getInstance()->getCommand($cmdid);
      } catch (Exception $e) {
         self::$logger->error("addCommand($cmdid): Command $cmdid does not exist !");
         echo "<span style='color:red'>ERROR: Command  '$cmdid' does not exist !</span>";
         return NULL;
      }

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Add command $cmdid to commandset $this->id");
      }

      if (NULL == $this->getCommandIds($type)) {
         $this->cmdidByTypeList[$type] = array();
      }
      $this->cmdidByTypeList[$type][] = $cmdid;

      $query = "INSERT INTO `codev_commandset_cmd_table` (`commandset_id`, `command_id`, `type`) VALUES ($this->id, $cmdid, '$type');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $id = SqlWrapper::getInstance()->sql_insert_id();
      return $id;
   }

   /**
    * remove command from commandset commandList.
    * the Command itself is not deleted.
    *
    * @param int $cmdid
    */
   public function removeCommand($cmdid) {
      $typeList = array_keys($this->getCommandIds());
      foreach ($typeList as $type) {
         $key = array_search($cmdid, $this->cmdidByTypeList[$type]);
         if (FALSE !== $key) {
            unset($this->cmdidByTypeList[$type][$key]);
            # break;
         }
      }

      $query = "DELETE FROM `codev_commandset_cmd_table` WHERE commandset_id = ".$this->id." AND command_id = ".$cmdid.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * A CommandSet can be included in several ServiceContract from different teams.
    *
    * This returns the list of ServiceContracts where this CommandSet is defined.
    *
    * @return ServiceContract[]
    */
   public function getServiceContractList() {
      if (NULL == $this->serviceContractList) {
         $query = "SELECT servicecontract.* FROM `codev_servicecontract_table` as servicecontract ".
                  "JOIN `codev_servicecontract_cmdset_table` as servicecontract_cmdset ON servicecontract.id = servicecontract_cmdset.servicecontract_id ".
                  "WHERE servicecontract_cmdset.commandset_id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // a Command can belong to more than one commandset
         $this->serviceContractList = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $srvContract = ServiceContractCache::getInstance()->getServiceContract($row->id, $row);

            $this->serviceContractList[$row->id] = $srvContract;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("CommandSet $this->id is in ServiceContract $row->id (" . $srvContract->getName() . ")");
            }
         }
      }
      return $this->serviceContractList;
   }

   /**
    * @return ConsistencyError2[]
    */
   public function getConsistencyErrors() {
      $cmdList = $this->getCommands(Command::type_general);

      $csetErrors = array();
      foreach ($cmdList as $cmd) {
         $cmdErrors = $cmd->getConsistencyErrors();
         $csetErrors = array_merge($csetErrors, $cmdErrors);
      }
      return $csetErrors;
   }


   /**
    * Sum all the BudjetDays provisions
    *
    * @param int $cmd_type  Command::type_general
    * @param bool $checkBudgetOnly sum only 'is_in_check_budget' provisions
    * @return type
    *
    */
   public function getProvisionDays($cmd_type, $checkBudgetOnly = FALSE) {

      $provisions = $this->getProvisionList($cmd_type);
      $budgetDays = 0;
      foreach ($provisions as $prov) {
         if ((FALSE == $checkBudgetOnly) ||
             ((TRUE == $checkBudgetOnly) && ($prov->isInCheckBudget()))) {
            $budgetDays += $prov->getProvisionDays();
         }
      }
      return $budgetDays;
   }

   /**
    * @param int $cmdType  Command::type_general
    * @param int $provType CommandProvision::provision_xxx
    * @return array CommandProvision
    */
   public function getProvisionList($cmdType, $provType = NULL) {

      $key= 'P'.$cmdType.'_'.$provType;
      if (is_null($this->provisionList)) { $this->provisionList = array(); }

      if (is_null($this->provisionList[$key])) {

         $cmdidList = $this->getCommandIds($cmdType);
         if (empty($cmdidList)) {
             self::$logger->warn("CommandSet $this->id : no commands for type $cmdType");
            return array();
         }
         $formattedCmdidList = implode(',', $cmdidList);

         $query = "SELECT * FROM `codev_command_provision_table` ".
                 "WHERE `command_id` IN ($formattedCmdidList) ";

         if (!is_null($provType)) {
            $query .= " AND `type` = ".$provType;
         }
         $query .= " ORDER BY date ASC, type ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);

         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->provisionList[$key] = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            try {
               $provision = new CommandProvision($row->id, $row);
               $this->provisionList[$key]["$row->id"] = $provision;
            } catch (Exception $e) {
               echo "<span style='color:red'>WARNING: Provision $row->id does not exist !</span><br>";
            }
         }
      }
      return $this->provisionList[$key];
   }


}

CommandSet::staticInit();

