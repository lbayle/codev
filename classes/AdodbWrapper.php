<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Mantis Database Parameters Count class
 * Stores the current parameter count, provides method to generate parameters
 * and a simple stack mechanism to enable the caller to build multiple queries
 * concurrently on RDBMS using positional parameters (e.g. PostgreSQL)
 */
class MantisDbParam {
	public $count = 0;
	private $stack = array();

	/**
	 * Generate a string to insert a parameter into a database query string
	 * @return string 'wildcard' matching a parameter in correct ordered format for the current database.
	 */
	public function assign() {
		
		return $this->db->Param( $this->count++ );
	}

	/**
	 * Pushes current parameter count onto stack and resets its value to 0
	 * @return void
	 */
	public function push() {
		$this->stack[] = $this->count;
		$this->count = 0;
	}

	/**
	 * Pops the previous value of param count from the stack
	 * This function is called by {@see db_query()} and should not need
	 * to be executed directly
	 * @return void
	 */
	public function pop() {
		

		$this->count = (int)array_pop( $this->stack );
		if( $this->db_is_pgsql() ) {
			# Manually reset the ADOdb param number to the value we just popped
			$this->db->_pnum = $this->count;
		}
	}
}

/**
 * This class is adapted from the MantisBT database_api.php file
 * 
 */
class AdodbWrapper {

   private static $logger;
   private static $instance;

   private $server;
   private $username;
   private $password;
   private $database_name;
   private $database_type;
   
   private $adodb;
   private $isCheckParams;
   
   // An array in which all executed queries are stored.  This is used for profiling
   private $queries_array = array();

   // Stores whether a database connection was successfully opened.
   private $isDBconnected = false;

# set adodb to associative fetch mode with lowercase column names
# @global bool $ADODB_FETCH_MODE
//global $ADODB_FETCH_MODE;
//$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
//define( 'ADODB_ASSOC_CASE', ADODB_ASSOC_CASE_LOWER );
   
