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
  
  $FDJ_teamid = Config::getInstance()->getValue("FDJ_teamid");  // all FDJ users : used for reports (to diff $status_feedback_ATOS from  $status_feedback_FDJ)                     
  
?>
