<?php 
require_once "../artichow/LinePlot.class.php";

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
$graph->title->set("Evolution des Derives");



$group = new PlotGroup;
#$group->setXAxisZero(FALSE);
$group->setBackgroundColor(new Color(197, 180, 210, 80));

$group->setPadding(40, NULL, 50, NULL);

#$group->legend->setSpace(12);
$group->legend->setBackgroundColor(new Color(255, 255, 255));
$group->setPadding(NULL, 150, NULL, NULL);

$group->axis->left->label->hide(TRUE);
$group->axis->bottom->label->hide(TRUE);


// --------------------------------
$val1 = array(1, 2, 5, 1, 3, 8, 7, 6, 2, -4);
$color1 = new Color(255, 0, 0, 15);
$plot1 = plotOne($val1, $color1);

$group->add($plot1);
$group->legend->add($plot1, "EffortEstim", Legend::LINE);

// --------------------------------
$val2 = array(3, 8, 7, 6, 2, -4, 0, 2, 8, 7);
$color2 = new Color(0, 255, 0, 0);
$plot2 = plotOne($val2, $color2);

$group->add($plot2);
$group->legend->add($plot2, "ETA", Legend::LINE);


// ------------------------------

$graph->add($group);
$graph->draw();
?>