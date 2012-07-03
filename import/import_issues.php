<?php

include_once('../include/session.inc.php');
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

require '../path.inc.php';

require('super_header.inc.php');

/* INSERT INCLUDES HERE */

require_once('../smarty_tools.php');

require_once('user.class.php');
require_once('team.class.php');
require_once('project.class.php');
require_once('consistency_check2.class.php');

require('display.inc.php');


/* INSERT FUNCTIONS HERE */

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

   $issues[] = $newIssue;

   return $issues;
}

/**
 * jeditable formatted CommandList
 *
 * @param int $teamid
 * @param bool $selected
 * @return string json encoded list
 */
function getJsonCommands($teamid, $selected = NULL) {

   $team = TeamCache::getInstance()->getTeam($teamid);
   $cmdList = $team->getCommands();

   $commands = array();
   foreach ($cmdList as $id => $cmd) {
      $commands[$id] = $cmd->getName();
   }

   if ($selected) {
      $commands['selected'] = $selected;
   }

   return json_encode($commands);
}

/**
 *
 * @param type $teamid
 * @param type $selected
 * @return type
 */
function getCommands($teamid, $selected = NULL) {

   $team = TeamCache::getInstance()->getTeam($teamid);
   $cmdList = $team->getCommands();

   $items = array();
   if (0 != count($cmdList)) {

      foreach ($cmdList as $id => $cmd) {
         $items[] = array(
            'id' => $id,
            'name' => $cmd->getName(),
            'selected' => ($id == $selected)
         );
      }
   }
   return $items;
}

/**
 *
 * @param int $projectid
 * @param bool $selected
 * @return string json encoded list
 */
function getJsonProjectCategories($projectid, $selected = NULL) {

   $prj = ProjectCache::getInstance()->getProject($projectid);

   $categories = $prj->getCategories();

   if ($selected) {
      $categories['selected'] = $selected;
   }
   return json_encode($categories);
}

/**
 *
 * @param int $projectid
 * @return string json encoded list
 */
function getProjectCategories($projectid, $selected = NULL) {

   $items = array();
   if (0 != $projectid) {

   $prj = ProjectCache::getInstance()->getProject($projectid);
   $catList = $prj->getCategories();

      foreach ($catList as $id => $name) {
         $items[] = array(
            'id' => $id,
            'name' => $name,
            'selected' => ($id == $selected)
         );
      }
   }
   return $items;
}



/**
 *
 * @param type $projectid
 * @param type $selected
 * @return type
 */
function getJsonProjectTargetVersion($projectid, $selected = NULL) {

   $prj = ProjectCache::getInstance()->getProject($projectid);

   $versions = $prj->getProjectVersions();

   if ($selected) {
      $versions['selected'] = $selected;
   }

   return json_encode($versions);
}

/**
 *
 * @param int $projectid
 * @return string json encoded list
 */
function getProjectTargetVersion($projectid, $selected = NULL) {

   $items = array();
   if (0 != $projectid) {

   $prj = ProjectCache::getInstance()->getProject($projectid);
   $versions = $prj->getProjectVersions();

      foreach ($versions as $id => $name) {
         $items[] = array(
            'id' => $id,
            'name' => $name,
            'selected' => ($id == $selected)
         );
      }
   }
   return $items;
}


/**
 *
 * @param type $teamid
 * @param type $selected
 * @return type
 */
function getJsonUsers($teamid, $selected = NULL) {

   $userList = Team::getActiveMemberList($teamid);

   if ($selected) {
      $userList['selected'] = $selected;
   }
   return json_encode($userList);
}

/**
 *
 * @param type $teamid
 * @param type $selected
 * @return type
 */
function getUsers($teamid, $selected = NULL) {

   $userList = Team::getActiveMemberList($teamid);

   $items = array();
   if (0 != count($userList)) {

      foreach ($userList as $id => $name) {
         $items[] = array(
            'id' => $id,
            'name' => $name,
            'selected' => ($id == $selected)
         );
      }
   }
   return $items;
}


// ================ MAIN =================

$logger = Logger::getLogger("import_issues");

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_("Import Mantis Issues"));

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);

   $action = isset($_POST['action']) ? $_POST['action'] : '';


   if ("uploadFile" == $action) {
      if ($_FILES['importMantis']['error']) {
               switch ($_FILES['nom_du_fichier']['error']){
                        case 1: // UPLOAD_ERR_INI_SIZE
                        echo"Le fichier dépasse la limite autorisée par le serveur (fichier php.ini) !";
                        break;
                        case 2: // UPLOAD_ERR_FORM_SIZE
                        echo "Le fichier dépasse la limite autorisée dans le formulaire HTML !";
                        break;
                        case 3: // UPLOAD_ERR_PARTIAL
                        echo "L'envoi du fichier a été interrompu pendant le transfert !";
                        break;
                        case 4: // UPLOAD_ERR_NO_FILE
                        echo "Le fichier que vous avez envoyé a une taille nulle !";
                        break;
               }
      }
      else {
      // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
      // ce qui signifie qu'il n'y a eu aucune erreur
      }

      $extensions = array('.csv', '.xls');
      $extension = strrchr($_FILES['avatar']['name'], '.');
      if(!in_array($extension, $extensions)) {
         $erreur = 'Vous devez uploader un fichier de type png, gif, jpg, jpeg, txt ou doc...';
         $logger->error($erreur);
      }


      $teamid = 4;
      $projectid = 14;
      $smartyHelper->assign('teamid', $teamid);
      $smartyHelper->assign('projectid', $projectid);
      $smartyHelper->assign('newIssues', getFakeNewIssues());

      $smartyHelper->assign('commandList', getCommands($teamid));
      $smartyHelper->assign('categoryList', getProjectCategories($projectid));
      $smartyHelper->assign('targetversionList', getProjectTargetVersion($projectid));
      $smartyHelper->assign('userList', getUsers($teamid));

      $smartyHelper->assign('jed_commandList', getJsonCommands($teamid));
      $smartyHelper->assign('jed_categoryList', getJsonProjectCategories($projectid));
      $smartyHelper->assign('jed_targetVersionList', getJsonProjectTargetVersion($projectid));
      $smartyHelper->assign('jed_userList', getJsonUsers($teamid));

   }



}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>