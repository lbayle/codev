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

error_reporting(0); // no logs displayed in page (page is a generated image)
date_default_timezone_set('Europe/Paris');

require_once('../path.inc.php');

# WARN: this avoids the display of some PHP errors...
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

date_default_timezone_set("Europe/Paris");

require_once('lib/dynamic_autoloader/ClassFileMapAutoloader.php');
$_autoloader = unserialize(file_get_contents(BASE_PATH."/classmap.ser"));
$_autoloader->registerAutoload();

# WARN: order of these includes is important.
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("pie_graph");
   $logger->info("LOG activated !");

   // test
   #echo "configure LOG ".Logger::getConfigurationFile()."</br>";
   #echo "configure LOG ".Logger::getConfigurationClass()."</br>";
   #echo "configure LOG header exists: ".$logger->exists("header")."</br>";
}

/**
 * handle uncaught exceptions
 * @param Exception $e
 */
function exception_handler(Exception $e) {
   global $logger;
   echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
   $logger->error("UNCAUGHT EXCEPTION : ".$e->getMessage());
   $logger->error("UNCAUGHT EXCEPTION stack-trace:\n".$e->getTraceAsString());
}
set_exception_handler('exception_handler');

// content="text/plain; charset=utf-8"
require_once ('lib/jpgraph/src/jpgraph.php');
require_once ('lib/jpgraph/src/jpgraph_pie.php');

$title   = isset($_GET['title']) ? $_GET['title'] : NULL;
$size    = isset($_GET['size']) ? $_GET['size'] : "300:200";
$colors  = isset($_GET['colors']) ? $_GET['colors'] : "";
$legends = isset($_GET['legends']) ? $_GET['legends'] : NULL;

if (isset($_GET['values'])) {
   $values = $_GET['values'];
   $data = explode(':', $values);
   $logger->debug("values = <$values>");
} else {
   $logger->error("no values !");
   $data = array(0,0);
}

$aSize = explode(':', $size);
$logger->debug("width = ".$aSize[0].", heigh = ".$aSize[1]);


$graph = new PieGraph($aSize[0],$aSize[1]);
#$graph->ClearTheme();

#$graph->SetShadow();

if (NULL != $title) {
   $graph->title->Set($title);
   $logger->debug("title = <$title>");
}


$p1 = new PiePlot($data);
$graph->Add($p1);


$p1->SetLabelType(PIE_VALUE_ADJPERCENTAGE);
$p1->ExplodeAll(5);
$p1->SetShadow();

if ("" != $colors) {
   $aColors = explode(':', $colors);
   $p1->SetSliceColors($aColors);
   #$p1->SetSliceColors(array('orange','green','blue'));
   $logger->debug(count($aColors)." colors: = <$colors>");
}

if (NULL != $legends) {
   #$legends = "April (%d):May (%d):June (%d)";
   $aLegends = explode(':', $legends);
   $logger->debug(count($aLegends)." legends: = <$legends>");
   $p1->SetLegends($aLegends);
   $graph->legend->Pos(0.2,0.1,'right','top');
   $graph->legend->SetColumns(1);
}

// Enable and set policy for guide-lines. Make labels line up vertically
#$p1->SetLabelPos(0.6);
$p1->SetGuideLines(true,false);
$p1->SetGuideLinesAdjust(1.5);

// Setup the labels
$p1->SetLabelType(PIE_VALUE_ADJPER);
$p1->value->Show();
#$p1->value->SetFormat('%2.1f%%');
$p1->value->SetFormat('%2d%%');

// move piePlot to the Left of the image
$p1->SetCenter(0.2,0.5);

#$p1->SetSize(0.25);

$graph->Stroke();
?>
