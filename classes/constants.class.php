<?php

include_once('classes/config.class.php');

class Constants {

   private static $quiet; // do not display any warning message (used for install procedures only)


   public static $config_file;

   public static $codevInstall_timestamp;

   public static $mantisURL;
   public static $mantisPath;
   public static $codevRootDir;
   public static $codevOutputDir;  // logs, reports, etc. /tmp/codevtt

   // log file as defined in log4php.xml
   public static $codevtt_logfile;

   public static $homepage_title;

   // --- DATABASE ---
   public static $db_mantis_host;
   public static $db_mantis_database;
   public static $db_mantis_user;
   public static $db_mantis_pass;

   // --- MANTIS ---
   public static $statusNames;
   public static $priority_names;
   public static $resolution_names;
   public static $severity_names;
   public static $bug_resolved_status_threshold;

   // --- RESOLUTION ---
   public static $resolution_fixed;
   public static $resolution_reopened;

   //--- RELATIONSHIPS ---
   public static $relationship_constrained_by;
   public static $relationship_constrains;

   // --- STATUS ---
   # WARNING: CodevTT uses some status variables in the code, they need to set according to the mantis workflow.
   #          The mandatory variables are:
   #           $status_new, $status_feedback, $status_acknowledged,
   #           $status_open, $status_closed
   public static $status_new;
   public static $status_feedback;
   public static $status_acknowledged;
   public static $status_open;
   public static $status_closed;
   // TODO add equivalences for all mandatory status not present in workflow (see mantis 131)
   // ex: $status_open = $status_assigned;

   /**
    * If true, then no info/warning messages will be displayed.
    * this shall only be set during install procedures.
    * @static
    * @param bool $isQuiet
    */
   public static function setQuiet($isQuiet = false) {
      self::$quiet = $isQuiet;
   }

   public static function staticInit() {

      self::$quiet = true;

      #date_default_timezone_set('Europe/Paris');

      self::$config_file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.ini';

      #echo "configFile = ".self::$config_file."<br>";

   }

   /**
    * get config from ini file
    */
   public static function parseConfigFile() {

      // TODO workaround
      $file = self::$config_file;

      if (!file_exists($file)) {
         if (!self::$quiet) {
            echo "ERROR: parseConfigFile() file ".$file." NOT found !";
         }
         return FALSE;
      }

      $ini_array = parse_ini_file($file, true);

      #print_r($ini_array);

      $general = $ini_array['general'];
      self::$codevInstall_timestamp = $general['codevInstall_timestamp'];
      self::$codevtt_logfile        = $general['codevtt_logfile'];
      self::$codevOutputDir         = $general['codevtt_output_dir'];
      self::$homepage_title         = $general['homepage_title'];
      self::$codevRootDir           = $general['codevtt_dir'];
      self::$mantisPath             = $general['mantis_dir'];
      self::$mantisURL              = $general['mantis_url'];

      $database = $ini_array['database'];
      self::$db_mantis_host     = $database['db_mantis_host'];
      self::$db_mantis_database = $database['db_mantis_database'];
      self::$db_mantis_user     = $database['db_mantis_user'];
      self::$db_mantis_pass     = $database['db_mantis_pass'];

      $mantis = $ini_array['mantis'];
      self::$statusNames      = Tools::doubleExplode(':', ',', $mantis['status_enum_string']);
      self::$priority_names   = Tools::doubleExplode(':', ',', $mantis['priority_enum_string']);
      self::$resolution_names = Tools::doubleExplode(':', ',', $mantis['resolution_enum_string']);
      self::$severity_names   = Tools::doubleExplode(':', ',', $mantis['severity_enum_string']);
      self::$bug_resolved_status_threshold = $mantis['bug_resolved_status_threshold'];

      $status = $ini_array['status'];
      self::$status_new          = $status['status_new'];
      self::$status_feedback     = $status['status_feedback'];
      self::$status_acknowledged = $status['status_acknowledged'];
      self::$status_open         = $status['status_open'];
      self::$status_closed       = $status['status_closed'];

      $resolution = $ini_array['resolution'];
      self::$resolution_fixed    = $resolution['resolution_fixed'];
      self::$resolution_reopened = $resolution['resolution_reopened'];

      $relationships = $ini_array['relationships'];
      self::$relationship_constrained_by = $relationships['relationship_constrained_by'];
      self::$relationship_constrains = $relationships['relationship_constrains'];

      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY', $relationships['relationship_constrained_by'] );
      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS',     $relationships['relationship_constrains'] );

   }

