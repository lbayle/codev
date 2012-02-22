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

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
      Logger::configure(dirname(__FILE__).'/../log4php.xml');
      $logger = Logger::getLogger("default");
      $logger->info("LOG activated !");
   }

include_once 'project.class.php';
include_once 'team.class.php';
include_once 'jobs.class.php';
include_once 'config.class.php';
include_once 'config_mantis.class.php';


class Install {

   const FILENAME_MYSQL_CONFIG = "../include/mysql_config.inc.php";

   const FILENAME_CONSTANTS = "../constants.php";

   private $logger;
   private $fieldList;

   // --------------------------------------------------------
   public function __construct()
   {
      $this->logger = Logger::getLogger(__CLASS__);
   	  $this->logger->info("LOG activated !");

   	// get existing Mantis custom fields
      $this->fieldList = array();

   }

   /**
    *  check MySQL availability
    *
    * @return NULL if OK, or an error message.

    */
   public static function checkMysqlAccess() {

   	$command = "mysql --version";
   	$status = exec($command, $output, $retCode);
   	if (0 != $retCode) {
   		return "ERROR: system call to 'mysql' failed, please add it to your \$PATH variable</br>";
   	} else {
   		#echo $status."</br>";
   	}
   	echo "</br>";

   	$command = "mysqldump --version";
   	$status = exec($command, $output, $retCode);
   	if (0 != $retCode) {
   		return "ERROR: system call to 'mysqldump' failed, please add it to your \$PATH variable</br>";
   	} else {
   		#echo $status."</br>";
     	}

   	return NULL;

   }

   // --------------------------------------------------------
   /**
    * Checks if DB connection is OK
    * @param unknown_type $db_mantis_host
    * @param unknown_type $db_mantis_user
    * @param unknown_type $db_mantis_pass
    * @param unknown_type $db_mantis_database
    *
    * @return NULL if OK, or an error message starting with 'ERROR' .
    */
   public function checkDBConnection($db_mantis_host     = 'localhost',
                                     $db_mantis_user     = 'codev',
                                     $db_mantis_pass     = '',
                                     $db_mantis_database = 'bugtracker') {

      $bugtracker_link = mysql_connect($db_mantis_host, $db_mantis_user, $db_mantis_pass);
      if (!$bugtracker_link) {
      	return ("ERROR: Could not connect to database: ". mysql_error());
      }

      $db_selected = mysql_select_db($db_mantis_database);
      if (!$db_selected) {
      	return ("ERROR: Could not select database: ". mysql_error());
      }

      $database_version = ConfigMantis::getInstance()->getValue(ConfigMantis::id_database_version);
      echo "DEBUG: Mantis database_version = $database_version<br/>";

      if (NULL == $database_version) {
      	return "ERROR: Could not get mantis_config_table.database_version";
      }

      $error = Install::checkMysqlAccess();
      if (TRUE == strstr($error, T_("ERROR"))) {
      	return $error;
      }

      return NULL;
   }


   // --------------------------------------------------------
   /**
    *
    * remove CodevTT Config Files
    */
   public static function deleteConfigFiles() {

	   if (file_exists(Install::FILENAME_CONSTANTS)) {
	   	$retCode = unlink( Install::FILENAME_CONSTANTS );
	   	if (true == $retCode) {
	   		#echo "DEBUG: ". Install::FILENAME_CONSTANTS . " successfully deleted.</br>";
	     	} else {
	   		echo "ERROR: Could not delete file: ". Install::FILENAME_CONSTANTS . "</br>";
	   	}
	   }
	   if (file_exists(Install::FILENAME_MYSQL_CONFIG)) {
	   	$retCode = unlink( Install::FILENAME_MYSQL_CONFIG );
	   	if (true == $retCode) {
	   		#echo "DEBUG: ". Install::FILENAME_MYSQL_CONFIG . " successfully deleted.</br>";
	   	} else {
	      		echo "ERROR: Could not delete file: ". Install::FILENAME_MYSQL_CONFIG . "</br>";
	      }
	   }
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

		#echo "DEBUG create file ".self::FILENAME_MYSQL_CONFIG."<br/>";

      // create/overwrite file
      $fp = fopen(self::FILENAME_MYSQL_CONFIG, 'w');

      if (FALSE == $fp) {
      	echo "<span class='error_font'>ERROR: creating file ".self::FILENAME_MYSQL_CONFIG."</span><br/>";

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
	 * create a customField in Mantis (if not exist) & update codev_config_table
	 *
	 * ex: $install->createCustomField("ExtRef", 0, "customField_ExtId");
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

	  echo "<span class='warn_font'>WARN: using default attributes for CustomField $fieldName</span><br/>";
	}

      //--------
      $query = "SELECT id, name FROM `mantis_custom_field_table`";
	  $result = mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");
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

         $result2  = mysql_query($query2) or die("<span style='color:red'>Query FAILED: $query2 <br/>".mysql_error()."</span>");
         $fieldId = mysql_insert_id();

         #echo "custom field '$configId' created.<br/>";

      } else {
	      echo "<span class='success_font'>INFO: custom field '$configId' already exists.</span><br/>";
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
      $this->createCustomField(T_("CodevTT_Manager EffortEstim"), $mType_numeric,    "customField_MgrEffortEstim", $attributes);

      $attributes["require_report"]   = 0;
      $attributes["display_report"]   = 1;
      $this->createCustomField(T_("CodevTT_External ID"),       $mType_string,  "customField_ExtId", $attributes);
      $this->createCustomField(T_("CodevTT_Dead Line"),         $mType_date,    "customField_deadLine", $attributes);

      $attributes["display_report"]   = 0;
      $this->createCustomField(T_("CodevTT_EffortEstim"),        $mType_numeric, "customField_effortEstim", $attributes);
      $this->createCustomField(T_("CodevTT_Aditional Effort"),   $mType_numeric, "customField_addEffort", $attributes);
      $this->createCustomField(T_("CodevTT_Remaining"),          $mType_numeric, "customField_remaining", $attributes);

      $attributes["require_resolved"] = 0;
      $attributes["require_closed"]   = 0;
      #$this->createCustomField(T_("CodevTT_Delivery ticket"),   $mType_string,  "customField_deliveryId", $attributes);  // CoDev FDJ custom
      $this->createCustomField(T_("CodevTT_Delivery Date"),     $mType_date,    "customField_deliveryDate", $attributes);

	}



