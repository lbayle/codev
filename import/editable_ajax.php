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


$column = isset($_GET['column']) ? $_GET['column'] : 'unknown_column';



if ('command' == $column) {

   $command_id = $_POST['value'];

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'Command 1';
   $array['2'] = 'Command 2';
   $array['3'] = 'Command 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   echo $array[$command_id];
}

if ('category' == $column) {

   $category_id = $_POST['value'];

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'category 1';
   $array['2'] = 'category 2';
   $array['3'] = 'category 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   echo $array[$category_id];
}

if ('targetVersion' == $column) {

   $targetVersion_id = $_POST['value'];

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'targetVersion 1';
   $array['2'] = 'targetVersion 2';
   $array['3'] = 'targetVersion 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   echo $array[$targetVersion_id];
}

if ('userName' == $column) {

   $user_id = $_POST['value'];

   // -- encode combobox (select) elements
   $array = array();
   $array['1'] = 'userName 1';
   $array['2'] = 'userName 2';
   $array['3'] = 'userName 3';

   $selected = isset($_GET['selected']) ? $_GET['selected'] : NULL;

   if (isset($_GET['selected'])) {
      $array['selected'] = $_GET['selected'];
   }
   echo $array[$user_id];
}

if ('mgrEffortEstim' == $column) {

   echo $_POST['value'];
}
if ('effortEstim' == $column) {

   echo $_POST['value'];
}

?>