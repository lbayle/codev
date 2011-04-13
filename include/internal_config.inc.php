<?php
   #include_once "constants.php";
   #include_once "tools.php";


   $codevVersion = "v0.99.9 (11 Apr 2011)";

   $codevVersionHistory = array("v0.99.0" => "(09 Sept 2010) - team management complete",
                                "v0.99.1" => "(28 Sept 2010) - jobs management",
                                "v0.99.2" => "(08 Dec  2010) - Project Management",
                                "v0.99.3" => "(03 Jan  2011) - fix new year problems",
                                "v0.99.4" => "(13 Jan  2011) - ConsistencyCheck",
                                "v0.99.5" => "(21 Jan  2011) - Update directory structure & Apache config",
                                "v0.99.6" => "(16 Feb  2011) - i18n (internationalization)",
                                "v0.99.7" => "(25 Feb  2011) - Graph & Statistics",
                                "v0.99.8" => "(25 Mar  2011) - Add Job and specificities for 'support' + createTeam enhancements",
                                "v0.99.9" => "(11 Apr  2011) - Planning + enhance global performances"
                                );

                                
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

                                
?>