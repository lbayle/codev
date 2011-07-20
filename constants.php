<?php
   // This file is part of CoDev-Timetracking.

  // - The Variables in here can be customized to your needs
  // - This file has been generated at install_step2 on the <date>

  include_once "config.class.php";

  $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";

  $codevInstall_timestamp = 0;

  // --- RESOLUTION ---
  # WARNING: watch out for i18n ! the values depend on what you defined in codev_config_table.resolutionNames 
  $resolution_fixed    = array_search('fixed',    $resolutionNames);  # 20
  $resolution_reopened = array_search('reopened', $resolutionNames);  # 30;

  // --- STATUS ---
  $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

  $status_new          = array_search('new',          $statusNames);
  $status_feedback     = array_search('feedback',     $statusNames);
  $status_acknowledged = array_search('acknowledged', $statusNames);
  $status_confirmed    = array_search('confirmed',    $statusNames);
  $status_openned      = array_search('assigned',     $statusNames);
  $status_resolved     = array_search('resolved',     $statusNames);
  $status_closed       = array_search('closed',       $statusNames);

?>
