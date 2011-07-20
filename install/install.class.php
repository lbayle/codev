<?php /*
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
*/ ?>

<?php


include_once 'project.class.php';
include_once 'team.class.php';
include_once 'jobs.class.php';
include_once 'config.class.php';
include_once 'config_mantis.class.php';


class Install {

   const FILENAME_MYSQL_CONFIG = "../include/mysql_config.inc.php";
   //const FILENAME_MYSQL_CONFIG = "/tmp/mysql_config.inc.php";

   const FILENAME_CONSTANTS = "../constants.php";
   //const FILENAME_CONSTANTS = "/tmp/constants.php";

   const JOB_DEFAULT_SIDETASK = 1; // REM: N/A     job_id = 1, created by SQL file
   const JOB_SUPPORT          = 2; // REM: Support job_id = 2, created by SQL file

   const PREL_EFFORT_ESTIM_POSSIBLE_VALUES = "none|< 1 day|2-3 days|< 1 week|< 2 weeks|> 2 weeks";
   const PREL_EFFORT_ESTIM_DEFAULT_VALUE   = "none";
   const PREL_EFFORT_ESTIM_BALANCE         = "1,1,3,5,10,15";

   private $fieldList;

   // --------------------------------------------------------
   public function __construct()
   {
   	// get existing Mantis custom fields
      $this->fieldList = array();

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

      $database_version = ConfigMantis::getInstance()->getValue(ConfigMantis::id_database_version);
      echo "DEBUG: Mantis database_version = $database_version<br/>";

      if (NULL == $database_version) {
      	return "Could not get mantis_config_table.database_version";
      }
      return NULL;
   }

