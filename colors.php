<?php /*
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
*/ ?>
<?php

$planning_grey      = array(210, 210, 210);
$planning_black     = array(0, 0, 0);
$planning_green     = array(128, 255, 159);
$planning_red       = array(255, 183, 183);
$planning_orange    = array(255, 209, 84);
$planning_blue      = array(179, 199, 255);

/*
* Exemple d'utilisation de la fonction :
*  <?php print_r(html2rgb('B8B9B9')) ; ?>
*/
function html2rgb($color)
{
   if ($color[0] == '#') {
      $color = substr($color, 1);
   }
   if (strlen($color) == 6) {
      list($r, $g, $b) = array($color[0].$color[1],
                               $color[2].$color[3],
                               $color[4].$color[5]);
   } elseif (strlen($color) == 3) {
      list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1],   $color[2].$color[2]);
   } else {
      return false;
   }
   $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

   return array($r, $g, $b);
}

?>
