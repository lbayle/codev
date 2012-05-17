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
 * Un engagement (fiche de presta) est un ensemble de taches que l'on veut
 * piloter a l'aide d'indicateurs (cout, delai, qualite, avancement)
 *
 * un engagement peut contenir des taches précises (mantis)
 * mais également définir des objectifs d'ordre global ou non
 * liés au dev.
 *
 * un engagement est provisionné d'un certain budjet, négocié avec le client.
 * le cout de l'ensemble des taches devrait etre a l'equilibre avec ce budjet.
 */
class Engagement {

   private $logger;

   // codev_engagement_table
   private $id;
   private $name;
   private $desc;
   private $startDate;
   private $deadline;
   private $teamid;
   private $budjetDev;
   private $budjetMngt;

   // codev_engagement_bug_table
   private $issueSelection;


   function __construct($id) {

   	$this->logger = Logger::getLogger(__CLASS__);

   	$this->id = $id;
   	$this->initialize();
   }

   private function initialize() {
   	// ---
   	$query  = "SELECT * FROM `codev_engagement_table` WHERE id=$this->id ";
   	$result = mysql_query($query);
   	if (!$result) {
	   	$this->logger->error("Query FAILED: $query");
	   	$this->logger->error(mysql_error());
	   	echo "<span style='color:red'>ERROR: Query FAILED</span>";
	   	exit;
   	}
   	$row = mysql_fetch_object($result);
      $this->name       = $row->name;
      $this->desc       = $row->desc;
   	$this->startDate  = $row->start_date;
   	$this->deadline   = $row->deadline;
   	$this->teamid     = $row->team_id;
   	$this->budjetDev  = $row->budjet_dev;
   	$this->budjetMngt = $row->budjet_mngt;

   	// ---
   	$this->issueSelection = new IssueSelection($this->name);
   	$query  = "SELECT * FROM `codev_engagement_bug_table` WHERE engagement_id=$this->id ";
   	$result = mysql_query($query);
   	if (!$result) {
	   	$this->logger->error("Query FAILED: $query");
	   	$this->logger->error(mysql_error());
	   	echo "<span style='color:red'>ERROR: Query FAILED</span>";
	   	exit;
   	}
   	while($row = mysql_fetch_object($result))
   	{
   		$this->issueSelection->addIssue($row->bug_id);
   	}
   }

   public function getName() {
      return $this->name;
   }

   public function getDesc() {
      return $this->desc;
   }

   public function getIssueSelection() {
      return $this->issueSelection;
   }


   /**
    * create a new engagement in the DB
    *
    * @return int $id
    */
   public static function create($name, $startDate, $deadline, $teamid, $budjetDev, $budjetMngt) {
    $query = "INSERT INTO `codev_engagement_table`  (`name`, `start_date`, `deadline`, `team_id`, `budjet_dev`, `budjet_mngt`) ".
             "VALUES ('$name','$startDate','$deadline', '$teamid', '$budjetDev', '$budjetMngt');";
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
    * add Issue to engagement (in DB & current instance)
    */
   public function addIssue($bugid) {
      $this->issueSelection->addIssue($bugid);

      $query = "INSERT INTO `codev_engagement_bug_table` (`bug_id`) VALUES ('$bugid');";
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






}
?>