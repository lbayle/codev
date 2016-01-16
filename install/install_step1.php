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

include_once('i18n/i18n.inc.php');

$page_name = T_("Install - Step 1");
require_once('install/install_header.inc.php');

require_once('install/install_menu.inc.php');

require_once('install/codevtt_procedures.php');

$logger = Logger::getLogger("install");

?>

<script src="../lib/jquery/js/jquery-1.8.0.min.js" type="text/javascript"></script>

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
         document.forms["databaseForm"].submit();
      } else {
         alert(msgString);
      }
   }
   
   jQuery(document).ready(function() {
      var form = jQuery('#databaseForm');

      jQuery('#cb_proxyEnabled').click(function() {
         var isProxyEnabled = jQuery('#cb_proxyEnabled').attr('checked')?1:0;
         form.find('input[name=isProxyEnabled]').val(isProxyEnabled);
         jQuery('#proxy_host').prop('disabled', !jQuery('#cb_proxyEnabled').attr('checked'));
         jQuery('#proxy_port').prop('disabled', !jQuery('#cb_proxyEnabled').attr('checked'));
      });
   });
   
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

   SqlWrapper::createInstance($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);

   $query = "SELECT * FROM `mantis_config_table` WHERE config_id = 'database_version' ";

   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      throw new Exception("ERROR: Could not access Mantis database");
   }
   if (0 == SqlWrapper::getInstance()->sql_num_rows($result)) {
      throw new Exception("ERROR: Could not get mantis_config_table.database_version");
   }
   $row = SqlWrapper::getInstance()->sql_fetch_object($result);
   $database_version = $row->value;

   return $database_version;
}

/**
 * check if the user has enough privileges to create tables & procedures
 *
 * TODO: if 'CREATE' not set but 'CREATE ROUTINE' set,
 * then this method will not see that 'CREATE' is missing !
 *
 * Note: this is not enough on Windows, you need 'SUPER privilege'
 * see http://codevtt.org/site/?topic=sql-alert-you-do-not-have-the-super-privilege-and-binary-logging-is-enabled
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
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED</span>";
      exit;
   }

   while ($row = SqlWrapper::getInstance()->sql_fetch_array($result)) {
      if (FALSE != strstr($row[0], "`$db_mantis_database`")) {
         if($logger->isDebugEnabled()) {
            $logger->debug("Privileges: " . $row[0]);
         }

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
      throw new Exception($errStr);

   }
}


/**
 * writes an INCOMPLETE config.ini file (containing only DB access variables)
 *
 * WARN: depending on your HTTP server installation, the file may be created
 * by user 'apache', so be sure that this user has write access
 * to the CoDev install directory
 *
 * @return NULL if Success, ErrorString if Failed
 */
function createConfigFile($db_mantis_host = 'localhost',
                               $db_mantis_user = 'mantis',
                               $db_mantis_pass = '',
                               $db_mantis_database = 'bugtracker',
                               $proxy_host = NULL,
                               $proxy_port = NULL) {

   Constants::$db_mantis_host = $db_mantis_host;
   Constants::$db_mantis_user = $db_mantis_user;
   Constants::$db_mantis_pass = $db_mantis_pass;
   Constants::$db_mantis_database = $db_mantis_database;

   if (!is_null($proxy_host) && !is_null($proxy_port)) {
      Constants::$proxy = $proxy_host.':'.$proxy_port;
   }
   
   // this writes an INCOMPLETE config.ini file (containing only DB access variables)
   $retCode = Constants::writeConfigFile();

   if (!$retCode) {
      throw new Exception("ERROR: Could not create file ".Constants::$config_file);
   }
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

	if (Tools::isWindowsServer()) {
		echo "<br><span class='warn_font'>".T_("WARN Windows Install: to avoid a SUPER privilege error, use <b>root</b> mysql user (This can be changed in config.ini when installation is finished).")."</span><br>";
	}
	echo '<br><span class="error_font" id="errorMsg" style="font-size:larger; font-weight: bold;"></span><br>';

   echo "  <br/>\n";
   echo "<h2>".T_("Proxy Settings")."</h2>\n";
   echo "<span class='help_font'>".T_("CodevTT will check for updates.")."</span><br>";
   echo "<table class='invisible'>\n";
   echo "  <tr>\n";
   echo "    <td width='120'><input id='cb_proxyEnabled' type='checkbox' name='cb_proxyEnabled' /> ".T_("Enable proxy")."</td>\n";
   echo "    <td>".T_("Server").": <input size='15' type='text' name='proxy_host'  id='proxy_host' value='proxy' disabled></td>\n";
   echo "    <td>".T_("Port").": <input size='4' type='text' name='proxy_port'  id='proxy_port' value='8080' disabled></td>\n";
   echo "  </tr>\n";
   echo "</table>\n";
   
   echo "  <br/>\n";
   echo "  <br/>\n";
   echo "<div  style='text-align: center;'>\n";
   echo "<input type=button style='font-size:150%' value='".T_("Proceed Step 1")."' onclick='setDatabaseInfo()'>\n";
   echo "</div>\n";

   echo "<input type='hidden' name='action'      value='setDatabaseInfo'>\n";
   echo "<input type='hidden' name='isProxyEnabled' value='0'/>\n";
   echo "</form>";
   echo "<br/>\n";
   echo "<br/>\n";
}

