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

   /**
    * [cat_type] = IssueSelection("catTypeName")
    * @var IssueSelection[][]
    */
   private $sidetasksPerCategoryType;

   private $commandList;
   private $provisionList;

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
         $sql = AdodbWrapper::getInstance();
         $query  = "SELECT * FROM codev_servicecontract_table WHERE id = ".$sql->db_param();
         $result= $sql->sql_query($query, array($this->id));
         $row = $sql->fetchObject($result);
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

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT count(*) FROM codev_servicecontract_table WHERE name = ".$sql->db_param();
      $result = $sql->sql_query($query, array($name));

      $count = $sql->sql_result($result);

      if($count == 0) {

         $query = "INSERT INTO codev_servicecontract_table  (name, team_id) ".
                  "VALUES (".$sql->db_param().", ".$sql->db_param().")";

         $sql->sql_query($query, array($name, $teamid));

         return AdodbWrapper::getInstance()->getInsertId();
      } else {
         throw new Exception('Already exists');
      }
   }

   /**
    * delete a ServcieContract
    * @static
    * @param int $id
    * @return int $id
    */
   public static function delete($id) {
      $sql = AdodbWrapper::getInstance();
      try {
         $query = "DELETE FROM codev_servicecontract_cmdset_table WHERE servicecontract_id = ".$sql->db_param();
         $sql->sql_query($query, array($id));
      } catch (Exception $e) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      try {
         $query = "DELETE FROM codev_servicecontract_stproj_table WHERE servicecontract_id = ".$sql->db_param();
         $sql->sql_query($query, array($id));
      } catch (Exception $e) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }

      try {
         $query = "DELETE FROM codev_servicecontract_table WHERE id = ".$sql->db_param();
         $sql->sql_query($query, array($id));
      } catch (Exception $e) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>\n";
         #exit;
      }
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
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET team_id = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($value, $this->id));
   }

   public function getName() {
      return $this->name;
   }

   public function setName($name) {
      $this->name = $name;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET name = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($name, $this->id));
   }

   public function getReference() {
      return $this->reference;
   }

   public function setReference($value) {
      $this->reference = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET reference = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($value, $this->id));
   }

   public function getVersion() {
      return $this->version;
   }

   public function setVersion($value) {
      $this->version = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET version = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($value, $this->id));
   }

   public function getReporter() {
      return $this->reporter;
   }

   public function setReporter($value) {
      $this->reporter = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET reporter = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($value, $this->id));
   }

   public function getDesc() {
      return $this->description;
   }

   public function setDesc($description) {
      $this->description = $description;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET description = ".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($description, $this->id));
   }

   public function getState() {
      return $this->state;
   }

   public function setState($value) {
      $this->state = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET state=".$sql->db_param().
               " WHERE id = ".$sql->db_param();
      $sql->sql_query($query, array($value, $this->id));
   }

   public function getStartDate() {
      return $this->start_date;
   }

   public function setStartDate($value) {
      $this->start_date = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET start_date = ".$sql->db_param().
               " WHERE id=".$sql->db_param();
      $sql->sql_query($query, array($this->start_date, $this->id));
   }

   public function getEndDate() {
      return $this->end_date;
   }

   public function setEndDate($value) {
      $this->end_date = $value;
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_servicecontract_table SET end_date = ".$sql->db_param().
               " WHERE id=".$sql->db_param();
      $sql->sql_query($query, array($this->end_date, $this->id));
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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_servicecontract_cmdset_table ".
                  "WHERE servicecontract_id =  ".$sql->db_param().
                  "ORDER BY type ASC, commandset_id ASC";

         $result = $sql->sql_query($query, array($this->id));

         $this->cmdsetidByTypeList = array();
         while($row = $sql->fetchObject($result)) {
            if (!array_key_exists($row->type,$this->cmdsetidByTypeList)) {
               $this->cmdsetidByTypeList[$row->type] = array();
            }
            $this->cmdsetidByTypeList[$row->type][] = $row->commandset_id;
         }
      }

      if(NULL == $type) {
         return $this->cmdsetidByTypeList;
      } else if (array_key_exists($type, $this->cmdsetidByTypeList)) {
         return $this->cmdsetidByTypeList[$type];
      } else {
         return NULL;
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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT project.* FROM {project} as project ".
                  "JOIN codev_servicecontract_stproj_table as servicecontract_stproj ON project.id = servicecontract_stproj.project_id ".
                  "WHERE servicecontract_stproj.servicecontract_id = ".$sql->db_param().
                  "ORDER BY type ASC, project.id ASC;";
         $result = $sql->sql_query($query, array( $this->id));

         $this->sidetasksProjectList = array();
         while($row = $sql->fetchObject($result)) {
            $this->sidetasksProjectList["$row->id"] = ProjectCache::getInstance()->getProject($row->id, $row);
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

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Add CommandSet $commandset_id to ServiceContract $this->id");
      }

      if (NULL == $this->getCommandSetIds($type)) {
         $this->cmdsetidByTypeList[$type] = array();
      }
      $this->cmdsetidByTypeList[$type][] = $commandset_id;

      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO codev_servicecontract_cmdset_table (servicecontract_id, commandset_id, type)".
               " VALUES (".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().")";
      $sql->sql_query($query, array($this->id, $commandset_id, $type));

      return $sql->getInsertId();
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
         $key = array_search($commandset_id, $this->cmdsetidByTypeList[$type]);
         if (FALSE !== $key) {
            unset($this->cmdsetidByTypeList[$type][$key]);
            # break;
         }
      }

      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_servicecontract_cmdset_table WHERE servicecontract_id = ".$sql->db_param().
               " AND commandset_id = ".$sql->db_param();
      $sql->sql_query($query, array($this->id, $commandset_id));
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

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("Add CommandSet $project_id to ServiceContract $this->id");
      }

      $this->getProjects();
      if (!isset($this->sidetasksProjectList["$project_id"])) {
         $this->sidetasksProjectList["$project_id"] = $project;

         $sql = AdodbWrapper::getInstance();
         $query = "INSERT INTO codev_servicecontract_stproj_table (servicecontract_id, project_id, type)".
                  " VALUES (".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().")";
         $sql->sql_query($query, array($this->id, $project_id, $type));

         return AdodbWrapper::getInstance()->getInsertId();
      }
   }

   /**
    * remove command from commandset commandList.
    * the Command itself is not deleted.
    *
    * @param int $project_id
    */
   public function removeSidetaskProject($project_id) {

      $key = array_search($project_id, $this->sidetasksProjectList);
      if (FALSE !== $key) {
         unset($this->sidetasksProjectList[$key]);
      }

      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_servicecontract_stproj_table".
               " WHERE servicecontract_id = ".$sql->db_param().
               " AND project_id = ".$sql->db_param();
      $sql->sql_query($query, array($this->id, $project_id));
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
    * @return IssueSelection[] : array[category_type] = IssueSelection("categoryName")
    */
   function getSidetasksPerCategoryType($skipIfInCommands = false) {
      if (NULL == $this->sidetasksPerCategoryType) { $this->sidetasksPerCategoryType = array(); }

      $key = ($skipIfInCommands) ? 'skip_yes' : 'skip_no';

      if (!array_key_exists($key, $this->sidetasksPerCategoryType)) {

         $this->sidetasksPerCategoryType[$key] = array();

         if ($skipIfInCommands) {
            $cmdidList = array_keys($this->getCommands(CommandSet::type_general, Command::type_general));
         }

         $prjList = $this->getProjects();
         foreach ($prjList as $id => $project) {
            try {
               if (!$project->isSideTasksProject(array($this->teamid))) {
                  self::$logger->error("getSidetasksPerCategoryType: SKIPPED project $id (".$project->getName().") should be a SidetasksProject !");
                  continue;
               }
            } catch (Exception $e) {
               self::$logger->error("getSidetasksPerCategoryType: EXCEPTION SKIPPED project $id (".$project->getName().") : ".$e->getMessage());
               continue;
            }

            $issueList = $project->getIssues();
            foreach ($issueList as $issue) {

               if ($skipIfInCommands) {
                  // compare the Commands of the Issue whit the Commands of this ServiceContract
                  $issueCmdidList = array_keys($issue->getCommandList());
                  $isInCommands = 0 != count(array_intersect($cmdidList, $issueCmdidList));
                  if ($isInCommands) {
                     if(self::$logger->isDebugEnabled()) {
                        self::$logger->debug("getSidetasksPerCategoryType(): skip issue ".$issue->getId()." because already declared in a Command");
                     }
                     continue;
                  }
               }

               // find category type (depends on project)
               $proj = ProjectCache::getInstance()->getProject($issue->getProjectId());
               $categoryList = $proj->getCategoryList();
               $cat_type = array_search( $issue->getCategoryId() , $categoryList);

               if (is_numeric($cat_type)) {
                  $cat      = $cat_type;
                  $cat_name = Project::$catTypeNames["$cat_type"];
               } else {
                  $cat      = 'CAT_ID_'.$issue->getCategoryId();
                  $cat_name = $issue->getCategoryName();
               }
#echo "cat_type = $cat_type id=".$issue->getCategoryId()." $cat_name<br>";

               if (!array_key_exists($cat, $this->sidetasksPerCategoryType[$key])) {
                  $this->sidetasksPerCategoryType[$key][$cat] = new IssueSelection($cat_name);
               }
               $issueSel = $this->sidetasksPerCategoryType[$key][$cat];
               $issueSel->addIssue($issue->getId());
            }
         }
      }
      return $this->sidetasksPerCategoryType[$key];
   }


   /**
    * @param int $cset_type  CommandSet::type_general
    * @param int $cmd_type  Command::type_general
    * @param int $prov_type CommandProvision::provision_xxx
    * @return array CommandProvision
    */
   public function getProvisionList($cset_type, $cmd_type, $prov_type = NULL) {

      $key= 'P'.$cset_type.'_'.$cmd_type.'_'.$prov_type;
      if (is_null($this->provisionList)) { $this->provisionList = array(); }

      if (!array_key_exists($key, $this->provisionList)) {

         $cmdidList = array_keys($this->getCommands($cset_type, $cmd_type));
         if (empty($cmdidList)) {
             self::$logger->warn("ServiceContract $this->id : no commands for type $cmd_type");
            return array();
         }
         $formattedCmdidList = implode(',', $cmdidList);

         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_command_provision_table ".
                 "WHERE command_id IN (".$formattedCmdidList.") ";

         if (!is_null($prov_type)) {
            $query .= " AND type = ".$sql->db_param();
            $q_params[]=$prov_type;
         }
         $query .= " ORDER BY date ASC, type ASC";

         $result = $sql->sql_query($query, $q_params);

         $this->provisionList[$key] = array();
         while ($row = $sql->fetchObject($result)) {
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

   /**
    * Sum all the BudjetDays provisions
    *
    * @param int $cset_type  CommandSet::type_general
    * @param int $cmd_type  Command::type_general
    * @param int $prov_type CommandProvision::provision_xxx
    * @return type
    *
    */
   public function getProvisionDays($cset_type, $cmd_type, $prov_type = NULL) {

      $provisions = $this->getProvisionList($cset_type, $cmd_type, $prov_type);
      $budgetDays = 0;
      foreach ($provisions as $prov) {
         if (is_null($prov_type) || ($prov_type == $prov->getType())) {
            $budgetDays += $prov->getProvisionDays();
         }
      }
      return $budgetDays;
   }

   public function getProvisionDaysByType($cset_type, $cmd_type) {

      $provDaysByType = array();
      $provisions = $this->getProvisionList($cset_type, $cmd_type);
      foreach ($provisions as $prov) {
         $prov_type = $prov->getType();
         $provDaysByType["$prov_type"] += $prov->getProvisionDays();
      }
      return $provDaysByType;
   }


}

ServiceContract::staticInit();

