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

include_once('install/install.class.php');

class UninstallController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   protected function display() {
      if(isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         // Admins only
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('access', true);

            $is_modified = Tools::getSecurePOSTStringValue('is_modified', 'false');

            // init
            // 'is_modified' is used because it's not possible to make a difference
            // between an unchecked checkBox and an unset checkbox variable
            if ("false" == $is_modified) {
               $isBackup = true;
            } else {
               $isBackup = $_POST['cb_backup'];
            }

            $filename = Tools::getSecurePOSTStringValue('backup_filename', "codevtt_backup_".date("Ymj").".sql");

            $this->smartyHelper->assign('isBackup', $isBackup);
            $this->smartyHelper->assign('filename', $filename);

            if (isset($_POST['cb_backup'])) {
               $result = true;

               if ($isBackup) {
                  $result = SqlWrapper::getInstance()->sql_dump($filename) && $this->saveConfigFiles();
                  $this->smartyHelper->assign('backupResult', $result);
               }

               $this->smartyHelper->assign('stepOneResult', $result);

               if($result) {
                  $prjList = $this->displayProjectsToRemove();
                  $this->smartyHelper->assign('projects', $prjList);
               }
               $this->smartyHelper->assign('stepTwoResult', $result);

               if($result) {
                  $result = $this->removeCustomFields();
               }
               $this->smartyHelper->assign('stepThreeResult', $result);

               if($result) {
                  $result = Tools::execSQLscript("uninstall.sql");
               }
               $this->smartyHelper->assign('stepFourResult', $result);

               if($result) {
                  $result = $this->deleteConfigFiles();
               }
               $this->smartyHelper->assign('stepFiveResult', $result);
            } else {
               Config::setQuiet(true);
               $this->smartyHelper->assign('codevReportsDir', Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports');
               Config::setQuiet(false);
               $this->smartyHelper->assign('is_modified', $is_modified);
            }
         }
      }
   }

   function displayProjectsToRemove() {
      $prjList = array();

      // find externalTasks project
      $extproj_id = Config::getInstance()->getValue(Config::id_externalTasksProject);
      $project = ProjectCache::getInstance()->getProject($extproj_id);
      $prjList[$extproj_id] = $project->getName();

      // find sideTasks projects
      $sideTaskProj_id = Project::type_sideTaskProject;
      $query = "SELECT project.id, project.name ".
         "FROM `mantis_project_table` as project ".
         "JOIN `codev_team_project_table` as team_project ON project.id = team_project.project_id ".
         "WHERE team_project.type = $sideTaskProj_id ".
         "ORDER BY project.name DESC;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if(!$result) {
         return NULL;
      }

      while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $prjList[$row->id] = $row->name;
      }

      return $prjList;
   }

   /**
    * NOTE: function adapted from from mantis/core/custom_field_api.php
    *
    * Delete the field definition and all associated values and project associations
    * return true on success, false on failure
    * @return bool True if success
    */
   function removeCustomFields() {
      $fieldIds = array(
         Config::getInstance()->getValue(Config::id_customField_ExtId),
         Config::getInstance()->getValue(Config::id_customField_MgrEffortEstim),
         Config::getInstance()->getValue(Config::id_customField_effortEstim),
         Config::getInstance()->getValue(Config::id_customField_addEffort),
         Config::getInstance()->getValue(Config::id_customField_backlog),
         Config::getInstance()->getValue(Config::id_customField_deadLine),
         Config::getInstance()->getValue(Config::id_customField_deliveryDate)
      );

      # delete all values
      $query = "DELETE FROM `mantis_custom_field_string_table` WHERE field_id IN (".implode(', ', $fieldIds).");";
      SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");

      # delete all project associations
      $query = "DELETE FROM `mantis_custom_field_project_table` WHERE field_id IN (".implode(', ', $fieldIds).");";
      SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");

      # delete the definition
      $query = "DELETE FROM `mantis_custom_field_table` WHERE id IN (".implode(', ', $fieldIds).");";
      SqlWrapper::getInstance()->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".SqlWrapper::getInstance()->sql_error()."</span>");

      #custom_field_clear_cache( $p_field_id );

      #echo "DEBUG: customField $p_field_id removed</br>";
      return true;
   }

   /**
    * remove CodevTT Config Files
    * @return bool True if success
    */
   function saveConfigFiles() {
      $codevReportsDir = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports';
      if (file_exists(self::$config_file)) {
         $filename = ereg_replace(".*/", "", self::$config_file);
         $retCode = copy(self::$config_file, $codevReportsDir.DIRECTORY_SEPARATOR.$filename);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not save file: " . self::$config_file);
            return false;
         }
      }
      if (file_exists(Install::FILENAME_MYSQL_CONFIG)) {
         $filename = ereg_replace(".*/", "", Install::FILENAME_MYSQL_CONFIG);
         $retCode = copy(Install::FILENAME_MYSQL_CONFIG, $codevReportsDir.DIRECTORY_SEPARATOR.$filename);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not save file: " . Install::FILENAME_MYSQL_CONFIG);
            return false;
         }
      }

      return true;
   }

   /**
    * remove CodevTT Config Files
    * @return bool True if success
    */
   function deleteConfigFiles() {
      if (file_exists(self::$config_file)) {
         $retCode = unlink(self::$config_file);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not delete file: " . self::$config_file);
            return false;
         }
      }
      if (file_exists(Install::FILENAME_MYSQL_CONFIG)) {
         $retCode = unlink(Install::FILENAME_MYSQL_CONFIG);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not delete file: " . Install::FILENAME_MYSQL_CONFIG);
            return false;
         }
      }

      return true;
   }

}

// ========== MAIN ===========
UninstallController::staticInit();
$controller = new UninstallController('Uninstall','Admin');
$controller->execute();

?>
