<?php 
require_once "../artichow/LinePlot.class.php";


# WARNING: Never ever put an 'echo' in this file, the graph won't be displayed !


function plotOne($values, $color) {

   $plot = new LinePlot($values);
   $plot->setPadding(10, NULL, NULL, NULL); // Change component padding
   $plot->setSpace(5, 5, 5, 5); // Change component space
   $plot->setBackgroundColor(new Color(230, 230, 230)); // Set a background color
   $plot->setColor($color);

   $plot->grid->setBackgroundColor(new Color(235, 235, 180, 60)); // Change grid background color
   $plot->grid->hide(TRUE);

   $plot->label->set($values);
   $plot->label->setFormat('%d');
   $plot->label->setBackgroundColor(new Color(240, 240, 240, 15));
   $plot->label->setPadding(5, 3, 1, 1);

   $plot->yAxis->label->hide(TRUE);

   $plot->xAxis->label->setInterval(2);
   $plot->xAxis->label->move(0, 5);
   $plot->xAxis->label->setBackgroundColor(new Color(240, 240, 240, 15));
   #$plot->xAxis->label->setPadding(5, 3, 1, 1);
   $plot->xAxis->label->setAngle(0);

   return $plot;
}



# =================================================

$graph = new Graph(600, 300);
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

$group->axis->left->label->hide(TRUE);
$group->axis->bottom->label->hide(TRUE);

$strY = isset($_GET['y']) ? $_GET['y'] : NULL;
$y = explode(':', $strY);


// --------------------------------
$legend1 = isset($_GET['leg1']) ? $_GET['leg1'] : "unknown";
$strVal1 = isset($_GET['x1']) ? $_GET['x1'] : array();
$val1 = explode(':', $strVal1);
$color1 = new Color(255, 0, 0, 15);
$plot1 = plotOne($val1, $color1);

$group->add($plot1);
$group->legend->add($plot1, $legend1, Legend::LINE);

// --------------------------------
$legend2 = isset($_GET['leg2']) ? $_GET['leg2'] : NULL;
$strVal2 = isset($_GET['x2']) ? $_GET['x2'] : array();

if (isset($legend2)) {
   $val2 = explode(':', $strVal2);
   $color2 = new Color(0, 255, 0, 0);
   $plot2 = plotOne($val2, $color2);

   $group->add($plot2);
   $group->legend->add($plot2, $legend2, Legend::LINE);
}

// --------------------------------
$legend3 = isset($_GET['leg3']) ? $_GET['leg3'] : NULL;
$strVal3 = isset($_GET['x3']) ? $_GET['x3'] : array();

if (isset($legend3)) {
   $val3 = explode(':', $strVal3);
   $color3 = new Color(0, 255, 0, 0);
   $plot3 = plotOne($val3, $color3);

   $group->add($plot3);
   $group->legend->add($plot3, $legend3, Legend::LINE);
}

// ------------------------------

$graph->add($group);
$graph->draw();
?>