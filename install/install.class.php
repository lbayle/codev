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


/*
 * 
 * Step 1
 * 
 * - [user] create DB config file & test connection
 * - [auto] create DB tables (from SQL file)
 * - [auto] create admin team
 * 
 * Step 2
 * 
 * - [auto] create custom fields & add to codev_config_table
 * - [auto] create CodevMetaProject (optional ?)
 * - [user] update codev_config_table with user prefs
 * - [user] 
 * 
 * - [user] create CommonSideTasks Project
 * - [auto] asign N/A job to commonSideTasks
 * - [user] create default side tasks
 * - [user] config astreintes

 * Step 3
 * - [user] create jobs
 * - [user] config support job
 * 
 */

include_once 'project.class.php'; 


class Install {
   
   const FILENAME_MYSQL_CONFIG = "../include/mysql_config_inc2.php";
    
   private $fieldList;
   
   // --------------------------------------------------------
   public function __construct() 
   {
   	// get existing Mantis custom fields
      $this->fieldList = array();
      $query = "SELECT id, name FROM `mantis_custom_field_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $this->fieldList["$row->name"] = $row->id;
      }
      
   }

    
   // --------------------------------------------------------
   /**
    * Checks if DB connection is OK
    * @param unknown_type $db_mantis_host
    * @param unknown_type $db_mantis_user
    * @param unknown_type $db_mantis_pass
    * @param unknown_type $db_mantis_database
    *
    * @return NULL if OK, or an error message.
    */   
   public function checkDBConnection($db_mantis_host     = 'localhost', 
                                     $db_mantis_user     = 'codev', 
                                     $db_mantis_pass     = '',
                                     $db_mantis_database = 'bugtracker') {
      
      $bugtracker_link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass);
      if (!$bugtracker_link) {
      	return ("Could not connect to database: ". mysql_error());
      }
   
      $db_selected = mysql_select_db($db_mantis_database);
      if (!$db_selected) {
      	return ("Could not select database: ". mysql_error());
      }
                                     	
                                     	
      $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'database_version'";
      $result = mysql_query($query);
      if (!$result) {
         return ("Query failed : " . mysql_error());
      }
      $database_version  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : -1;
      
      if (-1 == $database_version) {
      	return "Could not get mantis_config_table.database_version";
      }
      return NULL;
   }
    
   // --------------------------------------------------------
	/**
	 * updates mysql_config_inc.php with connection parameters
	 */
	public function createMysqlConfigFile($db_mantis_host     = 'localhost', 
	                                      $db_mantis_user     = 'codev', 
	                                      $db_mantis_pass     = '',
	                                      $db_mantis_database = 'bugtracker') {
      // create/overwrite file
      $fp = fopen(self::FILENAME_MYSQL_CONFIG, 'w');
      
      $stringData = "<?php\n";
      $stringData .= "   // Mantis DB infomation.\n";
      $stringData .= "   \$db_mantis_host      =  '$db_mantis_host';\n";
      $stringData .= "   \$db_mantis_user      =  '$db_mantis_user';\n";
      $stringData .= "   \$db_mantis_pass      =  '$db_mantis_pass';\n";
      $stringData .= "   \$db_mantis_database  =  '$db_mantis_database';\n";
      $stringData .= "?>\n";
      fwrite($fp, $stringData);
      fclose($fp);
	}
	
	
   // --------------------------------------------------------
   /**
    * 
    * @param $sqlFile
    */
	public function execSQLscript($sqlFile = "bugtracker_install.sql") {
 
      $requetes="";
 
      $sql=file($sqlFile); 
      foreach($sql as $l){ 
         if (substr(trim($l),0,2)!="--"){ // remove comments
            $requetes .= $l;
         }
      }
 
      $reqs = split(";",$requetes);// identify single requests
      foreach($reqs as $req){
         if (!mysql_query($req,$bdd) && trim($req)!="") {
            die("ERROR : ".$req); 
         }
      }
      echo "done";
	}
	
   // --------------------------------------------------------
	/**
	 * create a customField in Mantis (if not exist) & update codev_config_table
	 * 
	 * ex: $install->createCustomField("TC", 0, "customField_TC");
	 * 
	 * @param string $fieldName Mantis field name
	 * @param int $fieldType Mantis field type
	 * @param string $configId  codev_config_table.config_id 
	 */
	public function createCustomField($fieldName, $fieldType, $configId, $default_value=NULL, $possible_values=NULL) {

      $access_level_r   = 10;
      $access_level_rw  = 25;
      $require_report   = 1;
      $require_update   = 1;
      $require_resolved = 0;
      $require_closed   = 0;
      $display_report   = 1;
      $display_update   = 1;
      $display_resolved = 0;
      $display_closed   = 0;

		//--------
      $fieldId = $this->fieldList[$fieldName];
      if (!$fieldId) {
      	echo "DEBUG INSERT $fieldName<br/>";
         $query2  = "INSERT INTO `mantis_custom_field_table` ".
                    "(`name`, `type` ,`access_level_r`,`access_level_rw` ,`require_report` ,`require_update` ,`display_report` ,`display_update` ,`require_resolved` ,`display_resolved` ,`display_closed` ,`require_closed` ";
         if ($possible_values) {
         	$query2 .= ", `possible_values`";
         }
         if ($default_value) {
            $query2 .= ", `default_value`";
         }
         $query2 .= ") VALUES ('$fieldName', '$fieldType', '$access_level_r', '$access_level_rw', '$require_report', '$require_update', '$display_report', '$display_update', '$require_resolved', '$display_resolved', '$display_closed', '$require_closed'";
         if ($possible_values) {
            $query2 .= ", '$possible_values'";
         }
         if ($default_value) {
            $query2 .= ", '$default_value'";
         }
         $query2 .= ");";
         $result2  = mysql_query($query2) or die("Query failed: $query2");
         $fieldId = mysql_insert_id();
      }
      Config::getInstance()->addValue($configId, $fieldId, Config::configType_int);
      
	}

   // --------------------------------------------------------
	/**
	 * 
	 */
	public function createCustomFields() {
      $this->createCustomField("TC",                               0, "customField_TC");          // CoDev FDJ custom
      $this->createCustomField("Est. Effort (BI)",                 1, "customField_effortEstim");
      $this->createCustomField("Remaining (RAE)",                  1, "customField_remaining");
      $this->createCustomField("Budget supp. (BS)",                1, "customField_addEffort");
      $this->createCustomField("Dead Line",                        8, "customField_deadLine");
      $this->createCustomField("FDL",                              0, "customField_deliveryId");  // CoDev FDJ custom
      $this->createCustomField("Liv. Date",                        8, "customField_deliveryDate");
      $this->createCustomField("Preliminary Est. Effort (ex ETA)", 3, "customField_PrelEffortEstim", "none", "none|< 1 day|2-3 days|< 1 week|< 2 weeks|> 2 weeks");
      
	}
	
	
	
   // --------------------------------------------------------
   /**
    * 
    * @param unknown_type $projectName
    */
	public function createCommonSideTasksProject($projectName = "SideTasks") {
		
		// create project
		$projectid = Project::createSideTaskProject($projectName);
		
		// update defaultSideTaskProject in codev_config_table
      Config::getInstance()->addValue("defaultSideTaskProject", $projectid, Config::configType_int ,T_("CoDev commonSideTasks Project"));
		
      // assign N/A Job
      #REM: N/A job is id=1, created by SQL file
      $query  = "INSERT INTO `codev_project_job_table` (`project_id`, `job_id`) VALUES ('$projectid', '1');";
      $result = mysql_query($query) or die("Query failed: $query");
		
      return $projectid;
	}
	
} // class

?>