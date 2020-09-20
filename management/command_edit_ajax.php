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

   } elseif ('addProvision' == $action) {

      try {
         // mandatory fields
         $myCmdId = Tools::getSecurePOSTIntValue('cmdid');
         $prov_date = Tools::getSecurePOSTStringValue('date');
         $provTypeId = Tools::getSecurePOSTIntValue('type');
         $prov_currency = Tools::getSecurePOSTStringValue('provisionCurrency');
         $isInCheckBudget = (0 == Tools::getSecurePOSTIntValue("isInCheckBudget")) ? false : true;
         $prov_budgetDays = Tools::getSecurePOSTNumberValue('budgetDays');

         // optional fields
         $prov_summary = Tools::getSecurePOSTStringValue('summary', '');
         $prov_budget = Tools::getSecurePOSTNumberValue('budget', 0);
         $prov_averageDailyRate = Tools::getSecurePOSTNumberValue('averageDailyRate', 0);

         $prov_type = CommandProvision::$provisionNames[$provTypeId];
         if (NULL == $prov_type){
            throw new Exception("Unknown provision type: ". $provTypeId);
         }

         $currencies = Currencies::getInstance()->getCurrencies();
         if (!array_key_exists($prov_currency, $currencies)) {
            throw new Exception("Invalid currency: ".$prov_currency);
         }
         $timestamp = Tools::date2timestamp($prov_date);
         if (0 == $timestamp) {
            throw new Exception("Invalid date: ".$prov_date);
         }

         $provId = CommandProvision::create($myCmdId, $timestamp, $provTypeId, $prov_summary, $prov_budgetDays, $prov_budget, $prov_averageDailyRate, $isInCheckBudget, $prov_currency);

         $prov = array(
            'provId' => $provId,
            'date' => $prov_date,
            'type' => $prov_type,
            'type_id' =>  $provTypeId,
            'budget_days' => $prov_budgetDays,
            'budget' => $prov_budget,
            'currency' => $prov_currency,
            'average_daily_rate' => $prov_averageDailyRate,
            'summary' => $prov_summary,
            'is_checked' => $isInCheckBudget,
         );

         $jsonData["statusMsg"] = "SUCCESS";
         $jsonData["action"] = $action; // js uses same dialog for add/edit
         $jsonData["provData"] = $prov;

      } catch (Exception $e) {
         $jsonData =  array(
            'statusMsg' => 'ERROR could not upload file: '.$e->getMessage(),
         );
         $logger->error($e->getMessage());
         $logger->error("EXCEPTION importProvisionCSV: ".$e->getMessage());
         $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }
      //$logger->error($jsonData);
      echo json_encode($jsonData);

   } elseif ('editProvision' == $action) {
      try {
         $myCmdId = Tools::getSecurePOSTIntValue('cmdid');
         $provRowId = Tools::getSecurePOSTIntValue('provRowId');
         $prov_date = Tools::getSecurePOSTStringValue('date');
         $provTypeId = Tools::getSecurePOSTIntValue('type');
         $prov_budget = Tools::getSecurePOSTNumberValue('budget');
         $prov_budgetDays = Tools::getSecurePOSTNumberValue('budgetDays');
         $prov_averageDailyRate = Tools::getSecurePOSTNumberValue('averageDailyRate');
         $prov_currency = Tools::getSecurePOSTStringValue('provisionCurrency');
         $prov_summary = Tools::getSecurePOSTStringValue('summary');
         $isInCheckBudget = (0 == Tools::getSecurePOSTIntValue("isInCheckBudget")) ? false : true;

         $prov_type = CommandProvision::$provisionNames[$provTypeId];
         if (NULL == $prov_type){
            throw new Exception("Unknown provision type: ". $provTypeId);
         }
         $currencies = Currencies::getInstance()->getCurrencies();
         if (!array_key_exists($prov_currency, $currencies)) {
            throw new Exception("Invalid currency: ".$prov_currency);
         }
         $timestamp = Tools::date2timestamp($prov_date);
         if (0 == $timestamp) {
            throw new Exception("Invalid date: ".$prov_date);
         }

         $prov = new CommandProvision($provRowId);
         if ($myCmdId != $prov->getCommandId()) {
            $msg = "ERROR: Provision $provRowId does not belong to Command $myCmdId !";
            $data["statusMsg"] = msg;
            $logger->error("editProvision :".msg);
         } else {
            $prov->update($timestamp, $provTypeId, $prov_summary, $prov_budgetDays, $prov_budget, $prov_averageDailyRate, $isInCheckBudget, $prov_currency);

            // TODO return values from DB
            $provData = array(
               'provId' => $provRowId,
               'date' => $prov_date,
               'type' => $prov_type,
               'type_id' =>  $provTypeId,
               'budget_days' => $prov_budgetDays,
               'budget' => $prov_budget,
               'currency' => $prov_currency,
               'average_daily_rate' => $prov_averageDailyRate,
               'summary' => $prov_summary,
               'is_checked' => $isInCheckBudget,
            );
            $jsonData["statusMsg"] = "SUCCESS";
            $jsonData["action"] = $action; // js uses same dialog for add/edit
            $jsonData["provData"] = $provData;
         }
      } catch (Exception $e) {
         $jsonData =  array(
            'statusMsg' => 'ERROR could not upload file: '.$e->getMessage(),
         );
         $logger->error($e->getMessage());
         $logger->error("EXCEPTION importProvisionCSV: ".$e->getMessage());
         $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }
      //$logger->error($jsonData);
      echo json_encode($jsonData);

   } elseif ('deleteProvision' == $action) {
      try {
         $myCmdId = Tools::getSecurePOSTIntValue('cmdId');
         $provRowId = Tools::getSecurePOSTIntValue('provRowId');

         // securityCheck: does provid belong to this command ?
         $prov = new CommandProvision($provRowId);
         if ($myCmdId != $prov->getCommandId()) {
            $msg = "ERROR: Provision $provRowId does not belong to Command $myCmdId !";
            $data["statusMsg"] = msg;
            $logger->error("deleteProvision :".msg);
         } else {
            CommandProvision::delete($provRowId, $myCmdId);
            $data["statusMsg"] = "SUCCESS";
            $data["rowId"] = $provRowId;
         }
      } catch (Exception $e) {
         $logger->error("EXCEPTION addUserDailyCost: ".$e->getMessage());
         $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         Tools::sendBadRequest($e->getMessage());
      }
      $jsonData = json_encode($data);
      echo $jsonData;

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
                      'is_checked' => !$isMngtProv, // must be false if management
                  );
                  $provData[$row] = $prov;
               }
            }
         } // while (!$file->eof())

         // now that each lines have been read & validated, create
         foreach($provData as $row => $prov){
            $timestamp = Tools::date2timestamp($prov['date']);

            $provId = CommandProvision::create($cmdid,
               $timestamp,
               $prov['type_id'],
               $prov['summary'],
               $prov['budget_days'],
               $prov['budget'],
               $prov['average_daily_rate'],
               $prov['is_checked'],
               $prov['currency']);

            $provData[$row]['provId'] = $provId;
            $provData[$row]['csvFileLine'] = $row;
         }

         $jsonData =  array(
            'statusMsg' => 'SUCCESS',
            'provData' => $provData,
         );
      } catch (Exception $e) {
         $jsonData =  array(
            'statusMsg' => 'ERROR could not upload file: '.$e->getMessage(),
         );
         $logger->error($e->getMessage());
         $logger->error("EXCEPTION importProvisionCSV: ".$e->getMessage());
         $logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
      }
      //$logger->error($jsonData);
      echo json_encode($jsonData);

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
      global $logger;

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
