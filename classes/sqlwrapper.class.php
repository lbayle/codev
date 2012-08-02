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

require_once('lib/log4php/Logger.php');

class SqlWrapper {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * @var SqlWrapper class instances
    */
   private static $instance;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   /**
    * @var resource a MySQL link identifier on success or false on failure.
    */
   private $link;

   /**
    * @var int Queries count for info purpose
    */
   private $count;
   
   /**
    * @var array int[string] number[query] 
    */
   private $countByQuery;
   
   private $server;
   private $username;
   private $password;
   private $database_name;

   /**
    * Create a SQL connection
    * @param string $server The MySQL server
    * @param string $username The username
    * @param string $password The password
    * @param string $database_name The name of the database that is to be selected.
    */
   private function __construct($server, $username, $password, $database_name) {
      $this->server = $server;
      $this->username = $username;
      $this->password = $password;
      $this->database_name = $database_name;
      $this->link = mysql_connect($server, $username, $password) or die("Could not connect to database: " . $this->sql_error());
      mysql_select_db($database_name, $this->link) or die("Could not select database: " . $this->sql_error());
   }

   /**
    * Create a SQL connection
    * @static
    * @param string $server The MySQL server
    * @param string $username The username
    * @param string $password The password
    * @param string $database_name The name of the database that is to be selected.
    * @return SqlWrapper The SQLWrapper
    */
   public static function createInstance($server, $username, $password, $database_name) {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c($server, $username, $password, $database_name);
      }
      return self::$instance;
   }

   /**
    * Get the connection or die if there is no connection
    * @static
    * @return SqlWrapper The SQLWrapper
    */
   public static function getInstance() {
      if (!isset(self::$instance)) {
         die("No SQL connection");
      }
      return self::$instance;
   }

   /**
    * Open a connection to a MySQL Server
    * @static
    * @param string $server The MySQL server
    * @param string $username The username
    * @param string $password The password
    * @param string $database_name The name of the database that is to be selected.
    * @return SqlWrapper The SQLWrapper
    */
   public static function sql_connect($server, $username, $password, $database_name) {
      return self::createInstance($server, $username, $password, $database_name);
   }

   /**
    * Send a MySQL query
    * @param string $query  An SQL query
    * @return resource For SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returns a resource on success, or false on error.
    * For other type of SQL statements, INSERT, UPDATE, DELETE, DROP, etc, returns true on success or false on error.
    */
   public function sql_query($query) {
      if (self::$logger->isDebugEnabled()) {
         $start = microtime(true);
      }

      $result = mysql_query($query, $this->link);

      $this->count++;
         
      if (self::$logger->isInfoEnabled()) {
         if (NULL == $this->countByQuery) {
            $this->countByQuery = array();
         }
         if ($this->countByQuery[$query] == NULL) {
            $this->countByQuery[$query] = 1;
         } else {
            $this->countByQuery[$query] += 1;
         }

         if (self::$logger->isEnabledFor(LoggerLevel::getLevelTrace())) {
            self::$logger->trace("SQL Query #" . $this->count . " (" . round(microtime(true) - $start, 4) . " sec) : " . $query);
         }
      }

      if (!$result) {
         $e = new Exception('SQL ALERT: '.$this->sql_error().' : '.$query);
         self::$logger->error('EXCEPTION: '.$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }

      return $result;
   }

   /**
    * Returns the text of the error message from previous MySQL operation
    * @return string the error text from the last MySQL function, or '' (empty string) if no error occurred.
    */
   public function sql_error() {
      return mysql_error($this->link);
   }

   /**
    * Get result data
    * @param resource $result
    * @param int $row The row number from the result that's being retrieved. Row numbers start at 0.
    * @return string The contents of one cell from a MySQL result set on success, or false on failure.
    */
   function sql_result($result, $row = 0) {
      return mysql_result($result, $row);
   }

   /**
    * Fetch a result row as an object
    * @param resource $result
    * @return object an object with string properties that correspond to the fetched row, or false if there are no more rows.
    */
   public function sql_fetch_object($result) {
      return mysql_fetch_object($result);
   }

   /**
    * Fetch a result row as an associative array, a numeric array, or both
    * @param resource $result
    * @return array an array of strings that corresponds to the fetched row, or false if there are no more rows.
    */
   public function sql_fetch_array($result) {
      return mysql_fetch_array($result);
   }

   /**
    * Fetch a result row as an associative array
    * @param resource $result
    * @return array an associative array of strings that corresponds to the fetched row, or false if there are no more rows.
    */
   public function sql_fetch_assoc($result) {
      return mysql_fetch_assoc($result);
   }

   /**
    * Escapes special characters in a string for use in an SQL statement
    * @param string $unescaped_string The string that is to be escaped.
    * @return string the escaped string, or false on error.
    */
   public function sql_real_escape_string($unescaped_string) {
      return mysql_real_escape_string($unescaped_string, $this->link);
   }

   /**
    * Get the ID generated in the last query
    * @return int The ID generated for an AUTO_INCREMENT column by the previous query on success, 0 if the previous query does not generate an AUTO_INCREMENT value, or false if no MySQL connection was established.
    */
   public function sql_insert_id() {
      return mysql_insert_id($this->link);
   }

   /**
    * Get number of rows in result
    * @param resource $result
    * @return int The number of rows in a result set on success or false on failure.
    */
   public function sql_num_rows($result) {
      return mysql_num_rows($result);
   }

   /**
    * Free result memory
    * @param resource $result
    * @return bool true on success or false on failure.
    */
   public function sql_free_result($result) {
      return mysql_free_result($result);
   }

   /**
    * Close MySQL connection
    * @return bool true on success or false on failure.
    */
   public function sql_close() {
      return mysql_close($this->link);
   }
   
   /**
    * Backup the database
    * @param string $filename
    * @return bool True if successfull
    */
   public function sql_dump($filename) {
      $codevReportsDir = Config::getInstance()->getValue(Config::id_codevReportsDir);
      $filepath = $codevReportsDir.DIRECTORY_SEPARATOR.$filename;
         
      $command = "mysqldump --host=$this->server --user=$this->username --password=$this->password  $this->database_name > $filepath";

      $retCode = -1;
      #$status = system($command, $retCode);
      exec($command, $output, $retCode);
      
      if (0 != $retCode) {
         self::$logger->debug("Dump with mysqldump failed, so we use the PHP method");
         
         //get all of the tables
         $tables = array();
         $result = $this->sql_query('SHOW TABLES');
         while($row = mysql_fetch_row($result)) {
            $tables[] = $row[0];
         }

         //cycle through
         $return = "-- MySQL dump\n";
         $return .= "--\n";
         $return .= "-- Host: ".$this->server."    Database: ".$this->database_name."\n";
         $return .= "-- ------------------------------------------------------\n";
         $return .= "-- Server version	\n\n";
         foreach($tables as $table) {
            $result = $this->sql_query('SELECT * FROM '.$table);
            $num_fields = mysql_num_fields($result);

            $return .= "--\n";
            $return .= "-- Table structure for table `".$table."`\n";
            $return .= "--\n\n";
            $return .= 'DROP TABLE IF EXISTS '.$table.';';
            $row2 = mysql_fetch_row($this->sql_query('SHOW CREATE TABLE '.$table));
            $return .= "\n".$row2[1].";\n\n";
            if(self::sql_num_rows($result) > 0) {
               $return .= "--\n";
               $return .= "-- Dumping data for table `".$table."`\n";
               $return .= "--\n\n";
               $return .= "LOCK TABLES `".$table."` WRITE;\n";
               $return .= "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */\n";
               while($row = mysql_fetch_row($result)) {
                  $return.= 'INSERT INTO '.$table.' VALUES(';
                  for($j=0; $j<$num_fields; $j++) {
                     $row[$j] = addslashes($row[$j]);
                     $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                     if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                     if ($j<($num_fields-1)) { $return.= ','; }
                  }
                  $return .= ");\n";
               }
               $return .= "/*!40000 ALTER TABLE `".$table."` ENABLE KEYS */\n";
               $return .= "UNLOCK TABLES `".$table."` WRITE;\n";
               $return .= "\n";
            }
         }

         //save file
         $folderExists = file_exists($codevReportsDir);
         if(!$folderExists) {
            self::$logger->info("The folder ".$codevReportsDir." doesn't exist, so we try to create it");
            $folderExists = mkdir($codevReportsDir);
            if($result) {
               self::$logger->info("Successfull creation : ".$codevReportsDir);
            } else {
               self::$logger->warn("Failed to create : ".$codevReportsDir);
            }
         }
         
         $result = FALSE;
         if($folderExists) {
            $return .= file_get_contents(BASE_PATH.DIRECTORY_SEPARATOR."install".DIRECTORY_SEPARATOR."codevtt_procedures.sql")."\n";
            $gzdata = gzencode($return, 9);
            $fp = fopen($filepath.".gz", 'wb+');
            if($fp) {
               fwrite($fp, $gzdata);
               $result = fclose($fp);
            }
         }
         
         if($result) {
            self::$logger->info("Database dump successfully done in ".$filepath.".gz");
         } else {
            self::$logger->error("Failed to dump the database");
         }
         
         return $result;
      } else {
         self::$logger->info("Database dump successfully done in ".$filepath);
         return TRUE;
      }
   }

   /**
    * Get the queries count
    * @return int Number of queries
    */
   public function getQueriesCount() {
      return $this->count;
   }
   
   public function getCountByQuery() {
      return $this->countByQuery;
   }

   /**
    * Get the connection link
    * @return resource a MySQL link identifier on success or false on failure.
    */
   public function getLink() {
      return $this->link;
   }

   public function logStats() {
      if (self::$logger->isInfoEnabled()) {
         $queriesCount = $this->getQueriesCount();

         foreach($this->getCountByQuery() as $query => $count) {
            #if($count > 10) {
            #   self::$logger->info($count. ' identical SQL queries on : ' . $query);
            #} else if($count > 1) {
               self::$logger->debug($count. ' identical SQL queries on : ' . $query);
            #}
         }

         self::$logger->info('TOTAL SQL queries: ' . $queriesCount . ' to display Page '.$_SERVER['PHP_SELF']);
      }
   }

}

SqlWrapper::staticInit();

?>
