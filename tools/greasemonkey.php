<?php
require('../include/session.inc.php');

/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../path.inc.php');

function createGreasemonkeyFile() {

   //read the source file
   $str = implode("\n", file(Install::FILENAME_GREASEMONKEY_SAMPLE));

   //replace tags
   $str = str_replace('@TAG_MANTIS_URL@', Constants::$mantisURL, $str);
   $str = str_replace('@TAG_CODEVTT_URL@', Constants::$codevURL, $str);

   // write dest file
   $fp = fopen(Install::FILENAME_GREASEMONKEY, 'w');
   if (FALSE == $fp) {
      return "ERROR: creating file " . Install::FILENAME_GREASEMONKEY;
   }
   if (FALSE == fwrite($fp, $str, strlen($str))) {
      fclose($fp);
      return "ERROR: could not write to file " . Install::FILENAME_GREASEMONKEY;
   }
   fclose($fp);
   return NULL;
}


   echo "Create Greasemonkey file<br/>";
   $errStr = createGreasemonkeyFile();
   if (NULL != $errStr) {
      echo "<span class='error_font'>".$errStr."</span><br/>";
   } else {
      $url = Constants::$codevURL.'/mantis_monkey.user.js';
      echo "load script with: <a href=\"$url\">$url</a>";
   }

?>
