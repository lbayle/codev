<?php

# creates a PNG file containing a barr with some text in it.

# call with:
# <img src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=20&width=200&text=345&color=red'/>"; 
# <a href='./reports/issue_info.php?bugid=225'><img title='$formatedTitle' src='".getServerRootURL()."/graphs/scheduledtask.png.php?height=20&width=200&text=225&color=green' /></a>
header("Content-type: image/png");
$string = $_GET['text'];
$height = $_GET['height'];
$width  = $_GET['width'];
$color  = $_GET['color'];

$border = 2;
$font = 4;

// Create the image
$im = imagecreatetruecolor($width, $height);

$white  = imagecolorallocate($im, 255, 255, 255);
$grey   = imagecolorallocate($im, 128, 128, 128);
$black  = imagecolorallocate($im, 0, 0, 0);
$green  = imagecolorallocate($im, 128, 255, 159);
$red    = imagecolorallocate($im, 255, 183, 183);

if ("red" == $color) {
   $border_color = $red;
} else if ("green" == $color) {
   $border_color = $green;
} else if ("grey" == $color) {
   $border_color = $grey;
} else {
   $border_color = $black;
}

// image color
imagefilledrectangle($im, 0, 0, $width, $height, $border_color);
imagefilledrectangle($im, $border, $border, imagesx($im)-$border-1, imagesy($im)-$border-1, $white);

/*
imagesy($im)-$border-1, $red2);
imagefilledrectangle($im, $border, (2*$border),
imagesx($im)-$border-1, imagesy($im)-(2*$border)-1, $red3);
imagefilledrectangle($im, $border, (3*$border),
imagesx($im)-$border-1, imagesy($im)-(3*$border)-1, $red4);
imagefilledrectangle($im, $border, (4*$border),
imagesx($im)-$border-1, imagesy($im)-(4*$border)-1, $white);
*/
// text size
while ((imagefontwidth($font) * strlen($string) > ($width -(2*$border))) && ($font >= 1)) {
 $font -= 1;
}

// add text
if (imagefontwidth($font) * strlen($string) <= $width) {
 $px     = (imagesx($im) - imagefontwidth($font) * strlen($string)) / 2;
 $py     = (imagesy($im) - imagefontheight($font)) / 2;
 imagestring($im, $font, $px, $py, $string, $black);
}

imagepng($im);
imagedestroy($im);

?>