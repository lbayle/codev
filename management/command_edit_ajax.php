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

   // use the cmdid set in the form, if not defined use session cmdid
   if(isset($_POST['cmdid'])) {
      $cmdid = $_POST['cmdid'];
      $_SESSION['cmdid'] = $cmdid;
   } else if(isset($_GET['cmdid'])) {
      $cmdid = $_GET['cmdid'];
      $_SESSION['cmdid'] = $cmdid;
   } else if(isset($_SESSION['cmdid'])) {
      $cmdid = $_SESSION['cmdid'];
      $logger->error("WARN: cmdid not defined in form, using _SESSION");
   }
   if (0 == $cmdid) {
      Tools::sendBadRequest('Invalid CommandId: 0');
   }

   if('saveProvisionChanges' == $action) {

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

   } elseif ('importProvisionCSV' == $action) {
      try {
         $currencies = Currencies::getInstance()->getCurrencies();

         // CSV format: Date;Type;budget_day;budget;currency;summary

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
                  $myDate = Tools::convertToUTF8($data[0]);
                  $date = DateTime::createFromFormat('Y-m-d', $myDate);
                  $provType = Tools::convertToUTF8($data[1]);
                  $provTypeId = CommandProvision::getProvisionTypeidFromName($provType);
                  $myBudgetDays = str_replace(",", ".", Tools::convertToUTF8($data[2])); // 3,5 => 3.5
                  $myBudget = str_replace(",", ".", Tools::convertToUTF8($data[3]));
                  $myCurrency = mb_strtoupper(Tools::convertToUTF8($data[4]), 'UTF-8');
                  $mySummary = Tools::convertToUTF8($data[5]);

                  if(FALSE === $date) {
                     throw new Exception("Could not parse date (Y-m-d) : ".$myDate);
                  }
                  
                  if (false === $provTypeId){
                     throw new Exception("Unknown provision type: ". $provType);
                  }
                  
                  if (!is_numeric($myBudgetDays) || $myBudgetDays < 0){
                     throw new Exception("Invalid BudgetDays: ".$myBudgetDays);
                  }
                  
                  if (!is_numeric($myBudget) || $myBudget < 0){
                     throw new Exception("Invalid budget: ".$myBudget);
                  }

                  if (!array_key_exists($myCurrency, $currencies)) {
                     throw new Exception("Invalid currency: ".$myCurrency);
                  }

                  // compute ADR
                  if (0 != $myBudgetDays) {
                     $myAverageDailyRate = $myBudget / $myBudgetDays;
                  } else {
                     $myAverageDailyRate = 0;
                  }
                  
                  $isMngtProv = ( $provMngtName === $provType);
                  $prov = array(
                      'date' => $myDate,
                      'type' => $provType,
                      'type_id' =>  $provTypeId,
                      'budget_days' => $myBudgetDays,
                      'budget' => $myBudget,
                      'currency' => $myCurrency,
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
         $currencies = Currencies::getInstance()->getCurrencies();
         
         foreach($provDataReceived as $provReceived){
            $provDateReceive = $provReceived["dateProv"];
            $provTypeIdReceive = $provReceived["typeIdProv"];
            $provBudgetDaysReceive = $provReceived["budget_daysProv"];
            $provBudgetReceive = $provReceived["budgetProv"];
            $provCurrencyReceive = $provReceived["currencyProv"];
            $provSummaryReceive = $provReceived["summaryProv"];
            $provIsInCheckBudgetReceive = $provReceived["cb_isInCheckBudgetProv"];

            $provTypeReceive = CommandProvision::$provisionNames[$provTypeIdReceive];
            $timestamp = Tools::date2timestamp($provDateReceive);
            $checkDate = DateTime::createFromFormat('Y-m-d', $provDateReceive);

            if(FALSE === $checkDate) {
               throw new Exception("Wrong provision date : "+ $provDateReceive);
            }
            if (false === $provTypeIdReceive){
               throw new Exception("Undefined provision type");
            }
            if (!is_numeric($provBudgetDaysReceive) || $provBudgetDaysReceive < 0){
               throw new Exception("The days budget is not valid");
            }
            if (!is_numeric($provBudgetReceive) || $provBudgetReceive < 0){
               throw new Exception("The currency budget is not valid");
            }
            if (!array_key_exists($provCurrencyReceive, $currencies)) {
               throw new Exception("Invalid currency: ".$provCurrencyReceive);
            }


            // compute ADR (but remember that it's unused by CodevTT : deprecated)
            if (0 != $provBudgetDaysReceive) {
               $provAverageDailyRate = $provBudgetReceive / $provBudgetDaysReceive;
            } else {
               $provAverageDailyRate = 0;
            }

            $provId = CommandProvision::create($cmdid, $timestamp, $provTypeIdReceive, $provSummaryReceive, $provBudgetDaysReceive, $provBudgetReceive, $provAverageDailyRate, $provIsInCheckBudgetReceive, $provCurrencyReceive);
            
            $provReceived = array(
               'id' => $provId,
               'date' => $provDateReceive,
               'type' => $provTypeReceive,
               'type_id' => $provTypeIdReceive,
               'budget_days' => $provBudgetDaysReceive,
               'budget' => $provBudgetReceive,
               'currency' => $provCurrencyReceive,
               'average_daily_rate' => $provAverageDailyRate,
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
