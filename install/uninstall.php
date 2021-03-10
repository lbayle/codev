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
      if(Tools::isConnectedUser()) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         // Admins only
         if ($session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('access', true);

            $action = Tools::getSecurePOSTStringValue('action', 'none');
            $is_modified = Tools::getSecurePOSTStringValue('is_modified', 'false');

            // init
            // 'is_modified' is used because it's not possible to make a difference
            // between an unchecked checkBox and an unset checkbox variable
            //if ("false" == $is_modified) {
            //   $isBackup = true;
            //} else {
            //   $isBackup = $_POST['cb_backup'];
            //}
            $isBackup = false;

            $filename = Tools::getSecurePOSTStringValue('backup_filename', "codevtt_backup_".date("Ymd").".sql");

            $this->smartyHelper->assign('isBackup', $isBackup);
            $this->smartyHelper->assign('filename', $filename);

            //if (isset($_POST['cb_backup'])) {
            if ('uninstall' === $action) {
               $result = true;

               if ($isBackup) {
                  $result = SqlWrapper::getInstance()->sql_dump($filename) && $this->saveConfigFiles();
                  $this->smartyHelper->assign('backupResult', $result);
               }

               // remove from mantis menu
               //$this->smartyHelper->assign('stepOneResult', true);

               $prjList = $this->displayProjectsToRemove();
               $this->smartyHelper->assign('prjList', $prjList);
               $this->smartyHelper->assign('stepTwoResult', true);

               $result = $this->removeCustomFields();
               $this->smartyHelper->assign('stepThreeResult', $result);

               #$result = Tools::execSQLscript2(Constants::$codevRootDir.'/install/uninstall.sql');
               $result = $this->removeDatabaseTables();
               $this->smartyHelper->assign('stepFourResult', $result);

               $result = $this->deleteConfigFiles();
               $this->smartyHelper->assign('stepFiveResult', $result);

               $result = $this->removeMantisPlugins();
               $this->smartyHelper->assign('stepSixResult', $result);

               $result = $this->removeExternalTasksProject();
               $this->smartyHelper->assign('stepSevenResult', $result);

               echo ("<script type='text/javascript'> parent.location.replace('install.php'); </script>");

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

      // find sideTasks projects
      $sideTaskProj_id = Project::type_sideTaskProject;
      $query = "SELECT project.id, project.name ".
         "FROM {project} as project ".
         "JOIN codev_team_project_table as team_project ON project.id = team_project.project_id ".
         "WHERE team_project.type = $sideTaskProj_id ".
         "ORDER BY project.name DESC;";

      $sql = AdodbWrapper::getInstance();
      $result = $sql->sql_query($query);
      if(!$result) {
         return NULL;
      }

      while($row = $sql->fetchObject($result)) {
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
         #Config::getInstance()->getValue(Config::id_customField_addEffort),
         Config::getInstance()->getValue(Config::id_customField_backlog),
         Config::getInstance()->getValue(Config::id_customField_deadLine),
         Config::getInstance()->getValue(Config::id_customField_deliveryDate),
         Config::getInstance()->getValue(Config::id_customField_type),
         Config::getInstance()->getValue(Config::id_customField_dailyPrice)
      );

      $sql = AdodbWrapper::getInstance();

      # delete all values
      $query = "DELETE FROM {custom_field_string} WHERE field_id IN (".implode(', ', $fieldIds).");";
      $sql->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

      # delete all project associations
      $query = "DELETE FROM {custom_field_project} WHERE field_id IN (".implode(', ', $fieldIds).");";
      $sql->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

      # delete the definition
      $query = "DELETE FROM {custom_field} WHERE id IN (".implode(', ', $fieldIds).");";
      $sql->sql_query($query) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

      #custom_field_clear_cache( $p_field_id );

      #echo "DEBUG: customField $p_field_id removed</br>";
      return true;
   }

   /**
    * same as uninstall.sql, but loading .sql files does not always work.
    */
   function removeDatabaseTables() {
      $sql = AdodbWrapper::getInstance();

      $query = "DROP FUNCTION IF EXISTS get_project_resolved_status_threshold;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP FUNCTION IF EXISTS get_issue_resolved_status_threshold;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP FUNCTION IF EXISTS is_project_in_team;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP FUNCTION IF EXISTS is_issue_in_team_commands;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS `codev_blog_activity_table`;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS `codev_blog_table`;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_commandset_cmd_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_commandset_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_command_bug_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_command_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_config_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_holidays_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_job_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_project_category_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_project_job_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_servicecontract_cmdset_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_servicecontract_stproj_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_servicecontract_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_team_project_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_team_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_team_user_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_userdailycost_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_currencies_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_timetracking_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS `codev_sidetasks_category_table`;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_command_provision_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_wbs_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_plugin_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      $query = "DROP TABLE IF EXISTS codev_timetrack_note_table;";
      $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      return true;
   }

   function removeExternalTasksProject() {
      try {
         $sql = AdodbWrapper::getInstance();
         $extProjId = Config::getInstance()->getValue(Config::id_externalTasksProject);
         $q_params[]=$extProjId;

         $query = "DELETE FROM {bug_text} WHERE id IN (SELECT id FROM {bug} WHERE project_id = ".$sql->db_param().')';
         $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

         $query = "DELETE FROM {bug} WHERE project_id = ".$sql->db_param();
         $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

         $query = "DELETE FROM {category} WHERE project_id = ".$sql->db_param();
         $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");

         $query = "DELETE FROM {project} WHERE id = ".$sql->db_param();
         $sql->sql_query($query, $q_params) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      } catch (Exception $e) {
         return false;
      }
      return true;
   }

   /**
    * remove CodevTT Config Files
    * @return bool True if success
    */
   function saveConfigFiles() {
      $codevReportsDir = Constants::$codevOutputDir.DIRECTORY_SEPARATOR.'reports';
      if (file_exists(Constants::$config_file)) {
         $filename = ereg_replace("^.*\\".DIRECTORY_SEPARATOR, "", Constants::$config_file);
         $retCode = copy(Constants::$config_file, $codevReportsDir.DIRECTORY_SEPARATOR.$filename);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not save file: " . Constants::$config_file);
            return false;
         }
      }

      return true;
   }

   /**
    * remove CodevTT plugin, not FilterBugList
    */
   function removeMantisPlugins() {

      // deactivate plugin
      try {
         $sql = AdodbWrapper::getInstance();
         $query = "DELETE FROM {plugin} WHERE basename FROM {plugin} WHERE basename = ".$sql->db_param();
         $sql->sql_query($query, array('CodevTT')) or die("<span style='color:red'>Query FAILED: $query <br/>".AdodbWrapper::getInstance()->getErrorMsg()."</span>");
      } catch (Exception $e) {
         return false;
      }

      $mantisPluginDir = Constants::$mantisPath . DIRECTORY_SEPARATOR . 'plugins';
      $codevttPluginDir = $mantisPluginDir . DIRECTORY_SEPARATOR . 'CodevTT';

      // remove previous installed plugin
      if (is_writable($codevttPluginDir)) {
         Tools::deleteDir($codevttPluginDir);
      }
      return true;
   }

   /**
    * remove CodevTT Config Files
    * @return bool True if success
    */
   function deleteConfigFiles() {
      if (file_exists(Constants::$config_file) &&
          is_writable(Constants::$config_file)) {
         $retCode = unlink(Constants::$config_file);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not delete file: " . Constants::$config_file);
            return false;
         }
      }
      $log4php_file = Constants::$codevRootDir.DIRECTORY_SEPARATOR.'log4php.xml';
      if (file_exists($log4php_file) &&
          is_writable($log4php_file)) {
         $retCode = unlink($log4php_file);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not delete file: " . $log4php_file);
            return false;
         }
      }

/*      $greasemonkey_url = Constants::$codevURL . DIRECTORY_SEPARATOR . 'mantis_monkey.user.js';
      if (file_exists($greasemonkey_url) &&
          is_writable($greasemonkey_url)) {
         $retCode = unlink($greasemonkey_url);
         if (!$retCode) {
            self::$logger->error("ERROR: Could not delete file: " . $greasemonkey_url);
            return false;
         }
      }
*/
      return true;
   }
}



// ========== MAIN ===========
UninstallController::staticInit();
$controller = new UninstallController('../', 'Uninstall','Admin');
$controller->execute();

