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

require('include/super_header.inc.php');

require('smarty_tools.php');

require('classes/smarty_helper.class.php');

require_once('classes/user.class.php');
require_once('classes/team.class.php');
require_once('classes/project.class.php');
require_once('classes/consistency_check2.class.php');

require_once('tools.php');

require_once('lib/log4php/Logger.php');

$logger = Logger::getLogger("import_issues");

/*
function getFakeNewIssues() {
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
function getIssuesFromCSV($filename, $delimiter = ';', $enclosure = '"', $escape = '"') {
   $issues = array();

   $file = new SplFileObject($filename);
   /* Can't be use with PHP 5.1
   $file->setFlags(SplFileObject::READ_CSV);
   $file->setCsvControl($delimiter,$enclosure,$escape);
   foreach ($file as $row) {
      echo $row;
   }
   */
   $row = 0;
   while (!$file->eof()) {
      while ($data = $file->fgetcsv($delimiter,$enclosure)) {
         $row++;
         if (1 == $row) { continue; } // skip column names

         //echo "<p> ".count($data)." champs à la ligne $row: ".$data[0].' '.$data[1]."<br /></p>\n";
         if ('' != $data[4]) {

            $newIssue = array();
            $newIssue['lineNum']        = $row;
            $newIssue['summary']        = Tools::convertToUTF8($data[0]);
            $newIssue['mgrEffortEstim'] = str_replace(",", ".", Tools::convertToUTF8($data[1])); // 3,5 => 3.5
            $newIssue['effortEstim']    = str_replace(",", ".", Tools::convertToUTF8($data[2])); // 3,5 => 3.5
            $newIssue['description']    = Tools::convertToUTF8($data[3]);
            $newIssue['deadline']       = Tools::convertToUTF8($data[4]);  // YYY-MM-DD
            $newIssue['extRef']         = Tools::convertToUTF8($data[5]);
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
function getProjectCategories($projectid) {
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
function getProjectTargetVersion($projectid) {
   $versions = array();
   if (0 != $projectid) {
      $prj = ProjectCache::getInstance()->getProject($projectid);
      $versions = $prj->getProjectVersions();
   }
   return $versions;
}

// ================ MAIN =================
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', "Import Mantis Issues");

if (isset($_SESSION['userid'])) {

   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

   $action = Tools::getSecurePOSTStringValue('action', '');

   $dTeamList = $session_user->getDevTeamList();
   $lTeamList = $session_user->getLeadedTeamList();
   $managedTeamList = $session_user->getManagedTeamList();
   $teamList = $dTeamList + $lTeamList + $managedTeamList;

   // use the teamid set in the form, if not defined (first page call) use session teamid
   if (isset($_POST['teamid'])) {
      $teamid = Tools::getSecurePOSTIntValue($_POST['teamid']);
      $_SESSION['teamid'] = $teamid;
   } elseif(isset($_SESSION['teamid'])) {
      $teamid = $_SESSION['teamid'];
   } else {
      $teamIds = array_keys($teamList);
      $teamid = $teamIds[0];
   }

   $team = TeamCache::getInstance()->getTeam($teamid);

   $smartyHelper->assign('teamid', $teamid);
   if (0 != $teamid) {
      $smartyHelper->assign('teamName', $team->name);
   }

   // use the projectid set in the form, if not defined (first page call) use session projectid
   if (isset($_POST['projectid'])) {
      $projectid = Tools::getSecurePOSTIntValue('projectid');
      $_SESSION['projectid'] = $projectid;
   } else {
      $projectid = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : 0;
   }
   $smartyHelper->assign('projectid', $projectid);
   if (0 != $projectid) {
      $proj = ProjectCache::getInstance()->getProject($projectid);
      $smartyHelper->assign('projectName', $proj->name);
   }

   #if ('' == $action) {
   // first call to the page, display FileSelector

   $smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList,$teamid));
   $smartyHelper->assign('projects', SmartyTools::getSmartyArray($team->getProjects(false),$projectid));
   #}

   if ("uploadFile" == $action) {

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
         $logger->error($err_msg);
      } else {
         // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
         // ce qui signifie qu'il n'y a eu aucune erreur
      }

      $extensions = array('.csv', '.CSV');
      $extension = strrchr($filename, '.');
      if(!in_array($extension, $extensions)) {
         $err_msg = T_('Please upload files with the following extension: ').implode(', ', $extensions);
         $logger->error($err_msg);
      }

      // --- READ CSV FILE ---
      #$smartyHelper->assign('newIssues', getFakeNewIssues());
      $smartyHelper->assign('newIssues', getIssuesFromCSV($tmpFilename));

      if (!$err_msg) {

         $smartyHelper->assign('filename', $filename);

         $commands = getCommands($team);
         $projectCategories = getProjectCategories($projectid);
         $projectTargetVersion = getProjectTargetVersion($projectid);
         $activeMembers = $team->getActiveMembers();

         $smartyHelper->assign('commandList', SmartyTools::getSmartyArray($commands, 0));
         $smartyHelper->assign('categoryList', SmartyTools::getSmartyArray($projectCategories, 0));
         $smartyHelper->assign('targetversionList', SmartyTools::getSmartyArray($projectTargetVersion, 0));
         $smartyHelper->assign('userList', SmartyTools::getSmartyArray($activeMembers, 0));

         $smartyHelper->assign('jed_commandList', Tools::array2json($commands));
         $smartyHelper->assign('jed_categoryList', Tools::array2json($projectCategories));
         $smartyHelper->assign('jed_targetVersionList', Tools::array2json($projectTargetVersion));
         $smartyHelper->assign('jed_userList', Tools::array2json($activeMembers));

      } else {
         $smartyHelper->assign('errorMsg', $err_msg);
      }
   }

}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);

?>