   // --------------------------------------------------------
	/**
	 * updates mysql_config_inc.php with connection parameters
	 *
	 * WARN: depending on your HTTP server installation, the file may be created
	 * by user 'apache', so be sure that this user has write access
	 * to the CoDev install directory
	 */
	public function createMysqlConfigFile($db_mantis_host     = 'localhost',
	                                      $db_mantis_user     = 'codev',
	                                      $db_mantis_pass     = '',
	                                      $db_mantis_database = 'bugtracker') {
      echo "create file ".self::FILENAME_MYSQL_CONFIG."<br/>";

      // create/overwrite file
      $fp = fopen(self::FILENAME_MYSQL_CONFIG, 'w');

      if (FALSE == $fp) {
      	echo "ERROR creating file ".self::FILENAME_MYSQL_CONFIG."<br/>";

      } else {
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
         if (!mysql_query($req) && trim($req)!="") {
            die("ERROR : ".$req." ---> ".mysql_error());
         }
      }
      echo "done<br/>";
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
	public function createCustomField($fieldName, $fieldType, $configId,
	                                  $attributes=NULL, $default_value=NULL, $possible_values=NULL) {

	if (NULL == $attributes) {
		$attributes = array();

      $attributes["access_level_r"]   = 10;
      $attributes["access_level_rw"]  = 25;
      $attributes["require_report"]   = 1;
      $attributes["require_update"]   = 1;
      $attributes["require_resolved"] = 0;
      $attributes["require_closed"]   = 0;
      $attributes["display_report"]   = 1;
      $attributes["display_update"]   = 1;
      $attributes["display_resolved"] = 0;
      $attributes["display_closed"]   = 0;

	  echo "WARN: default attributes for CustomField $fieldName<br/>";
	}



      //--------
      $query = "SELECT id, name FROM `mantis_custom_field_table`";
      $result = mysql_query($query) or die("Query failed: $query");
      while($row = mysql_fetch_object($result))
      {
         $this->fieldList["$row->name"] = $row->id;
      }


		//--------
      $fieldId = $this->fieldList[$fieldName];
      if (!$fieldId) {
         $query2  = "INSERT INTO `mantis_custom_field_table` ".
                    "(`name`, `type` ,`access_level_r`," .
                    "                 `access_level_rw` ,`require_report` ,`require_update` ,`display_report` ,`display_update` ,`require_resolved` ,`display_resolved` ,`display_closed` ,`require_closed` ";
         if ($possible_values) {
         	$query2 .= ", `possible_values`";
         }
         if ($default_value) {
            $query2 .= ", `default_value`";
         }
         $query2 .= ") VALUES ('$fieldName', '$fieldType', '".$attributes["access_level_r"]."', '".
                                                              $attributes["access_level_rw"]."', '".
                                                              $attributes["require_report"]."', '".
                                                              $attributes["require_update"]."', '".
                                                              $attributes["display_report"]."', '".
                                                              $attributes["display_update"]."', '".
                                                              $attributes["require_resolved"]."', '".
                                                              $attributes["display_resolved"]."', '".
                                                              $attributes["display_closed"]."', '".
                                                              $attributes["require_closed"]."'";
         if ($possible_values) {
            $query2 .= ", '$possible_values'";
         }
         if ($default_value) {
            $query2 .= ", '$default_value'";
         }
         $query2 .= ");";

      	 #echo "DEBUG INSERT $fieldName --- query $query2 <br/>";

         $result2  = mysql_query($query2) or die("Query failed: $query2");
         $fieldId = mysql_insert_id();

         echo "custom field '$configId' created.<br/>";

      } else {
      	echo "custom field '$configId' already exists.<br/>";
      }

	  // add to codev_config_table
      Config::getInstance()->setValue($configId, $fieldId, Config::configType_int);


	}

   // --------------------------------------------------------
	/**
	 *
	 */
	public function createCustomFields() {

	  // Mantis customFields types
	  $mType_string  = 0;
      $mType_numeric = 1;
      $mType_enum    = 3;
      $mType_date    = 8;

	  // default values, to be updated for each Field
	  $attributes= array();
      $attributes["access_level_r"]   = 10;
      $attributes["access_level_rw"]  = 25;
      $attributes["require_report"]   = 1;
      $attributes["display_report"]   = 1;
      $attributes["require_update"]   = 0;
      $attributes["display_update"]   = 1;
      $attributes["require_resolved"] = 0;
      $attributes["display_resolved"] = 1;
      $attributes["require_closed"]   = 0;
      $attributes["display_closed"]   = 1;


      $attributes["require_report"]   = 1;
      $attributes["display_report"]   = 1;
      $this->createCustomField(T_("Preliminary Est. Effort"), $mType_enum,    "customField_PrelEffortEstim", $attributes, self::PREL_EFFORT_ESTIM_DEFAULT_VALUE, self::PREL_EFFORT_ESTIM_POSSIBLE_VALUES);

      $attributes["require_report"]   = 0;
      $attributes["display_report"]   = 1;
      $this->createCustomField(T_("Client bugId (TC)"),       $mType_string,  "customField_TC", $attributes);          // CoDev FDJ custom
      $this->createCustomField(T_("Dead Line"),               $mType_date,    "customField_deadLine", $attributes);

      $attributes["display_report"]   = 0;
      $this->createCustomField(T_("Est. Effort (BI)"),        $mType_numeric, "customField_effortEstim", $attributes);
      $this->createCustomField(T_("Aditional Effort (BS)"),   $mType_numeric, "customField_addEffort", $attributes);
      $this->createCustomField(T_("Remaining (RAE)"),         $mType_numeric, "customField_remaining", $attributes);

      $attributes["require_resolved"] = 0;
      $attributes["require_closed"]   = 0;
      $this->createCustomField(T_("Delivery ticket (FDL)"),   $mType_string,  "customField_deliveryId", $attributes);  // CoDev FDJ custom
      $this->createCustomField(T_("Delivery Date"),           $mType_date,    "customField_deliveryDate", $attributes);

	}



   // --------------------------------------------------------
   /**
    * create SideTasks Project and assign N/A Job
    *
    * @param unknown_type $projectName
    */
	public function createCommonSideTasksProject($projectName = "SideTasks", $projectDesc = "CoDev commonSideTasks Project") {

		// create project
		$projectid = Project::createSideTaskProject($projectName);

		if (-1 != $projectid) {

		   // --- update defaultSideTaskProject in codev_config_table
      	   Config::getInstance()->setValue(Config::id_defaultSideTaskProject, $projectid, Config::configType_int , $projectDesc);

           $stproj = ProjectCache::getInstance()->getProject($projectid);

           // --- add SideTaskProject Categories
      	   $stproj->addCategoryInactivity(T_("Inactivity"));
      	   $stproj->addCategoryProjManagement(T_("Project Management"));
           $stproj->addCategoryIncident(T_("Incident"));
           $stproj->addCategoryTools(T_("Tools"));
           $stproj->addCategoryWorkshop(T_("Team Workshop")); // TODO is this Cat relevant for CommonSideTaskProj ?

      		// --- assign SideTaskProject specific Job
      		#REM: 'N/A' job_id = 1, created by SQL file
      		Jobs::addJobProjectAssociation($projectid, self::JOB_DEFAULT_SIDETASK);
		}
      return $projectid;
	}



	/**
	 * create Admin team & add to codev_config_table
	 *
	 */
   public function createAdminTeam($name, $leader_id) {

   	  $now = time();
   	  $formatedDate  = date("Y-m-d", $now);
      $today = date2timestamp($formatedDate);

      // create admin team
   	  $teamId = Team::create($name, T_("CoDev admin team"), $leader_id, $today);

   	  if (-1 != $teamId) {
         // add to codev_config_table
   	     Config::getInstance()->setValue(Config::id_adminTeamId, $teamId, Config::configType_int);

   	     // add leader as member
   	     $adminTeam = new Team($teamId);
   	     $adminTeam->addMember($leader_id, $today, Team::accessLevel_dev);

         // add default SideTaskProject
         $adminTeam->addCommonSideTaskProject();

      }
      return $teamId;
   }

	function setConfigItems() {

      echo "DEBUG create Variable : ".Config::id_periodStatsExcludedProjectList."<br/>";
      $desc = T_("Projects NOT included in Statistics");
      Config::getInstance()->setValue(Config::id_periodStatsExcludedProjectList, NULL, Config::configType_array, $desc);

      echo "DEBUG create Variable : ".Config::id_astreintesTaskList."<br/>";
      $desc = T_("The absence SideTasks considered as astreinte");
      Config::getInstance()->setValue(Config::id_astreintesTaskList, NULL, Config::configType_array, $desc);

      echo "DEBUG create Variable : ".Config::id_ClientTeamid."<br/>";
      $desc = T_("Client teamId");
  	  Config::getInstance()->setValue(Config::id_ClientTeamid, NULL, Config::configType_int, $desc);

      echo "DEBUG create Variable : ".Config::id_prelEffortEstim_balance."<br/>";
      $desc = T_("Values (in days) for : ").self::PREL_EFFORT_ESTIM_POSSIBLE_VALUES;
      Config::getInstance()->setValue(Config::id_prelEffortEstim_balance,
                                      self::PREL_EFFORT_ESTIM_BALANCE,
                                      Config::configType_array,
                                      $desc);

	}

	function checkReportsDir($codevReportsDir) {

	  // Note: the 'ERROR' token in return string will be parsed, so
	  //       do not remove it.

	  // if path does not exist, try to create it
	  if (FALSE == file_exists ($codevReportsDir)) {
	  	if (!mkdir($codevReportsDir, 0755, true)) {
           return(T_("ERROR").T_(": Could not create folder: $codevReportsDir"));
        }
	  }

	  // create a test file to check write access to the directory
	  $testFilename = $codevReportsDir . DIRECTORY_SEPARATOR . "test.txt";
	  $fh = fopen($testFilename, 'w');
	  if (FALSE == $fh) {
	  	return (T_("ERROR").T_(": could not create test file: $testFilename"));
	  }

	  // write something to the file
	  $stringData = date("Y-m-d G:i:s", time()) . " - This is a TEST file generated during CoDev installation, You can remove it.\n";
      if (FALSE == fwrite($fh, $stringData)) {
        fclose($fh);
	  	return (T_("ERROR").T_(": could not write to test file: $testFilename"));
      }

	  fclose($fh);
	  return "SUCCESS ! Please check that the following test file has been created: <span style='font-family: sans-serif'>$testFilename</span>";
	}

    /**
     * Creates constants.php that contains variable
     * definitions that the codev admin may want to tune.
     *
	 * WARN: depending on your HTTP server installation, the file may be created
	 * by user 'apache', so be sure that this user has write access
	 * to the CoDev install directory
     */
	public function createConstantsFile() {

      echo "create file ".self::FILENAME_CONSTANTS."<br/>";

      $today  = date2timestamp(date("Y-m-d"));

      // create/overwrite file
      $fp = fopen(self::FILENAME_CONSTANTS, 'w');

      if (FALSE == $fp) {
      	echo "ERROR creating file ".self::FILENAME_CONSTANTS."<br/>";

      } else {
      	$stringData = "<?php\n";
      	$stringData .= "   // This file is part of CoDev-Timetracking.\n";
      	$stringData .= "  // - The Variables in here can be customized to your needs\n";
      	$stringData .= "  // - This file has been generated during install on ".date("D d M Y H:i")."\n";
      	$stringData .= "\n";
      	$stringData .= "  include_once \"config.class.php\";\n";
      	$stringData .= "\n";
      	$stringData .= "\$codevInstall_timestamp = ".$today.";\n";
      	$stringData .= "\n";
      	$stringData .= "  \$mantisURL=\"http://\".\$_SERVER['HTTP_HOST'].\"/mantis\";\n";
      	$stringData .= "\n";
         $stringData .= "  // --- RESOLUTION ---\n";
         $stringData .= "  # WARNING: watch out for i18n ! the values depend on what you defined in codev_config_table.resolutionNames\n"; 
         $stringData .= "  \$resolution_fixed    = array_search('fixed',    \$resolutionNames);  # 20\n";
         $stringData .= "  \$resolution_reopened = array_search('reopened', \$resolutionNames);  # 30;\n";
         $stringData .= "\n";
      	
      	$stringData .= "  // --- STATUS ---\n";
      	$stringData .= "  \$statusNames = Config::getInstance()->getValue(Config::id_statusNames);\n";
      	$stringData .= "\n";

		$statusList = Config::getInstance()->getValue(Config::id_statusNames);
      	foreach($statusList as $key => $s_name) {
      	   // TODO stringFormat s_name
      	   $stringData .= "  \$status_".$s_name."       = array_search('".$s_name."', \$statusNames);\n";
      	}
      	$stringData .= "\n";
      	$stringData .= "?>\n\n";
      	fwrite($fp, $stringData);
      	fclose($fp);
      }
	}


} // class

?>