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