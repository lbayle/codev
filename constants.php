<?php
   // This file is part of CoDev-Timetracking.

  // - The Variables in here can be customized to your needs
  // - This file has been generated at install_step2 on the <date>

  include_once "config.class.php";

  $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";

  // --- STATUS ---
  $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

  $status_new       = array_search('new', $statusNames);
  $status_feedback  = array_search('feedback', $statusNames);
  $status_ack       = array_search('acknowledged', $statusNames);
  $status_confirmed = array_search('confirmed', $statusNames);
  $status_openned   = array_search('assigned', $statusNames);
  $status_resolved  = array_search('resolved', $statusNames);
  $status_closed    = array_search('closed', $statusNames);

?>
