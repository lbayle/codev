<?php

class Constants {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private static $quiet; // do not display any warning message (used for install procedures only)


   public static $config_file;

   public static $codevInstall_timestamp;

   public static $mantisURL;
   public static $mantisPath;
   public static $codevURL;
   public static $codevRootDir;
   public static $codevOutputDir;  // logs, reports, etc. /tmp/codevtt

   // log file as defined in log4php.xml
   public static $codevtt_logfile;

   public static $homepage_title;
   public static $logoImage   = "images/codevtt_logo_03_mini.png";
   public static $doodles   = array(
      'logo_image_0207_0214' => 'images/codevtt_logo_03_stvalentin.png',
      'logo_image_1031_1031' => 'images/codevtt_logo_03_halloween.png',
      'logo_image_1201_1227' => 'images/codevtt_logo_03_christmas.png',
      'logo_image_1231_1231' => 'images/codevtt_logo_03_happynewyear.png',
      'logo_image_0101_0106' => 'images/codevtt_logo_03_happynewyear.png',
      );
   
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

   //--- INTERNET ---
   public static $proxy;
   public static $isCheckLatestVersion = 1;
   
   // --- STATUS ---
   # WARNING: CodevTT uses some status variables in the code, they need to set according to the mantis workflow.
   #          The mandatory variables are:
   #           $status_new, $status_feedback, $status_acknowledged,
   #           $status_open, $status_closed
   public static $status_new;
   public static $status_feedback;
   #public static $status_acknowledged; // DEPRECATED since 0.99.19
   public static $status_open;
   public static $status_closed;
   // TODO add equivalences for all mandatory status not present in workflow (see mantis 131)
   // ex: $status_open = $status_assigned;

   // --- PERF ---
   /**
    * display tooltips on only the x last_updated issues.
    * set to 0 to display all tooltips.
    *
    * tooltips use +30% Memory on project_info page...
    */
   public static $maxTooltipsPerPage = 500;

   // ---TIMESHEETS ---
   public static $taskDurationList = array (
      '1' => '1',
      '0.9' => '0.9',
      '0.8' => '0.8',
      '0.75' => '0.75',
      '0.7' => '0.7',
      '0.6' => '0.6',
      '0.5' => '0.5',
      '0.4' => '0.4',
      '0.3' => '0.3',
      '0.25' => '0.25',
      '0.2' => '0.2',
      '0.1' => '0.1',
      '0.05' => '0.05',
      );
   
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

      self::$logger = Logger::getLogger(__CLASS__);

      self::$quiet = true;

