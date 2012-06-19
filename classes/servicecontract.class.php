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

require_once "project.class.php";

/**
 * Description of ServiceContract
 *
 */
class ServiceContract {


   // TODO states must be defined
   const state_default       = 1;

  // TODO i18n for constants
  public static $stateNames = array(ServiceContract::state_default       => "Default");

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

   // [cat_id] = IssueSelection("categoryName")
   private $sidetasksPerCategory;

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
      // ---
      $query  = "SELECT * FROM `codev_servicecontract_table` WHERE id=$this->id ";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = mysql_fetch_object($result);
      $this->name        = $row->name;
      $this->teamid      = $row->team_id;
   	$this->state            = $row->state;
      $this->reference        = $row->reference;
      $this->version          = $row->version;
      $this->reporter         = $row->reporter;
      $this->description = $row->description;
      $this->start_date        = $row->start_date;
      $this->end_date        = $row->end_date;

      // --- CommandSets
      $this->cmdsetidByTypeList = array();
      $query  = "SELECT * FROM `codev_servicecontract_cmdset_table` ".
                "WHERE servicecontract_id=$this->id ";
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
         if (NULL == $this->cmdsetidByTypeList["$row->type"]) {
            $this->cmdsetidByTypeList["$row->type"] = array();
         }
          $this->cmdsetidByTypeList["$row->type"][] = $row->commandset_id;
      }

      // --- SidetaskProjects
      $this->sidetasksProjectList = array();
      $query  = "SELECT * FROM `codev_servicecontract_stproj_table` ".
                "WHERE servicecontract_id=$this->id ";
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
          $this->sidetasksProjectList[] = $row->project_id;
      }
   }

   /**
    * create a new commandset in the DB
    *
    * @return int $id
    */
   public static function create($name, $teamid) {
    $query = "INSERT INTO `codev_servicecontract_table`  (`name`, `team_id`) ".
             "VALUES ('$name', '$teamid');";
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
    *
    * @return array project_id => Project
    */
   public function getProjects() {

      // TODO: if type==NULL return for all types

      $prjList = array();

      foreach ($this->sidetasksProjectList as $project_id) {

         $prjList[$project_id] = ProjectCache::getInstance()->getProject($project_id);
      }

      return $prjList;
   }

   /**
    * Collect the Issues of all the CommandSets (of a given type)
    *
    * @param int $type CommandSet::type_general
    *
    * @return IssueSelection
    */
   public function getIssueSelection($cset_type, $cmd_type) {

      // TODO: if type==NULL return for all types

      $issueSelection = new IssueSelection();

      $cmdsetidList = $this->cmdsetidByTypeList[$cset_type];

      foreach ($cmdsetidList as $commandset_id) {

         $cmdset = CommandSetCache::getInstance()->getCommandSet($commandset_id);
         $cmdsetIS = $cmdset->getIssueSelection($cmd_type);
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

/**
 *
 * @param int $servicecontractid
 * @return array[category_id] = IssueSelection("categoryName")
 */
function getSidetasksPerCategory() {

   if (NULL == $this->sidetasksPerCategory) {

      $this->sidetasksPerCategory = array();

      $prjList = $this->getProjects();
      foreach ($prjList as $id => $project) {

         if (!$project->isSideTasksProject(array($this->getTeamid()))) {
            $this->logger->error("getSidetasks: SKIPPED project $id (".$project->name.") should be a SidetasksProject !");
            continue;
         }
         $bugidList = $project->getIssueList();

         foreach ($bugidList as $bugid) {

            $issue = IssueCache::getInstance()->getIssue($bugid);

            if (NULL == $this->sidetasksPerCategory[$issue->categoryId]) {
               $this->sidetasksPerCategory[$issue->categoryId] = new IssueSelection($issue->getCategoryName());
            }
            $issueSel = $this->sidetasksPerCategory[$issue->categoryId];
            $issueSel->addIssue($bugid);
         }
      }
   }
   return $this->sidetasksPerCategory;
}





}

?>
