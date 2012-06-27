<?php

include_once('../include/session.inc.php');
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

require '../path.inc.php';

require('super_header.inc.php');

$logger = Logger::getLogger("import_issues_ajax");



$column = isset($_GET['column']) ? $_GET['column'] : 'unknown_column';



if ('command' == $column) {

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'Command 1';
   $array['2'] = 'Command 2';
   $array['3'] = 'Command 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   print json_encode($array);
}

if ('category' == $column) {

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'category 1';
   $array['2'] = 'category 2';
   $array['3'] = 'category 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   print json_encode($array);
}

if ('targetVersion' == $column) {

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'targetVersion 1';
   $array['2'] = 'targetVersion 2';
   $array['3'] = 'targetVersion 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   print json_encode($array);
}

if ('userName' == $column) {

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'userName 1';
   $array['2'] = 'userName 2';
   $array['3'] = 'userName 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   print json_encode($array);
}





$logger->error("called import_issues_ajax.php");

?>