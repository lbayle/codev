<?php
require(realpath(dirname(__FILE__)).'/../include/session.inc.php');
require(realpath(dirname(__FILE__)).'/../path.inc.php');

/*
   This file is part of CodevTT

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

/* ==================================================
 * ==== ACTIONS A EFFECTUER APRES EXECUTION =====
 *
 * - supprimer les droits d'administration mantis du user 'manager' (qui est temporairement admin, le compte administrator ayant ete desactive)
 *   => il n'y aura plus aucun admin mantis, il faut reactiver administrator avec phpmyadmin...
 *
 * - cloturer les commandes qui ne sont pas interessantes pour la demo (n'en laisser que 2 ou 3)
 * - postionner le filtre des commandes pour user1 et manager pour ne pas afficher les commandes cloturees
 * - verifier les plugins affichés dans chaque dashboard
 * - supprimer user1 de la team 23
 * - desactiver les doodles ?
 * 
 */



$logger = Logger::getLogger("create_fake_db");

# Make sure this script doesn't run via the webserver
if( php_sapi_name() != 'cli' ) {
	echo "create_fake_db.php is not allowed to run through the webserver.\n ";
   $logger->error("create_fake_db.php is not allowed to run through the webserver.");
	exit( 1 );
}

   $stprojId=73; // 73 =TachesAnnexes RSI_TMA_Sante   // 24;
   $mgrId=2; // 37;
   $usrId=134; // 44
   $lbayleId=2;
   $demoTeamId=20; //11; // RSI_TMA_Sante



/*
 *
demander le projet, puis:

1) rename summary (except SideTasks Projects)
1.1) rename projects name (except SideTasks Projects)
1.2) rename project categories

2) rename ExtRef for all Issues
3) remove notes
4) remove attachments
4) remove descriptions
4.1) remove history items : all ADEL Fields

5) rename users

6) ServiceContracts & Contracts
   - remove description
   - set fake reference, reporter, Cost

 */

function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "ERROR: Query FAILED $query\n";
      exit;
   }
   return $result;
}

function create_fake_db($formattedFieldList) {
   
   $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);

   $extProjTypeProject=Config::getInstance()->getValue(Config::id_externalTasksProject); // 1


   updateUsers();
   updateTeams();
   updateProjects();
   
   
   echo "-  Clean issues...\n"; flush();
   $j = 0;

   // all prj except SideTasksProjects (and externalTasksPrj)
   $resProjects = execQuery("SELECT * from `mantis_project_table` WHERE id NOT IN (SELECT DISTINCT project_id FROM `codev_team_project_table` WHERE type = 1)");
   while($rowPrj = SqlWrapper::getInstance()->sql_fetch_object($resProjects))	{

      $projid = $rowPrj->id;

      if ($extProjTypeProject === $projid) { continue; } // skip externalTasksPrj


      // change project name
      execQuery("UPDATE `mantis_project_table` SET `name`='Project_".$projid."' where `id`='$projid'");
      $j++;

      execQuery("DELETE FROM `mantis_email_table` ");

      // clean project issues
      $result1 = execQuery("SELECT * from `mantis_bug_table` WHERE `project_id`='$projid'");
      $i = 0;
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{

         $i++;
         #echo "process project $projid issue $row->id";

         $query  = "UPDATE `mantis_bug_table` SET `summary`='task p".$projid."_".$row->id." ' WHERE `id`='$row->id' ";
         execQuery($query);

         $query  = "UPDATE `mantis_bug_text_table` SET `description`='this is a fake issue...' WHERE `id`='$row->bug_text_id' ";
         execQuery($query);

         $query  = "DELETE FROM `mantis_bugnote_table` WHERE `bug_id`='$row->id' ";
         execQuery($query);

         $query  = "UPDATE `mantis_bug_revision_table` SET `value` = 'revision on fake issue' WHERE `bug_id`='$row->id' ";
         execQuery($query);
         
         $query  = "DELETE FROM `mantis_bug_history_table` WHERE `bug_id`='$row->id' AND `field_name` IN ($formattedFieldList)";
         execQuery($query);

         $query  = "UPDATE `mantis_custom_field_string_table` SET `value`='R".($projid*2).($i*231)."' WHERE `field_id`='".$extIdField."' AND `bug_id`='$row->id' AND `value` <> '' ";
         execQuery($query);

         
      } // issue
   } // proj

   // commands
   echo "-  Clean commands...\n"; flush();
   execQuery("UPDATE codev_command_table SET `reporter` = 'Joe the customer'");
   execQuery("UPDATE codev_command_table SET `description` = 'fake description...'");

   $result1 = execQuery("SELECT * from `codev_command_table`");
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      execQuery("UPDATE codev_command_table set `name` = 'command_$row->id' WHERE `id` ='$row->id' ");
      execQuery("UPDATE codev_command_table set `reference` = 'R$row->id' WHERE `id` ='$row->id'");
