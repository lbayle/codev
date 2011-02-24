<?php 
require_once "../artichow/LinePlot.class.php";


# WARNING: Never ever put an 'echo' in this file, the graph won't be displayed !


function plotOne($values, $color) {

   $plot = new LinePlot($values);
   $plot->setPadding(10, NULL, NULL, NULL);             // Change component padding
   $plot->setSpace(5, 5, 5, 5);                         // Change component space
   $plot->setBackgroundColor(new Color(230, 230, 230)); // Set a background color
   $plot->setColor($color);

   $plot->grid->setBackgroundColor(new Color(235, 235, 180, 60)); // Change grid background color
   $plot->grid->hide(TRUE);

   if (isset($_GET["displayPointLabels"])) {
   	
   	$format = isset($_GET["pointFormat"]) ? $_GET["pointFormat"] : "%d";
   	
      # write value on each point
      $plot->label->set($values);
      $plot->label->setFormat($format);
      $plot->label->setBackgroundColor(new Color(240, 240, 240, 15));
      $plot->label->setPadding(5, 3, 1, 1);
   }

   #$plot->yAxis->label->hide(TRUE);
   #$plot->xAxis->label->hide(TRUE);
   
   return $plot;
}

/**
 * addPlot($myGroup, 'leg1', 'x1', new Color(0, 255, 0, 0))
 */
function addPlot($group, $legName, $valName, $color) {
   
	$legend3 = isset($_GET[$legName]) ? $_GET[$legName] : NULL;
   $strVal3 = isset($_GET[$valName]) ? $_GET[$valName] : array();

   if (isset($legend3)) {
      $val3 = explode(':', $strVal3);
      $plot3 = plotOne($val3, $color);

      $group->add($plot3);
      $group->legend->add($plot3, $legend3, Legend::LINE);
   }
}

# =================================================

$graph_width = isset($_GET['width']) ? $_GET['width'] : 600;
$graph_height = isset($_GET['height']) ? $_GET['height'] : 300;
$graph = new Graph($graph_width, $graph_height); // TODO
$graph->setAntiAliasing(TRUE);
$graph_title = isset($_GET['title']) ? $_GET['title'] : "unknown";
$graph->title->set($graph_title);



$group = new PlotGroup;
#$group->setXAxisZero(FALSE);
$group->setBackgroundColor(new Color(197, 180, 210, 80));

$group->setPadding(40, NULL, 50, NULL);

#$group->legend->setSpace(12);
$group->legend->setBackgroundColor(new Color(255, 255, 255));
$group->setPadding(NULL, 150, NULL, NULL);

if (isset($_GET['bottomLabel'])) {
   $strBottomLabel = $_GET['bottomLabel'];
   $bottomLabel = explode(':', $strBottomLabel);
   $group->axis->bottom->setLabelText($bottomLabel);
   
} else {
	$group->axis->bottom->label->hide(TRUE);	
   $group->axis->bottom->hideTicks(TRUE);  
}

if (isset($_GET['leftLabel'])) {
   $strLeftLabel = $_GET['leftLabel'];
   $leftLabel = explode(':', $strLeftLabel);
   $group->axis->left->setLabelText($leftLabel);
   
} else {
   #$group->axis->left->label->hide(TRUE);  
   #$group->axis->left->hideTicks(TRUE);  

   $group->axis->left->setLabelPrecision(0);
}

// --------------------------------


addPlot($group, 'leg1', 'x1', new Color(255, 0, 0, 15));
addPlot($group, 'leg2', 'x2', new Color(0, 255, 0, 0));
addPlot($group, 'leg3', 'x3', new Color(0, 0, 255, 0));


// ------------------------------

$graph->add($group);
$graph->draw();
?>