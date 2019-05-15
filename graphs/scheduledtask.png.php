<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

# creates a PNG file containing a barr with some text in it.
# call with:
# <img src='graphs/scheduledtask.png.php?height=20&width=200&text=345&color=red'/>";
# <a href='./reports/issue_info.php?bugid=225'><img title='$formatedTitle' src='".Tools::getServerRootURL()."/graphs/scheduledtask.png.php?height=20&width=200&text=225&color=green' /></a>

class ScheduledTaskView {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public function execute() {
      if(Tools::isConnectedUser()) {
         header("Content-type: image/png");

         $height = Tools::getSecureGETIntValue('height');
         $width = Tools::getSecureGETIntValue('width');
         $color = Tools::getSecureGETStringValue('color');
         $strike = isset($_GET['strike']) ? TRUE : FALSE; // barrer le texte
         $text = isset($_GET['text']) ? $_GET['text'] : NULL;

         // image color
         $img = self::createGradiantImage($width, $height, $color);

         if ($strike) {
            self::createStrikeImage($img, $width, $height);
         }

         if ($text) {
            self::createText($img, $width, $height, $text);
         }

         imagepng($img);
         imagedestroy($img);
      } else {
         Tools::sendUnauthorizedAccess();
      }
   }

   /**
    * @param int $width
    * @param int $height
    * @param string $color
    * @return resource
    */
   private static function createGradiantImage($width, $height, $color) {
      // Create the image
      $img = imagecreatetruecolor($width, $height);

      $color1 = self::getRGBColor($color);
      $color2 = array(255,255,255);

      $diffs = array(
         ($color2[0] - $color1[0]) / ($height / 2),
         ($color2[1] - $color1[1]) / ($height / 2),
         ($color2[2] - $color1[2]) / ($height / 2)
      );

      for($i = 0 ; $i < $height / 2 ; $i++) {
         $r = $color1[0] + ($diffs[0] * $i);
         $g = $color1[1] + ($diffs[1] * $i);
         $b = $color1[2] + ($diffs[2] * $i);
         $color = imagecolorallocate($img, $r, $g, $b);
         imagefilledrectangle($img, $i, $i, $width - $i, $height - $i, $color);
      }

      return $img;
   }

   /**
    * @param string $color
    * @return int[]
    */
   private static function getRGBColor($color) {
      if ("red" == $color) {
         $border_color = array(255, 183, 183);
      } else if ("green" == $color) {
         $border_color = array(128, 255, 159);
      } else if ("grey" == $color) {
         $border_color = array(210, 210, 210);
      } else if ("orange" == $color) {
         $border_color = array(255, 209, 84);
      } else if ("black" == $color) {
         $border_color = array(0, 0, 0);
      } else if ("blue" == $color) {
         $border_color = array(179, 199, 255);
      } else {
         $border_color = array(0, 0, 0);
      }
      return $border_color;
   }

   /**
    * @param resource $img
    * @param int $width
    * @param int $height
    * @return bool
    */
   private static function createStrikeImage($img, $width, $height) {
      global $logger;
      $strike_color = Tools::html2rgb("9E9E9E");
      if(self::$logger->isDebugEnabled()) {
         $logger->debug("imageline($img, 0, 0, $width, $height, ($strike_color[0], $strike_color[1], $strike_color[2]) )");
      }

      $color = imagecolorallocate($img, $strike_color[0], $strike_color[1], $strike_color[2]);

      return imageline($img, 0, $height, $width, 0, $color);
   }

   /**
    * @param resource $img
    * @param int $width
    * @param int $height
    * @param string $text
    * @return bool
    */
   private static function createText($img, $width, $height, $text) {
      $font = 4;

      // text size
      while ((imagefontwidth($font) * strlen($text) > ($width)) && ($font > 1)) {
         $font -= 1;
      }

      // add text
      if (imagefontwidth($font) * strlen($text) <= $width) {
         $px = ($width - imagefontwidth($font) * strlen($text)) / 2;
         $py = ($height - imagefontheight($font)) / 2;
         $textColor = imagecolorallocate($img, 0, 0, 0);
         return imagestring($img, $font, $px, $py, $text, $textColor);
      }

      return false;
   }

}

// ========== MAIN ===========
ScheduledTaskView::staticInit();
$view = new ScheduledTaskView();
$view->execute();

?>
