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

   $stprojId=73; // 73 =TachesAnnexes RSI_TMA_Sante
   $mgrId=2;
   $usrId=134;
   $lbayleId=60;
   $demoTeamId=20; // 20 = RSI_TMA_Sante



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


function create_fake_db($formattedFieldList) {
   $sql = AdodbWrapper::getInstance();
   $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);

   $extProjTypeProject=Config::getInstance()->getValue(Config::id_externalTasksProject); // 1


   updateUsers();
   updateTeams();
   updateProjects();
   
   
   echo "-  Clean issues...\n"; flush();
   $j = 0;

   // all prj except SideTasksProjects (and externalTasksPrj)
   $resProjects = $sql->sql_query("SELECT * from {project} WHERE id NOT IN (SELECT DISTINCT project_id FROM codev_team_project_table WHERE type = 1)");
   while($rowPrj = $sql->fetchObject($resProjects))	{

      $projid = $rowPrj->id;

      if ($extProjTypeProject === $projid) { continue; } // skip externalTasksPrj


      // change project name
      $sql->sql_query("UPDATE {project} SET name='Project_".$projid."' where id=$projid");
      $j++;

      $sql->sql_query("DELETE FROM {email} ");

      // clean project issues
      $result1 = $sql->sql_query("SELECT * from {bug} WHERE project_id=$projid");
      $i = 0;
      while($row = $sql->fetchObject($result1))	{

         $i++;
         #echo "process project $projid issue $row->id";

         $query  = "UPDATE {bug} SET summary='task p".$projid."_".$row->id."' WHERE id=$row->id ";
         $sql->sql_query($query);

         $query  = "UPDATE {bug_text} SET description='this is a fake issue...' WHERE id=$row->bug_text_id ";
         $sql->sql_query($query);

         $query  = "DELETE FROM {bugnote} WHERE bug_id=$row->id ";
         $sql->sql_query($query);

         $query  = "UPDATE {bug_revision} SET value = 'revision on fake issue' WHERE bug_id='$row->id' ";
         $sql->sql_query($query);
         
         $query  = "DELETE FROM {bug_history} WHERE bug_id='$row->id' AND field_name IN ($formattedFieldList)";
         $sql->sql_query($query);

         $query  = "UPDATE {custom_field_string} SET value='R".($projid*2).($i*231)."' WHERE field_id='".$extIdField."' AND bug_id='$row->id' AND value <> '' ";
         $sql->sql_query($query);

         
      } // issue
   } // proj

   // commands
   echo "-  Clean commands...\n"; flush();
   $sql->sql_query("UPDATE codev_command_table SET reporter = 'Joe the customer'");
   $sql->sql_query("UPDATE codev_command_table SET description = 'fake description...'");

   $result1 = $sql->sql_query("SELECT * from codev_command_table");
   $i = 0;
   while($row = $sql->fetchObject($result1))	{
      $i++;
      $sql->sql_query("UPDATE codev_command_table set name = 'command_$row->id' WHERE id ='$row->id' ");
      $sql->sql_query("UPDATE codev_command_table set reference = 'R$row->id' WHERE id ='$row->id'");
//      $query  = "UPDATE codev_command_table SET cost = '".($i*123+1001200)."00' WHERE id ='$row->id' ";
//      execQuery($query);
   }   

   // commandSets
   $sql->sql_query("UPDATE codev_commandset_table SET description = 'fake description...'");

   $result1 = $sql->sql_query("SELECT * from codev_commandset_table");
   $i = 0;
   while($row = $sql->fetchObject($result1))	{
      $i++;
      $sql->sql_query("UPDATE codev_commandset_table set name = 'CommantSet_$row->id' WHERE id ='$row->id'");
      $sql->sql_query("UPDATE codev_commandset_table SET reference = 'Ref_$row->id' WHERE id ='$row->id' ");
      
      //$query  = "UPDATE codev_commandset_table SET budget = '".($i*623+2001200)."50' WHERE id ='$row->id' ";
      //execQuery($query);
   }   

   // ServiceContract
   $sql->sql_query("UPDATE codev_servicecontract_table SET reporter = 'Joe the customer'");
   $sql->sql_query("UPDATE codev_servicecontract_table SET description = 'fake description...'");

   $result1 = $sql->sql_query("SELECT * from codev_servicecontract_table");
   $i = 0;
   while($row = $sql->fetchObject($result1))	{
      $i++;
      $sql->sql_query("UPDATE codev_servicecontract_table set name = 'serviceContract_$row->id' WHERE id ='$row->id'");
      $sql->sql_query("UPDATE codev_servicecontract_table SET reference = 'OTP_$row->id' WHERE id ='$row->id' ");
   }   

   

}


