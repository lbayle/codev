<?php 

// MANTIS CoDev

// LoB 01 Feb 2011

// =======================================
class Job {
   var $id;
   var $name;
   var $type;
   
    public function Job($id, $name, $type) {
    	$this->id   = $id;
      $this->name = $name;
      $this->type = $type;
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
      	$j = new Job($row->id, $row->name, $row->type);
         $this->jobList[$row->id] = $j;
      }
   }
   
   public function getJobName($id) {
   	return $this->jobList[$id]->name;
   }

}
?>