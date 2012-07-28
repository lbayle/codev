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
   Logger::configure(dirname(__FILE__) . '/../log4php.xml');
   $logger = Logger::getLogger("default");
   $logger->info("LOG activated !");
}

include_once('classes/issue_selection.class.php');
include_once('classes/team.class.php');
include_once('classes/commandset.class.php');
include_once('classes/command_cache.class.php');
include_once('classes/consistency_check2.class.php');

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
class Command {

  const type_general = 1;    // in codev_commandset_cmd_table

  const state_toBeSent       = 1;
  const state_sent           = 2;
  const state_toBeValidated  = 3;
  const state_validated      = 4;
  const state_toBeClosed     = 5;
  const state_closed         = 6;
  const state_billMustBeSent = 7;
  const state_billSent       = 8;
  const state_payed          = 9;

  // TODO i18n for constants
  public static $stateNames = array(Command::state_toBeSent       => "A émettre",
                                    Command::state_sent           => "Emis",
                                    Command::state_toBeValidated  => "A valider",
                                    Command::state_validated      => "Validé",
                                    Command::state_toBeClosed     => "A clôturer",
                                    Command::state_closed         => "Clôturé",
                                    Command::state_billMustBeSent => "Facture à émettre",
                                    Command::state_billSent       => "Facture émise",
                                    Command::state_payed          => "Facturé");


   private $logger;

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

   /**
    * @param int $id The command id
    * @param resource $details The command details
    * @throws Exception if $id = 0
    */
   function __construct($id, $details = NULL) {
      $this->logger = Logger::getLogger(__CLASS__);

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating an Command with id=0 is not allowed.");
         $this->logger->error("EXCEPTION Command constructor: ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
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
      $query  = "SELECT * FROM `codev_command_table` WHERE id=$this->id ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }
      $this->name             = $row->name;
      $this->reference        = $row->reference;
      $this->version          = $row->version;
      $this->reporter         = $row->reporter;
      $this->description      = $row->description;
      $this->startDate        = $row->start_date;
      $this->deadline         = $row->deadline;
      $this->teamid           = $row->team_id;
      $this->state            = $row->state;
      $this->cost             = $row->cost;
      $this->currency         = $row->currency;
      $this->budgetDev        = $row->budget_dev;
      $this->budgetMngt       = $row->budget_mngt;
      $this->budgetGarantie   = $row->budget_garantie;
      $this->averageDailyRate = $row->average_daily_rate;
      $this->enabled          = (1 == $row->enabled);
   }

   /**
    * create a new command in the DB
    *
    * @return int $id
    */
   public static function create($name, $teamid) {
      $query = "INSERT INTO `codev_command_table`  (`name`, `team_id`) ".
               "VALUES ('$name', '$teamid');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $id = SqlWrapper::getInstance()->sql_insert_id();
      return $id;
   }

   /**
    * delete a command
    *
    * @return bool true id deleted
    */
   public static function delete($id) {

      $query = "DELETE FROM `codev_commandset_cmd_table` WHERE `command_id`='$id';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_command_bug_table` WHERE `command_id`='$id';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_command_table` WHERE `id`='$id';";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         exit;
      }
      return true;
   }


   /**
    * @param string $name commandName
    * @return id commandid
    */
   public static function getCommandId($name) {

         $query = "SELECT id FROM `codev_command_table` WHERE name='$name'";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $commandid = SqlWrapper::getInstance()->sql_result($result, 0);

      return $commandid;
   }


   public function getId() {
      return $this->id;
   }