//      $query  = "UPDATE `codev_command_table` SET `cost` = '".($i*123+1001200)."00' WHERE `id` ='$row->id' ";
//      execQuery($query);
   }   

   // commandSets
   execQuery("UPDATE `codev_commandset_table` SET `description` = 'fake description...'");

   $result1 = execQuery("SELECT * from `codev_commandset_table`");
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      execQuery("UPDATE codev_commandset_table set `name` = 'CommantSet_$row->id' WHERE `id` ='$row->id'");
      execQuery("UPDATE codev_commandset_table SET `reference` = 'Ref_$row->id' WHERE `id` ='$row->id' ");
      
      //$query  = "UPDATE `codev_commandset_table` SET `budget` = '".($i*623+2001200)."50' WHERE `id` ='$row->id' ";
      //execQuery($query);
   }   

   // ServiceContract
   execQuery("UPDATE `codev_servicecontract_table` SET `reporter` = 'Joe the customer'");
   execQuery("UPDATE `codev_servicecontract_table` SET `description` = 'fake description...'");

   $result1 = execQuery("SELECT * from `codev_servicecontract_table`");
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      execQuery("UPDATE codev_servicecontract_table set `name` = 'serviceContract_$row->id' WHERE `id` ='$row->id'");
      execQuery("UPDATE codev_servicecontract_table SET `reference` = 'OTP_$row->id' WHERE `id` ='$row->id' ");
   }   

   

}


function updateUsers() {
   echo "-  Clean users...\n"; flush();

   global $usrId;
   global $mgrId;
   global $lbayleId;
/*
   $mgrId=37;
   $usrId=41; // 44
   $lbayleId=2;
 */
   $query  = "SELECT id from `mantis_user_table` WHERE id NOT IN (1, $lbayleId, $mgrId, $usrId)"; // administrator, manager, lbayle, user1
   $result1 = execQuery($query);
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      $query  = "UPDATE `mantis_user_table` SET `realname` = 'User NAME".$row->id."' WHERE `id` ='$row->id' ";
      execQuery($query);
      $query  = "UPDATE `mantis_user_table` SET `username` = 'user".$row->id."' WHERE `id` ='$row->id' ";
      execQuery($query);
      $query  = "UPDATE `mantis_user_table` SET `email` = 'user".$row->id."@yahoo.com' WHERE `id` ='$row->id' ";
      execQuery($query);
      $query  = "UPDATE `mantis_user_table` SET `password` = '5ebe2294ecd0e0f08eab7690d2a6ee69' WHERE `id` ='$row->id' ";
      execQuery($query);
   }

   // john the manager
   $query  = "UPDATE `mantis_user_table` SET `realname` = 'John the MANAGER' WHERE `id` ='$mgrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `username` = 'manager' WHERE `id` ='$mgrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `email` = 'manager@yahoo.com' WHERE `id` ='$mgrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `password` = 'e26f604637ae454f792f4fcbff878bd1' WHERE `id` ='$mgrId' ";
   execQuery($query); // passwd: manager2012

   // user1
   $query  = "UPDATE `mantis_user_table` SET `realname` = 'User ONE' WHERE `id` ='$usrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `username` = 'user1' WHERE `id` ='$usrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `email` = 'user1@yahoo.com' WHERE `id` ='$usrId' ";
   execQuery($query);
   $query  = "UPDATE `mantis_user_table` SET `password` = 'ea36a50f4c8944dacadb16e6ca0dd582' WHERE `id` ='$usrId' ";
   execQuery($query); // passwd: user2012

   // admin (password must be changed manualy in OVH !!)
   $query  = "UPDATE `mantis_user_table` SET `password` = 'e26f604637ae454f792f4fcbff878bd1' WHERE `id` ='1' ";
   execQuery($query);
}

