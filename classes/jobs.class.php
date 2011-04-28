<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?php 

// MANTIS CoDev

// LoB 01 Feb 2011

// =======================================
class Job {
   var $id;
   var $name;
   var $type;
   var $color;
   
    public function Job($id, $name, $type, $color) {
    	$this->id    = $id;
      $this->name  = $name;
      $this->type  = $type;
      $this->color = $color;
    }
}


// =======================================
class Jobs {

   var $jobList;
   
   // --------------------
   public function Jobs() {

   	$this->jobList = array();
   	
      $query = "SELECT * FROM `codev_job_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
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
   	global $job_support;
      return ($this->id == $job_support);
   }
}
?>