   // Tracks the query parameter count
   private $db_param;
   
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }
   
   public static function createInstance($server, $username, $password, $database_name, $database_type) {
      if (!isset(self::$instance)) {
         $c = __CLASS__;
         self::$instance = new $c($server, $username, $password, $database_name, $database_type);
      }
      return self::$instance;
   }

   public static function getInstance() {
      if (!isset(self::$instance)) {
         self::createInstance(Constants::$db_mantis_host, Constants::$db_mantis_user,
                              Constants::$db_mantis_pass, Constants::$db_mantis_database, 
                              Constants::$db_mantis_type);
      }
      return self::$instance;
   }

   /**
    * @param string  $hostname      Database server hostname.
    * @param string  $username      Database server username.
    * @param string  $password      Database server password.
    * @param string  $database_name Database name.
    * @param string  $database_type Database type.
    * @param boolean $persistConnect      Use a Persistent connection to database.
    * @throws Exception on invalid database
    */
   private function __construct($hostname, $username, $password, $database_name, $database_type, $persistConnect = false) {
      
      $this->server   = $hostname;
      $this->username = $username;
      $this->password = $password;
      $this->database_name = $database_name;
      
       switch ($database_type) {
           case 'mysqli':
               $this->database_type = 'mysqli';
               break;
           case 'postgresql':
           case 'postgres':
           case 'pgsql':
               $this->database_type = 'pgsql';
               break;
           case 'mssql':
           case 'mssqlnative':
               $this->database_type = 'mssqlnative';
               break;
           case 'odbc_mssql':
               $this->database_type = 'odbc_mssql';
               break;
           case 'oracle':
           case 'oci8':
               $this->database_type = 'oci8';
               break;
           default:
                $e = new Exception("Unknown database type: ".$database_type);
                self::$logger->error("EXCEPTION SqlWrapper constructor: ".$e->getMessage());
                self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
                throw $e;
       }
      
      if( !$this->checkDatabaseSupport( $this->database_type ) ) {
         $e = new Exception('PHP Support for database ('.$this->database_type.') is not enabled ');
         self::$logger->error("EXCEPTION SqlWrapper constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->isCheckParams = ( $this->isPgsql() || $this->isMssql() );

      $this->db_param = new MantisDbParam();
      $this->db_connect(NULL, $persistConnect);
   }


   /**
    * Open a connection to the database.
    * @param string  $p_dsn           Database connection string ( specified instead of other params).
    * @param boolean $p_pconnect      Use a Persistent connection to database.
    * @return boolean indicating if the connection was successful
    */
   private function db_connect( $p_dsn = NULL, $p_pconnect = false ) {

      if( empty( $p_dsn ) ) {
         $this->adodb = ADONewConnection( $this->database_type );

         if( $p_pconnect ) {
            $t_result = $this->adodb->PConnect( $this->server, $this->username, $this->password, $this->database_name );
         } else {
            $t_result = $this->adodb->Connect( $this->server, $this->username, $this->password, $this->database_name );
         }
      } else {
         $this->adodb = ADONewConnection( $p_dsn );
         $t_result = $this->adodb->IsConnected();
      }

      if( $t_result ) {
         # For MySQL, the charset for the connection needs to be specified.
         if( db_is_mysql() ) {
            # @todo Is there a way to translate any charset name to MySQL format? e.g. remote the dashes?
            # @todo Is this needed for other databases?
            db_query( 'SET NAMES UTF8' );
         }
      } else {
         $e = new Exception('Could not connect to database: '.$this->getErrorMsg());
         throw $e;               
      }

      $this->isDBconnected = true;

      return true;
   }

   /**
    * Returns whether a connection to the database exists
    * @global stores database connection state
    * @return boolean indicating if the a database connection has been made
    */
   public function isConnected() {
      return $this->isDBconnected;
   }

   /**
    * Returns whether php support for a database is enabled
    * @param string $p_db_type Database type.
    * @return boolean indicating if php current supports the given database type
    */
   private function checkDatabaseSupport( $p_db_type ) {
      switch( $p_db_type ) {
         case 'mysqli':
            $t_support = function_exists( 'mysqli_connect' );
            break;
         case 'pgsql':
            $t_support = function_exists( 'pg_connect' );
            break;
         case 'mssqlnative':
            $t_support = function_exists( 'sqlsrv_connect' );
            break;
         case 'oci8':
            $t_support = function_exists( 'OCILogon' );
            break;
         case 'odbc_mssql':
            $t_support = function_exists( 'odbc_connect' );
            break;
         default:
            $t_support = false;
      }
      return $t_support;
   }


   /**
    * Checks if the database driver is MySQL
    * @return boolean true if mysql
    */
   public function isMysql() {
      
      return( 'mysqli' == $this->database_type );
   }

   /**
    * Checks if the database driver is PostgreSQL
    * @return boolean true if postgres
    */
   public function isPgsql() {
      
      return ( 'pgsql' == $this->database_type );
   }

   /**
    * Checks if the database driver is MS SQL
    * @return boolean true if mssql
    */
   public function isMssql() {
      
      return (( 'mssqlnative' == $this->database_type ) ||
              ( 'odbc_mssql'  == $this->database_type ));
   }

   /**
    * Checks if the database driver is Oracle (oci8)
    * @return boolean true if oracle
    */
   public function isOracle() {
      
      return( 'oci8' == $this->database_type );
   }

   /**
    * Validates that the given identifier's length is OK for the database platform
    * Triggers an error if the identifier is too long
    * @param string $p_identifier Identifier to check.
    * @return void
    */
   public function checkIdentifierSize( $p_identifier ) {
      # Oracle does not support long object names (30 chars max)
      if( $this->isOracle() && 30 < strlen( $p_identifier ) ) {
         $e = new Exception("Identifier <$p_identifier> is too long");
         self::$logger->error("EXCEPTION SqlWrapper constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }
   }

   /**
    * execute query, requires connection to be opened
    * An error will be triggered if there is a problem executing the query.
    * This will pop the database parameter stack {@see MantisDbParam} after a
    * successful execution, unless specified otherwise
    *
    * @global array of previous executed queries for profiling
    * @global adodb database connection object
    * @global boolean indicating whether queries array is populated
    * @param string  $p_query     Parameterlised Query string to execute.
    * @param array   $p_arr_parms Array of parameters matching $p_query.
    * @param integer $p_limit     Number of results to return.
    * @param integer $p_offset    Offset query results for paging.
    * @param boolean $p_pop_param Set to false to leave the parameters on the stack
    * @return IteratorAggregate|boolean adodb result set or false if the query failed.
    */
   public function sql_query( $p_query, array $p_arr_parms = null, $p_limit = -1, $p_offset = -1, $p_pop_param = true ) {
      

      $t_start = microtime( true );

      # This ensures that we don't get an error from ADOdb if $p_arr_parms == null,
      # as Execute() expects either an array or false if there are no parameters -
      # null actually gets treated as array( 0 => null )
      if( is_null( $p_arr_parms ) ) {
         $p_arr_parms = array();
      }

      if( !empty( $p_arr_parms ) && $this->isCheckParams ) {
         $t_params = count( $p_arr_parms );
         for( $i = 0;$i < $t_params;$i++ ) {
            if( $p_arr_parms[$i] === false ) {
               $p_arr_parms[$i] = 0;
            } elseif ( true === ($p_arr_parms[$i] ) && ('mssqlnative' == $this->database_type) ) {
               $p_arr_parms[$i] = 1;
            }
         }
      }

/* TODO enable prefix/suffix in CodevTT 
      
      static $s_prefix;
      static $s_suffix;
      if( $s_prefix === null ) {
         # Determine table prefix and suffixes including trailing and leading '_'
         $s_prefix = trim( config_get_global( 'db_table_prefix' ) );
         $s_suffix = trim( config_get_global( 'db_table_suffix' ) );

         if( !empty( $s_prefix ) && '_' != substr( $s_prefix, -1 ) ) {
            $s_prefix .= '_';
         }
         if( !empty( $s_suffix ) && '_' != substr( $s_suffix, 0, 1 ) ) {
            $s_suffix = '_' . $s_suffix;
         }
      }

      $p_query = strtr($p_query, array(
                        '{' => $s_prefix,
                        '}' => $s_suffix,
                        ) );
*/
      # Pushing params to safeguard the ADOdb parameter count (required for pgsql)
      $this->db_param->push();

      if( $this->isOracle() ) {
         $p_query = $this->oracleAdaptQuerySyntax( $p_query, $p_arr_parms );
      }

      if( ( $p_limit != -1 ) || ( $p_offset != -1 ) ) {
         $t_result = $this->adodb->SelectLimit( $p_query, $p_limit, $p_offset, $p_arr_parms );
      } else {
         $t_result = $this->adodb->Execute( $p_query, $p_arr_parms );
      }

      # Restore ADOdb parameter count
      $this->db_param->pop();

      $t_elapsed = number_format( microtime( true ) - $t_start, 4 );

      if( ON == $this->db_log_queries ) {
         $t_query_text = $this->formatQueryLogMsg( $p_query, $p_arr_parms );
         log_event( LOG_DATABASE, array( $t_query_text, $t_elapsed ) );
      } else {
         # If not logging the queries the actual text is not needed
         $t_query_text = '';
      }
      array_push( $this->queries_array, array( $t_query_text, $t_elapsed ) );

      # Restore param stack: only pop if asked to AND the query has params
      if( $p_pop_param && !empty( $p_arr_parms ) ) {
         $this->db_param->pop();
      }

      if( !$t_result ) {
         $e = new Exception("Query failed: ".$p_query);
         self::$logger->error("EXCEPTION SqlWrapper constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      } else {
         return $t_result;
      }
   }

   /**
    * Generate a string to insert a parameter into a database query string
    * @return string 'wildcard' matching a parameter in correct ordered format for the current database.
    */
   public function db_param() {
      return $this->db_param->assign();
   }

   /**
    * Pushes current parameter count onto stack and resets its value
    * Allows the caller to build multiple queries concurrently on RDBMS using
    * positional parameters (e.g. PostgreSQL)
    * @return void
    */
   public function db_param_push() {
      $this->db_param->push();
   }

   /**
    * Pops the previous parameter count from the stack
    * It is generally not necessary to call this, because the param count is popped
    * automatically whenever a query is executed via db_query(). There are some
    * corner cases when doing it manually makes sense, e.g. when a query is built
    * but not executed.
    * @return void
    */
   public function db_param_pop() {
      $this->db_param->pop();
   }

   /**
    * Retrieve number of rows returned for a specific database query
    * @param IteratorAggregate $p_result Database Query Record Set to retrieve record count for.
    * @return integer Record Count
    */
   public function getNumRows( IteratorAggregate $p_result ) {
      return $p_result->RecordCount();
   }

   /**
    * Retrieve number of rows affected by a specific database query
    * @return integer Affected Rows
    */
   public function getAffectedRows() {
      return $this->adodb->Affected_Rows();
   }

   /**
    * Retrieve the next row returned from a specific database query
    * @param IteratorAggregate &$p_result Database Query Record Set to retrieve next result for.
    * @return array Database result
    */
   public function fetchArray( IteratorAggregate &$p_result ) {

      if( $p_result->EOF ) {
         return false;
      }

      # Retrieve the fields from the recordset
      $t_row = $p_result->fields;

      # Additional handling for specific RDBMS
      switch( $this->database_type ) {

         case 'pgsql':
            # pgsql's boolean fields are stored as 't' or 'f' and must be converted
            static $s_current_result = null, $s_convert_needed;

            if( $s_current_result != $p_result ) {
               # Processing a new query
               $s_current_result = $p_result;
               $s_convert_needed = false;
            } elseif( !$s_convert_needed ) {
               # No conversion needed, return the row as-is
               $p_result->MoveNext();
               return $t_row;
            }

            foreach( $p_result->FieldTypesArray() as $t_field ) {
               switch( $t_field->type ) {
                  case 'bool':
                     switch( $t_row[$t_field->name] ) {
                        case 'f':
                           $t_row[$t_field->name] = false;
                           break;
                        case 't':
                           $t_row[$t_field->name] = true;
                           break;
                     }
                     $s_convert_needed = true;
                     break;
               }
            }
            break;

         case 'oci8':
            # oci8 returns null values for empty strings, convert them back
            foreach( $t_row as &$t_value ) {
               if( !isset( $t_value ) ) {
                  $t_value = '';
               }
            }
            break;
      }

      $p_result->MoveNext();
      return $t_row;
   }

   /**
    * Retrieve a specific field from a database query result
    * @param boolean|IteratorAggregate $p_result		Database Query Record Set to retrieve the field from.
    * @param integer                   $p_row_index	Row to retrieve, zero-based (optional).
    * @param integer                   $p_col_index	Column to retrieve, zero-based (optional).
    * @return mixed Database result
    */
   public function sql_result( $p_result, $p_row_index = 0, $p_col_index = 0 ) {
      if( $p_result && ( $this->getNumRows( $p_result ) > 0 ) ) {
         $p_result->Move( $p_row_index );
         $t_row = $this->fetchArray( $p_result );

         # Make the array numerically indexed. This is required to retrieve the
         # column ($p_index2), since we use ADODB_FETCH_ASSOC fetch mode.
         $t_result = array_values( $t_row );

         return $t_result[$p_col_index];
      }

      return false;
   }

   /**
    * return the last inserted id for a specific database table
    * @param string $p_table A valid database table name.
    * @param string $p_field A valid field name (default 'id').
    * @return integer last successful insert id
    */
   public function getInsertId( $p_table = null, $p_field = 'id' ) {

      if( isset( $p_table ) ) {
         switch( $this->database_type ) {
            case 'oci':
               $t_query = 'SELECT seq_' . $p_table . '.CURRVAL FROM DUAL';
               break;
            case 'pgsql':
               $t_query = 'SELECT currval(\'' . $p_table . '_' . $p_field . '_seq\')';
               break;
            case 'mssqlnative':
            case 'odbc_mssql':
               $t_query = 'SELECT IDENT_CURRENT(\'' . $p_table . '\')';
               break;
         }
         if( isset( $t_query ) ) {
            $t_result = $this->sql_query( $t_query );
            return (int)$this->sql_result( $t_result );
         }
      }
      return $this->adodb->Insert_ID();
   }

   /**
    * Check if the specified table exists.
    * @param string $p_table_name A valid database table name.
    * @return boolean indicating whether the table exists
    */
   public function tableExists( $p_table_name ) {
      if( is_blank( $p_table_name ) ) {
         return false;
      }

      $t_tables = $this->getTableList();
      if( !is_array( $t_tables ) ) {
         return false;
      }

      # Can't use in_array() since it is case sensitive
      $t_table_name = utf8_strtolower( $p_table_name );
      foreach( $t_tables as $t_current_table ) {
         if( utf8_strtolower( $t_current_table ) == $t_table_name ) {
            return true;
         }
      }

      return false;
   }

   /**
    * Check if the specified table index exists.
    * @param string $p_table_name A valid database table name.
    * @param string $p_index_name A valid database index name.
    * @return boolean indicating whether the index exists
    */
   public function indexExists( $p_table_name, $p_index_name ) {
      

      if( is_blank( $p_index_name ) || is_blank( $p_table_name ) ) {
         return false;
      }

      $t_indexes = $this->adodb->MetaIndexes( $p_table_name );
      if( $t_indexes === false ) {
         # no index found
         return false;
      }

      if( !empty( $t_indexes ) ) {
         # Can't use in_array() since it is case sensitive
         $t_index_name = utf8_strtolower( $p_index_name );
         foreach( $t_indexes as $t_current_index_name => $t_current_index_obj ) {
            if( utf8_strtolower( $t_current_index_name ) == $t_index_name ) {
               return true;
            }
         }
      }
      return false;
   }

   /**
    * Check if the specified field exists in a given table
    * @param string $p_field_name A database field name.
    * @param string $p_table_name A valid database table name.
    * @return boolean indicating whether the field exists
    */
   public function fieldExists( $p_field_name, $p_table_name ) {
      $t_columns = $this->getFieldNames( $p_table_name );

      # ADOdb oci8 driver works with uppercase column names, and as of 5.19 does
      # not provide a way to force them to lowercase
      if( $this->isOracle() ) {
         $p_field_name = strtoupper( $p_field_name );
      }

      return in_array( $p_field_name, $t_columns );
   }

   /**
    * Retrieve list of fields for a given table
    * @param string $p_table_name A valid database table name.
    * @return array array of fields on table
    */
   public function getFieldNames( $p_table_name ) {
      
      $t_columns = $this->adodb->MetaColumnNames( $p_table_name );
      return is_array( $t_columns ) ? $t_columns : array();
   }

   /**
    * Returns the last error number. The error number is reset after every call to Execute(). If 0 is returned, no error occurred.
    * @return int last error number
    * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
    */
   public function getErrorNum() {
      return $this->adodb->ErrorNo();
   }

   /**
    * Returns the last status or error message. Returns the last status or error message. The error message is reset when Execute() is called.
    * This can return a string even if no error occurs. In general you do not need to call this function unless an ADOdb function returns false on an error.
    * @return string last error string
    * @todo Use/Behaviour of this function should be reviewed before 1.2.0 final
    */
   public function getErrorMsg() {
      return $this->adodb->ErrorMsg();
   }

   /**
    * close the connection.
    * Not really necessary most of the time since a connection is automatically closed when a page finishes loading.
    * @return void
    */
   public function close() {
      $t_result = $this->adodb->Close();
   }

   /**
    * Prepare a binary string before DB insertion
    * Use of this function is required for some DB types, to properly encode
    * BLOB fields prior to calling db_query()
    * @param string $p_string Raw binary data.
    * @return string prepared database query string
    */
   public function prepareBinaryString( $p_string ) {
      
      switch( $this->database_type ) {
         case 'odbc_mssql':
            $t_content = unpack( 'H*hex', $p_string );
            return '0x' . $t_content['hex'];
            break;
         case 'pgsql':
            return $this->adodb->BlobEncode( $p_string );
            break;
         case 'mssqlnative':
         case 'oci8':
            # Fall through, mssqlnative, oci8 store raw data in BLOB
         default:
            return $p_string;
      }
   }

   /**
    * return current time as Unix timestamp
    * @return integer Unix timestamp of the current date and time
    */
   public function db_now() {
      return time();
   }

   /**
    * convert minutes to a time format [h]h:mm
    * @param integer $p_min Integer representing number of minutes.
    * @return string representing formatted duration string in hh:mm format.
    */
   public function db_minutes_to_hhmm( $p_min = 0 ) {
      return sprintf( '%02d:%02d', $p_min / 60, $p_min % 60 );
   }

   /**
    * A helper function that generates a case-sensitive or case-insensitive like phrase based on the current db type.
    * The field name and value are assumed to be safe to insert in a query (i.e. already cleaned).
    * @param string  $p_field_name     The name of the field to filter on.
    * @param boolean $p_case_sensitive True: case sensitive, false: case insensitive.
    * @return string returns (field LIKE 'value') OR (field ILIKE 'value')
    */
   public function db_helper_like( $p_field_name, $p_case_sensitive = false ) {
      $t_like_keyword = ' LIKE ';

      if( $p_case_sensitive === false ) {
         if( $this->isPgsql() ) {
            $t_like_keyword = ' ILIKE ';
         }
      }
      return '(' . $p_field_name . $t_like_keyword . db_param() . ')';
   }

   /**
    * Compare two dates against a certain number of days
    * 'val_or_col' parameters will be used "as is" in the query component,
    * allowing use of a column name. To compare against a specific date,
    * it is recommended to pass db_param() instead of a date constant.
    * @param string  $p_val_or_col_1 Value or Column to compare.
    * @param string  $p_operator     SQL comparison operator.
    * @param string  $p_val_or_col_2 Value or Column to compare.
    * @param integer $p_num_secs     Number of seconds to compare against
    * @return string Database query component to compare dates
    * @todo Check if there is a way to do that using ADODB rather than implementing it here.
    */
   public function db_helper_compare_time( $p_val_or_col_1, $p_operator, $p_val_or_col_2, $p_num_secs ) {
      if( $p_num_secs == 0 ) {
         return "($p_val_or_col_1 $p_operator $p_val_or_col_2)";
      } elseif( $p_num_secs > 0 ) {
         return "($p_val_or_col_1 $p_operator $p_val_or_col_2 + $p_num_secs)";
      } else {
         # Invert comparison to avoid issues with unsigned integers on MySQL
         return "($p_val_or_col_1 - $p_num_secs $p_operator $p_val_or_col_2)";
      }
   }

   /**
    * count queries
    * @return integer
    */
   public function countQueries() {
      return count( $this->queries_array );
   }

   /**
    * count unique queries
    * @return integer
    */
   public function countUniqueQueries() {
      $t_unique_queries = 0;
      $t_shown_queries = array();
      foreach( $this->queries_array as $t_val_array ) {
         if( !in_array( $t_val_array[0], $t_shown_queries ) ) {
            $t_unique_queries++;
            array_push( $t_shown_queries, $t_val_array[0] );
         }
      }
      return $t_unique_queries;
   }

   /**
    * get total time for queries
    * @return integer
    */
   public function timeQueries() {
      
      $t_count = count( $this->queries_array );
      $t_total = 0;
      for( $i = 0;$i < $t_count;$i++ ) {
         $t_total += $this->queries_array[$i][1];
      }
      return $t_total;
   }

   /**
    * get database table name
    *
    * @param string $p_name Can either be specified as 'XXX' (e.g. 'bug'), or
    *                       using the legacy style 'mantis_XXX_table'; in the
    *                       latter case, a deprecation warning will be issued.
    * @return string containing full database table name (with prefix and suffix)
    */
 /*
   public function db_get_table( $p_name ) {
      if( preg_match( '/^mantis_(.*)_table$/', $p_name, $t_matches ) ) {
         $t_table = $t_matches[1];
         error_parameters(
            __FUNCTION__ . "( '$p_name' )",
            __FUNCTION__ . "( '$t_table' )"
         );
         trigger_error( ERROR_DEPRECATED_SUPERSEDED, DEPRECATED );
      } else {
         $t_table = $p_name;
      }

      # Determine table prefix including trailing '_'
      $t_prefix = trim( config_get_global( 'db_table_prefix' ) );
      if( !empty( $t_prefix ) && '_' != substr( $t_prefix, -1 ) ) {
         $t_prefix .= '_';
      }
      # Determine table suffix including leading '_'
      $t_suffix = trim( config_get_global( 'db_table_suffix' ) );
      if( !empty( $t_suffix ) && '_' != substr( $t_suffix, 0, 1 ) ) {
         $t_suffix = '_' . $t_suffix;
      }

      # Physical table name
      $t_table = $t_prefix . $t_table . $t_suffix;
      db_check_identifier_size( $t_table );
      return $t_table;
   }
*/
   /**
    * get list database tables
    * @return array containing table names
    */
   public function getTableList() {
      $t_tables = $this->adodb->MetaTables( 'TABLE' );
      return $t_tables;
   }

   /**
    * Updates a BLOB column
    *
    * This function is only needed for oci8; it will do nothing and return
    * false if used with another RDBMS.
    *
    * @param string $p_table  Table name.
    * @param string $p_column The BLOB column to update.
    * @param string $p_val    Data to store into the BLOB.
    * @param string $p_where  Where clause to identify which record to update
    *                         if null, defaults to the last record inserted in $p_table.
    * @return boolean
    */
   public function updateBlob( $p_table, $p_column, $p_val, $p_where = null ) {

      if( !$this->isOracle() ) {
         return false;
      }

      if( null == $p_where ) {
         $p_where = 'id=' . $this->getInsertId( $p_table );
      }

      if( ON == $this->db_log_queries ) {
         $t_start = microtime( true );

         $t_backtrace = debug_backtrace();
         $t_caller = basename( $t_backtrace[0]['file'] );
         $t_caller .= ':' . $t_backtrace[0]['line'];

         # Is this called from another function?
         if( isset( $t_backtrace[1] ) ) {
            $t_caller .= ' ' . $t_backtrace[1]['function'] . '()';
         } else {
            # or from a script directly?
            $t_caller .= ' ' . $_SERVER['SCRIPT_NAME'];
         }
      }

      $t_result = $this->adodb->UpdateBlob( $p_table, $p_column, $p_val, $p_where );

      if( $this->db_log_queries ) {
         $t_elapsed = number_format( microtime( true ) - $t_start, 4 );
         $t_log_data = array(
            'Update BLOB in ' . $p_table . '.' . $p_column . ' where ' . $p_where,
            $t_elapsed,
            $t_caller
         );
         //log_event( LOG_DATABASE, var_export( $t_log_data, true ) );
         array_push( $this->queries_array, $t_log_data );
      }

      if( !$t_result ) {
         $e = new Exception("Querry failed: ".$this->getErrorMsg());
         self::$logger->error("EXCEPTION SqlWrapper constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e; 
      }
      return $t_result;
   }

   /**
    * Sorts bind variable numbers and puts them in sequential order
    * e.g. input:  "... WHERE F1=:12 and F2=:97 ",
    *      output: "... WHERE F1=:0 and F2=:1 ".
    * Used in db_oracle_adapt_query_syntax().
    * @param string $p_query Query string to sort.
    * @return string Query string with sorted bind variable numbers.
    */
   public function oracleOrderBindsSequentially( $p_query ) {
      $t_new_query= '';
      $t_is_odd = true;
      $t_after_quote = false;
      $t_iter = 0;

      # Divide statement to skip processing string literals
      $t_p_query_arr = explode( '\'', $p_query );
      foreach( $t_p_query_arr as $t_p_query_part ) {
         if( $t_new_query != '' ) {
            $t_new_query .= '\'';
         }
         if( $t_is_odd ) {
            # Divide to process all bindvars
            $t_p_query_subpart_arr = explode( ':', $t_p_query_part );
            if( count( $t_p_query_subpart_arr ) > 1 ) {
               foreach( $t_p_query_subpart_arr as $t_p_query_subpart ) {
                  if( ( !$t_after_quote ) && ( $t_new_query != '' ) ) {
                     $t_new_query .= ':' . preg_replace( '/^(\d+?)/U', strval( $t_iter ), $t_p_query_subpart );
                     $t_iter++;
                  } else {
                     $t_new_query .= $t_p_query_subpart;
                  }
                  $t_after_quote = false;
               }
            } else {
               $t_new_query .= $t_p_query_part;
            }
            $t_is_odd = false;
         } else {
            $t_after_quote = true;
            $t_new_query .= $t_p_query_part;
            $t_is_odd = true;
         }
      }
      return $t_new_query;
   }

   /**
    * Adapt input query string and bindvars array to Oracle DB syntax:
    * 1. Change bind vars id's to sequence beginning with 0
    *    (calls db_oracle_order_binds_sequentially() )
    * 2. Remove "AS" keyword, because it is not supported with table aliasing
    * 3. Remove null bind variables in insert statements for default values support
    * 4. Replace "tab.column=:bind" to "tab.column IS NULL" when :bind is empty string
    * 5. Replace "SET tab.column=:bind" to "SET tab.column=DEFAULT" when :bind is empty string
    * @param string $p_query      Query string to sort.
    * @param array  &$p_arr_parms Array of parameters matching $p_query, function sorts array keys.
    * @return string Query string with sorted bind variable numbers.
    */
   public function oracleAdaptQuerySyntax( $p_query, array &$p_arr_parms = null ) {
      # Remove "AS" keyword, because not supported with table aliasing
      # - Do not remove text literal within "'" quotes
      # - Will remove all "AS", except when it's part of a "CAST(x AS y)" expression
      #   To do so, we will assume that the "AS" following a "CAST", is safe to be kept.
      #   Using a counter for "CAST" appearances to allow nesting: CAST(CAST(x AS y) AS z)

      # split the string by the relevant delimiters. The delimiters will be part of the splitted array
      $t_parts = preg_split("/(')|( AS )|(CAST\s*\()/mi", $p_query, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
      $t_is_literal = false;
      $t_cast = 0;
      $t_query = '';
      foreach( $t_parts as $t_part ) {
         # if quotes, switch literal flag
         if( $t_part == '\'' ) {
            $t_is_literal = !$t_is_literal;
            $t_query .= $t_part;
            continue;
         }
         # if this part is litereal, do not change
         if( $t_is_literal ) {
            $t_query .= $t_part;
            continue;
         } else {
            # if there is "CAST" delimiter, flag the counter
            if( preg_match( '/^CAST\s*\($/i', $t_part ) ) {
               $t_cast++;
               $t_query .= $t_part;
               continue;
            }
            # if there is "AS"
            if( strcasecmp( $t_part, ' AS ' ) == 0 ) {
               # if there's a previous CAST, keep the AS
               if( $t_cast > 0 ) {
                  $t_cast--;
                  $t_query .= $t_part;
               } else {
                  # otherwise, remove the " AS ", replace by a space
                  $t_query .= ' ';
               }
               continue;
            }
            $t_query .= $t_part;
            continue;
         }
      }
      $p_query = $t_query;

      # Remove null bind variables in insert statements for default values support
      if( is_array( $p_arr_parms ) ) {
         preg_match( '/^[\s\n\r]*insert[\s\n\r]+(into){0,1}[\s\n\r]+(?P<table>[a-z0-9_]+)[\s\n\r]*\([\s\n\r]*[\s\n\r]*(?P<fields>[a-z0-9_,\s\n\r]+)[\s\n\r]*\)[\s\n\r]*values[\s\n\r]*\([\s\n\r]*(?P<values>[:a-z0-9_,\s\n\r]+)\)/i', $p_query, $t_matches );

         if( isset( $t_matches['values'] ) ) { #if statement is a INSERT INTO ... (...) VALUES(...)
            # iterates non-empty bind variables
            $i = 0;
            $t_fields_left = $t_matches['fields'];
            $t_values_left = $t_matches['values'];

            for( $t_arr_index = 0; $t_arr_index < count( $p_arr_parms ); $t_arr_index++ ) {
               # inserting fieldname search
               if( preg_match( '/^[\s\n\r]*([a-z0-9_]+)[\s\n\r]*,{0,1}([\d\D]*)\z/i', $t_fields_left, $t_fieldmatch ) ) {
                  $t_fields_left = $t_fieldmatch[2];
                  $t_fields_arr[$i] = $t_fieldmatch[1];
               }
               # inserting bindvar name search
               if( preg_match( '/^[\s\n\r]*(:[a-z0-9_]+)[\s\n\r]*,{0,1}([\d\D]*)\z/i', $t_values_left, $t_valuematch ) ) {
                  $t_values_left = $t_valuematch[2];
                  $t_values_arr[$i] = $t_valuematch[1];
               }
               # skip unsetting if bind array value not empty
               if( $p_arr_parms[$t_arr_index] !== '' ) {
                  $i++;
               } else {
                  $t_arr_index--;
                  # Shift array and unset bind array element
                  for( $n = $i + 1; $n < count( $p_arr_parms ); $n++ ) {
                     $p_arr_parms[$n-1] = $p_arr_parms[$n];
                  }
                  unset( $t_fields_arr[$i] );
                  unset( $t_values_arr[$i] );
                  unset( $p_arr_parms[count( $p_arr_parms ) - 1] );
               }
            }

            # Combine statement from arrays
            $p_query = 'INSERT INTO ' . $t_matches['table'] . ' (' . $t_fields_arr[0];
            for( $i = 1; $i < count( $p_arr_parms ); $i++ ) {
               $p_query = $p_query . ', ' . $t_fields_arr[$i];
            }
            $p_query = $p_query . ') values (' . $t_values_arr[0];
            for( $i = 1; $i < count( $p_arr_parms ); $i++ ) {
               $p_query = $p_query . ', ' . $t_values_arr[$i];
            }
            $p_query = $p_query . ')';
         } else {
            # if input statement is NOT a INSERT INTO (...) VALUES(...)

            # "IS NULL" adoptation here
            $t_set_where_template_str = substr( md5( uniqid( rand(), true ) ), 0, 50 );
            $t_removed_set_where = '';

            # Need to order parameter array element correctly
            $p_query = $this->oracleOrderBindsSequentially( $p_query );

            # Find and remove temporarily "SET var1=:bind1, var2=:bind2 WHERE" part
            preg_match( '/^(?P<before_set_where>.*)(?P<set_where>[\s\n\r]*set[\s\n\r]+[\s\n\ra-z0-9_\.=,:\']+)(?P<after_set_where>where[\d\D]*)$/i', $p_query, $t_matches );
            $t_set_where_stmt = isset( $t_matches['after_set_where'] );

            if( $t_set_where_stmt ) {
               $t_removed_set_where = $t_matches['set_where'];
               # Now work with statement without "SET ... WHERE" part
               $t_templated_query = $t_matches['before_set_where'] . $t_set_where_template_str . $t_matches['after_set_where'];
            } else {
               $t_templated_query = $p_query;
            }

            # Replace "var1=''" by "var1 IS NULL"
            while( preg_match( '/^(?P<before_empty_literal>[\d\D]*[\s\n\r(]+([a-z0-9_]*[\s\n\r]*\.){0,1}[\s\n\r]*[a-z0-9_]+)[\s\n\r]*=[\s\n\r]*\'\'(?P<after_empty_literal>[\s\n\r]*[\d\D]*\z)/i', $t_templated_query, $t_matches ) > 0 ) {
               $t_templated_query = $t_matches['before_empty_literal'] . ' IS NULL ' . $t_matches['after_empty_literal'];
            }
            # Replace "var1!=''" and "var1<>''" by "var1 IS NOT NULL"
            while( preg_match( '/^(?P<before_empty_literal>[\d\D]*[\s\n\r(]+([a-z0-9_]*[\s\n\r]*\.){0,1}[\s\n\r]*[a-z0-9_]+)[\s\n\r]*(![\s\n\r]*=|<[\s\n\r]*>)[\s\n\r]*\'\'(?P<after_empty_literal>[\s\n\r]*[\d\D]*\z)/i', $t_templated_query, $t_matches ) > 0 ) {
               $t_templated_query = $t_matches['before_empty_literal'] . ' IS NOT NULL ' . $t_matches['after_empty_literal'];
            }

            $p_query = $t_templated_query;
            # Process input bind variable array to replace "WHERE fld=:12"
            # by "WHERE fld IS NULL" if :12 is empty
            while( preg_match( '/^(?P<before_var>[\d\D]*[\s\n\r(]+)(?P<var_name>([a-z0-9_]*[\s\n\r]*\.){0,1}[\s\n\r]*[a-z0-9_]+)(?P<dividers>[\s\n\r]*=[\s\n\r]*:)(?P<bind_name>[0-9]+)(?P<after_var>[\s\n\r]*[\d\D]*\z)/i', $t_templated_query, $t_matches ) > 0 ) {
               $t_bind_num = $t_matches['bind_name'];

               $t_search_substr = $t_matches['before_var'] . $t_matches['var_name'] . $t_matches['dividers'] . $t_matches['bind_name'] . $t_matches['after_var'];
               $t_replace_substr = $t_matches['before_var'] . $t_matches['var_name'] . '=:' . $t_matches['bind_name']. $t_matches['after_var'];

               if( $p_arr_parms[$t_bind_num] === '' ) {
                  for( $n = $t_bind_num + 1; $n < count( $p_arr_parms ); $n++ ) {
                     $p_arr_parms[$n - 1] = $p_arr_parms[$n];
                  }
                  unset( $p_arr_parms[count( $p_arr_parms ) - 1] );
                  $t_replace_substr = $t_matches['before_var'] . $t_matches['var_name'] . ' IS NULL ' . $t_matches['after_var'];
               }
               $p_query = str_replace( $t_search_substr, $t_replace_substr, $p_query );

               $t_templated_query = $t_matches['before_var'] . $t_matches['after_var'];
            }

            if( $t_set_where_stmt ) {
               # Put temporarily removed "SET ... WHERE" part back
               $p_query = str_replace( $t_set_where_template_str, $t_removed_set_where, $p_query );
               # Need to order parameter array element correctly
               $p_query = $this->oracleOrderBindsSequentially( $p_query );
               # Find and remove temporary "SET var1=:bind1, var2=:bind2 WHERE" part again
               preg_match( '/^(?P<before_set_where>.*)(?P<set_where>[\s\n\r]*set[\s\n\r]+[\s\n\ra-z0-9_\.=,:\']+)(?P<after_set_where>where[\d\D]*)$/i', $p_query, $t_matches );
               $t_removed_set_where = $t_matches['set_where'];
               $p_query = $t_matches['before_set_where'] . $t_set_where_template_str . $t_matches['after_set_where'];

               #Replace "SET fld1=:1" to "SET fld1=DEFAULT" if bind array value is empty
               $t_removed_set_where_parsing = $t_removed_set_where;

               while( preg_match( '/^(?P<before_var>[\d\D]*[\s\n\r,]+)(?P<var_name>([a-z0-9_]*[\s\n\r]*\.){0,1}[\s\n\r]*[a-z0-9_]+)(?P<dividers>[\s\n\r]*=[\s\n\r]*:)(?P<bind_name>[0-9]+)(?P<after_var>[,\s\n\r]*[\d\D]*\z)/i', $t_removed_set_where_parsing, $t_matches ) > 0 ) {
                  $t_bind_num = $t_matches['bind_name'];
                  $t_search_substr = $t_matches['before_var'] . $t_matches['var_name'] . $t_matches['dividers'] . $t_matches['bind_name'] ;
                  $t_replace_substr = $t_matches['before_var'] . $t_matches['var_name'] . $t_matches['dividers'] . $t_matches['bind_name'] ;

                  if( $p_arr_parms[$t_bind_num] === '' ) {
                     for( $n = $t_bind_num + 1; $n < count( $p_arr_parms ); $n++ ) {
                        $p_arr_parms[$n - 1] = $p_arr_parms[$n];
                     }
                     unset( $p_arr_parms[count( $p_arr_parms ) - 1] );
                     $t_replace_substr = $t_matches['before_var'] . $t_matches['var_name'] . '=DEFAULT ';
                  }
                  $t_removed_set_where = str_replace( $t_search_substr, $t_replace_substr, $t_removed_set_where );
                  $t_removed_set_where_parsing = $t_matches['before_var'] . $t_matches['after_var'];
               }
               $p_query = str_replace( $t_set_where_template_str, $t_removed_set_where, $p_query );
            }
         }
      }
      $p_query = $this->oracleOrderBindsSequentially( $p_query );
      return $p_query;
   }

   /**
    * Replace 4-byte UTF-8 chars
    * This is a workaround to avoid data getting truncated on MySQL databases
    * using native utf8 encoding, which only supports 3 bytes chars (see #20431)
    * @param string $p_string
    * @return string
    */
   public function mysqlFixUtf8( $p_string ) {
      if( !db_is_mysql() ) {
         return $p_string;
      }
      return preg_replace(
         # 4-byte UTF8 chars always start with bytes 0xF0-0xF7 (0b11110xxx)
         '/[\xF0-\xF7].../s',
         # replace with U+FFFD to avoid potential Unicode XSS attacks,
         # see http://unicode.org/reports/tr36/#Deletion_of_Noncharacters
         "\xEF\xBF\xBD",
         $p_string
      );
   }

   /**
    * Creates an empty record set, compatible with db_query() result
    * This object can be used when a query can't be performed, or is not needed,
    * and still want to return an empty result as a transparent return value.
    * @return \ADORecordSet_empty
    */
   public function emptyResult() {
      return new ADORecordSet_empty();
   }

   /**
    * Process a query string by replacing token parameters by their bound values
    * @param string $p_query     Query string
    * @param array $p_arr_parms  Parameter array
    * @return string             Processed query string
    */
   public function formatQueryLogMsg( $p_query, array $p_arr_parms ) {
      

      $t_lastoffset = 0;
      $i = 0;
      if( !empty( $p_arr_parms ) ) {
         # For mysql, tokens are '?', and parameters are bound sequentially
         # For pgsql, tokens are '$number', and parameters are bound by the denoted
         # index (1-based) in the parameter array
         # For oracle, tokens are ':string', but mantis rewrites them as sequentially
         # ordered, so they behave like mysql. See db_oracle_order_binds_sequentially()
         $t_regex = '/(?<token>\?|\$|:)(?<index>[0-9]*)/';
         while( preg_match( $t_regex , $p_query, $t_matches, PREG_OFFSET_CAPTURE, $t_lastoffset ) ) {
            $t_match_param = $t_matches[0];
            # Realign the offset returned by preg_match as it is byte-based,
            # which causes issues with UTF-8 characters in the query string
            # (e.g. from custom fields names)
            $t_utf8_offset = utf8_strlen( substr( $p_query, 0, $t_match_param[1] ), mb_internal_encoding() );
            if( $i <= count( $p_arr_parms ) ) {
               if( $this->isPgsql() ) {
                  # For pgsql, the bound value is indexed by the parameter name
                  $t_index = (int)$t_matches['index'][0];
                  $t_value = $p_arr_parms[$t_index-1];
               } else {
                  $t_value = $p_arr_parms[$i];
               }
               if( is_null( $t_value ) ) {
                  $t_replace = 'NULL';
               } else if( is_string( $t_value ) ) {
                  $t_replace = "'" . $t_value . "'";
               } else if( is_integer( $t_value ) || is_float( $t_value ) ) {
                  $t_replace = (float)$t_value;
               } else if( is_bool( $t_value ) ) {
                  # use the actual literal from db driver
                  $t_replace = $t_value ? $this->adodb->true : $this->adodb->false;
               } else {
                  # Could not find a supported type for this parameter value.
                  # Skip this token, so replacing it with itself.
                  $t_replace = $t_match_param[0];
               }
               $p_query = utf8_substr( $p_query, 0, $t_utf8_offset )
                  . $t_replace
                  . utf8_substr( $p_query, $t_utf8_offset + utf8_strlen( $t_match_param[0] ) );
               $t_lastoffset = $t_match_param[1] + strlen( $t_replace ) + 1;
            } else {
               $t_lastoffset = $t_match_param[1] + 1;
            }
            $i++;
         }
      }
      return $p_query;
   }
}

AdodbWrapper::staticInit();