   public function getTeamid() {
      return $this->teamid;
   }
   public function setTeamid($value) {

      $this->teamid = $value;
      $query = "UPDATE `codev_command_table` SET team_id = '$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   /**
    *
    * @return bool isEnabled
    */
   public function isEnabled() {
      return $this->enabled;
   }

   /**
    *
    * @var bool isEnabled
    */
   public function setEnabled($isEnabled) {
      $this->enabled = $isEnabled;

      $value = ($isEnabled) ? '1' : '0';

      $query = "UPDATE `codev_command_table` SET enabled = '$value' WHERE id='$this->id' ";
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

      $this->name = $name;
      $query = "UPDATE `codev_command_table` SET name = '$name' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getReference() {
      return $this->reference;
   }
   public function setReference($value) {

      $this->reference = $value;
      $query = "UPDATE `codev_command_table` SET reference = '$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getVersion() {
      return $this->version;
   }
   public function setVersion($value) {

      $this->version = $value;
      $query = "UPDATE `codev_command_table` SET version = '$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getReporter() {
      return $this->reporter;
   }
   public function setReporter($value) {

      $this->reporter = $value;
      $query = "UPDATE `codev_command_table` SET reporter = '$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getDesc() {
      return $this->description;
   }
   public function setDesc($description) {

      $this->description = $description;
      $query = "UPDATE `codev_command_table` SET description = '$description' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getState() {
      return $this->state;
   }
   
   public function setState($value) {

      $this->state = $value;
      $query = "UPDATE `codev_command_table` SET state='$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }


   public function getCost() {
      // cost is stored in cents
      return ($this->cost / 100);
   }
   public function setCost($value) {

      $this->cost = floatval($value) * 100;
      $query = "UPDATE `codev_command_table` SET cost = '$this->cost' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }


   public function getCurrency() {
      return $this->currency;
   }
   public function setCurrency($value) {

      $this->currency = $value;
      $query = "UPDATE `codev_command_table` SET currency = '$value' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getAverageDailyRate() {
      return ($this->averageDailyRate / 100);
   }
   public function setAverageDailyRate($value) {


      $this->averageDailyRate = floatval($value) * 100;
      $query = "UPDATE `codev_command_table` SET average_daily_rate = '$this->averageDailyRate' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getStartDate() {
      return $this->startDate;
   }

   public function setStartDate($timestamp) {

      $this->startDate = $timestamp;
      $query = "UPDATE `codev_command_table` SET start_date = '$this->startDate' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getDeadline() {
      return $this->deadline;
   }
   public function setDeadline($timestamp) {
      
      $this->deadline = $timestamp;
      $query = "UPDATE `codev_command_table` SET deadline = '$this->deadline' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getBudgetDev() {
      return ($this->budgetDev / 100);
   }
   public function setBudgetDev($value) {

      $this->budgetDev = floatval($value) * 100;
      $query = "UPDATE `codev_command_table` SET budget_dev = '$this->budgetDev' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getBudgetMngt() {
      return ($this->budgetMngt / 100);
   }
   public function setBudgetMngt($value) {

      $this->budgetMngt = floatval($value) * 100;
      $query = "UPDATE `codev_command_table` SET budget_mngt = '$this->budgetMngt' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getBudgetGarantie() {
      return ($this->budgetGarantie / 100);
   }
   public function setBudgetGarantie($value) {

      $this->budgetGarantie = floatval($value) * 100;
      $query = "UPDATE `codev_command_table` SET budget_garantie = '$this->budgetGarantie' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getIssueSelection() {
      if(NULL == $this->issueSelection) {
      $this->issueSelection = new IssueSelection($this->name);
         $query = "SELECT * FROM `codev_command_bug_table` " .
                 "WHERE command_id=$this->id ";
         #", `mantis_bug_table`".
         #"WHERE codev_command_bug_table.command_id=$this->id ".
         #"AND codev_command_bug_table.bug_id = mantis_bug_table.id ".
         #"ORDER BY mantis_bug_table.project_id ASC, mantis_bug_table.target_version DESC, mantis_bug_table.status ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            try {
               $this->getIssueSelection()->addIssue($row->bug_id);
            } catch (Exception $e) {
               echo "<span style='color:red'>WARNING: Task $row->bug_id does not exist in Mantis and has been removed from this Command !</span><br>";
               $this->removeIssue($row->bug_id);
            }
         }
      }
      return $this->issueSelection;
   }


   /**
    * add Issue to command (in DB & current instance)
    *
    * @param int $bugid
    * @param type $isDBonly if true, do not update current instance (PERF issue on)
    *
    * @return insertion id if success, NULL on failure
    */
   public function addIssue($bugid, $isDBonly = false) {

      // security check
      if (!is_numeric($bugid)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($bugid)");
         $this->logger->fatal("EXCEPTION addIssue: ".$e->getMessage());
         $this->logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      try {
         $issue = IssueCache::getInstance()->getIssue($bugid);
      } catch (Exception $e) {
         $this->logger->error("addIssue($bugid): issue $bugid does not exist !");
         echo "<span style='color:red'>ERROR: issue  '$bugid' does not exist !</span>";
         return NULL;
      }


      $id = NULL;
      if ( !array_key_exists($this->id, $issue->getCommandList())) {

         $this->logger->debug("Add issue $bugid to command $this->id");

         $query = "INSERT INTO `codev_command_bug_table` (`command_id`, `bug_id`) VALUES ('$this->id', '$bugid');";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $id = SqlWrapper::getInstance()->sql_insert_id();
      } else {
            $this->logger->debug("addIssue($bugid) to command $this->id: already in !");
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
    */
   public function removeIssue($bugid) {

      // security check
      if (!is_numeric($bugid)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("SECURITY ALERT: Attempt to set non_numeric value ($bugid)");
         $this->logger->fatal("EXCEPTION removeIssue: ".$e->getMessage());
         $this->logger->fatal("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }


      $this->getIssueSelection()->removeIssue($bugid);

      $query = "DELETE FROM `codev_command_bug_table` WHERE command_id='$this->id' AND bug_id='$bugid';";
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
    * @return array[commandset_id] = commandsetName
    */
   public function getCommandSetList() {

      if (NULL == $this->commandSetList) {

         $query  = "SELECT * FROM `codev_commandset_cmd_table` WHERE command_id=$this->id ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // a Command can belong to more than one commandset
         $this->commandSetList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {

            $cmdset = CommandSetCache::getInstance()->getCommandSet($row->commandset_id);
            
            $this->commandSetList["$row->commandset_id"] = $cmdset->getName();
            $this->logger->debug("Command $this->id is in commandset $row->commandset_id (".$cmdset->getName().")");
         }
      }
      return $this->commandSetList;
   }

public function getConsistencyErrors() {

   $issueSel = $this->getIssueSelection();
   $issueList = $issueSel->getIssueList();
   $ccheck = new ConsistencyCheck2($issueList);

   $cerrList = $ccheck->check();

   return $cerrList;
}
   
   
   
   
}
?>
