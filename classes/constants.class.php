<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Read/Write CodevTT configuration file: config.ini
 */
class Constants {

   /**
    * @var Logger The logger
    */
   private static $logger;

   private static $quiet; // do not display any warning message (used for install procedures only)


   public static $config_file;
   public static $config_file_old;

   public static $log4php_file;
   public static $log4php_file_old;

   public static $codevInstall_timestamp;

   public static $mantisURL;
   public static $mantisPath;
   public static $codevURL;
   public static $codevRootDir;
   public static $codevOutputDir;  // logs, reports, etc. /tmp/codevtt

   // log file as defined in log4php.xml
   public static $codevtt_logfile;

   public static $homepage_title;
   public static $logoImageDefault   = "images/codevtt_logo_03_mini.png";
   public static $logoImage   = "images/codevtt_logo_03_mini.png";
   public static $doodles   = array(
      'logo_image_0207_0214' => 'images/codevtt_logo_03_stvalentin.png',
      'logo_image_0317_0317' => 'images/codevtt_logo_03_stpatrick.jpg',
      'logo_image_0401_0401' => 'images/codevtt_logo_03_april_1st.jpg',
      'logo_image_1031_1031' => 'images/codevtt_logo_03_halloween.png',
      'logo_image_1201_1227' => 'images/codevtt_logo_03_christmas.png',
      #'logo_image_1231_1231' => 'images/codevtt_logo_03_happynewyear.png',
      #'logo_image_0101_0106' => 'images/codevtt_logo_03_happynewyear.png',
      );

   // --- DATABASE ---
   public static $db_mantis_type = 'mysqli';
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
   public static $status_enum_workflow;
   public static $mantis_db_table_prefix = 'mantis_';
   public static $mantis_db_table_suffix = '_table';

   // --- RESOLUTION ---
   public static $resolution_fixed;
   public static $resolution_reopened;

   //--- RELATIONSHIPS ---
   public static $relationship_constrained_by;
   public static $relationship_constrains;
   public static $relationship_parent_of = 2; // default mantis value

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

   /**
    * displaying all timetracks at once could overload the server
    */
   public static $issueInfoMaxTimetracksDisplayed = 1000;

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

   // --- DASHBOARDS ---
   // Note: keys defined in IndicatorPluginInterface::DOMAIN_XXX
   public static $dashboardDefaultPlugins = array (
       IndicatorPluginInterface::DOMAIN_HOMEPAGE => array('BlogPlugin'),
       IndicatorPluginInterface::DOMAIN_PROJECT => array('SubmittedResolvedHistoryIndicator','StatusHistoryIndicator2'),
       IndicatorPluginInterface::DOMAIN_TASK => array('IssueBacklogVariationIndicator'),
       IndicatorPluginInterface::DOMAIN_TEAM => array('AvailableWorkforceIndicator','BacklogPerUserIndicator','LoadPerUserIndicator'),
       IndicatorPluginInterface::DOMAIN_USER => array('LoadPerProjectIndicator','OngoingTasks'),
       IndicatorPluginInterface::DOMAIN_COMMAND => array('ProgressHistoryIndicator2','WBSExport'),
       IndicatorPluginInterface::DOMAIN_COMMAND_SET => array('ProgressHistoryIndicator2'),
       IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT => array('ManagementCosts', 'ManagementLoadHistoryIndicator'),
       IndicatorPluginInterface::DOMAIN_IMPORT_EXPORT => array('ImportIssueCsv'),
       IndicatorPluginInterface::DOMAIN_TEAM_ADMIN => array('ImportUsers','FillPeriodWithTimetracks', 'MoveIssueTimetracks'),
       IndicatorPluginInterface::DOMAIN_ADMIN => array('AdminTools','TimetrackDetailsIndicator','UserTeamList'),
   );

   // ---EMAIL---
   public static $emailSettings = array(
      'enable_email_notification' => 1,  // default is enabled
   );

   // ---CNIL---
   // French "Commission nationale de l'informatique et des libertés"
   public static $cnil_company = NULL;
   public static $cnil_contact_email = NULL;

   // ---I18N---
   public static $force_lc_numeric = 0;

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

      self::$log4php_file_old = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'log4php.xml';
      self::$log4php_file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'log4php.xml';

      self::$config_file_old = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.ini';
      self::$config_file = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.ini';

      // check previous configFile location (before v1.7.0)
      if (!file_exists(self::$config_file)) {
         if (file_exists(self::$config_file_old)) {
            //if (!self::$quiet) {
               $errMsg = "config.ini should be in ".dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
               self::$logger->error($errMsg);
            //}
            self::$config_file = self::$config_file_old;
         }
      }
      // check previous configFile location (before v1.6.0)
      if (!file_exists(self::$log4php_file)) {
         if (file_exists(self::$log4php_file_old)) {
            //if (!self::$quiet) {
               $errMsg = "log4php.xml should be in ".dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR;
               self::$logger->error($errMsg);
            //}
            self::$log4php_file = self::$log4php_file_old;
         }
      }
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

      $cnil = $ini_array['cnil'];
      self::$cnil_company       = $cnil['company'];
      self::$cnil_contact_email = $cnil['contact_email'];

      $database = $ini_array['database'];
      if (array_key_exists('db_mantis_type', $database)) {
          self::$db_mantis_type     = $database['db_mantis_type'];
      }
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
      self::$status_enum_workflow = json_decode($mantis['status_enum_workflow'], true); // jsonStr to array

