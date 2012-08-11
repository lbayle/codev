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

include_once('classes/config.class.php');
include_once('classes/sqlwrapper.class.php');

require_once('classes/comparable.interface.php');

/**
 * @author LoB
 * @date 01 Feb 2011
 */
class Job implements Comparable {

  const type_commonJob = 0;     // jobs common to all projects are type 0
  const type_assignedJob = 1;     // jobs specific to one or more projects are type 1

  public static $typeNames = array(
     self::type_commonJob => "Common",
     self::type_assignedJob => "Assigned"
  );

   var $id;
   var $name;
   var $type;
   var $color;

   /**
    * @param int $id
    * @param string $name
    * @param $type
    * @param $color
    */
   public function __construct($id, $name, $type, $color) {
      $this->id = $id;
      $this->name = $name;
      $this->type = $type;
      $this->color = $color;
   }

   /**
    * Sort by asc
    * @static
    * @param Job $jobA
    * @param Job $jobB
    * @return int 1 if $jobB is higher, -1 if $jobB is lower, 0 if equals
    */
   public static function compare(Comparable $jobA, Comparable $jobB) {
      return strcmp($jobA->name, $jobB->name);
   }
}

class Jobs {

   const JOB_NA = 1; // REM: N/A     job_id = 1, created by SQL file at install
   const JOB_SUPPORT = 2; // REM: Support job_id = 2, created by SQL file at install

   /**
    * @var Job[] The job list
    */
   private $jobList;

   public function __construct() {
      $this->jobList = array();

      $query = "SELECT * FROM `codev_job_table`";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $j = new Job($row->id, $row->name, $row->type, $row->color);
         $this->jobList[$row->id] = $j;
      }
   }

   /**
    * @return Job[]
    */
   public function getJobs() {
      return array_values($this->jobList);
   }

   /**
    * @param int $id
    * @return string
    */
   public function getJobName($id) {
      return $this->jobList[$id]->name;
   }

   /**
    * @param int $id
    * @return string
    */
   public function getJobColor($id) {
      return $this->jobList[$id]->color;
   }

   /**
    * @param int $id
    * @return mixed
    */
   public function getJobType($id) {
      return $this->jobList[$id]->type;
   }

   /**
    * @param int $id
    * @return bool
    */
   public function isSupport($id) {
      return ($id == Config::getInstance()->getValue(Config::id_jobSupport));
   }

   /**
    * @param string $job_name
    * @param string $job_type
    * @param string $job_color
    * @return int $job_id
    */
   public static function create($job_name, $job_type, $job_color) {
      $formattedName = SqlWrapper::sql_real_escape_string($job_name);
      $query = "INSERT INTO `codev_job_table`  (`name`, `type`, `color`) VALUES ('$formattedName','$job_type','$job_color');";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      return SqlWrapper::getInstance()->sql_insert_id();
   }

   /**
    * @static
    * @param int $project_id
    * @param int $job_id
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
