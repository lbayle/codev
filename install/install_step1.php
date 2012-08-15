<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

include_once 'i18n/i18n.inc.php';

$page_name = T_("Install - Step 1");
require_once 'install_header.inc.php';

require_once('classes/config_mantis.class.php');
require_once('classes/sqlwrapper.class.php');
require_once('install/install.class.php');

require_once 'install_menu.inc.php';

?>

<script type="text/javascript">

   function setDatabaseInfo(){
      // check fields
      var foundError = 0;
      var msgString = "The following fields are missing:\n\n";

      if (0 == document.forms["databaseForm"].db_mantis_host.value)     { msgString += "Hostname\n"; ++foundError; }
      if (0 == document.forms["databaseForm"].db_mantis_database.value)     { msgString += "Database\n"; ++foundError; }
      if (0 == document.forms["databaseForm"].db_mantis_user.value)     { msgString += "User\n"; ++foundError; }
      //if (0 == document.forms["databaseForm"].db_mantis_password.value)     { msgString += Password"\n"; ++foundError; }

      if (0 == foundError) {
         document.forms["databaseForm"].action.value="setDatabaseInfo";
         document.forms["databaseForm"].submit();
      } else {
         alert(msgString);
      }
   }
</script>

<div id="content">

<?php

/**
 * Checks if DB connection is OK
 * @param string $db_mantis_host
 * @param string $db_mantis_user
 * @param string $db_mantis_pass
 * @param string $db_mantis_database
 *
 * @return NULL if OK, or an error message starting with 'ERROR' .
 */
function checkDBConnection($db_mantis_host = 'localhost',
                           $db_mantis_user = 'mantis',
                           $db_mantis_pass = '',
                           $db_mantis_database = 'bugtracker') {

   $connection = SqlWrapper::createInstance($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   $connection->sql_query('SET CHARACTER SET utf8');
   $connection->sql_query('SET NAMES utf8');

   $database_version = ConfigMantis::getInstance()->getValue(ConfigMantis::id_database_version);
   echo "DEBUG: Mantis database_version = $database_version<br/>";

   if (NULL == $database_version) {
      return "ERROR: Could not get mantis_config_table.database_version";
   }

   return NULL;
}

/**
 * check if the user has enough privileges to create tables & procedures
 *
 * TODO: if 'CREATE' not set but 'CREATE ROUTINE' set,
 * then this method will not see that 'CREATE' is missing !
 *
 * @return NULL if OK, or an error message starting with 'ERROR' .
 */
function checkDBprivileges($db_mantis_database = 'bugtracker') {
   global $logger;

   $mandatoryPriv = array('SELECT', 'INSERT', 'UPDATE', 'DELETE',
      'CREATE', 'DROP', 'EXECUTE', 'CREATE ROUTINE', 'ALTER ROUTINE');
   $errStr = NULL;

   #$query = "SHOW GRANTS FOR '$db_mantis_user'@'$db_mantis_host'";
   $query = "SHOW GRANTS FOR CURRENT_USER";
   $result = mysql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   while ($row = mysql_fetch_array($result)) {
      if (FALSE != strstr($row[0], "`$db_mantis_database`")) {
         $logger->debug("Privileges: " . $row[0]);

         // all privileges should be ok !
         if (FALSE != strstr($row[0], "GRANT ALL PRIVILEGES")) {
            break; // found, get out
         }

         foreach ($mandatoryPriv as $priv) {
            if (!strstr($row[0], $priv)) {
               $errStr .= "ERROR: user has no $priv privileges on $db_mantis_database<br>";
            }
         }
         break;  // found, get out
      }
   }
   if (NULL != $errStr) {
      $allPriv = implode(', ', $mandatoryPriv);
      $errStr .= "Please add the following privileges: $allPriv";
   }
   return $errStr;
}


/**
 * updates mysql_config_inc.php with connection parameters
 *
 * WARN: depending on your HTTP server installation, the file may be created
 * by user 'apache', so be sure that this user has write access
 * to the CoDev install directory
 *
 * @return NULL if Success, ErrorString if Failed
 */
function createMysqlConfigFile($db_mantis_host = 'localhost',
                               $db_mantis_user = 'mantis',
                               $db_mantis_pass = '',
                               $db_mantis_database = 'bugtracker') {

   #echo "DEBUG create file ".self::FILENAME_MYSQL_CONFIG."<br/>";
   // create/overwrite file
   $fp = fopen(Install::FILENAME_MYSQL_CONFIG, 'w');

   if (!$fp) {
      return "ERROR: creating file " . Install::FILENAME_MYSQL_CONFIG;
   } else {
      $stringData = "<?php\n";
      $stringData .= "\n";
      $stringData .= "/**\n";
      $stringData .= " * Mantis DB infomation.\n";
      $stringData .= " */\n";
      $stringData .= "class DatabaseInfo {\n";
      $stringData .= "   public static \$db_mantis_host = '$db_mantis_host';\n";
      $stringData .= "   public static \$db_mantis_user = '$db_mantis_user';\n";
      $stringData .= "   public static \$db_mantis_pass = '$db_mantis_pass';\n";
      $stringData .= "   public static \$db_mantis_database = '$db_mantis_database';\n";
      $stringData .= "}\n";
      $stringData .= "\n";
      $stringData .= "?>\n";
      if (!fwrite($fp, $stringData)) {
         fclose($fp);
         return "ERROR: could not write to file " . Install::FILENAME_MYSQL_CONFIG;
      }
      fclose($fp);
   }
   return NULL;
}

function displayStepInfo() {
   echo "<h2>".T_("Prerequisites")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Successfully installed Mantis</li>";
   echo "<li>user 'apache' has write access to CodevTT directory</li>";
   echo "<li>MySQL 'codev' user created with access to Mantis DB</li>";
   echo "</ul>\n";
   echo "<h2>".T_("Actions")."</h2>\n";
   echo "<ul>\n";
   echo "<li>Create database configuration file for CodevTT</li>";
   echo "<li>Create CodevTT database tables</li>";
   echo "<li>Add CodevTT specific custom fields to Mantis</li>";
   echo "<li>Create ExternalTasks Project</li>";
   echo "<li>Create CodevTT Admin team</li>";
   echo "</ul>\n";
   echo "";
}

function displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass) {
   echo "<form id='databaseForm' name='databaseForm' method='post' action='$originPage' >\n";

   echo "<h2>".T_("Mantis Database Info")."</h2>\n";

   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Hostname")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_host'  id='db_mantis_host' value='$db_mantis_host'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Database Name")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_database'  id='db_mantis_database' value='$db_mantis_database'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("User")."</td>\n";
   echo "    <td><input size='50' type='text' name='db_mantis_user'  id='db_mantis_user' value='$db_mantis_user'></td>\n";
   echo "  </tr>\n";
   echo "  <tr>\n";
   echo "    <td width='120'>".T_("Password")."</td>\n";
   echo "    <td><input size='50' type='password' name='db_mantis_pass'  id='db_mantis_pass' value='$db_mantis_pass'></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";

   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 1")."' onclick='setDatabaseInfo()'>\n";
   echo "</div>\n";

   echo "<input type=hidden name=action      value=noAction>\n";

   echo "</form>";
}

