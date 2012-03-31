<?php
header("Content-type: image/png");

# creates a PNG file containing a barr with some text in it.
# call with:
# <img src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=20&width=200&text=345&color=red'/>";
# <a href='./reports/issue_info.php?bugid=225'><img title='$formatedTitle' src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=20&width=200&text=225&color=green' /></a>

/*
    This file is part of CoDev-Timetracking.

    CoDev-Timetracking is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    CoDev-Timetracking is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once '../path.inc.php';
require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
   Logger::configure(dirname(__FILE__).'/../log4php.xml');
   $logger = Logger::getLogger("scheduledtask.png");
   $logger->trace("LOG activated !");
}

include_once "../colors.php";

// ----------------------------
function gradiant($img,$color1,$color2)
{
        $size = imagesy($img);
        $sizeX = imagesx($img);

        $diffs = array(
                (($color2[0]-$color1[0])/($size/2)),
                (($color2[1]-$color1[1])/($size/2)),
                (($color2[2]-$color1[2])/($size/2))
        );

        for($i=0;$i<$size/2;$i++)
        {
                $r = $color1[0]+($diffs[0]*$i);
                $g = $color1[1]+($diffs[1]*$i);
                $b = $color1[2]+($diffs[2]*$i);
                $color = imagecolorallocate($img,$r,$g,$b);
                imagefilledrectangle($img, $i, $i, $sizeX-$i, $size-$i, $color);
        }
        return $img;
}


function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{
    /* this way it works well only for orthogonal lines
    imagesetthickness($image, $thick);
    return imageline($image, $x1, $y1, $x2, $y2, $color);
    */
    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
        round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
        round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
        round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}


// ================ MAIN =================

$string = isset($_GET['text']) ? $_GET['text'] : NULL;
$height = $_GET['height'];
$width  = $_GET['width'];
$color  = $_GET['color'];
$strike = isset($_GET['strike']) ? true : false; // barrer le texte 


$font = 4;

// Create the image
$im = imagecreatetruecolor($width, $height);

$grey      = $planning_grey;   # array(210, 210, 210);
$black     = $planning_black;  # array(0, 0, 0);
$green     = $planning_green;  # array(128, 255, 159);
$red       = $planning_red;    # array(255, 183, 183);
$orange    = $planning_orange; # array(255, 209, 84);
$blue      = $planning_blue;   # array(204, 218, 255);
$textColor = imagecolorallocate($im, 0, 0, 0);

if ("red" == $color) {
   $border_color = $red;
} else if ("green" == $color) {
   $border_color = $green;
} else if ("grey" == $color) {
   $border_color = $grey;
} else if ("orange" == $color) {
   $border_color = $orange;
} else if ("black" == $color) {
   $border_color = $black;
} else if ("blue" == $color) {
   $border_color = $blue;
} else {
   $border_color = $black;
}

// image color
$im = gradiant($im, $border_color, array(255,255,255));
$strike_color = html2rgb("9E9E9E");

// text size
while ((imagefontwidth($font) * strlen($string) > ($width)) && ($font > 1)) {
 $font -= 1;
}

if (true == $strike) {
   $logger->debug("imageline($im, 0, 0, $width, $height, ($strike_color[0], $strike_color[1], $strike_color[2]) )");

   $col = imagecolorallocate ( $im , $strike_color[0], $strike_color[1], $strike_color[2] );
   #$col = imagecolorallocate ( $im , $border_color[0], $border_color[1], $border_color[2] );

   imagelinethick($im, 0, $height, $width, 0, $col, $thick = 1);
   #imagelinethick($im, 0, 0, $width, $height, $col, $thick = 2);
}

// add text
if ($string) {
   if (imagefontwidth($font) * strlen($string) <= $width) {
      $px     = (imagesx($im) - imagefontwidth($font) * strlen($string)) / 2;
      $py     = (imagesy($im) - imagefontheight($font)) / 2;
      imagestring($im, $font, $px, $py, $string, $textColor);
   }
}




imagepng($im);
imagedestroy($im);

?>
