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

// Set up the include path
define('BASE_PATH', realpath(dirname(__FILE__)));
set_include_path(BASE_PATH.PATH_SEPARATOR.get_include_path());

#echo "DEBUG PHP include_path : ".get_include_path()." <br/>";

// This avoids the display of some PHP errors...
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

// WARN Set up the timezone
// /etc/php.ini
//   [Date]
//   date.timezone = Europe/Paris
// OR
//date_default_timezone_set("Europe/Paris");

// Set up the autoloader system
require_once('lib/dynamic_autoloader/ClassFileMapAutoloader.php');
$_autoloader = unserialize(file_get_contents(BASE_PATH."/classmap.ser"));
$_autoloader->registerAutoload();

// Set up the logger
try {
   //if (is_null(Logger::getConfigurationFile())) {

      $filename = dirname(__FILE__).DIRECTORY_SEPARATOR.'log4php.xml';
      if (!file_exists($filename)) {
         // if file not found or during INSTALL procedure...
         Logger::configure(array(
               'rootLogger' => array(
                   'appenders' => array('default'),
                   'level' => 'ERROR'
                   ),
               'appenders' => array(
                   'default' => array(
                       'class' => 'LoggerAppenderEcho',
                       'layout' => array(
                           'class' => 'LoggerLayoutSimple'
                       ),
                       'params' => array(
                           'htmlLineBreaks' => true
                       )
                   )
               )
            ));
      } else {
         Logger::configure($filename);
      }

      if (!file_exists(Constants::$codevtt_logfile)) {
         $errStr = Tools::checkWriteAccess(dirname(Constants::$codevtt_logfile));
         if (NULL !== $errStr) { throw new Exception($errStr); }
         
         $errStr = @file_put_contents(Constants::$codevtt_logfile, date('Y-m-d G:i:s').' - logfile creation.');
         if (FALSE === $errStr) {
            throw new Exception('Could not create file '.Constants::$codevtt_logfile);
         }
      }

		// WARN: if logger is LoggerAppenderEcho, then logs will break the login Ajax call !
      $logger = Logger::getLogger("header");
      $logger->info("LOG activated !");

      // test
		#$appenders = $logger->getParent()->getAllAppenders();
		#foreach ($appenders as $appender) {
		#	echo "appender ".get_class($appender)."<br>";
		#}
      #echo "configure LOG ".Logger::getConfigurationFile()."</br>";
      #echo "configure LOG ".Logger::getConfigurationClass()."</br>";
      #echo "configure LOG header exists: ".$logger->exists("header")."</br>";
   //}
} catch (Exception $e) {
   echo 'LOGGER ERROR: '.$e->getMessage().'<br><br>';
   echo 'Tips:<br>';
   echo ' - Check logger configuration file "log4php.xml"<br>';
   echo ' - Check that user <b>'.exec('whoami').'</b> has write access to the log directory.<br>';
   exit;
}

// Set up the exception handler
set_exception_handler('exception_handler');

/**
 * Handle uncaught exceptions
 * @param Exception $e
 */
function exception_handler(Exception $e) {
   $logger = Logger::getLogger("header");
	$userid = $_SESSION['userid'];

   echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span><br>";
   $logger->error("UNCAUGHT EXCEPTION : user=$userid, ".$e->getMessage());
   $logger->error("UNCAUGHT EXCEPTION stack-trace:\n".$e->getTraceAsString());
}

?>
