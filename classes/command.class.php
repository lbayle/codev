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
 * Un command (fiche de presta) est un ensemble de taches que l'on veut
 * piloter a l'aide d'indicateurs (cout, delai, qualite, avancement)
 *
 * un command peut contenir des taches précises (mantis)
 * mais également définir des objectifs d'ordre global ou non
 * liés au dev.
 *
 * un command est provisionné d'un certain budget, négocié avec le client.
 * le cout de l'ensemble des taches devrait etre a l'equilibre avec ce budget.
 */
class Command extends Model {

   const type_general = 1; // in codev_commandset_cmd_table

   const state_toBeSent = 1;
   const state_sent = 2;
   const state_toBeValidated = 3;
   const state_validated = 4;
   const state_toBeClosed = 5;
   const state_closed = 6;
   const state_billMustBeSent = 7;
   const state_billSent = 8;
   const state_payed = 9;

   const provision_projectManagement = 1;
   const provision_risk = 2;
   const provision_guarantee = 3;
   const provision_other = 99;
   
   // TODO i18n for constants
   public static $stateNames = array(
      self::state_toBeSent => "A émettre",
      self::state_sent => "Emis",
      self::state_toBeValidated => "A valider",
      self::state_validated => "Validé",
      self::state_toBeClosed => "A clôturer",
      self::state_closed => "Clôturé",
      self::state_billMustBeSent => "Facture à émettre",
      self::state_billSent => "Facture émise",
      self::state_payed => "Facturé"
   );

   // TODO i18n for constants
   public static $provisionNames = array(
      self::provision_projectManagement => "Project Management",
      self::provision_risk => "Risk",
      self::provision_guarantee => "Guarantee",
      self::provision_other => "Other",
   );
   
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

   // codev_command_table
   private $id;
   private $name;
   private $reference;
   private $version;
   private $reporter;
   private $description;
   private $startDate;
   private $deadline;
   private $teamid;
   private $state;
   private $cost;
   private $currency;
   private $budgetDev;
   private $budgetMngt;
   private $budgetGarantie;
   private $averageDailyRate;
   private $enabled;

   // codev_command_bug_table
   private $issueSelection;

   // codev_commandset_cmd_table
   private $commandSetList;

   // codev_command_provision_table
   private $provisionList;

