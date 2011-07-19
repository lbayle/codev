
<?php /*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Foobar is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>

<?php

  // The Variables in here can be customized to your needs
  
  // LoB 17 May 2010

  include_once "config.class.php"; 

   
   
  $mantisURL="http://".$_SERVER['HTTP_HOST']."/mantis";
   
  
  // --- RESOLUTION ---
  // see mantis config file: core/constant_inc.php
  //$resolution_open              = 10;
  $resolution_fixed             = 20;
  $resolution_reopened          = 30;
  //$resolution_unableToDuplicate = 40;
  //$resolution_notFixable        = 50;
  //$resolution_duplicate         = 60;
  //$resolution_notABug           = 70;
  //$resolution_suspended         = 80;
  //$resolution_wontFix           = 90;
  
  // --- STATUS ---
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
  $status_feedback_ATOS = array_search('feedback_ATOS', $statusNames);;
  $status_feedback_FDJ  = array_search('feedback_FDJ', $statusNames);;
  
  
?>
