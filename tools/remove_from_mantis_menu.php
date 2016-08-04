<?php
/*
  This file is part of CodevTT.

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

include_once('../include/session.inc.php');
require('../path.inc.php');


function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query<br/>" . SqlWrapper::getInstance()->sql_error() . "</span>";
      exit;
   }
   return $result;
}

/**
 * Mantis 1.2 only !!
 * remove existing entries from mantis menu
 *
 * @param string $name 'CodevTT'
 */
function removeCustomMenuItem($name) {

   // get current mantis custom menu entries
   $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
   $result = execQuery($query);

   $serialized = (0 != SqlWrapper::getInstance()->mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

   // add entry
   if ((!is_null($serialized)) && ("" != $serialized)) {

      $menuItems = unserialize($serialized);

      foreach($menuItems as $key => $item) {
         if (in_array($name, $item)) {
            echo "remove key=$key<br>";
            unset($menuItems[$key]);
         }
      }

      $newSerialized = serialize($menuItems);

      // update mantis menu
      if (NULL != $serialized) {
         $query = "UPDATE `mantis_config_table` SET value = '$newSerialized' " .
            "WHERE config_id = 'main_menu_custom_options'";
      } else {
         $query = "INSERT INTO `mantis_config_table` (`config_id`, `value`, `type`, `access_reqd`) " .
            "VALUES ('main_menu_custom_options', '$newSerialized', '3', '90');";
      }
      $result = execQuery($query);


   } else {
      // echo "no custom menu entries found<br>";
   }

}

// ========== MAIN ===========

if (!Tools::isMantisV1_3()) {
   echo "Remove 'CodevTT' from Mantis main menu<br>";
   removeCustomMenuItem('CodevTT');
} else {
   echo "Remove 'main_menu_custom_options' from Mantis DB<br>";
   $query = "DELETE FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
   $result = execQuery($query);
}