// ================ MAIN =================
$originPage = "install_step1.php";

$db_mantis_host = Tools::getSecurePOSTStringValue('db_mantis_host', 'localhost');
$db_mantis_database = Tools::getSecurePOSTStringValue('db_mantis_database', 'bugtracker');
$db_mantis_user = Tools::getSecurePOSTStringValue('db_mantis_user', 'mantisdbuser');
$db_mantis_pass = Tools::getSecurePOSTStringValue('db_mantis_pass', '');

$action = Tools::getSecurePOSTStringValue('action', '');

#displayStepInfo();
#echo "<hr align='left' width='20%'/>\n";

displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass);

if ("setDatabaseInfo" == $action) {

   $errStr = checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   // check if I can create CodevTT tables & procedures
   $errStr = checkDBprivileges($db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   echo "DEBUG 1/3 createMysqlConfigFile<br/>";
   $errStr = createMysqlConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
      exit;
   }

   // TODO check user access (create_table, create_procedure, alter, insert,delete, ...)

   echo "DEBUG 2/3 execSQLscript - create Tables<br/>";
   $retCode = Tools::execSQLscript2(Install::FILENAME_TABLES);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: Install::FILENAME_TABLES</span><br/>";
      exit;
   }

   echo "DEBUG 3/3 execSQLscript2 - create Procedures<br/>";
   $retCode = Tools::execSQLscript2(Install::FILENAME_PROCEDURES);
   if (0 != $retCode) {
      echo "<span class='error_font'>Could not execSQLscript: Install::FILENAME_PROCEDURES</span><br/>";
      exit;
   }

   // everything went fine, goto step2
   echo ("<script type='text/javascript'> parent.location.replace('install_step2.php'); </script>");
}

?>
