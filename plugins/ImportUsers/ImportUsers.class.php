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
 * Description of ImportUsers
 *
 * @author lob
 */
class ImportUsers extends IndicatorPluginAbstract {

    const OPTION_CSV_FILENAME = 'csvFilename';
    const OPTION_TEAM_ID = 'teamId';
    const CONF_MANTIS_ACCESS_LEVEL = '10:viewer, 25:reporter, 40:updater, 55:developer, 70:manager, 90:administrator';

    private static $logger;
    private static $domains;
    private static $categories;
    // params from PluginDataProvider
    private $session_userid;
    private $session_user;
    private $selectedProject;
    // config options from Dashboard
    private $csvFilename;
    private $teamId;
    // internal
    protected $execData;
    
    public static $mantisAccessLevelList;

    /**
     * Initialize static variables
     * @static
     */
    public static function staticInit() {
        self::$logger = Logger::getLogger(__CLASS__);
        
        self::convertConfAccessLevelToArray();

        self::$domains = array(
            self::DOMAIN_IMPORT_EXPORT,
        );
        self::$categories = array(
            self::CATEGORY_IMPORT,
        );
    }

    public static function getName() {
        return 'Import users';
    }

    public static function getDesc($isShortDesc = true) {
        return 'Import a list of users to MantisBT / CodevTT';
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
            'lib/DataTables/media/js/jquery.dataTables.min.js',
        );
    }

    /**
     *
     * @param \PluginDataProviderInterface $pluginDataProv
     * @throws Exception
     */
    public function initialize(PluginDataProviderInterface $pluginDataProv) {

        //self::$logger->error("Params = " . var_export($pluginDataProv, true));

        if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
            $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
        } else {
            throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_TEAM_ID);
        }
        if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
            $this->session_userid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
            $this->session_user = UserCache::getInstance()->getUser($this->session_userid);
        } else {
            throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
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
     * Convert the string enumeration of access level to an array [id] => [name]
     */
    public static function convertConfAccessLevelToArray()
    {
        self::$mantisAccessLevelList = array();
        
        $accessLevels = explode(',', str_replace(" ", "", self::CONF_MANTIS_ACCESS_LEVEL));
        foreach($accessLevels as $accessLevel)
        {
            $accessLevelSplit = explode(':', $accessLevel);
            self::$mantisAccessLevelList[$accessLevelSplit[0]] = ucfirst($accessLevelSplit[1]);
        }
    }
    
    public static function getMantisAccessLevelList()
    {
        return self::$mantisAccessLevelList;
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
            if (array_key_exists(self::OPTION_TEAM_ID, $pluginSettings)) {
                $this->teamId = $pluginSettings[self::OPTION_TEAM_ID];
            }
        }
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
                switch ($err_id) {
                    case 1:
                        $err_msg = "UPLOAD_ERR_INI_SIZE ($err_id) on file : " . $filename;
                        //echo"Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !";
                        break;
                    case 2:
                        $err_msg = "UPLOAD_ERR_FORM_SIZE ($err_id) on file : " . $filename;
                        //echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
                        break;
                    case 3:
                        $err_msg = "UPLOAD_ERR_PARTIAL ($err_id) on file : " . $filename;
                        //echo "L'envoi du fichier a été interrompu pendant le transfert !";
                        break;
                    case 4:
                        $err_msg = "UPLOAD_ERR_NO_FILE ($err_id) on file : " . $filename;
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
            if (!in_array($extension, $extensions)) {
                $err_msg = T_('Please upload files with the following extension: ') . implode(', ', $extensions);
                self::$logger->error($err_msg);
            }
        } else {
            $err_msg = "no file to upload.";
            self::$logger->error($err_msg);
            self::$logger->error('$_FILES=' . var_export($_FILES, true));
        }
        if (NULL !== $err_msg) {
            throw new Exception($err_msg);
        }
        return $tmpFilename;
    }
    
     /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @return mixed[]
     */
    public function getUsersFromCSV($filename, $delimiter = ';', $enclosure = '"', $escape = '"') {
        $users = array();

        $file = new SplFileObject($filename);
        // Can't be use with PHP 5.1
        $file->setFlags(SplFileObject::READ_CSV);
        $file->setCsvControl($delimiter, $enclosure, $escape);

        $row = 0;
        while (!$file->eof()) {
            while ($data = $file->fgetcsv($delimiter, $enclosure)) {
                $row++;
                if (1 == $row) {
                    continue;
                } // skip column names
                // mandatory fields
                if ('' != $data[0] && '' != $data[1] && '' != $data[2]) {

                    // "John MAC FERSON" => "jmacferson"
                    $username = str_replace(' ', '', strtolower($data[1][0] . $data[0]));

                    $newUser = array();
                    $newUser['lineNum'] = $row - 1;
                    $newUser['username'] = Tools::convertToUTF8($username);
                    $newUser['name'] = strtoupper(Tools::convertToUTF8($data[0]));
                    $newUser['firstname'] = Tools::convertToUTF8($data[1]);
                    $newUser['email'] = Tools::convertToUTF8($data[2]);
                    $date = date_create_from_format('Y-m-d', Tools::convertToUTF8($data[3]));
                    // If date has corret format
                    if($date)
                    {
                       $newUser['entryDate'] = date('Y-m-d', $date->getTimestamp());
                    }
                    $users[] = $newUser;
                }
            }
        }

        return $users;
    }

    /**
     *
     */
    public function execute() {

      if ($this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
         // admins can edit any team
         $this->execData['teams'] = Team::getTeams();
      } else {
         // only teamLeaders, not even managers
         $this->execData['teams']  = $this->session_user->getLeadedTeamList();
      }
      return $this->execData;
    }

    /**
     *
     * @param boolean $isAjaxCall
     * @return array
     */
    public function getSmartyVariables($isAjaxCall = false) {
        
        $smartyVariables['importUsers_teams'] = SmartyTools::getSmartyArray($this->execData['teams'], 0);
        
        if (false == $isAjaxCall) {
            $smartyVariables['importUsers_ajaxFile'] = self::getSmartySubFilename();
        }
        $smartyVariables['importUsers_ajaxPhpURL'] = self::getAjaxPhpURL();

        return $smartyVariables;
    }

    public function getSmartyVariablesForAjax() {
        return $this->getSmartyVariables(true);
    }

}

// Initialize static variables
ImportUsers::staticInit();