   public static function writeConfigFile() {

      // TODO workaround
      $file = self::$config_file;


      $general = array();
      $general['codevInstall_timestamp'] =  $today = Tools::date2timestamp(date("Y-m-d")); #self::$codevInstall_timestamp;
      $general['codevtt_output_dir']     = self::$codevOutputDir;
      $general['codevtt_logfile']        = self::$codevtt_logfile;
      $general['homepage_title']         = self::$homepage_title;
      $general['codevtt_dir']            = self::$codevRootDir;
      $general['mantis_dir']             = self::$mantisPath;
      $general['mantis_url']             = self::$mantisURL;

      $database = array();
      $database['db_mantis_host']     = self::$db_mantis_host;
      $database['db_mantis_database'] = self::$db_mantis_database;
      $database['db_mantis_user']     = self::$db_mantis_user;
      $database['db_mantis_pass']     = self::$db_mantis_pass;

      $mantis = array();
      $mantis['status_enum_string']     = self::$statusNames ? Tools::doubleImplode(':', ',', self::$statusNames) : ' ';
      $mantis['priority_enum_string']   = self::$priority_names ? Tools::doubleImplode(':', ',', self::$priority_names) : ' ';
      $mantis['resolution_enum_string'] = self::$resolution_names ? Tools::doubleImplode(':', ',', self::$resolution_names) : ' ';
      $mantis['severity_enum_string']   = self::$severity_names ? Tools::doubleImplode(':', ',', self::$severity_names) : ' ';
      $mantis['bug_resolved_status_threshold'] = self::$bug_resolved_status_threshold;

      $status = array();
      $status[] = '; Note: CodevTT needs some status to be defined (new, feedback, acknowledged, open, closed)';
      $status[] = '; please add equivalences in accordance to your workflow.';
      $status[] = '; ex: status_open = 50 (assigned)';
      $status['status_new']          = self::$status_new;
      $status['status_feedback']     = self::$status_feedback;
      $status['status_acknowledged'] = self::$status_acknowledged;
      $status['status_open']         = self::$status_open;
      $status['status_closed']       = self::$status_closed;

      $resolution = array();
      $resolution['resolution_fixed'] = self::$resolution_fixed;
      $resolution['resolution_reopened'] = self::$resolution_reopened;

      $relationships = array();
      $relationships['relationship_constrained_by'] = self::$relationship_constrained_by; // BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY;
      $relationships['relationship_constrains']     = self::$relationship_constrains;     // BUG_CUSTOM_RELATIONSHIP_CONSTRAINS;

      $ini_array = array();
      $ini_array[] = '; This file is part of CodevTT.';
      $ini_array[] = '; - The Variables in here can be customized to your needs';
      $ini_array[] = '; - This file has been generated during install on '.date("D d M Y H:i");
      
      $ini_array[] = '';
      $ini_array['general']       = $general;
      $ini_array[] = '';
      $ini_array['database']      = $database;
      $ini_array[] = '';
      $ini_array['mantis']        = $mantis;
      $ini_array[] = '';
      $ini_array['status']        = $status;
      $ini_array[] = '';
      $ini_array['resolution']    = $resolution;
      $ini_array[] = '';
      $ini_array['relationships'] = $relationships;
      $ini_array[] = '';
      $ini_array[] = '';

      return Tools::write_php_ini($ini_array, $file);
      #return Tools::write_php_ini($ini_array, "/tmp/toto.ini");

   }
}

Constants::staticInit();
Constants::parseConfigFile();

?>
