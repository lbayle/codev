<?php
/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once('classes/command.class.php');
include_once('classes/commandset.class.php');
include_once('classes/commandset_cache.class.php');
include_once('classes/issue_selection.class.php');
include_once('classes/project_cache.class.php');
include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

/**
 * Description of ServiceContract
 */
class ServiceContract extends Model {

   // TODO states must be defined
   const state_default = 1;

   // TODO i18n for constants
   public static $stateNames = array(
      self::state_default => "Default"
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

   private $id;
   private $name;
   private $teamid;
   private $state;
   private $reference;
   private $version;
   private $reporter;
   private $description;
   private $start_date;
   private $end_date;

   // list of commandset_id, ordered by type
   // $cmdsetidByTypeList[type][commandset_id]
   private $cmdsetidByTypeList;

   // list of project_id
   // $cmdsetidByTypeList[project_id]
   private $sidetasksProjectList;

   /**
    * [cat_id] = IssueSelection("categoryName")
    * @var IssueSelection[][]
    */
   private $sidetasksPerCategory;

   private $commandList;

   /**
    * @param int $id The service contract id
    * @param resource $details The service contract details
    * @throws Exception
    */
   public function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a ServiceContract with id=0 is not allowed.");
         self::$logger->error("EXCEPTION ServiceContract constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The service contract details
    */
   private function initialize($row) {
      if($row == NULL) {
         // get info from DB
         $query  = "SELECT * FROM `codev_servicecontract_table` WHERE id = ".$this->id.";";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }

      $this->name = $row->name;
      $this->teamid = $row->team_id;
      $this->state = $row->state;
      $this->reference = $row->reference;
      $this->version = $row->version;
      $this->reporter = $row->reporter;
      $this->description = $row->description;
      $this->start_date = $row->start_date;
      $this->end_date = $row->end_date;
   }

   /**
    * create a new commandset in the DB
    * @static
    * @param string $name
    * @param int $teamid
    * @return int $id
    */
   public static function create($name, $teamid) {
      $query = "INSERT INTO `codev_servicecontract_table`  (`name`, `team_id`) ".
               "VALUES ('$name', $teamid);";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * delete a ServcieContract
    * @static
    * @param int $id
    * @return int $id
    */
   public static function delete($id) {
      $query = "DELETE FROM `codev_servicecontract_cmdset_table` WHERE servicecontract_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_servicecontract_stproj_table` WHERE servicecontract_id = ".$id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      $query = "DELETE FROM `codev_servicecontract_table` WHERE id = ".$id.";";
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
      $query = "UPDATE `codev_servicecontract_table` SET team_id = '$value' WHERE id = ".$this->id.";";
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
      $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($name);  // should be in controler, not here
      $this->name = $formattedValue;
      $query = "UPDATE `codev_servicecontract_table` SET name = '$formattedValue' WHERE id = ".$this->id.";";
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
      $query = "UPDATE `codev_servicecontract_table` SET reference = '$value' WHERE id = ".$this->id.";";
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
      $query = "UPDATE `codev_servicecontract_table` SET version = '$value' WHERE id = ".$this->id.";";
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
      $query = "UPDATE `codev_servicecontract_table` SET reporter = '$value' WHERE id = ".$this->id.";";
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
      $formattedValue = SqlWrapper::getInstance()->sql_real_escape_string($description);  // should be in controler, not here
      $this->description = $formattedValue;
      $query = "UPDATE `codev_servicecontract_table` SET description = '$formattedValue' WHERE id = ".$this->id.";";
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
      $query = "UPDATE `codev_servicecontract_table` SET state='$value' WHERE id = ".$this->id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function getStartDate() {
      return $this->start_date;
   }

   public function setStartDate($value) {
      $this->start_date = $value;
      $query = "UPDATE `codev_servicecontract_table` SET start_date = '$this->start_date' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function getEndDate() {
      return $this->end_date;
   }

   public function setEndDate($value) {
      $this->end_date = $value;
      $query = "UPDATE `codev_servicecontract_table` SET end_date = '$this->end_date' WHERE id='$this->id' ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * @param int $type  CommandSet::type_general
    * @return CommandSet[] : array commandset_id => CommandSet
    */
   public function getCommandSets($type) {
      $cmdsetList = array();

      $cmdsetidList = $this->getCommandSetIds($type);

      if(($cmdsetidList) && (0 != count($cmdsetidList))) {
         foreach ($cmdsetidList as $commandset_id) {
            $cmdsetList[$commandset_id] = CommandSetCache::getInstance()->getCommandSet($commandset_id);
         }
      }
      return $cmdsetList;
   }

   private function getCommandSetIds($type = NULL) {
      if(NULL == $this->cmdsetidByTypeList) {
         // CommandSets
         $query = "SELECT * FROM `codev_servicecontract_cmdset_table` ".
                  "WHERE servicecontract_id = $this->id ".
                  "ORDER BY type ASC, commandset_id ASC;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->cmdsetidByTypeList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            if (!array_key_exists($row->type,$this->cmdsetidByTypeList)) {
               $this->cmdsetidByTypeList[$row->type] = array();
            }
            $this->cmdsetidByTypeList[$row->type][] = $row->commandset_id;
         }
      }

      if(NULL == $type) {
         return $this->cmdsetidByTypeList;
      } else {
         return $this->cmdsetidByTypeList[$type];
      }
   }

   /**
    * @param int $cset_type CommandSet::type_general
    * @param int $cmd_type Command::type_general
    * @return Command[]
    */
   public function getCommands($cset_type, $cmd_type) {
      // TODO  $key = $cset_type . '_' . $cmd_type;

      if (NULL == $this->commandList) {
         $this->commandList = array();

         $cmdsetList = $this->getCommandSets($cset_type);

         foreach ($cmdsetList as $cset) {
            $cmdList = $cset->getCommands($cmd_type);
            foreach ($cmdList as $id => $cmd) {
               $this->commandList[$id] = $cmd;  // array_merge looses the $key
            }
         }
      }

      return $this->commandList;
   }


   /**
    * @return Project[] array project_id => Project
    */
   public function getProjects() {
      // TODO: if type==NULL return for all types
      if(NULL == $this->sidetasksProjectList) {
         // SidetaskProjects
         $query = "SELECT project.* FROM `mantis_project_table` as project ".
                  "JOIN `codev_servicecontract_stproj_table` as servicecontract_stproj ON project.id = servicecontract_stproj.project_id ".
                  "WHERE servicecontract_stproj.servicecontract_id = $this->id ".
                  "ORDER BY type ASC, project.id ASC;";

         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->sidetasksProjectList = array();
         while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $this->sidetasksProjectList[$row->id] = ProjectCache::getInstance()->getProject($row->id, $row);
         }
      }

      return $this->sidetasksProjectList;
   }

   /**
    * Collect the Issues of all the CommandSets (of a given type)
    *
    * @param int $cset_type CommandSet::type_general
    * @param int $cmd_type Command::type_general
    * @return IssueSelection
    */
   public function getIssueSelection($cset_type, $cmd_type) {
      // TODO: if type==NULL return for all types

      $issueSelection = new IssueSelection();

      $cmdsetidList = $this->getCommandSetIds($cset_type);

      if(($cmdsetidList) && (0 != count($cmdsetidList))) {
         foreach ($cmdsetidList as $commandset_id) {
            $cmdset = CommandSetCache::getInstance()->getCommandSet($commandset_id);
            $cmdsetIS = $cmdset->getIssueSelection($cmd_type);
            $issueSelection->addIssueList($cmdsetIS->getIssueList());
         }
      }

      return $issueSelection;
   }

   /**
    * add Command to commandset (in DB & current instance)
    *
    * @param int $commandset_id
    * @param int $type CommandSet::type_general
    * @return int id in codev_servicecontract_cmdset_table
    */
   public function addCommandSet($commandset_id, $type) {
      try {
         CommandSetCache::getInstance()->getCommandSet($commandset_id);
      } catch (Exception $e) {
         self::$logger->error("addCommandSet($commandset_id): CommandSet $commandset_id does not exist !");
         echo "<span style='color:red'>ERROR: CommandSet '$commandset_id' does not exist !</span>";
         return NULL;
      }

      self::$logger->debug("Add CommandSet $commandset_id to ServiceContract $this->id");

      if (NULL == $this->getCommandSetIds($type)) {
         $this->cmdsetidByTypeList[$type] = array();
      }
      $this->cmdsetidByTypeList[$type][] = $commandset_id;

      $query = "INSERT INTO `codev_servicecontract_cmdset_table` (`servicecontract_id`, `commandset_id`, `type`) VALUES ($this->id, $commandset_id, '$type');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * remove command from commandset commandList.
    * the Command itself is not deleted.
    *
    * @param int $commandset_id
    */
   public function removeCommandSet($commandset_id) {
      $typeList = array_keys($this->getCommandSetIds());
      foreach ($typeList as $type) {
         if (NULL != $this->cmdsetidByTypeList[$type][$commandset_id]) {
            unset($this->cmdsetidByTypeList[$type][$commandset_id]);
            # break;
         }
      }

      $query = "DELETE FROM `codev_servicecontract_cmdset_table` WHERE servicecontract_id = ".$this->id." AND commandset_id = ".$commandset_id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * add Command to commandset (in DB & current instance)
    *
    * @param int $project_id
    * @param int $type Project::type_sideTaskProject
    * @return int id in codev_servicecontract_stproj_table
    */
   public function addSidetaskProject($project_id, $type) {
      try {
         $project = ProjectCache::getInstance()->getProject($project_id);
      } catch (Exception $e) {
         self::$logger->error("addCommandSet($project_id): CommandSet $project_id does not exist !");
         echo "<span style='color:red'>ERROR: CommandSet '$project_id' does not exist !</span>";
         return NULL;
      }

      self::$logger->debug("Add CommandSet $project_id to ServiceContract $this->id");

      if (NULL == $this->sidetasksProjectList) {
         $this->sidetasksProjectList = array();
      }
      $this->sidetasksProjectList[$project_id] = $project;

      $query = "INSERT INTO `codev_servicecontract_stproj_table` (`servicecontract_id`, `project_id`, `type`) VALUES ($this->id, $project_id, '$type');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * remove command from commandset commandList.
    * the Command itself is not deleted.
    *
    * @param int $project_id
    */
   public function removeSidetaskProject($project_id) {
      if (NULL != $this->sidetasksProjectList[$project_id]) {
         unset($this->sidetasksProjectList[$project_id]);
      }

      $query = "DELETE FROM `codev_servicecontract_stproj_table` WHERE servicecontract_id = ".$this->id." AND project_id = ".$project_id.";";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   /**
    * @return ConsistencyError2[]
    */
   public function getConsistencyErrors() {
      $cmdsetList = $this->getCommandSets(CommandSet::type_general);

      $servicecontractErrors = array();
      foreach ($cmdsetList as $cmdset) {
         $csetErrors = $cmdset->getConsistencyErrors();
         $servicecontractErrors = array_merge($servicecontractErrors, $csetErrors);
      }
      return $servicecontractErrors;
   }

   /**
    * @param bool $skipIfInCommands SideTasks already declared in a child Commands will be skipped
    * @return IssueSelection[] : array[category_id] = IssueSelection("categoryName")
    */
   function getSidetasksPerCategory($skipIfInCommands = false) {
      if (NULL == $this->sidetasksPerCategory) { $this->sidetasksPerCategory = array(); }

      $key = ($skipIfInCommands) ? 'skip_yes' : 'skip_no';

      if (!array_key_exists($key, $this->sidetasksPerCategory)) {

         $this->sidetasksPerCategory[$key] = array();

         if ($skipIfInCommands) {
            $cmdidList = array_keys($this->getCommands(CommandSet::type_general, Command::type_general));
         }

         $prjList = $this->getProjects();
         foreach ($prjList as $id => $project) {
            try {
               if (!$project->isSideTasksProject(array($this->teamid))) {
                  self::$logger->error("getSidetasksPerCategory: SKIPPED project $id (".$project->getName().") should be a SidetasksProject !");
                  continue;
               }
            } catch (Exception $e) {
               self::$logger->error("getSidetasksPerCategory: EXCEPTION SKIPPED project $id (".$project->getName().") : ".$e->getMessage());
               continue;
            }

            $issueList = $project->getIssues();
            foreach ($issueList as $issue) {

               if ($skipIfInCommands) {
                  // compare the Commands of the Issue whit the Commands of this ServiceContract
                  $issueCmdidList = array_keys($issue->getCommandList());
                  $isInCommands = 0 != count(array_intersect($cmdidList, $issueCmdidList));
                  if ($isInCommands) {
                     self::$logger->debug("getSidetasksPerCategory(): skip issue ".$issue->getId()." because already declared in a Command");
                     continue;
                  }
               }

               if (NULL == $this->sidetasksPerCategory[$key][$issue->getCategoryId()]) {
                  $this->sidetasksPerCategory[$key][$issue->getCategoryId()] = new IssueSelection($issue->getCategoryName());
               }
               $issueSel = $this->sidetasksPerCategory[$key][$issue->getCategoryId()];
               $issueSel->addIssue($issue->getId());
            }
         }
      }
      return $this->sidetasksPerCategory[$key];
   }

}

ServiceContract::staticInit();

?>