   /**
    * @param int $id The command id
    * @param resource $details The command details
    * @throws Exception if $id = 0
    */
   function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating an Command with id=0 is not allowed.");
         self::$logger->error("EXCEPTION Command constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The details
    */
   private function initialize($row) {
      if($row == NULL) {
         $query  = "SELECT * FROM `codev_command_table` WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }
      $this->name = $row->name;
      $this->reference = $row->reference;
      $this->version = $row->version;
      $this->reporter = $row->reporter;
      $this->description = $row->description;
      $this->startDate = $row->start_date;
      $this->deadline = $row->deadline;
      $this->teamid = $row->team_id;
      $this->state = $row->state;
      $this->cost = $row->cost;
      $this->currency = $row->currency;
      $this->budgetDev = $row->budget_dev;
      $this->budgetMngt = $row->budget_mngt;
      $this->budgetGarantie = $row->budget_garantie;
      $this->averageDailyRate = $row->average_daily_rate;
      $this->enabled = (1 == $row->enabled);
   }

   /**
    * create a new command in the DB
    *
    * @static
    * @param string $name
    * @param int $teamid
    * @return int $id
    * @throws Exception if already exists
    */
   public static function create($name, $teamid) {
      $query = "SELECT count(*) FROM `codev_command_table` WHERE name = '".$name."';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $count = SqlWrapper::getInstance()->sql_result($result);

      if($count == 0) {
         $query = "INSERT INTO `codev_command_table`  (`name`, `team_id`) ".
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
    * delete a command
    *
    * @static
    * @param int $id
    * @return bool true id deleted
    */
   public static function delete($id) {
      $query = "DELETE FROM `codev_commandset_cmd_table` WHERE command_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_command_bug_table` WHERE command_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_command_table` WHERE id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         exit;
      }

      return true;
   }

   /**
    * @param string $name commandName
    * @return int id commandid
    */
   public static function getCommandId($name) {
      $query = "SELECT id FROM `codev_command_table` WHERE name = '$name';";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_result($result, 0);
   }

   public function getId() {
      return $this->id;
   }

   public function getTeamid() {
      return $this->teamid;
   }

   public function setTeamid($value) {
      if($this->teamid != $value) {
         $this->teamid = $value;
         $query = "UPDATE `codev_command_table` SET team_id = '$value' WHERE id = ".$this->id.";";
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
   public function isEnabled() {
      return $this->enabled;
   }

   /**
    * @param bool $isEnabled
    */
   public function setEnabled($isEnabled) {
      $this->enabled = $isEnabled;

      $value = ($isEnabled) ? '1' : '0';

      $query = "UPDATE `codev_command_table` SET enabled = '$value' WHERE id = ".$this->id.";";
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
         $query = "UPDATE `codev_command_table` SET name = '$name' WHERE id = ".$this->id.";";
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
         $query = "UPDATE `codev_command_table` SET reference = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getVersion() {
      return $this->version;
   }

   public function setVersion($value) {
      if($this->version != $value) {
         $this->version = $value;
         $query = "UPDATE `codev_command_table` SET version = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getReporter() {
      return $this->reporter;
   }

   public function setReporter($value) {
      if($this->reporter = $value) {
         $this->reporter = $value;
         $query = "UPDATE `codev_command_table` SET reporter = '$value' WHERE id = ".$this->id.";";
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
         $query = "UPDATE `codev_command_table` SET description = '$description' WHERE id = ".$this->id.";";
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
         $query = "UPDATE `codev_command_table` SET state='$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getCost() {
      // cost is stored in cents
      return ($this->cost / 100);
   }

   public function setCost($value) {
      if($this->cost != floatval($value) * 100) {
         $this->cost = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET cost = '$this->cost' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getCurrency() {
      return $this->currency;
   }

   public function setCurrency($value) {
      if($this->currency != $value) {
         $this->currency = $value;
         $query = "UPDATE `codev_command_table` SET currency = '$value' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getAverageDailyRate() {
      return ($this->averageDailyRate / 100);
   }

   public function setAverageDailyRate($value) {
      if($this->averageDailyRate != floatval($value) * 100) {
         $this->averageDailyRate = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET average_daily_rate = '$this->averageDailyRate' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getStartDate() {
      return $this->startDate;
   }

   public function setStartDate($timestamp) {
      if($this->startDate != $timestamp) {
         $this->startDate = $timestamp;
         $query = "UPDATE `codev_command_table` SET start_date = '$this->startDate' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getDeadline() {
      return $this->deadline;
   }

   public function setDeadline($timestamp) {
      if($this->deadline != $timestamp) {
         $this->deadline = $timestamp;
         $query = "UPDATE `codev_command_table` SET deadline = '$this->deadline' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getBudgetDev() {
      return ($this->budgetDev / 100);
   }

   public function setBudgetDev($value) {
      if($this->budgetDev != floatval($value) * 100) {
         $this->budgetDev = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET budget_dev = '$this->budgetDev' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getBudgetMngt() {
      return ($this->budgetMngt / 100);
   }

   public function setBudgetMngt($value) {
      if($this->budgetMngt != floatval($value) * 100) {
         $this->budgetMngt = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET budget_mngt = '$this->budgetMngt' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   public function getBudgetGarantie() {
      return ($this->budgetGarantie / 100);
   }

   public function setBudgetGarantie($value) {
      if($this->budgetGarantie != floatval($value) * 100) {
         $this->budgetGarantie = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET budget_garantie = '$this->budgetGarantie' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    * @return IssueSelection
    */
   public function getIssueSelection() {
      if(NULL == $this->issueSelection) {
         $this->issueSelection = new IssueSelection($this->name);
         $query = "SELECT bug.* FROM `mantis_bug_table` AS bug ".
                  "JOIN `codev_command_bug_table` AS command_bug ON bug.id = command_bug.bug_id " .
                  "WHERE command_bug.command_id = ".$this->id.";";
         #", `mantis_bug_table`".
         #"WHERE codev_command_bug_table.command_id=$this->id ".
         #"AND codev_command_bug_table.bug_id = mantis_bug_table.id ".
         #"ORDER BY mantis_bug_table.project_id ASC, mantis_bug_table.target_version DESC, mantis_bug_table.status ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         
         $issues = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            try {
               $issue = IssueCache::getInstance()->getIssue($row->id, $row);
               $issues[$row->id] = $issue;
            } catch (Exception $e) {
               echo "<span style='color:red'>WARNING: Task $row->id does not exist in Mantis and has been removed from this Command !</span><br>";
               $this->removeIssue($row->id);
            }
         }
         $this->getIssueSelection()->addIssueList($issues);
      }
      return $this->issueSelection;
   }

   /**
    * add Issue to command (in DB & current instance)
    *
    * @param int $bugid
    * @param bool $isDBonly if true, do not update current instance (PERF issue on)
    *
    * @return int insertion id if success, NULL on failure
    * @throws Exception
    */
   public function addIssue($bugid, $isDBonly = false) {
      // security check
      if (!is_numeric($bugid)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($bugid)");
         self::$logger->fatal("EXCEPTION addIssue: ".$e->getMessage());
         self::$logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      try {
         $issue = IssueCache::getInstance()->getIssue($bugid);
      } catch (Exception $e) {
         self::$logger->error("addIssue($bugid): issue $bugid does not exist !");
         echo "<span style='color:red'>ERROR: issue  '$bugid' does not exist !</span>";
         return NULL;
      }

      $id = NULL;
      if ( !array_key_exists($this->id, $issue->getCommandList())) {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("Add issue $bugid to command $this->id");
         }

         $query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES ($this->id, $bugid);";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $id = SqlWrapper::getInstance()->sql_insert_id();
      } else {
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("addIssue($bugid) to command $this->id: already in !");
         }
      }

      if (!$isDBonly) {
         $this->getIssueSelection()->addIssue($bugid);
      }

      return $id;
   }

   /**
    * remove issue from command issueList.
    * the issue itself is not deleted.
    *
    * @param int $bugid
    * @throws Exception
    */
   public function removeIssue($bugid) {
      // security check
      if (!is_numeric($bugid)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($bugid)");
         self::$logger->fatal("EXCEPTION removeIssue: ".$e->getMessage());
         self::$logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->getIssueSelection()->removeIssue($bugid);

      $query = "DELETE FROM `codev_command_bug_table` WHERE command_id = ".$this->id." AND bug_id = ".$bugid.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * A command can be included in several ComandSet from different teams.
    *
    * This returns the list of CommandSets where this command is defined.
    *
    * @return CommandSet[] array[commandset_id] = commandsetName
    */
   public function getCommandSetList() {
      if (NULL == $this->commandSetList) {
         $query = "SELECT commandset.* FROM `codev_commandset_table` as commandset ".
                  "JOIN `codev_commandset_cmd_table` as commandset_cmd ON commandset.id = commandset_cmd.commandset_id ".
                  "WHERE commandset_cmd.command_id = $this->id;";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // a Command can belong to more than one commandset
         $this->commandSetList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $cmdset = CommandSetCache::getInstance()->getCommandSet($row->id, $row);
            $this->commandSetList[$row->id] = $cmdset;
            if(self::$logger->isDebugEnabled()) {
               self::$logger->debug("Command $this->id is in commandset $row->id (".$cmdset->getName().")");
            }
         }
      }
      return $this->commandSetList;
   }

   /**
    * @return ConsistencyError2[]
    */
   public function getConsistencyErrors() {
      $issueSel = $this->getIssueSelection();
      $issueList = $issueSel->getIssueList();
      $ccheck = new ConsistencyCheck2($issueList);
      return $ccheck->check();
   }

}

Command::staticInit();

?>
