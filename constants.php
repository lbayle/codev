<?php
  // The Variables in here can be customized to your needs

  include_once "config.class.php";



  $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";

  // CoDev


  // --- STATUS ---
  // mantis default statusNames = 10:new,20:feedback,30:acknowledged,40:confirmed,50:assigned,80:resolved,90:closed
  $statusNames = Config::getInstance()->getValue(Config::id_statusNames);

  $status_new       = array_search('new', $statusNames);
  $status_feedback  = array_search('feedback', $statusNames);
  $status_ack       = array_search('acknowledged', $statusNames);
  $status_openned   = array_search('openned', $statusNames);
  $status_resolved  = array_search('resolved', $statusNames);  // "assigned" ?
  $status_closed    = array_search('closed', $statusNames);

?>
