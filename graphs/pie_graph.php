<?php
# WARNING: Never ever put an 'echo' in this file, the graph won't be displayed !

/*
    This file is part of CoDevTT.

    CoDevTT is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/
?>
<?php

   # ================
   # NOTE: header.inc.php is not loaded, so some config must be done.
   error_reporting(0); // no logs displayed in page (page is a generated image)
   date_default_timezone_set('Europe/Paris');

   require_once '../path.inc.php';
   require_once('Logger.php');
   if (NULL == Logger::getConfigurationFile()) {
      Logger::configure(dirname(__FILE__).'/../log4php.xml');
      $logger = Logger::getLogger("pie_graph");
      $logger->trace("LOG activated !");
   }

   include_once "tools.php";
   #include_once "mysql_connect.inc.php";
   #include_once "internal_config.inc.php";
   #include_once "constants.php";
   # ================


// content="text/plain; charset=utf-8"
require_once ('jpgraph.php');
require_once ('jpgraph_pie.php');

$title = isset($_GET['title']) ? $_GET['title'] : NULL;
$colors = isset($_GET['colors']) ? $_GET['colors'] : NULL;

if (isset($_GET['values'])) {
   $values = $_GET['values'];
   $data = explode(':', $values);
   $logger->debug("values = <$values>");
} else {
   $logger->error("no values ! (using default values)");
   $data = array(3,1,6);
}


$graph = new PieGraph(300,200);
$graph->SetShadow();

if (NULL != $title) {
   $graph->title->Set($title);
}


$p1 = new PiePlot($data);
$p1->SetLabelType(PIE_VALUE_ADJPERCENTAGE);

if (NULL != $colors) {
   $logger->debug("colors = <$colors>");
   $aColors = explode(':', $colors);
   $p1->SetSliceColors(colors);
}

$graph->Add($p1);
$graph->Stroke();
?>