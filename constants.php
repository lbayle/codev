<?php

  // MANTIS CoDev Reports/TimeTracking
  
  // constants
  // LoB 17 May 2010

   include_once "config.class.php"; 

   $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";
   

  // --- STATUS ---
  // REM: these vars are convenience access to Config::statusNames
  $statusNames = Config::getInstance()->getValue("statusNames");
  
  $status_new       = array_search('new', $statusNames);
  $status_feedback  = array_search('feedback', $statusNames);
  $status_ack       = array_search('acknowledged', $statusNames);
  $status_analyzed  = array_search('analyzed', $statusNames);
  $status_accepted  = array_search('accepted', $statusNames);  // CoDev FDJ custom, defined in Mantis
  $status_openned   = array_search('openned', $statusNames);
  $status_deferred  = array_search('deferred', $statusNames);
  $status_resolved  = array_search('resolved', $statusNames);
  $status_delivered = array_search('delivered', $statusNames);  // CoDev FDJ custom, defined in Mantis
  $status_closed    = array_search('closed', $statusNames);
  
  // CoDev FDJ custom (not defined in Mantis)
  $status_feedback_ATOS = 21;
  $status_feedback_FDJ  = 22;
  $statusNames[$status_feedback_ATOS] = "feedback_ATOS";
  $statusNames[$status_feedback_FDJ]  = "feedback_FDJ";
  
  


  
  
  
  
  
  
  
  
  
  
  
  
  
  
    
  
  
  // ============== MOVED TO codev_config_table ================
  
  $admin_teamid = 3; // users allowed to do CoDev administration
  $job_support = 23; // jobid in codev_job_table corresponding to the 'Support' job (used to compute drifts)                         
  
  // this is the custom field added to mantis issues for TimeTracking
  $tcCustomField           = 1; // in mantis_custom_field_table
  $estimEffortCustomField  = 3; // in mantis_custom_field_table BI
  $remainingCustomField    = 4; // in mantis_custom_field_table RAE
  $deadLineCustomField     = 8; // in mantis_custom_field_table
  $addEffortCustomField    = 10; // in mantis_custom_field_table BS
  $deliveryIdCustomField   = 9; // in mantis_custom_field_table FDL (id of the associated Delivery Issue)
  $deliveryDateCustomField = 11; // in mantis_custom_field_table
  
  
  // ---
  // toughness indicator to compute "Productivity Rate ETA"
  $ETA_balance = array(10 => 1,   // none 
                       20 => 1,   // < 1 day
                       30 => 3,   // 2-3 days
                       40 => 5,   // < 1 week
                       50 => 10,  // < 15 days
                       60 => 15); // > 15 days
  
  //$eta_balance_string = '10:1,20:1,30:3,40:5,50:10,60:15';
  //$ETA_balance = doubleExplode(':', ',', $eta_balance_string);
  
  // ---
  $astreintesTaskList = array(526); // fiches de SuiviOp:Inactivite qui sont des astreintes
                       
  
  // --- Mantis Values ---
  // Unfortunately the following values are not in Mantis database, you'll have to manualy copy those
  // definition if you customized them.
  
  // Values copied from: mantis/config_inc.php
  $g_eta_enum_string    = '10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days';
  $g_status_enum_string = "10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed";
  
  $statusNames     = doubleExplode(':', ',', $g_status_enum_string);
  $ETA_names       = doubleExplode(':', ',', $g_eta_enum_string);
  
  // Values copied from:  mantis/lang/strings_english.txt
  #$s_priority_enum_string   = '10:none,20:low,30:normal,40:high,50:urgent,60:immediate';
  #$s_resolution_enum_string = '10:open,20:fixed,30:reopened,40:unable to reproduce,50:not fixable,60:duplicate,70:no change required,80:suspended,90:won\'t fix';
  $s_priority_enum_string = '10:aucune,20:basse,30:normale,40:elevee,50:urgente,60:immediate';
  $s_resolution_enum_string = '10:ouvert,20:resolu,30:rouvert,40:impossible a reproduire,50:impossible a corriger,60:doublon,70:pas un bogue,80:suspendu,90:ne sera pas resolu';
  
  $priorityNames   = doubleExplode(':', ',', $s_priority_enum_string);
  $resolutionNames = doubleExplode(':', ',', $s_resolution_enum_string);
  // ---
  $FDJ_teamid = 21;  // all FDJ users : used for reports (to diff $status_feedback_ATOS from  $status_feedback_FDJ)                     
  
  // ---
  // the projects listed here will be excluded from PeriodStatsReport 
  $periodStatsExcludedProjectList = array($FDLProject);
  $FDLProject       = 18;
  
  $defaultSideTaskProject = 11; // "SuiviOp" in table mantis_project_table
  
   $codevReportsDir = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";
  
  
  
?>