/**
 * override filter_input to handle errors
 * @param int $type INPUT_POST or INPUT_GET
 * @param string $variable_name
 * @param string $defaultValue
 * @param int $filter
 * @return string value
 */
 function getHttpVariable($type, $variable_name, $defaultValue=NULL, $filter = FILTER_DEFAULT) {
   $value = filter_input($type, $variable_name, $filter);
   if (NULL === $value) {
      // undefined
      $value = $defaultValue;
   }
   if (FALSE === $value) {
      echo "<span class='error_font'>Could not get ".$variable_name."</span><br/>";
      exit;
   }
   return $value;
}

// ================ MAIN =================
$originPage = "install_step1.php";

$db_mantis_host = (string)getHttpVariable(INPUT_POST, 'db_mantis_host', 'localhost');
$db_mantis_database = (string)getHttpVariable(INPUT_POST, 'db_mantis_database', 'bugtracker');
$db_mantis_user = (string)getHttpVariable(INPUT_POST, 'db_mantis_user', Tools::isWindowsServer() ? 'root' : 'mantisdbuser');
$db_mantis_pass = (string)getHttpVariable(INPUT_POST, 'db_mantis_pass', '');

$isProxyEnabled = (string)getHttpVariable(INPUT_POST, 'isProxyEnabled', '0');
if ('1' == $isProxyEnabled) {
   $proxy_host = (string)getHttpVariable(INPUT_POST, 'proxy_host', '');
   $proxy_port = (string)getHttpVariable(INPUT_POST, 'proxy_port', '');
} else {
   $proxy_host = NULL;
   $proxy_port = NULL;
}

displayDatabaseForm($originPage, $db_mantis_host, $db_mantis_database, $db_mantis_user, $db_mantis_pass);

$action = (string)getHttpVariable(INPUT_POST, 'action', 'none');

if ("setDatabaseInfo" == $action) {

   try {

      $database_version = checkDBConnection($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database);
      echo "<script type=\"text/javascript\">console.log(\"DEBUG: Mantis database_version = $database_version\");</script>";

      checkDBprivileges($db_mantis_database);

      echo "<script type=\"text/javascript\">console.log(\"Step 1/4 create config.ini file\");</script>";
      createConfigFile($db_mantis_host, $db_mantis_user, $db_mantis_pass, $db_mantis_database, $proxy_host, $proxy_port);

      echo "<script type=\"text/javascript\">console.log(\"Step 2/4 execSQLscript2 - create Tables\");</script>";
      //$retCode = Tools::execSQLscript2(Install::FILENAME_TABLES);
      $retCode = SqlParser::execSqlScript(Install::FILENAME_TABLES);
      if (0 != $retCode) {
         throw new Exception('ERROR: Could not execute SQL script: '.Install::FILENAME_TABLES);
      }
      $request = "SELECT value from `codev_config_table` WHERE `config_id` = 'database_version' ";
      if (!SqlWrapper::getInstance()->sql_query($request)) {
         throw new Exception('ERROR: CodevTT database tables not created.');
      }

      echo "<script type=\"text/javascript\">console.log(\"Step 3/4 execSQLscript2 - create Procedures\");</script>";
      // procedures are defined in install/codevtt_procedures.php
      foreach ($codevtt_sqlProcedures as $query) {
         $result = SqlWrapper::getInstance()->sql_query(trim($query));
         if (!$result) {
            throw new Exception('ERROR: SQL procedure creation failed FAILED');
         }
      }

      echo "<script type=\"text/javascript\">console.log(\"Step 4/4 Perf: CREATE INDEX handler_id ON mantis_bug_table\");</script>";
      $request = "CREATE INDEX `handler_id` ON `mantis_bug_table` (`handler_id`); ";
      $result = SqlWrapper::getInstance()->sql_query($request);
      // Note: we do not care about the result: if failed, then the INDEX already exists.

      // everything went fine, goto step2
      echo ("<script type='text/javascript'> parent.location.replace('install_step2.php'); </script>");

   } catch (Exception $ex) {

      echo "<script type=\"text/javascript\">document.getElementById(\"errorMsg\").innerHTML=\"".$ex->getMessage()."\";</script>";

      if (file_exists(Constants::$config_file)) {
         echo "<script type=\"text/javascript\">console.log(\"ROLLBACK: remove config file\");</script>";
         $retCode = unlink(Constants::$config_file);
         if (!$retCode) {
            echo "<script type=\"text/javascript\">console.error(\"ERROR: could not remove config file\");</script>";
         }
      }
      exit;
   }

}
