<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

class ImportIssuesController extends Controller {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger("import_issues");
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         // except Observed teams
         $dTeamList = $this->session_user->getDevTeamList();
         $lTeamList = $this->session_user->getLeadedTeamList();
         $managedTeamList = $this->session_user->getManagedTeamList();
         $teamList = $dTeamList + $lTeamList + $managedTeamList;

         
         if ((0 == $this->teamid) || 
             !array_key_exists($this->teamid, $teamList) ||
             ($this->session_user->isTeamCustomer($this->teamid))) {
            $this->smartyHelper->assign('accessDenied', TRUE);
         } else {
         
         #if ((0 != $this->teamid) && array_key_exists($this->teamid, $teamList)) {

            $team = TeamCache::getInstance()->getTeam($this->teamid);

            $this->smartyHelper->assign('teamid', $this->teamid);
            if (0 != $this->teamid) {
               $this->smartyHelper->assign('teamName', $team->getName());
            }

            // use the projectid set in the form, if not defined (first page call) use session projectid
            if (isset($_POST['projectid'])) {
               $projectid = Tools::getSecurePOSTIntValue('projectid');
               $_SESSION['projectid'] = $projectid;
            } else {
               $projectid = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
            }
            $this->smartyHelper->assign('projectid', $projectid);
            if (0 != $projectid) {
               $proj = ProjectCache::getInstance()->getProject($projectid);
               $this->smartyHelper->assign('projectName', $proj->getName());
            }

            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$this->teamid));
            // exclude noStatsProjects and disabled projects
            $this->smartyHelper->assign('projects', SmartyTools::getSmartyArray($team->getProjects(false, false),$projectid));

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

               // --- READ CSV FILE ---
               #$smartyHelper->assign('newIssues', getFakeNewIssues());
               $this->smartyHelper->assign('newIssues', $this->getIssuesFromCSV($tmpFilename));

               if (!$err_msg) {
                  $this->smartyHelper->assign('filename', $filename);

                  $commands = $this->getCommands($team);
                  $projectCategories = $this->getProjectCategories($projectid);
                  $projectTargetVersion = $this->getProjectTargetVersion($projectid);
                  $activeMembers = $team->getActiveMembers();

                  $smartyCmdList = array();
                  foreach ($commands as $id => $name) {
                     $smartyCmdList[$id] = array(
                        'id' => $id,
                        'name' => $name,
                        'selected' => $id == 0
                     );
                  }

                  
                  $this->smartyHelper->assign('commandList', $smartyCmdList);
                  $this->smartyHelper->assign('categoryList', SmartyTools::getSmartyArray($projectCategories, 0));
                  $this->smartyHelper->assign('targetversionList', SmartyTools::getSmartyArray($projectTargetVersion, 0));
                  $this->smartyHelper->assign('userList', SmartyTools::getSmartyArray($activeMembers, 0));

                  $this->smartyHelper->assign('jed_commandList', Tools::array2json($commands));
                  $this->smartyHelper->assign('jed_categoryList', Tools::array2json($projectCategories));
                  $this->smartyHelper->assign('jed_targetVersionList', Tools::array2json($projectTargetVersion));
                  $this->smartyHelper->assign('jed_userList', Tools::array2json($activeMembers));
               } else {
                  $this->smartyHelper->assign('errorMsg', $err_msg);
               }
            }
         }
      }
   }

   /*
   private function getFakeNewIssues() {
      $issues = array();

      $newIssue = array();
      $newIssue['summary'] = 'summary, blabla';
      $newIssue['mgrEffortEstim'] = '10';
      $newIssue['effortEstim'] = '8';
      $newIssue['extRef'] = 'ADEL0000';
      $newIssue['status'] = 'new';
      $newIssue['command'] = '';
      $newIssue['category'] = '';
      $newIssue['targetVersion'] = '';
      $newIssue['userName'] = '';
      $newIssue['deadline'] = '2012-07-06';

      $issues[] = $newIssue;

      $newIssue = array();
      $newIssue['summary'] = 'blabla2';
      $newIssue['mgrEffortEstim'] = '12';
      $newIssue['effortEstim'] = '7';
      $newIssue['extRef'] = 'ADEL0001';
      $newIssue['status'] = 'new';
      $newIssue['command'] = '';
      $newIssue['category'] = '';
      $newIssue['targetVersion'] = '';
      $newIssue['userName'] = '';
      $newIssue['deadline'] = '2012-07-06';

      $issues[] = $newIssue;

      return $issues;
   }
   */

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
      /* Can't be use with PHP 5.1
      $file->setFlags(SplFileObject::READ_CSV);
      $file->setCsvControl($delimiter,$enclosure,$escape);
      foreach ($file as $row) {
         var_dump($row);
      }
      */
      $row = 0;
      while (!$file->eof()) {
         while ($data = $file->fgetcsv($delimiter,$enclosure)) {
            $row++;
            if (1 == $row) { continue; } // skip column names

            // $data[0] contains 'summary' which is the only mandatory field
            if ('' != $data[0]) {
               $newIssue = array();
               $newIssue['lineNum'] = $row;
               $newIssue['summary'] = preg_replace('![\t\r\n]+!',' ',Tools::convertToUTF8($data[0]));
               $newIssue['mgrEffortEstim'] = str_replace(",", ".", Tools::convertToUTF8($data[1])); // 3,5 => 3.5
               $newIssue['effortEstim'] = str_replace(",", ".", Tools::convertToUTF8($data[2])); // 3,5 => 3.5
               $newIssue['description'] = Tools::convertToUTF8($data[3]);
               $newIssue['deadline'] = Tools::convertToUTF8($data[4]);  // YYY-MM-DD
               $newIssue['extRef'] = Tools::convertToUTF8($data[5]);
               //$newIssue['summary_attr'] = "style='background-color: #FF82B4;'";
               $issues[] = $newIssue;
            }
         }
      }

      return $issues;
   }

   /**
    * @param Team $team
    * @return string[]
    */
   function getCommands(Team $team) {
      $cmdList = $team->getCommands();

      $items = array();
      if (0 != count($cmdList)) {
         foreach ($cmdList as $id => $cmd) {
            $items[$id] = $cmd->getName();
         }
      }
      return $items;
   }

   /**
    * @param int $projectid
    * @return string[]
    */
   private function getProjectCategories($projectid) {
      $catList = array();
      if (0 != $projectid) {
         $prj = ProjectCache::getInstance()->getProject($projectid);
         $catList = $prj->getCategories();
      }
      return $catList;
   }

   /**
    * @param int $projectid
    * @return string[]
    */
   private function getProjectTargetVersion($projectid) {
      $versions = array();
      if (0 != $projectid) {
         $prj = ProjectCache::getInstance()->getProject($projectid);
         $versions = $prj->getProjectVersions();
      }
      return $versions;
   }

}

// ========== MAIN ===========
ImportIssuesController::staticInit();
$controller = new ImportIssuesController('../', 'Import Mantis Issues', 'ImportExport');
$controller->execute();

?>
