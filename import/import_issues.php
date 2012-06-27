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
require_once('consistency_check2.class.php');

require('display.inc.php');


/* INSERT FUNCTIONS HERE */

function getFakeNewIssues() {
   $issues = array();

   $newIssue = array();
   $newIssue['summary'] = 'summary blabla';
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


// ================ MAIN =================

$logger = Logger::getLogger("import_issues");

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_("PAGENAME"));

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);


   /* INSERT CODE HERE */


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

//On fait un tableau contenant les extensions autorisées.
$extensions = array('.csv', '.xls');
// récupère la partie de la chaine à partir du dernier . pour connaître l'extension.
$extension = strrchr($_FILES['avatar']['name'], '.');
//Ensuite on teste
if(!in_array($extension, $extensions)) //Si l'extension n'est pas dans le tableau
{
     $erreur = 'Vous devez uploader un fichier de type png, gif, jpg, jpeg, txt ou doc...';
}



   // TEST EDITABLE DATATABLE

   $smartyHelper->assign('newIssues', getFakeNewIssues());



}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'], $mantisURL);
?>