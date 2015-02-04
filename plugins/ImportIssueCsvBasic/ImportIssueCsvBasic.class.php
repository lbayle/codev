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
 * Description of ImportIssueCsvBasic
 *
 * @author lob
 */
class ImportIssueCsvBasic  extends IndicatorPluginAbstract {


   const OPTION_CSV_FILENAME = 'csvFilename';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $teamid;
   private $session_userid;
   private $session_user;
   private $selectedProject;

   // config options from Dashboard
   private $csvFilename;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_IMPORT_EXPORT,
      );
      self::$categories = array (
         self::CATEGORY_IMPORT,
      );
   }

   public static function getName() {
      return 'CSV issue import (basic)';
   }
   public static function getDesc($isShortDesc = true) {
      return 'Import a list of issues to MantisBT / CodevTT';
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
         'js_min/editable.min.js',
         'lib/datatables/media/js/jquery.dataTables.min.js'
      );
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->session_userid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
         $this->session_user   = UserCache::getInstance()->getUser($this->session_userid);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID)) {
         $this->selectedProject = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_PROJECT_ID);
      } else {
         $this->selectedProject = 0;
         //throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_PROJECT_ID);
      }

      // set default pluginSettings
      $this->csvFilename = NULL;
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(PluginDataProviderInterface::PARAM_PROJECT_ID, $pluginSettings)) {
            $this->selectedProject = $pluginSettings[PluginDataProviderInterface::PARAM_PROJECT_ID];
         }
         if (array_key_exists(self::OPTION_CSV_FILENAME, $pluginSettings)) {
            $this->csvFilename = $pluginSettings[self::OPTION_CSV_FILENAME];
         }
      }
   }


   /**
    * user shall not be observer or customer
    */
   private function isAccessGranted() {
      if ((0 == $this->teamid) ||
          ($this->session_user->isTeamObserver($this->teamid)) ||
          ($this->session_user->isTeamCustomer($this->teamid))
         ) {
         return false;
      }
      return true;
   }

   /**
    * 
    * @return string the filename of the uploaded CSV file.
    * @throws Exception
    */
   public static function getSourceFile() {
      
      if (isset($_FILES['uploaded_csv'])) {
         $filename = $_FILES['uploaded_csv']['name'];
         $tmpFilename = $_FILES['uploaded_csv']['tmp_name'];

         $err_msg = NULL;

         if ($_FILES['uploaded_csv']['error']) {
            $err_id = $_FILES['uploaded_csv']['error'];
            switch ($err_id){
               case 1:
                  $err_msg = "UPLOAD_ERR_INI_SIZE ($err_id) on file : ".$filename;
                  //echo"Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !";
                  break;
               case 2:
                  $err_msg = "UPLOAD_ERR_FORM_SIZE ($err_id) on file : ".$filename;
                  //echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
                  break;
               case 3:
                  $err_msg = "UPLOAD_ERR_PARTIAL ($err_id) on file : ".$filename;
                  //echo "L'envoi du fichier a été interrompu pendant le transfert !";
                  break;
               case 4:
                  $err_msg = "UPLOAD_ERR_NO_FILE ($err_id) on file : ".$filename;
                  //echo "Le fichier que vous avez envoyé a une taille nulle !";
                  break;
            }
            self::$logger->error($err_msg);
         } else {
            // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
            // ce qui signifie qu'il n'y a eu aucune erreur
         }

         $extensions = array('.csv', '.CSV');
         $extension = strrchr($filename, '.');
         if(!in_array($extension, $extensions)) {
            $err_msg = T_('Please upload files with the following extension: ').implode(', ', $extensions);
            self::$logger->error($err_msg);
         }
         
      } else {
         $err_msg = "no file to upload.";
         self::$logger->error($err_msg);
         self::$logger->error('$_FILES='.  var_export($_FILES, true));
      }
      if (NULL !== $err_msg) {
         throw new Exception($err_msg);
      }
      self::$logger->error('tmpFilename='. $tmpFilename);
      return $tmpFilename;
   }
   
   /**
    * @param string $filename
    * @param string $delimiter
    * @param string $enclosure
    * @param string $escape
    * @return mixed[]
    */
   private function getIssuesFromCSV($filename, $delimiter = ';', $enclosure = '"', $escape = '"') {
      $issues = array();

      $file = new SplFileObject($filename);
      // Can't be use with PHP 5.1
      $file->setFlags(SplFileObject::READ_CSV);
      $file->setCsvControl($delimiter,$enclosure,$escape);
      foreach ($file as $row) {
         //var_dump($row);
         self::$logger->error('SplFileObject row='.  var_export($row, true));
      }
      
      $row = 0;
      while (!$file->eof()) {
         while ($data = $file->fgetcsv($delimiter,$enclosure)) {
            $row++;
            if (1 == $row) { continue; } // skip column names

            // $data[0] contains 'summary' which is the only mandatory field
            if ('' != $data[0]) {
               $newIssue = array();
               $newIssue['lineNum'] = $row;
               $newIssue['summary'] = Tools::convertToUTF8($data[0]);
               $newIssue['mgrEffortEstim'] = str_replace(",", ".", Tools::convertToUTF8($data[1])); // 3,5 => 3.5
               $newIssue['effortEstim'] = str_replace(",", ".", Tools::convertToUTF8($data[2])); // 3,5 => 3.5
               $newIssue['description'] = Tools::convertToUTF8($data[3]);
               $newIssue['deadline'] = Tools::convertToUTF8($data[4]);  // YYYY-MM-DD
               $newIssue['extRef'] = Tools::convertToUTF8($data[5]);
               //$newIssue['summary_attr'] = "style='background-color: #FF82B4;'";
               $issues[] = $newIssue;
            }
         }
      }
      return $issues;
   }
   
  /**
    *
    */
   public function execute() {

      $this->execData = array ();
      
      $isAccessGranted = $this->isAccessGranted();
      if ($isAccessGranted) {
         try {
            
            $team = TeamCache::getInstance()->getTeam($this->teamid);
            // exclude noStatsProjects and disabled projects
            $this->execData['projects'] = $team->getProjects(false, false);
            
            // if file is defined
            if (!empty($this->csvFilename)) {
               
               // read CSV file
               $issues =      $this->getIssuesFromCSV($this->csvFilename);

               // set GLOBAL VALUES BUTTONS
            
            }
            
            

         } catch (Exception $e) {
            $this->execData['errorMsg'] = $e->getMessage();
         }
      } else {
         $this->execData['accessDenied'] = TRUE;
      }      
      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {
/*
      $this->smartyHelper->assign('projectid', $projectid);
      if (0 != $projectid) {
         $proj = ProjectCache::getInstance()->getProject($projectid);
         $this->smartyHelper->assign('projectName', $proj->getName());
      }

      $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$this->teamid));
      // exclude noStatsProjects and disabled projects
      $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($team->getProjects(false, false),$projectid));
               $this->smartyHelper->assign('newIssues', $this->getIssuesFromCSV($tmpFilename));
                  $this->smartyHelper->assign('filename', $filename);

                  $this->smartyHelper->assign('commandList', $smartyCmdList);
                  $this->smartyHelper->assign('categoryList', SmartyTools::getSmartyArray($projectCategories, 0));
                  $this->smartyHelper->assign('targetversionList', SmartyTools::getSmartyArray($projectTargetVersion, 0));
                  $this->smartyHelper->assign('userList', SmartyTools::getSmartyArray($activeMembers, 0));

                  $this->smartyHelper->assign('jed_commandList', Tools::array2json($commands));
                  $this->smartyHelper->assign('jed_categoryList', Tools::array2json($projectCategories));
                  $this->smartyHelper->assign('jed_targetVersionList', Tools::array2json($projectTargetVersion));
                  $this->smartyHelper->assign('jed_userList', Tools::array2json($activeMembers));
      
*/      
      
      $smartyVariables = array();
      
      if (array_key_exists('errorMsg', $this->execData)) {
         $smartyVariables['importIssueCsvBasic_errorMsg'] = $this->execData['errorMsg'];
         return $smartyVariables;
      }
      
      $smartyVariables['importIssueCsvBasic_projects'] = SmartyTools::getSmartyArray($this->execData['projects'],$this->selectedProject);
      

      if (false == $isAjaxCall) {
         $smartyVariables['importIssueCsvBasic_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['importIssueCsvBasic_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
ImportIssueCsvBasic::staticInit();