      self::$config_file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.ini';

   }

   /**
    * get config from ini file
    */
   public static function parseConfigFile() {

      // TODO workaround
      $file = self::$config_file;

      if (!file_exists($file)) {
         if (!self::$quiet) {
            self::$logger->error("ERROR: parseConfigFile() file ".$file." NOT found !");
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
      if (array_key_exists('logo_image', $general)) {
         self::$logoImage             = $general['logo_image'];
      }

      self::$codevURL               = $general['codevtt_url'];
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
      #self::$status_acknowledged = $status['status_acknowledged'];
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

      $perf = $ini_array['perf'];
      if ($perf != null && array_key_exists('max_tooltips_per_page', $perf)) {
         self::$maxTooltipsPerPage = $perf['max_tooltips_per_page'];
      }

      $doodles = $ini_array['doodles'];
      if (is_array($doodles)) {
         $today = date("md");
         self::$doodles = array(); // remove default doodles
         foreach ($doodles as $key => $value) {
            self::$doodles[$key] = $value;

            if ((substr($key, 0, 11) === 'logo_image_') &&
                (substr($key, 11,4) <= $today && substr($key, 16,4) >= $today) &&
                (file_exists(self::$codevRootDir.DIRECTORY_SEPARATOR.$value))) {
               self::$logoImage = $value;
            }
         }
      }
      
      $timesheets = $ini_array['timesheets'];
      if (null != $timesheets && array_key_exists('task_duration_list', $timesheets)) {
         self::$taskDurationList = Tools::doubleExplode(':', ',', $timesheets['task_duration_list']);
      }

      $internet = $ini_array['internet'];
      if (is_array($internet)) { 
         if (array_key_exists('proxy', $internet)) {
            self::$proxy = $internet['proxy'];
         }
         if (array_key_exists('check_latest_version', $internet)) {
            self::$isCheckLatestVersion = $internet['check_latest_version'];
         }
      }

      // -----

      /* FIXME WORKAROUND: SQL procedures still use codev_config_table.bug_resolved_status_threshold ! */
      $desc = "bug resolved threshold as defined in Mantis (g_bug_resolved_status_threshold)";
      self::$logger->warn("WORKAROUND update codev_config_table.bug_resolved_status_threshold = ".self::$bug_resolved_status_threshold);
      Config::getInstance()->setValue(Config::id_bugResolvedStatusThreshold, self::$bug_resolved_status_threshold, Config::configType_int , $desc);
   }

   public static function writeConfigFile() {

      // TODO workaround
      $file = self::$config_file;


      $general = array();
      $general['codevInstall_timestamp'] =  $today = Tools::date2timestamp(date("Y-m-d")); #self::$codevInstall_timestamp;
      $general['codevtt_logfile']        = self::$codevtt_logfile;
      $general['homepage_title']         = self::$homepage_title;
      $general['logo_image']             = self::$logoImage;
      $general['codevtt_output_dir']     = self::$codevOutputDir;
      $general['codevtt_dir']            = self::$codevRootDir;
      $general['mantis_dir']             = self::$mantisPath;
      $general['mantis_url']             = self::$mantisURL;
      $general['codevtt_url']            = self::$codevURL;

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
      $status[] = '; Note: CodevTT needs some status to be defined (new, feedback, open, closed)';
      $status[] = '; please add equivalences in accordance to your workflow.';
      $status[] = '; ex: status_open = 50 (assigned)';
      $status['status_new']          = self::$status_new;
      $status['status_feedback']     = self::$status_feedback;
      #$status['status_acknowledged'] = self::$status_acknowledged;
      $status['status_open']         = self::$status_open;
      $status['status_closed']       = self::$status_closed;

      $resolution = array();
      $resolution['resolution_fixed'] = self::$resolution_fixed;
      $resolution['resolution_reopened'] = self::$resolution_reopened;

      $relationships = array();
      $relationships['relationship_constrained_by'] = self::$relationship_constrained_by; // BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY;
      $relationships['relationship_constrains']     = self::$relationship_constrains;     // BUG_CUSTOM_RELATIONSHIP_CONSTRAINS;

      $perf = array();
      $perf[] = '; display tooltips on only the x last_updated issues.';
      $perf[] = '; set to 0 to display all tooltips.';
      $perf['max_tooltips_per_page'] = self::$maxTooltipsPerPage;

      $doodles = array();
      $doodles[] = '; logo_image_startDate_endDate = "images/doodle_logo.png" (date "MMdd")';
      foreach (self::$doodles as $key => $value) {
         $doodles[$key] = $value;
      }
      
      $timesheets = array();
      $timesheets['task_duration_list'] = self::$taskDurationList ? Tools::doubleImplode(':', ',', self::$taskDurationList) : ' ';

      $internet = array();
      if (!empty(self::$proxy)) {
         $internet['proxy'] = self::$proxy;
      } else {
         $internet[] = ';proxy = "proxy:8080"';
      }
      $internet['check_latest_version'] = self::$isCheckLatestVersion;
      
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
      $ini_array['perf'] = $perf;
      $ini_array[] = '';
      $ini_array['doodles'] = $doodles;
      $ini_array[] = '';
      $ini_array['timesheets'] = $timesheets;
      $ini_array[] = '';
      $ini_array['internet'] = $internet;
      $ini_array[] = '';
      $ini_array[] = '';

      return Tools::write_php_ini($ini_array, $file);
      #return Tools::write_php_ini($ini_array, "/tmp/toto.ini");

   }
}

Constants::staticInit();
Constants::parseConfigFile();

?>