   // --------------------------------------------------------
   /**
    * create SideTasks Project and assign N/A Job
    *
    * @param unknown_type $projectName
    */
	public function createExternalTasksProject($projectName = "(generic) ExternalTasks", $projectDesc = "CoDevTT ExternalTasks Project") {

		// create project
		$projectid = Project::createExternalTasksProject($projectName);

		if (-1 != $projectid) {

		   // --- update ExternalTasksProject in codev_config_table
      	   Config::getInstance()->setValue(Config::id_externalTasksProject, $projectid, Config::configType_int , $projectDesc);

           $stproj = ProjectCache::getInstance()->getProject($projectid);

      		// --- assign ExternalTasksProject specific Job
      		#REM: 'N/A' job_id = 1, created by SQL file
      		Jobs::addJobProjectAssociation($projectid, Jobs::JOB_NA);
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
      $teamId = Team::create($name, T_("CoDevTT Administrators team"), $leader_id, $today);

   	  if (-1 != $teamId) {

           // --- add to codev_config_table
           Config::getInstance()->setQuiet(true);
   	     Config::getInstance()->setValue(Config::id_adminTeamId, $teamId, Config::configType_int);
           Config::getInstance()->setQuiet(false);

           // add leader as member
   	     $adminTeam = new Team($teamId);
   	     $adminTeam->addMember($leader_id, $today, Team::accessLevel_dev);

   	     // add default ExternalTasksProject
           $adminTeam->addExternalTasksProject();

           // --- add <team> SideTaskProject
            $stproj_id = $adminTeam->createSideTaskProject(T_("SideTasks")." $name");

            if ($stproj_id < 0) {
               die ("ERROR: SideTaskProject creation FAILED.<br/>\n");
            } else {
               $stproj = ProjectCache::getInstance()->getProject($stproj_id);

               $stproj->addCategoryInactivity(T_("Inactivity"));
               $stproj->addCategoryTools(T_("Tools"));

               $stproj->addIssueInactivity(T_("(generic) Leave"));
               $stproj->addIssueTools(T_("(generic) Mantis/CoDevTT administration"));
            }
      } else {
      	echo "ERROR: $name team creation failed</br>";
      }
      return $teamId;
   }

	function setConfigItems() {

      #echo "DEBUG create Variable : ".Config::id_astreintesTaskList."<br/>";
      $desc = T_("The absence SideTasks considered as astreinte");
      Config::getInstance()->setValue(Config::id_astreintesTaskList, NULL, Config::configType_array, $desc);

      #echo "DEBUG create Variable : ".Config::id_ClientTeamid."<br/>";
      # TODO should be a table, there can be more than one client !
      $desc = T_("Client teamId");
  	   Config::getInstance()->setValue(Config::id_ClientTeamid, NULL, Config::configType_int, $desc);

	}

	public static function checkWriteAccess($directory) {

	  // Note: the 'ERROR' token in return string will be parsed, so
	  //       do not remove it.

	  // if path does not exist, try to create it
	  if (FALSE == file_exists ($directory)) {
	  	if (!mkdir($directory, 0755, true)) {
           return(T_("ERROR").T_(": Could not create folder: $directory"));
        }
	  }

	  // create a test file to check write access to the directory
	  $testFilename = $directory . DIRECTORY_SEPARATOR . "test.txt";
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

	   if (file_exists($testFilename)) {
	   	$retCode = unlink($testFilename);
	   	if (false == $retCode) {
	  	      return (T_("ERROR").T_(": Could not delete file: ". $testFilename));
	   	}
	   }

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

      #echo "DEBUG: create file ".self::FILENAME_CONSTANTS."<br/>";

      $today  = date2timestamp(date("Y-m-d"));

      // create/overwrite file
      $fp = fopen(self::FILENAME_CONSTANTS, 'w');

      if (FALSE == $fp) {
      	echo "<span class='error_font'>ERROR: creating file ".self::FILENAME_CONSTANTS." (current dir=".getcwd().")</span><br/>";

        // try to create a temporary file, for manual install...
        $fp = fopen("/tmp/constants.php", 'w');
        if (FALSE == $fp) {
      	   return "ERROR creating debug file: /tmp/constants.php";
        }
      }

      	$stringData = "<?php\n";
      	$stringData .= "   // This file is part of CoDev-Timetracking.\n";
      	$stringData .= "  // - The Variables in here can be customized to your needs\n";
      	$stringData .= "  // - This file has been generated during install on ".date("D d M Y H:i")."\n";
      	$stringData .= "\n";
      	$stringData .= "  date_default_timezone_set('Europe/Paris');\n";
      	$stringData .= "\n";
      	$stringData .= "  include_once \"config.class.php\";\n";
      	$stringData .= "\n";
      	$stringData .= "\$codevInstall_timestamp = ".$today.";\n";
      	$stringData .= "\n";
      	$stringData .= "  \$mantisURL=\"http://\".\$_SERVER['HTTP_HOST'].\"/mantis\";\n";
      	$stringData .= "\n";
      	$stringData .= "  \$codevRootDir = dirname(__FILE__);\n";
      	$stringData .= "\n";
        $stringData .= "  // --- RESOLUTION ---\n";
        $stringData .= "  # WARNING: watch out for i18n ! special chars may break PHP code and/or DB values\n";
        $stringData .= "  # INFO: the values depend on what you defined in codev_config_table.resolutionNames\n";
        $stringData .= "  \$resolution_fixed    = array_search('fixed',    \$resolutionNames);  # 20\n";
        $stringData .= "  \$resolution_reopened = array_search('reopened', \$resolutionNames);  # 30;\n";
        $stringData .= "\n";

      	$stringData .= "  // --- STATUS ---\n";
      	$stringData .= "  # WARNING: CodevTT uses some global variables for status.\n";
      	$stringData .= "  #          Some of these variables are used in the code, so if they are not defined\n";
      	$stringData .= "  #          in the mantis workflow, they need to be created. The mandatory variables are:\n";
      	$stringData .= "  #           \$status_new, \$status_feedback, \$status_acknowledged,\n";
      	$stringData .= "  #           \$status_open, \$status_closed\n";
        $stringData .= "\n";
      	$stringData .= "  \$statusNames = Config::getInstance()->getValue(Config::id_statusNames);\n";
      	$stringData .= "\n";

		$statusList = Config::getInstance()->getValue(Config::id_statusNames);
      	foreach($statusList as $key => $s_name) {
      	   // TODO stringFormat s_name
      	   $stringData .= "  \$status_".$s_name."       = array_search('".$s_name."', \$statusNames);\n";
      	}

        // TODO add equivalences for mandatory statusses not present in workflow (see mantis 131)
        // ex: $status_open = $status_assigned;

         // Constrains
      	$stringData .= "\n";
         $stringData .= "# Custom Relationships\n";
         $stringData .= "define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY',       2500 );\n";
         $stringData .= "define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS',           2501 );\n";


      	$stringData .= "\n";
      	$stringData .= "?>\n\n";
      	fwrite($fp, $stringData);
      	fclose($fp);
      }

    /**
     * Add a new entry in MantisBT menu (main_menu_custom_options)
     *
     * ex: addCustomMenuItem('CodevTT', '../codev/index.php')
     */
    public function addCustomMenuItem($name, $url) {

	    $pos = '10'; // invariant

        // get current mantis custom menu entries
        $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
	     mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

        $serialized  = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

	    // add entry
	    if ((NULL != $serialized) && ("" != $serialized)) {
			$menuItems = unserialize($serialized);
	    } else {
	    	$menuItems = array();
	    }

		 $menuItems[] = array($name, $pos, $url);
	    $newSerialized = serialize($menuItems);

        // update mantis menu
        if (NULL != $serialized) {
        	$query = "UPDATE `mantis_config_table` SET value = '$newSerialized' ".
                     "WHERE config_id = 'main_menu_custom_options'";
        } else {
            $query = "INSERT INTO `mantis_config_table` (`config_id`, `value`, `type`, `access_reqd`) ".
                     "VALUES ('main_menu_custom_options', '$newSerialized', '3', '90');";
        }
	   mysql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".mysql_error()."</span>");

		return $newSerialized;
	}


} // class

?>