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

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

$logger = Logger::getLogger("commandEdit_ajax");

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $action = Tools::getSecurePOSTStringValue('action', 'none');
   $cmdid = $_SESSION['cmdid'];  // WARN: CommandId should be returned by the page ! what if user opened 2 commands in his browser ?!?

   if('saveProvisionChanges' == $action) {
      if(isset($_SESSION['cmdid'])) {
         if (0 != $cmdid) {

            // <provid>:<isInCheckBudget>,
            $imploded = Tools::getSecurePOSTStringValue("isInCheckBudgetImploded");
            $provisions = Tools::doubleExplode(':', ',', $imploded);

            try {
               // save Provision changes
               foreach ($provisions as $provid => $isInCheckBudget) {
                  $prov = new CommandProvision($provid);

                  // securityCheck: does provid belong to this command ?
                  if ($cmdid == $prov->getCommandId()) {
                     $prov->setIsInCheckBudget($isInCheckBudget);
                  } else {
                     // LOG SECURITY ERROR !!
                     Tools::sendBadRequest("Provision $provid does not belong to Command $cmdid !");
                  }
               }
            } catch (Exception $e) {
               Tools::sendBadRequest(T_('Provisions updated FAILED !'));
            }

            // write in 'data'
            echo ('SUCCESS');

         } else {
            Tools::sendBadRequest('Invalid CommandId: 0');
         }
      } else {
         Tools::sendBadRequest("Command not set");
      }
   } elseif ('importProvisionCSV' == $action) {
      try {

         // CSV format: Date;Type;budget_day;budget;average_daily_rate;summary

         $tmpfilename = getSourceFile1();
         $delimiter=";";
         $file = new SplFileObject($tmpfilename);
         // If you want to skip blank lines when reading a CSV file, you need *all * the flags:
         $file->setFlags(SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
         $file->setCsvControl($delimiter);
         $provMngtName = CommandProvision::$provisionNames[CommandProvision::provision_mngt];
         $row = 0;
         while (!$file->eof()) {
            while ($data = $file->fgetcsv($delimiter)) {
               $row++;
               if (1 == $row) {     // skip column names
                  continue;
               } else {
                  $myDate = $data[0];
                  $date = DateTime::createFromFormat('Y-m-d', $myDate);
                  $type = $data[1];
                  $typeId = CommandProvision::getProvisionTypeidFromName($type);
                  $myBudgetDays = $data[2];
                  $myBudget = $data[3];
                  $myAverageDailyRate = $data[4];
                  $mySummary = $data[5];
                  if(FALSE === $date) {
                     throw new Exception("Problème de date");
                  }
                  
                  if (false === $typeId){
                     throw new Exception("Le type n'existe pas");
                  }
                  
                  if (!is_numeric($myBudgetDays) || $myBudgetDays < 0){
                     throw new Exception("Le budget en jour n'est pas valide");
                  }
                  
                  if (!is_numeric($myBudget) || $myBudget < 0){
                     throw new Exception("Le budget n'est pas valide");
                  }
                  
                  if (!is_numeric($myAverageDailyRate) || $myAverageDailyRate < 0){
                     throw new Exception("Le budget en jour n'est pas valide");
                  }
                  
                  $isMngtProv = ( $provMngtName === $type);
                  $prov = array(
                      'date' => $myDate,
                      'type' => $type,
                      'type_id' =>  $typeId,
                      'budget_days' => $myBudgetDays,
                      'budget' => $myBudget,
                      'average_daily_rate' => $myAverageDailyRate,
                      'summary' => $mySummary,
                      'is_checked' => $isMngtProv ? "false" : "true",
                  );
                  $provData[] = $prov;
               }
            }
         }

         $jsonData =  array(
            'statusMsg' => 'SUCCESS',
            'provData' => $provData,
         );
//         $logger->error($jsonData);
      } catch (Exception $e) {
         $jsonData =  array(
            'statusMsg' => 'ERROR could not upload file',
         );
         $logger->error($e->getMessage());
      }
      echo json_encode($jsonData);

   } elseif ('saveProvisionCSV' == $action) {
      try{
         $provDataReceived = $_POST['provdata'];
         
         foreach($provDataReceived as $provReceived){
            $provDateReceive = $provReceived["dateProv"];
            $provTypeIdReceive = $provReceived["typeIdProv"];
            $provBudgetDaysReceive = $provReceived["budget_daysProv"];
            $provBudgetReceive = $provReceived["budgetProv"];
            $provAverageDailyRateReceive = $provReceived["average_daily_rateProv"];
            $provSummaryReceive = $provReceived["summaryProv"];
            $provIsInCheckBudgetReceive = $provReceived["cb_isInCheckBudgetProv"];

            $provTypeReceive = CommandProvision::$provisionNames[$provTypeIdReceive];
            $timestamp = Tools::date2timestamp($provDateReceive);
            $checkDate = DateTime::createFromFormat('Y-m-d', $provDateReceive);

            if(FALSE === $checkDate) {
               throw new Exception("Problème de date");
            }
            if (false === $provTypeIdReceive){
               throw new Exception("Le type n'existe pas");
            }
            if (!is_numeric($provBudgetDaysReceive) || $provBudgetDaysReceive < 0){
               throw new Exception("Le budget en jour n'est pas valide");
            }
            if (!is_numeric($provBudgetReceive) || $provBudgetReceive < 0){
               throw new Exception("Le budget n'est pas valide");
            }
            if (!is_numeric($provAverageDailyRateReceive) || $provAverageDailyRateReceive < 0){
               throw new Exception("Le budget en jour n'est pas valide");
            }

            //enregistrer dans la base de donnée voir avec Louis
            $provId = CommandProvision::create($cmdid, $timestamp, $provTypeIdReceive, $provSummaryReceive, $provBudgetDaysReceive, $provBudgetReceive, $provAverageDailyRateReceive, $provIsInCheckBudgetReceive);

            
            $provReceived = array(
               'id' => $provId,
               'date' => $provDateReceive,
               'type' => $provTypeReceive,
               'type_id' => $provTypeIdReceive,
               'budget_days' => $provBudgetDaysReceive,
               'budget' => $provBudgetReceive,
               'average_daily_rate' => $provAverageDailyRateReceive,
               'summary' => $provSummaryReceive,
               'is_checked' => $provIsInCheckBudgetReceive,
            );
            $provDataSend[] = $provReceived;

            $jsonDataSend =  array(
               'statusMsg' => 'SUCCESS',
               'provDataSend' => $provDataSend,
            );
         }
      } catch (Exception $e){
         $jsonDataSend =  array(
            'statusMsg' => 'ERROR could not create provisions',
         );
         $logger->error($e->getMessage());
      }
      echo json_encode($jsonDataSend);

//      $logger->error("WAZAAAAA");
//      $logger->error($provDataReceive);
      //explode(" ", $pizza)
      // create 
      // return 'statusMsg' => 'SUCCESS',
   } else {
      Tools::sendNotFoundAccess();
   }
} else {
   // send 'Forbidden' caught by ajax: function(jqXHR, textStatus, errorThrown)
   Tools::sendUnauthorizedAccess(); 
}

   /**
    *
    * @return string the filename of the uploaded CSV file.
    * @throws Exception
    */
   function getSourceFile1() {

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
            $logger->error($err_msg);
         } //else {
            // $_FILES['nom_du_fichier']['error'] vaut 0 soit UPLOAD_ERR_OK
            // ce qui signifie qu'il n'y a eu aucune erreur
         //}

         $extensions = array('.csv', '.CSV');
         $extension = strrchr($filename, '.');
         if(!in_array($extension, $extensions)) {
            $err_msg = T_('Please upload files with the following extension: ').implode(', ', $extensions);
            $logger->error($err_msg);
         }

      } else {
         $err_msg = "no file to upload.";
         $logger->error($err_msg);
         $logger->error('$_FILES='.  var_export($_FILES, true));
      }
      if (NULL !== $err_msg) {
         throw new Exception($err_msg);
      }
      //$logger->error('tmpFilename='. $tmpFilename);

      return $tmpFilename;
   }
