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
 * Un service (fiche de presta) est un ensemble de taches que l'on veut
 * piloter a l'aide d'indicateurs (cout, delai, qualite, avancement)
 *
 * un service peut contenir des taches précises (mantis)
 * mais également définir des objectifs d'ordre global ou non
 * liés au dev.
 *
 * un service est provisionné d'un certain budjet, négocié avec le client.
 * le cout de l'ensemble des taches devrait etre a l'equilibre avec ce budjet.
 */
class Service {

   const engType_dev   = 1;    // in table codev_service_eng_table
   const engType_mngt  = 2;    // in table codev_service_eng_table


   private $logger;

   // codev_service_table
   private $id;
   private $name;
   private $description;
   private $date;
   private $teamid;

   // list of engagements, ordered by type
   // engByTypeList[type][engid]
   private $engByTypeList;


   function __construct($id) {

      $this->logger = Logger::getLogger(__CLASS__);

      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating an Service with id=0 is not allowed.");
         $this->logger->error("EXCEPTION Service constructor: ".$e->getMessage());
         $this->logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize();
   }

   private function initialize() {
      // ---
      $query  = "SELECT * FROM `codev_service_table` WHERE id=$this->id ";
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

      // ---
      $this->engByTypeList = array();
      $query  = "SELECT * FROM `codev_service_eng_table` ".
                "WHERE service_id=$this->id ";
                "ORDER BY type ASC, engagement_id ASC";

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
          $this->engByTypeList["$row->type"][] = $row->engagement_id;
      }
   }

   public function getId() {
      return $this->id;
   }

   public function getName() {
      return $this->name;
   }
   public function setName($name) {
      $formattedValue = mysql_real_escape_string($name);
      $this->name = $formattedValue;
      $query = "UPDATE `codev_service_table` SET name = '$formattedValue' WHERE id='$this->id' ";
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
      $query = "UPDATE `codev_service_table` SET description = '$formattedValue' WHERE id='$this->id' ";
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
      $query = "UPDATE `codev_service_table` SET date = '$this->date' WHERE id='$this->id' ";
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
    * @return array engid => Engagement
    */
   public function getEngagements($type) {

      $engList = array();

      $engidList = $this->engByTypeList[$type];

      foreach ($engidList as $engid) {

         $engList[$engid] = EngagementCache::getInstance()->getEngagement($engid);
      }

      return $engList;
   }


   /**
    * create a new service in the DB
    *
    * @return int $id
    */
   public static function create($name, $date, $teamid) {
    $query = "INSERT INTO `codev_service_table`  (`name`, `date`, `team_id`) ".
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
    * add Issue to service (in DB & current instance)
    *
    * @param int $bugid
    */
   public function addEngagement($engid, $type) {

      try {
         EngagementCache::getInstance()->getEngagement($engid);
      } catch (Exception $e) {
         $this->logger->error("addEngagement($engid): Engagement $engid does not exist !");
         echo "<span style='color:red'>ERROR: Engagement  '$engid' does not exist !</span>";
         return NULL;
      }

      $this->logger->debug("Add engagement $engid to service $this->id");

      if (NULL == $this->engByTypeList["$type"]) {
         $this->engByTypeList["$type"] = array();
      }
      $this->engByTypeList["$type"][] = $engid;

      $query = "INSERT INTO `codev_service_eng_table` (`service_id`, `engagement_id`, `type`) VALUES ('$this->id', '$engid', '$type');";
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
    * remove engagement from service engagementList.
    * the Engagement itself is not deleted.
    *
    * @param int $engid
    */
   public function removeEngagement($engid) {

      $typeList = array_keys($this->engByTypeList);

      foreach ($typeList as $type) {
         if (NULL != $this->engByTypeList[$type][$engid]) {
            unset($this->engByTypeList[$type][$engid]);
            # break;
         }
      }


      $query = "DELETE FROM `codev_service_eng_table` WHERE service_id='$this->id' AND engagement_id='$engid';";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }




}
?>