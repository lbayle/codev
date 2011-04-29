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
 * - [user] create default side tasks
 * - [user] config astreintes

 * Step 3
 * - [user] create jobs
 * - [user] config support job
 * 
 */


class Install {
   
   const FILENAME_MYSQL_CONFIG = "../include/mysql_config_inc2.php";
    
   // --------------------------------------------------------
   public function __construct() 
   {
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
	public function execSQLscript($sqlFile) {
 
      $requetes="";
 
      $sql=file($sqlFile); // on charge le fichier SQL
      foreach($sql as $l){ // on le lit
         if (substr(trim($l),0,2)!="--"){ // suppression des commentaires
            $requetes .= $l;
         }
      }
 
      $reqs = split(";",$requetes);// on sépare les requêtes
      foreach($reqs as $req){ // et on les éxécute
         if (!mysql_query($req,$bdd) && trim($req)!=""){
            die("ERROR : ".$req); // stop si erreur 
         }
      }
      echo "done";
	}
	
} // class

?>