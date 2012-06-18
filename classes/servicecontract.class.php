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

require_once('Logger.php');

/* INSERT INCLUDES HERE */
require_once "servicecontract_cache.class.php";


/**
 * Description of ServiceContract
 *
 */
class ServiceContract {
   
   private $logger;

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


   public function __construct($id) {
      $this->logger = Logger::getLogger(__CLASS__);

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a ServiceContract with id=0 is not allowed.");
         $this->logger->error("EXCEPTION ServiceContract constructor: " . $e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize();
   }

   private function initialize() {

      // get info from DB
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
      $query = "UPDATE `codev_servicecontract_table` SET team_id = '$value' WHERE id='$this->id' ";
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
      $formattedValue = mysql_real_escape_string($name);  // should be in controler, not here
      $this->name = $formattedValue;
      $query = "UPDATE `codev_servicecontract_table` SET name = '$formattedValue' WHERE id='$this->id' ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getReference() {
      return $this->reference;
   }
   public function setReference($value) {

      $this->reference = $value;
      $query = "UPDATE `codev_servicecontract_table` SET reference = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }

   public function getVersion() {
      return $this->version;
   }
   public function setVersion($value) {

      $this->version = $value;
      $query = "UPDATE `codev_servicecontract_table` SET version = '$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }

   public function getReporter() {
      return $this->reporter;
   }
   public function setReporter($value) {

      $this->reporter = $value;
      $query = "UPDATE `codev_servicecontract_table` SET reporter = '$value' WHERE id='$this->id' ";
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
      $formattedValue = mysql_real_escape_string($description);  // should be in controler, not here
      $this->description = $formattedValue;
      $query = "UPDATE `codev_servicecontract_table` SET description = '$formattedValue' WHERE id='$this->id' ";
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
      $query = "UPDATE `codev_command_table` SET state='$value' WHERE id='$this->id' ";
      $result = mysql_query($query);
	   if (!$result) {
    	      $this->logger->error("Query FAILED: $query");
    	      $this->logger->error(mysql_error());
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   }


   public function getStartDate() {
      return $this->start_date;
   }
   public function setStartDate($value) {
      $formattedValue = mysql_real_escape_string($value); // should be in controler, not here
      $this->start_date = date2timestamp($formattedValue);
      $query = "UPDATE `codev_servicecontract_table` SET start_date = '$this->start_date' WHERE id='$this->id' ";
      $result = mysql_query($query);
      if (!$result) {
             $this->logger->error("Query FAILED: $query");
             $this->logger->error(mysql_error());
             echo "<span style='color:red'>ERROR: Query FAILED</span>";
             exit;
      }
   }

   public function getEndDate() {
      return $this->end_date;
   }
   public function setEndDate($value) {
      $formattedValue = mysql_real_escape_string($value); // should be in controler, not here
      $this->end_date = date2timestamp($formattedValue);
      $query = "UPDATE `codev_servicecontract_table` SET end_date = '$this->end_date' WHERE id='$this->id' ";
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
    * @param int $type  CommandSet::type_general
    * @return array commandset_id => CommandSet
    */
   public function getCommandSets($type) {

      // TODO: if type==NULL return for all types

      $cmdsetList = array();

      $cmdsetidList = $this->cmdsetidByTypeList[$type];

      foreach ($cmdsetidList as $commandset_id) {

         $cmdsetList[$commandset_id] = CommandSetCache::getInstance()->getCommandSet($commandset_id);
      }

      return $cmdsetList;
   }

   /**
    * Collect the Issues of all the CommandSets (of a given type)
    *
    * @param int $type CommandSet::type_general
    *
    * @return IssueSelection
    */
   public function getIssueSelection($type) {

      // TODO: if type==NULL return for all types

      $issueSelection = new IssueSelection();

      $cmdsetidList = $this->cmdsetidByTypeList[$type];

      foreach ($cmdsetidList as $commandset_id) {

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandset_id);

         $cmdsetIS = $cmdset->getIssueSelection();
         $issueSelection->addIssueList($cmdsetIS->getIssueList());

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
         $this->logger->error("addCommandSet($commandset_id): CommandSet $commandset_id does not exist !");
         echo "<span style='color:red'>ERROR: CommandSet '$commandset_id' does not exist !</span>";
         return NULL;
      }

      $this->logger->debug("Add CommandSet $commandset_id to ServiceContract $this->id");

      if (NULL == $this->cmdsetidByTypeList["$type"]) {
         $this->cmdsetidByTypeList["$type"] = array();
      }
      $this->cmdsetidByTypeList["$type"][] = $commandset_id;

      $query = "INSERT INTO `codev_servicecontract_cmdset_table` (`servicecontract_id`, `commandset_id`, `type`) VALUES ('$this->id', '$commandset_id', '$type');";
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
    * @param int $commandset_id
    */
   public function removeCommandSet($commandset_id) {

      $typeList = array_keys($this->cmdsetidByTypeList);
      foreach ($typeList as $type) {
         if (NULL != $this->cmdsetidByTypeList[$type][$commandset_id]) {
            unset($this->cmdsetidByTypeList[$type][$commandset_id]);
            # break;
         }
      }

      $query = "DELETE FROM `codev_servicecontract_cmdset_table` WHERE servicecontract_id='$this->id' AND commandset_id='$commandset_id';";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
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
         ProjectCache::getInstance()->getProject($project_id);
      } catch (Exception $e) {
         $this->logger->error("addCommandSet($project_id): CommandSet $project_id does not exist !");
         echo "<span style='color:red'>ERROR: CommandSet '$project_id' does not exist !</span>";
         return NULL;
      }

      $this->logger->debug("Add CommandSet $project_id to ServiceContract $this->id");

      if (NULL == $this->sidetasksProjectList) {
         $this->sidetasksProjectList = array();
      }
      $this->sidetasksProjectList[] = $project_id;

      $query = "INSERT INTO `codev_servicecontract_stproj_table` (`servicecontract_id`, `project_id`, `type`) VALUES ('$this->id', '$project_id', '$type');";
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
    * @param int $project_id
    */
   public function removeSidetaskProject($project_id) {

      if (NULL != $this->sidetasksProjectList[$project_id]) {
         unset($this->sidetasksProjectList[$project_id]);
      }

      $query = "DELETE FROM `codev_servicecontract_stproj_table` WHERE servicecontract_id='$this->id' AND project_id='$project_id';";
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
    * @return array
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






}

?>
