<?php

  // MANTIS CoDev Reports/TimeTracking
  
  // constants
  // LoB 17 May 2010


#define("TAMERE", "tamere et mescouilles");
# echo TAMERE;


   $codevVersion = "v0.99.0 (09 Sept 2010)";

	// Mantis DB infomation.
	$db_mantis_host		=	'localhost';
	$db_mantis_user		=	'codev';
	$db_mantis_pass		=	'';
	$db_mantis_database	=	'bugtracker';

  // mantis defs
  define ("STATUS_NEW", 10);

  $status_new      = 10;
  $status_feedback = 20;
  $status_ack      = 30;
  $status_analyzed = 40;
  $status_accepted = 45;
  $status_openned  = 50;
  $status_deferred = 55;
  $status_resolved = 80;
  $status_delivered = 85;
  $status_closed   = 90;
  
  // FDJ specificities
  $status_feedback_ATOS = 21;
  $status_feedback_FDJ  = 22;
  
  // unfortunately the status names are not a table in Mantis:
  // REM: $g_status_enum_string in mantis/config_inc.php
  $statusNames = array($status_new      => "New",
                       $status_feedback => "Feedback",
                       $status_ack      => "Acknowledged",
                       $status_analyzed => "Analyzed",
                       $status_accepted => "Accepted",
                       $status_openned  => "Openned",
                       $status_deferred => "Deferred",
                       $status_resolved => "Resolved",
                       $status_delivered => "Delivered",
                       $status_closed   => "Closed");

  // FDJ specificities
  $statusNames[$status_feedback_ATOS] = "feedback_ATOS";
  $statusNames[$status_feedback_FDJ]  = "feedback_FDJ";
  
                     
                     
  // unfortunately the status names are not a table in Mantis:
  // REM: $g_eta_enum_string in mantis/config_inc.php
  $ETA_names = array(10 => "none", 
                     20 => "< 1 day",
                     30 => "2-3 days",
                     40 => "< 1 week",
                     50 => "< 15 days",
                     60 => "> 15 days");
  
                          
  // ponderation des fiches en fonction de la difficulte.
  $ETA_balance = array(10 => 1,   // none 
                       20 => 1,   // < 1 day
                       30 => 3,   // 2-3 days
                       40 => 5,   // < 1 week
                       50 => 10,  // < 15 days
                       60 => 15); // > 15 days
  
  
  // TODO DEPRECATED $IncidentProject doit etre trouve dans  'codev_team_project_table'
  $IncidentProject  = 11;  // "SuiviOp"        in table mantis_project_table
  $IncidentCategory = 19;  // "Incidents"      in table mantis_category_table

  $docCategory      = 16;  // "Capitalisation" in table mantis_category_table
  $toolsCategory    = 18;  // "Outillage"      in table mantis_category_table
  
  $vacationProject  = 11;  // "SuiviOp"        in table mantis_project_table
  $vacationCategory = 17;  // Inactivite
  
  $defaultSideTaskProject = 11; // "SuiviOp"
  $FDLProject       = 18; 

  // users allowed to do CoDev administration
  $admin_teamid = 3;
  
  // all FDJ users
  // used for reports (to diff $status_feedback_ATOS from  $status_feedback_FDJ)  
  $FDJ_teamid = 21;                     
  
  // timestamps of holidays (national, religious, etc.)
  // REM: adding RTTs is not a good idea, users may decide to work anyways and productionDaysForecast will be wrong.
  $globalHolidaysList = array("2010-01-01", // reveillon
                               "2010-05-01", // fete du travail
                               "2010-05-08", // victoire 1945
                               "2010-05-13", // ascension
                               #"2010-05-14", // RTT employeur
                               "2010-07-14", // 14 juillet   
                               "2010-11-01", // toussaint   
                               "2010-11-11", // armistice  
                               #"2010-11-12", // RTT employeur  
                               #"2010-12-24", // RTT employeur  
                               "2010-12-25", // noel
                               #"2010-12-31"  // RTT employeur  
  );
  
  
  // the projects listed here will be excluded from PeriodStatsReport 
  $periodStatsExcludedProjectList = array($FDLProject);
  
?>