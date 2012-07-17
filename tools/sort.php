<?php
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

require('include/super_header.inc.php');

include_once('classes/project_cache.class.php');

require_once('tools.php');

$issues = ProjectCache::getInstance()->getProject(14)->getIssues();

echo "Not sorted issued<br>";

foreach($issues as $issue) {
   echo $issue->bugId."-";
}

echo "<br><br>";

echo "qsorted issued<br>";

$a = arrayCopy($issues);

$start = microtime(true);
Tools::qsort($a);
$end = microtime(true);

foreach($a as $issue) {
   echo $issue->bugId."-";
}

echo "<br>";

echo "Time : ".round(($end-$start)*1000)." ms";

echo "<br><br>";

echo "usorted issued<br>";

$b = arrayCopy($issues);

$start = microtime(true);
Tools::usort($b);
$end = microtime(true);

foreach($b as $issue) {
   echo $issue->bugId."-";
}

echo "<br>";

echo "Time : ".round(($end-$start)*1000)." ms";

echo "<br><br>";

for($i = 0; $i < count($issues) ; $i++) {
   if($a[$i]->bugId != $b[$i]->bugId) {
      echo "diff find<br>";
      break;
   }
}

echo "done";

function arrayCopy( array $array ) {
   $result = array();
   foreach( $array as $key => $val ) {
      if( is_array( $val ) ) {
         $result[$key] = arrayCopy( $val );
      } elseif ( is_object( $val ) ) {
         $result[$key] = clone $val;
      } else {
         $result[$key] = $val;
      }
   }
   return $result;
}

?>