function updateUsers() {
   echo "-  Clean users...\n"; flush();
   $sql = AdodbWrapper::getInstance();

   global $usrId;
   global $mgrId;
   global $lbayleId;

   $query  = "SELECT id from {user} WHERE id NOT IN (1, $lbayleId, $mgrId, $usrId)"; // administrator, manager, lbayle, user1
   $result1 = $sql->sql_query($query);
   $i = 0;
   while($row = $sql->fetchObject($result1))	{
      $i++;
      $query  = "UPDATE {user} SET realname = 'User NAME".$row->id."' WHERE id ='$row->id' ";
      $sql->sql_query($query);
      $query  = "UPDATE {user} SET username = 'user".$row->id."' WHERE id ='$row->id' ";
      $sql->sql_query($query);
      $query  = "UPDATE {user} SET email = 'user".$row->id."@yahoo.com' WHERE id ='$row->id' ";
      $sql->sql_query($query);
      $query  = "UPDATE {user} SET password = '5ebe2294ecd0e0f08eab7690d2a6ee69' WHERE id ='$row->id' ";
      $sql->sql_query($query);
   }

   // john the manager
   $query  = "UPDATE {user} SET realname = 'John the MANAGER' WHERE id ='$mgrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET username = 'manager' WHERE id ='$mgrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET email = 'manager@yahoo.com' WHERE id ='$mgrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET password = 'e26f604637ae454f792f4fcbff878bd1' WHERE id ='$mgrId' ";
   $sql->sql_query($query); // passwd: manager2012

   // user1
   $query  = "UPDATE {user} SET realname = 'User ONE' WHERE id ='$usrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET username = 'user1' WHERE id ='$usrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET email = 'user1@yahoo.com' WHERE id ='$usrId' ";
   $sql->sql_query($query);
   $query  = "UPDATE {user} SET password = 'ea36a50f4c8944dacadb16e6ca0dd582' WHERE id ='$usrId' ";
   $sql->sql_query($query); // passwd: user2012

   // admin (password must be changed manualy in OVH !!)
   $query  = "UPDATE {user} SET password = 'e26f604637ae454f792f4fcbff878bd1' WHERE id ='1' ";
   $sql->sql_query($query);
}

function updateTeams() {
   $sql = AdodbWrapper::getInstance();
   echo "-  Clean teams...\n"; flush();

   global $mgrId;
   global $lbayleId;
   global $demoTeamId;
   global $stprojId;

   $sql->sql_query("UPDATE codev_team_table SET leader_id = '$lbayleId' ");

   //execQuery("UPDATE codev_team_user_table SET access_level = 10 WHERE team_id <> $demoTeamId AND user_id = $mgrId");
   $sql->sql_query("DELETE FROM codev_team_user_table WHERE team_id <> $demoTeamId AND user_id = $mgrId");


   $resTeams = $sql->sql_query("SELECT * FROM codev_team_table WHERE id NOT IN (1, $demoTeamId)");
   while($rowTeam = $sql->fetchObject($resTeams))	{

      $sql->sql_query("UPDATE codev_team_table SET name = 'Team".$rowTeam->id."' WHERE id ='$rowTeam->id' ");
   }

   // codev_admin team
   $query  = "DELETE FROM codev_team_user_table WHERE team_id ='1' AND user_id NOT IN (1,$lbayleId)"; // admin,lbayle
   $sql->sql_query($query);

   // demo team
   $sql->sql_query("UPDATE codev_team_table SET name = 'DEMO_Team' WHERE id ='$demoTeamId' ");
   $sql->sql_query("UPDATE codev_team_table SET leader_id = '$mgrId' WHERE id ='$demoTeamId' ");
   $sql->sql_query("UPDATE codev_team_table SET description = '' WHERE id ='$demoTeamId' ");

}

function updateProjects() {
   $sql = AdodbWrapper::getInstance();
   echo "-  Clean projects...\n"; flush();

   global $stprojId;
   $extprojId= Config::getInstance()->getValue(Config::id_externalTasksProject); // 3

   // remove ALL files from ALL PROJECTS  (OVH upload fails)
   $sql->sql_query("DELETE FROM {bug_file} ");
   $sql->sql_query("UPDATE {project} SET description = '' ");

   //SELECT DISTINCT pt.project_id, p.name FROM codev_team_project_table as pt, {project} as p WHERE type = 1 AND p.id = pt.project_id;

   $sql->sql_query("UPDATE {project} SET name = CONCAT('SideTasks Project_',id) WHERE id in (SELECT DISTINCT project_id FROM codev_team_project_table WHERE type = 1)");

   // rename project categories
/*
   $result1 = execQuery("SELECT * from {category}");
   while($row = $sql->fetchObject($result1))	{
      $query  = "UPDATE {category} SET name='Category_".$row->project_id.$row->id."' WHERE id='$row->id' ";
      $result2 = execQuery($query);
   }
*/
   // external tasks project
   $sql->sql_query("UPDATE {project} SET name = 'ExternalTasks' WHERE id ='$extprojId' ");

   // demo projects
   $sql->sql_query("UPDATE {project} SET name = 'SideTasks DEMO_Team' WHERE id ='$stprojId' ");

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
 
