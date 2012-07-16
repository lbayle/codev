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
 * @author LoB
 * @date 01 Feb 2011
 */
class Job {

  const type_commonJob   = 0;     // jobs common to all projects are type 0
  const type_assignedJob = 1;     // jobs specific to one or more projects are type 1


  public static $typeNames = array(Job::type_commonJob    => "Common",
                                   Job::type_assignedJob  => "Assigned");

   var $id;
   var $name;
   var $type;
   var $color;

    public function __construct($id, $name, $type, $color) {
    	$this->id    = $id;
      $this->name  = $name;
      $this->type  = $type;
      $this->color = $color;
    }
}


// =======================================
class Jobs {

   private $logger;

   const JOB_NA      = 1; // REM: N/A     job_id = 1, created by SQL file at install
   const JOB_SUPPORT = 2; // REM: Support job_id = 2, created by SQL file at install

   var $jobList;

   // --------------------
   public function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

   	$this->jobList = array();

      $query = "SELECT * FROM `codev_job_table`";
   	$result = SqlWrapper::getInstance()->sql_query($query);
	   if (!$result) {
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
            while($row = SqlWrapper::getInstance()->sql_fetch_object($result))
      {
      	$j = new Job($row->id, $row->name, $row->type, $row->color);
         $this->jobList[$row->id] = $j;
      }
   }

   public function getJobName($id) {
   	return $this->jobList[$id]->name;
   }

   public function getJobColor($id) {
      return $this->jobList[$id]->color;
   }

   public function getJobType($id) {
      return $this->jobList[$id]->type;
   }

   public function isSupport($id) {
   	  $job_support = Config::getInstance()->getValue(Config::id_jobSupport);
      return ($id == $job_support);
   }

   /**
    *
    * @param unknown_type $job_name
    * @param unknown_type $job_type
    * @param unknown_type $job_color
    */
   public static function create($job_name, $job_type, $job_color) {

      $formattedName = SqlWrapper::getInstance()->sql_real_escape_string($job_name);
   	$query = "INSERT INTO `codev_job_table`  (`name`, `type`, `color`) VALUES ('$formattedName','$job_type','$job_color');";
      $result = SqlWrapper::getInstance()->sql_query($query);
	   if (!$result) {
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }
   	$job_id = SqlWrapper::getInstance()->sql_insert_id();

   	return $job_id;
   }

   /**
    *
    */
   public static function addJobProjectAssociation($project_id, $job_id) {
      $query = "INSERT INTO `codev_project_job_table`  (`project_id`, `job_id`) VALUES ('$project_id','$job_id');";
      $result = SqlWrapper::getInstance()->sql_query($query);
	   if (!$result) {
    	      echo "<span style='color:red'>ERROR: Query FAILED</span>";
    	      exit;
      }


   }
}
?>
