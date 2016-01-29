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
   const state_closed = 6; // WARN: used HARDCODED in Mantis Plugin
   const state_billMustBeSent = 7;
   const state_billSent = 8;
   const state_payed = 9;

   public static $stateNames;
   
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
      
      self::$stateNames = array(
      self::state_toBeSent => T_("to be sent"),
      self::state_sent => T_("Sent"),
      self::state_toBeValidated => T_("to be validated"),
      self::state_validated => T_("validated"),
      self::state_toBeClosed => T_("to be closed"),
      self::state_closed => T_("closed"),
      self::state_billMustBeSent => T_("bill must be sent"),
      self::state_billSent => T_("bill sent"),
      self::state_payed => T_("payed")
   );
      
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
   private $wbsid;
   private $state;
   private $cost;
   private $currency;
   private $totalSoldDays; // used to check if MgrEE is correctly dispatched on tasks
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
      $this->wbsid = $row->wbs_id;
      $this->state = $row->state;
      $this->cost = $row->cost;
      $this->currency = $row->currency;
      $this->totalSoldDays = $row->total_days;
      $this->averageDailyRate = $row->average_daily_rate;
      $this->enabled = (1 == $row->enabled);

         // commands created before v0.99.25 have no WBS
      if (is_null($this->wbsid)) {

         self::$logger->warn("Initialize: command $this->id has no WBS.");

         // add root element
         $wbs = new WBSElement(NULL, NULL, NULL, NULL, NULL, $this->name);
         $this->wbsid = $wbs->getId();
         $query = "UPDATE `codev_command_table` SET wbs_id = '".$this->wbsid."' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         // add existing Issues
         $bugidList = array_keys($this->getIssueSelection()->getIssueList());
         $order = 1;
         foreach($bugidList as $bug_id) {
            $child = new WBSElement(NULL, $this->wbsid, $bug_id, $this->wbsid, $order);
            $order++;
         }
      }
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

         // create empty WBS
         $wbs = new WBSElement(NULL, NULL, NULL, NULL, NULL, $name);
         $wbsid = $wbs->getId();

         // create Command
         $query = "INSERT INTO `codev_command_table`  (`name`, `team_id`, `wbs_id`) ".
            "VALUES ('$name', $teamid, $wbsid);";

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

      // delete WBS
      $query = "DELETE FROM `codev_wbs_table` ".
              "WHERE root_id = (SELECT wbs_id FROM `codev_command_table` WHERE id=$id) ".
              "OR id=(SELECT wbs_id FROM `codev_command_table` WHERE id=$id);";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // delete Command
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

   /**
    * parse all Commands for issues not found in mantis_bug_table. if any, remove them from the Commands.
    */
   public static function checkCommands() {
      $query0 = "SELECT command_id, bug_id FROM codev_command_bug_table WHERE bug_id NOT IN (SELECT id FROM mantis_bug_table)";
      $result0 = SqlWrapper::getInstance()->sql_query($query0);
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result0)) {
         self::$logger->warn("issue $row->bug_id does not exist in Mantis: now removed from Command $row->command_id");

         // remove from Command
         $query = "DELETE FROM `codev_command_bug_table` WHERE bug_id = ".$row->bug_id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
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

   public function getWbsid()   {
      return $this->wbsid;
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

         // update root WBS name
         $query2 = "UPDATE `codev_wbs_table` SET `title` = '" . $name . "' WHERE `id` = " . $this->wbsid.";";
         $result2 = SqlWrapper::getInstance()->sql_query($query2);
         if (!$result2) {
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

   /**
    * used to check if MgrEE is correctly dispatched on tasks
    *
    * this budjet is set at command creation and contains
    * totalDays = 'days sold for devTasks' + 'days declared in provisions'
    *
    * @return float nbDays
    */
   public function getTotalSoldDays() {
      return ($this->totalSoldDays / 100);
   }

   public function setTotalSoldDays($value) {
      if($this->totalSoldDays != floatval($value) * 100) {
         $this->totalSoldDays = floatval($value) * 100;
         $query = "UPDATE `codev_command_table` SET total_days = '$this->totalSoldDays' WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
      }
   }

   /**
    * Sum all the Budjet provisions
    *
    * @param bool $checkBudgetOnly sum only 'is_in_check_budget' provisions
    * @return type
    */
   public function getProvisionBudget($checkBudgetOnly = FALSE) {

      $provisions = $this->getProvisionList();
      $budget = 0;
      foreach ($provisions as $prov) {
         if ((FALSE == $checkBudgetOnly) ||
             ((TRUE == $checkBudgetOnly) && ($prov->isInCheckBudget()))) {
            $budget += $prov->getProvisionBudget();
         }
      }
      return $budget;
   }

   /**
    * Sum all the BudjetDays provisions
    *
    * @param bool $checkBudgetOnly sum only 'is_in_check_budget' provisions
    * @return type
    *
    */
   public function getProvisionDays($checkBudgetOnly = FALSE) {

      $provisions = $this->getProvisionList();
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
    *
    * @return array CommandProvision
    */
   public function getProvisionList($type = NULL) {

      if (is_null($this->provisionList)) {

         $query = "SELECT * FROM `codev_command_provision_table` WHERE `command_id` = ".$this->id;

         if (!is_null($type)) {
            $query .= " AND `type` = ".$type;
         }
         $query .= " ORDER BY date ASC, type ASC";

         $result = SqlWrapper::getInstance()->sql_query($query);

         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->provisionList = array();
         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            try {
               $provision = new CommandProvision($row->id, $row);
               $this->provisionList["$row->id"] = $provision;
            } catch (Exception $e) {
               echo "<span style='color:red'>WARNING: Provision $row->id does not exist !</span><br>";
            }
         }
      }
      return $this->provisionList;
   }

   public function deleteProvision($provid) {
      CommandProvision::delete($provid);
      if (!is_null($this->provisionList)) {
         unset($this->provisionList["$provid"]);
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
               // Note: this should never happen, the "lazy init" query only returns existing issues...
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

         // add to WBS
         $wbsChild = new WBSElement(NULL, $this->wbsid, $bugid, $this->wbsid);
         if(self::$logger->isDebugEnabled()) {
            self::$logger->debug("Add issue $bugid from command $this->id to WBS root_id=$this->wbsid wbse_id=".$wbsChild->getId());
         }

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

      // remove from Command
      $query = "DELETE FROM `codev_command_bug_table` WHERE command_id = ".$this->id." AND bug_id = ".$bugid.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // remove from WBS
      $query = "DELETE FROM `codev_wbs_table` WHERE root_id = ".$this->wbsid." AND bug_id = ".$bugid.";";
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
      $ccheck = new ConsistencyCheck2($issueList, $this->teamid);
      $cerrList = $ccheck->check();

      // check if sold days is set.
      if (0 != $this->totalSoldDays) {

         $checkTotalSoldDays = $this->getTotalSoldDays() - $this->getIssueSelection()->mgrEffortEstim - $this->getProvisionDays();
         $checkTotalSoldDays = round($checkTotalSoldDays, 2);
         if (0 != $checkTotalSoldDays) {
            $errMsg = T_("The total charge (MgrEffortEstim + Provisions) should be equal to the 'Sold Charge'").
                      ' ('.T_("balance")." = $checkTotalSoldDays ".T_('days').')';
            $cerr = new ConsistencyError2(NULL, NULL, NULL, NULL, $errMsg);
            array_unshift($cerrList, $cerr);
         }
      }
      return $cerrList;
   }

}

Command::staticInit();