function updateTeams() {

   echo "-  Clean teams...\n"; flush();

   global $mgrId;
   global $lbayleId;
   global $demoTeamId;
   global $stprojId;
   /*
   $mgrId=37;
   $lbayleId=2;
   $demoTeamId=11;
   $stprojId=24;
    */

   execQuery("UPDATE `codev_team_table` SET `leader_id` = '$lbayleId' ");

   //execQuery("UPDATE `codev_team_user_table` SET access_level = 10 WHERE team_id <> $demoTeamId AND user_id = $mgrId");
   execQuery("DELETE FROM `codev_team_user_table` WHERE team_id <> $demoTeamId AND user_id = $mgrId");


   $resTeams = execQuery("SELECT * FROM `codev_team_table` WHERE id NOT IN (1, $demoTeamId)");
   while($rowTeam = SqlWrapper::getInstance()->sql_fetch_object($resTeams))	{

      execQuery("UPDATE `codev_team_table` SET `name` = 'Team".$rowTeam->id."' WHERE `id` ='$rowTeam->id' ");
   }

   // codev_admin team
   $query  = "DELETE FROM `codev_team_user_table` WHERE `team_id` ='1' AND user_id NOT IN (1,$lbayleId)"; // admin,lbayle
   execQuery($query);

   // demo team
   execQuery("UPDATE `codev_team_table` SET `name` = 'DEMO_Team' WHERE `id` ='$demoTeamId' ");
   execQuery("UPDATE `codev_team_table` SET `leader_id` = '$mgrId' WHERE `id` ='$demoTeamId' ");
   execQuery("UPDATE `codev_team_table` SET `description` = '' WHERE `id` ='$demoTeamId' ");
   execQuery("UPDATE `mantis_project_table` SET `name` = 'SideTasks DEMO_Team' WHERE `id` ='$stprojId' ");

}

function updateProjects() {

   echo "-  Clean projects...\n"; flush();

   $stprojId=24;
   $extprojId= Config::getInstance()->getValue(Config::id_externalTasksProject); // 3

   // remove ALL files from ALL PROJECTS  (OVH upload fails)
   execQuery("DELETE FROM `mantis_bug_file_table` ");
   execQuery("UPDATE `mantis_project_table` SET `description` = '' ");

   //SELECT DISTINCT pt.project_id, p.name FROM `codev_team_project_table` as pt, `mantis_project_table` as p WHERE type = 1 AND p.id = pt.project_id;

   execQuery("UPDATE `mantis_project_table` SET `name` = CONCAT('SideTasks Project_',id) WHERE id in (SELECT DISTINCT project_id FROM `codev_team_project_table` WHERE type = 1)");

   // rename project categories
/*
   $result1 = execQuery("SELECT * from `mantis_category_table`");
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $query  = "UPDATE `mantis_category_table` SET `name`='Category_".$row->project_id.$row->id."' WHERE `id`='$row->id' ";
      $result2 = execQuery($query);
   }
*/
   // external tasks project
   execQuery("UPDATE `mantis_project_table` SET `name` = 'ExternalTasks' WHERE `id` ='$extprojId' ");

   // demo projects
   execQuery("UPDATE `mantis_project_table` SET `name` = 'SideTasks DEMO_Team' WHERE `id` ='$stprojId' ");

}

function i18n() {

   // the original DB is in french, convert strings
   // TODO
}



// ================ MAIN =================

$fieldNamesToClear = array(
             'Version produit interne',
             'Version produit client',
             );

$formattedFieldList = '';
foreach ($fieldNamesToClear as $fname) {
   if ('' != $formattedFieldList) { $formattedFieldList .= ","; }
   $formattedFieldList .= "'".$fname."'"; // add quotes
}

create_fake_db($formattedFieldList);

 echo "Done.\n";
 
