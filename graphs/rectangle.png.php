<?php
header("Content-type: image/png");

# creates a PNG file containing a barr with some text in it.
# call with:
# <img src='".getServerRootURL()."/graphs/rectangle.png.php?height=12&width=12&border&color=$color'/>

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
   $logger = Logger::getLogger("rectangle.png");
   $logger->trace("LOG activated !");
}

include_once "../colors.php";

// ================ MAIN =================

$string = isset($_GET['text']) ? $_GET['text'] : NULL;
$height = $_GET['height'];
$width  = $_GET['width'];
$color  = $_GET['color'];
$border = isset($_GET['border']) ? true : false;

# color
$logger->debug("color = <".$color.">");
$rgb = html2rgb($color);

$logger->debug("color = <".$color."> ($rgb[0], $rgb[1], $rgb[2])");

// Create the image
$im = imagecreatetruecolor($width, $height);

$bgColor = imagecolorallocate($im, $rgb[0], $rgb[1], $rgb[2]);

if (true == $border) {
   $borderColor = imagecolorallocate($im, 0, 0, 0); // black
   imagefilledrectangle($im, 0, 0, $width, $height, $borderColor);
   imagefilledrectangle($im, 1, 1, ($width-2), ($height-2), $bgColor);
} else {
   imagefilledrectangle($im, 0, 0, $width, $height, $bgColor);
}

// add text
if ($string) {

   // ajust text size
   $font = 4;
   while ((imagefontwidth($font) * strlen($string) > ($width)) && ($font > 1)) {
      $font -= 1;
   }

   $textColor = imagecolorallocate($im, 0, 0, 0);
   if (imagefontwidth($font) * strlen($string) <= $width) {
      $px     = (imagesx($im) - imagefontwidth($font) * strlen($string)) / 2;
      $py     = (imagesy($im) - imagefontheight($font)) / 2;
      imagestring($im, $font, $px, $py, $string, $textColor);
   }
}

imagepng($im);
imagedestroy($im);
?>
