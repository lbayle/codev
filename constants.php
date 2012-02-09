<?php
   // This file is part of CoDev-Timetracking.
  // - The Variables in here can be customized to your needs
  // - This file has been generated during install on Mon 05 Dec 2011 17:02

  include_once "config.class.php";

$codevInstall_timestamp = 1323039600;

  $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";

 $codevRootDir = dirname(__FILE__);

  // --- RESOLUTION ---
  # WARNING: watch out for i18n ! special chars may break PHP code and/or DB values
  # INFO: the values depend on what you defined in codev_config_table.resolutionNames
  $resolution_fixed    = array_search('fixed',    $resolutionNames);  # 20
  $resolution_reopened = array_search('reopened', $resolutionNames);  # 30;

  // --- STATUS ---
  # WARNING: CodevTT uses some global variables for status.
  #          Some of these variables are used in the code, so if they are not defined
  #          in the mantis workflow, they need to be created. The mandatory variables are:
  #           $status_new, $status_feedback, $status_acknowledged,
  #           $status_openned, $status_resolved, $status_closed

  $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

  $status_new       = array_search('new', $statusNames);
  $status_feedback       = array_search('feedback', $statusNames);
  $status_acknowledged       = array_search('acknowledged', $statusNames);
  $status_analyzed       = array_search('analyzed', $statusNames);
  $status_open       = array_search('open', $statusNames);
  $status_resolved       = array_search('resolved', $statusNames);
  #$status_delivered       = array_search('delivered', $statusNames);
  $status_closed       = array_search('closed', $statusNames);

# Custom Relationships
define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINED_BY',       2500 );
define( 'BUG_CUSTOM_RELATIONSHIP_CONSTRAINS',           2501 );

?>

