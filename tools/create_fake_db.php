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
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   return $result;
}

function create_fake_db($projectidList, $formattedFieldList, $projectNames, $StrReplacements) {
   
   $extIdField = Config::getInstance()->getValue(Config::id_customField_ExtId);

   // rename project categories
   $formattedProjList = implode(',', $projectidList);
   $query  = "SELECT * from `mantis_category_table` WHERE `project_id` IN ($formattedProjList)";
   $result1 = execQuery($query);
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $query  = "UPDATE `mantis_category_table` SET `name`='Category_".$row->project_id.$row->id."' WHERE `id`='$row->id' ";
      $result2 = execQuery($query);
   }   
   
   $j = 0;
   foreach($projectidList as $projid) {

      // change project name
      $query  = "UPDATE `mantis_project_table` SET `name`='".$projectNames[$j]."' where `id`='$projid'";
      $result = execQuery($query);
      $j++;

      $query  = "DELETE FROM `mantis_email_table` ";
      $result = execQuery($query);

      // clean project issues
      $query  = "SELECT * from `mantis_bug_table` WHERE `project_id`='$projid'";
      $result1 = execQuery($query);
      $i = 0;
      while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{

         $i++;
         echo "process project $projid issue $row->id <br>";

         $query  = "UPDATE `mantis_bug_table` SET `summary`='task p".$projid."_$i ' WHERE `id`='$row->id' ";
         $result = execQuery($query);

         $query  = "UPDATE `mantis_bug_text_table` SET `description`='this is a fake issue...' WHERE `id`='$row->bug_text_id' ";
         $result = execQuery($query);

         $query  = "DELETE FROM `mantis_bugnote_table` WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);

         $query  = "DELETE FROM `mantis_bug_file_table` WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);

         $query  = "UPDATE `mantis_bug_revision_table` SET `value` = 'revision on fake issue' WHERE `bug_id`='$row->id' ";
         $result = execQuery($query);
         
         $query  = "DELETE FROM `mantis_bug_history_table` WHERE `bug_id`='$row->id' AND `field_name` IN ($formattedFieldList)";
         $result = execQuery($query);

         $query  = "UPDATE `mantis_custom_field_string_table` SET `value`='R".($projid*2).($i*231)."' WHERE `field_id`='".$extIdField."' AND `bug_id`='$row->id' ";
         $result = execQuery($query);

         
      } // issue
   } // proj

   // commands
   $query  = "UPDATE `codev_command_table` SET `reporter` = 'Joe the custommer'";
   $result = execQuery($query);
   $query  = "UPDATE `codev_command_table` SET `description` = 'fake description...'";
   $result = execQuery($query);


   $query  = "SELECT * from `codev_command_table`";
   $result1 = execQuery($query);
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      $query  = "UPDATE `codev_command_table` SET `cost` = '".($i*123+1001200)."00' WHERE `id` ='$row->id' ";
      $result = execQuery($query);
   }   
   
   // commandSets
   $query  = "UPDATE `codev_commandset_table` SET `description` = 'fake description...'";
   $result = execQuery($query);

   $query  = "SELECT * from `codev_commandset_table`";
   $result1 = execQuery($query);
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      $query  = "UPDATE `codev_commandset_table` SET `reference` = 'Ref_$row->id".($i*3)."' WHERE `id` ='$row->id' ";
      $result = execQuery($query);
      
      $query  = "UPDATE `codev_commandset_table` SET `budget` = '".($i*623+2001200)."50' WHERE `id` ='$row->id' ";
      $result = execQuery($query);
   }   

   // ServiceContract
   $query  = "UPDATE `codev_servicecontract_table` SET `reporter` = 'Joe the custommer'";
   $result = execQuery($query);
   $query  = "UPDATE `codev_servicecontract_table` SET `description` = 'fake description...'";
   $result = execQuery($query);

   $query  = "SELECT * from `codev_servicecontract_table`";
   $result1 = execQuery($query);
   $i = 0;
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result1))	{
      $i++;
      $query  = "UPDATE `codev_servicecontract_table` SET `reference` = 'OTP_$row->id".($i*3)."' WHERE `id` ='$row->id' ";
      $result = execQuery($query);
   }   

   
   // string replacements
   $i = 0;
   foreach ($StrReplacements as $orig => $dest) {
      $query  = "UPDATE codev_command_table set `name` = REPLACE(`name`,'$orig','$dest')";
      $result = execQuery($query);
      $query  = "UPDATE codev_command_table set `reference` = REPLACE(`reference`,'$orig','$dest')";
      $result = execQuery($query);

      $query  = "UPDATE codev_servicecontract_table set `name` = REPLACE(`name`,'$orig','$dest')";
      $result = execQuery($query);
      $query  = "UPDATE codev_commandset_table set `name` = REPLACE(`name`,'$orig','$dest')";
      $result = execQuery($query);

      $query  = "UPDATE mantis_bug_table set `summary` = REPLACE(`summary`,'$orig','$dest')";
      $result = execQuery($query);

      $query  = "UPDATE mantis_custom_field_string_table set `value` = REPLACE(`value`,'$orig','$dest')";
      $result = execQuery($query);

   }

   
}

// ================ MAIN =================
$logger = Logger::getLogger("create_fake_db");

$projectidList = array(14,16,18,19,23,24,25,39);

$projectNames = array(
    'TSUNO',
    'ZORGLUB',
    'CORTO',
    'PIZZICATO',
    'BIMBO',
    'ENIGMA',
    'PURCELL',
    'GOMAZIO',
    'YANKEE');

$StrReplacements = array(
    'CMS_INDE' => 'TSUNO',
    'INDE' => 'TSUNO',
    'BRESIL' => 'ZORGLUB',
    'SBR' => 'ZORG',
    'BARRACUDA' => 'CORTO',
    'BARR' => 'CORTO',
    'NG4' => 'PIZZICATO',
    'DCNS' => 'PACC',
    'OPMNT' => 'CALC',
    'PMFL' => 'MAKI',
    'CMSADM' => 'OMMOPO',
    'CMS' => 'OMM',
    'GEMO' => 'OTTO',
    'FdP' => 'Cmd'
);


$fieldNamesToClear = array(
             'Version souhaitee de realisation',
             'Version produit interne',
             'Version produit client',
             'Version GEMO de réalisation décidée',
             'Version de realisation interne',
             'Version de realisation',
             'Version ciblée DCNS',
             'Traitement a appliquer',
             'Rea_CoutRealisation',
             'Produit niveau 1',
             'Pièces jointes ADEL',
#             "Phase d'analyse",
             'Phase activite detection de la FFT',
             'Origine',
             'Niveau produit interne',
             'Informations complementaires',
             'FFT mère',
             'FFT fille',
             'Emetteur ADEL',
             'Description',
             'Dcl_AutreIdentifiant',
             'Dci_VersionCibleeN',
             'Dci_ProduitClientVersion',
             'Dci_Produit',
             'Commentaire realisation',
             'Commentaire du controle',
             'Commentaire de réalisation DCNS',
             'Commentaire de décision DCNS',
             'Avis',
             'Attachments.filename',
             'Attachments.description',
             'Anomalie documentaire',
             'Ana_TypeAnomalie',
             'Analyse',
             'Reference externe',
             'Nouveau bogue du client ',
             'Projet'
             );

$formattedFieldList = '';
foreach ($fieldNamesToClear as $fname) {
   if ('' != $formattedFieldList) { $formattedFieldList .= ","; }
   $formattedFieldList .= "'".$fname."'"; // add quotes
}


create_fake_db($projectidList, $formattedFieldList, $projectNames, $StrReplacements);

?>