      if (array_key_exists('db_table_prefix', $mantis)) {
         self::$mantis_db_table_prefix = $mantis['db_table_prefix']; // 'mantis_'
         if ( !empty( self::$mantis_db_table_prefix ) && ('_' != substr( self::$mantis_db_table_prefix, -1 )) ) {
            self::$mantis_db_table_prefix .= '_';
         }
      }
      if (array_key_exists('db_table_suffix', $mantis)) {
         self::$mantis_db_table_suffix = $mantis['db_table_suffix']; // '_table'
         if ( !empty( self::$mantis_db_table_suffix ) && ('_' != substr( self::$mantis_db_table_suffix, 0, 1 )) ) {
            self::$mantis_db_table_suffix = '_' . self::$mantis_db_table_suffix;
         }
      }

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

      if (null != $relationships && array_key_exists('relationship_parent_of', $relationships)) {
         self::$relationship_parent_of = $relationships['relationship_parent_of'];
      }

      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY', $relationships['relationship_constrained_by'] );
      define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS',     $relationships['relationship_constrains'] );

      $perf = $ini_array['perf'];
      if ($perf != null && array_key_exists('max_tooltips_per_page', $perf)) {
         self::$maxTooltipsPerPage = $perf['max_tooltips_per_page'];
      }
      if ($perf != null && array_key_exists('issue_info_max_timetracks_displayed', $perf)) {
         self::$issueInfoMaxTimetracksDisplayed = $perf['issue_info_max_timetracks_displayed'];
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

      $dashboards = $ini_array['dashboardDefaultPlugins'];
      if (is_array($dashboards)) {
         foreach ($dashboards as $domain => $plugins) {
            self::$dashboardDefaultPlugins[$domain] = explode(',', $plugins);
         }
      }

      $emailSettings = $ini_array['email'];
      if (is_array($emailSettings)) {
         foreach ($emailSettings as $key => $val) {
            self::$emailSettings[$key] = $val;
         }
      }

      $i18n = $ini_array['i18n'];
      if (is_array($i18n)) {
         if (array_key_exists('force_lc_numeric', $i18n)) {
            self::$force_lc_numeric = $i18n['force_lc_numeric'];
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
      $install_timestamp = (NULL == self::$codevInstall_timestamp) ? Tools::date2timestamp(date("Y-m-d")) : self::$codevInstall_timestamp;
      $general['codevInstall_timestamp'] =  $install_timestamp;
      $general['codevtt_logfile']        = self::$codevtt_logfile;
      $general['homepage_title']         = self::$homepage_title;
      $general['logo_image']             = self::$logoImageDefault;
      $general['codevtt_output_dir']     = self::$codevOutputDir;
      $general['codevtt_dir']            = self::$codevRootDir;
      $general['mantis_dir']             = self::$mantisPath;
      $general['mantis_url']             = self::$mantisURL;
      $general['codevtt_url']            = self::$codevURL;

      $cnil = array();
      $cnil['company'] = self::$cnil_company;
      $cnil['contact_email'] = self::$cnil_contact_email;

      $database = array();
      $database['db_mantis_type']     = self::$db_mantis_type;
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
      $mantis['status_enum_workflow'] = json_encode(self::$status_enum_workflow); // array to jsonStr
      $mantis['db_table_prefix'] = self::$mantis_db_table_prefix;
      $mantis['db_table_suffix'] = self::$mantis_db_table_suffix;

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
      $relationships['relationship_parent_of']     = self::$relationship_parent_of;

      $perf = array();
      $perf[] = '; display tooltips on only the x last_updated issues.';
      $perf[] = '; set to 0 to display all tooltips.';
      $perf['max_tooltips_per_page'] = self::$maxTooltipsPerPage;
      $perf[] = '; displaying all timetracks at once can overload the server on issue_info page';
      $perf['issue_info_max_timetracks_displayed'] = self::$issueInfoMaxTimetracksDisplayed;

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

      $dashboards = array();
      foreach (self::$dashboardDefaultPlugins as $key => $value) {
         $dashboards[$key] = implode(',', $value);
      }

      $emailSettings = array();
      $emailSettings[] = '; --- cronjob (every Friday at 2:00 AM):';
      $emailSettings[] = '; --- 0 2 * * 5 php /var/www/html/codevtt/tools/send_timesheet_emails.php';
      foreach (self::$emailSettings as $key => $val) {
         $emailSettings[$key] = $val;
      }

      $i18n = array();
      $i18n[] = "; force_lc_numeric: default = '0'. Set to '1' if internationalization does not work";
      $i18n[] = '; this mostly depends on your environment: Windows, UX, Docker, apache version...';
      $i18n['force_lc_numeric'] = self::$force_lc_numeric;


      $ini_array = array();
      $ini_array[] = '; This file is part of CodevTT.';
      $ini_array[] = '; - The Variables in here can be customized to your needs';
      $ini_array[] = '; - This file has been generated during install on '.date("D d M Y H:i");

      $ini_array[] = '';
      $ini_array['general']       = $general;
      $ini_array[] = '';
      $ini_array[] = '; French "Commission nationale de l\'informatique et des libertés" (optional)';
      $ini_array['database']      = $database;
      $ini_array[] = '';
      $ini_array['i18n']          = $i18n;
      $ini_array[] = '';
      $ini_array['cnil']          = $cnil;
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
      $ini_array['dashboardDefaultPlugins'] = $dashboards;
      $ini_array[] = '';
      $ini_array['email'] = $emailSettings;
      $ini_array[] = '';

      return Tools::write_php_ini($ini_array, $file);
      #return Tools::write_php_ini($ini_array, "/tmp/toto.ini");

   }
}

Constants::staticInit();
Constants::parseConfigFile();


