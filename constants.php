<?php

  // MANTIS CoDev Reports/TimeTracking
  
  // constants
  // LoB 17 May 2010

   include_once "tools.php";


   $codevVersion = "v0.99.8 (25 Mar 2011)";

   $codevVersionHistory = array("v0.99.0" => "(09 Sept 2010) - team management complete",
                                "v0.99.1" => "(28 Sept 2010) - jobs management",
                                "v0.99.2" => "(08 Dec  2010) - Project Management",
                                "v0.99.3" => "(03 Jan  2011) - fix new year problems",
                                "v0.99.4" => "(13 Jan  2011) - ConsistencyCheck",
                                "v0.99.5" => "(21 Jan  2011) - Update directory structure & Apache config",
                                "v0.99.6" => "(16 Feb  2011) - i18n (internationalization)",
                                "v0.99.7" => "(25 Feb  2011) - Graph & Statistics",
                                "v0.99.8" => "(25 Mar  2011) - Add Job and specificities for 'support' + createTeam enhancements"
                                );
   
   $codevReportsDir = "\\\\172.24.209.4\Share\FDJ\Codev_Reports";
      
   $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";
   
	// Mantis DB infomation.
	$db_mantis_host		=	'localhost';
	$db_mantis_user		=	'codev';
	$db_mantis_pass		=	'';
	$db_mantis_database	=	'bugtracker';

  // mantis defs

  // --- Mantis Values ---
  // Unfortunately the following values are not in Mantis database, you'll have to manualy copy those
  // definition if you customized them.
  
  // Values copied from: mantis/config_inc.php
  $g_status_enum_string = "10:new,20:feedback,30:acknowledged,40:analyzed,45:accepted,50:openned,55:deferred,80:resolved,85:delivered,90:closed";
  $g_eta_enum_string    = '10:none,20:< 1 day,30:2-3 days,40:<1 week,50:< 15 days,60:> 15 days';
  
  // Values copied from:  mantis/lang/strings_english.txt
  #$s_priority_enum_string   = '10:none,20:low,30:normal,40:high,50:urgent,60:immediate';
  #$s_resolution_enum_string = '10:open,20:fixed,30:reopened,40:unable to reproduce,50:not fixable,60:duplicate,70:no change required,80:suspended,90:won\'t fix';
  $s_priority_enum_string = '10:aucune,20:basse,30:normale,40:elevee,50:urgente,60:immediate';
  $s_resolution_enum_string = '10:ouvert,20:resolu,30:rouvert,40:impossible a reproduire,50:impossible a corriger,60:doublon,70:pas un bogue,80:suspendu,90:ne sera pas resolu';
  
  $statusNames     = doubleExplode(':', ',', $g_status_enum_string);
  $ETA_names       = doubleExplode(':', ',', $g_eta_enum_string);
  $priorityNames   = doubleExplode(':', ',', $s_priority_enum_string);
  $resolutionNames = doubleExplode(':', ',', $s_resolution_enum_string);
  
  
  // --- STATUS ---
  // REM: see $g_status_enum_string defined in previous section
  $status_new       = 10;
  $status_feedback  = 20;
  $status_ack       = 30;
  $status_analyzed  = 40;
  $status_accepted  = 45;  // CoDev FDJ specific, defined in Mantis
  $status_openned   = 50;
  $status_deferred  = 55;
  $status_resolved  = 80;
  $status_delivered = 85;  // CoDev FDJ specific, defined in Mantis
  $status_closed    = 90;
  
  // CoDev FDJ specificities (not defined in Mantis)
  $status_feedback_ATOS = 21;
  $status_feedback_FDJ  = 22;
  

  // CoDev FDJ specificities (not defined in Mantis)
  $statusNames[$status_feedback_ATOS] = "feedback_ATOS";
  $statusNames[$status_feedback_FDJ]  = "feedback_FDJ";
  

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
  // il peut y avoir plusieurs observer
  // il n'y a qu'un seul teamLeader
  // il peut y avoir plusieurs managers, mais ils ne peuvent imputer que sur des SideTasks 
  // un observer ne fait jamais partie de l'equipe, il n'a acces qu'a des donnees impersonnelles
  
  $accessLevel_dev      = 10;    // in table codev_team_user_table
  $accessLevel_observer = 20;    // in table codev_team_user_table
  //$accessLevel_teamleader = 25;    // REM: NOT USED FOR NOW !!
  $accessLevel_manager  = 30;    // in table codev_team_user_table 
  $access_level_names = array($accessLevel_dev      => "Developper", // can modify, can NOT view stats
                              $accessLevel_observer => "Observer",  // can NOT modify, can view stats  
                              //$accessLevel_teamleader => "TeamLeader",  // REM: NOT USED FOR NOW !! can modify, can view stats, can work on projects ? , included in stats ?   
                              $accessLevel_manager  => "Manager");  // can modify, can view stats, can only work on sideTasksProjects, resource NOT in statistics
                              
  // this is the custom field added to mantis issues for TimeTracking
  $tcCustomField           = 1; // in mantis_custom_field_table
  $estimEffortCustomField  = 3; // in mantis_custom_field_table BI
  $remainingCustomField    = 4; // in mantis_custom_field_table RAE
  $deadLineCustomField     = 8; // in mantis_custom_field_table
  $addEffortCustomField    = 10; // in mantis_custom_field_table BS
  $deliveryIdCustomField   = 9; // in mantis_custom_field_table FDL (id of the associated Delivery Issue)
  $deliveryDateCustomField = 11; // in mantis_custom_field_table
  
  // ---
  $workingProjectType   = 0;     // normal projects are type 0
  $sideTaskProjectType  = 1;     // SuiviOp must be type 1
  $noCommonProjectType  = 2;     // projects which jave only assignedJobs (no common jobs) REM: these projects are not considered as sideTaskProjects

  $projectType_names = array($workingProjectType => "Project",
                             $noCommonProjectType => "Project (no common jobs)",
                             $sideTaskProjectType => "SideTasks");
  
  // ---
  $commonJobType   = 0;     // jobs common to all projects are type 0
  $assignedJobType = 1;     // jobs specific to one or more projects are type 1
  $jobType_names = array($commonJobType => "Common",
                         $assignedJobType => "Assigned");

  $job_support = 23; // jobid in codev_job_table corresponding to the 'Support' job (used to compute drifts)                         
  
  $defaultSideTaskProject = 11; // "SuiviOp" in table mantis_project_table
  $FDLProject       = 18;

  
  
  // ---
  $admin_teamid = 3; // users allowed to do CoDev administration
  $FDJ_teamid = 21;  // all FDJ users : used for reports (to diff $status_feedback_ATOS from  $status_feedback_FDJ)                     
  
  // ---
  // the projects listed here will be excluded from PeriodStatsReport 
  $periodStatsExcludedProjectList = array($FDLProject);
  
  
  // ---
  // codev_config_table types
  $configType_int      = 1;
  $configType_string   = 2;
  $configType_keyValue = 3;
  
  
?>