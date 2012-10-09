<?php
require('../include/session.inc.php');

require ('../path.inc.php');

function execQuery($query) {
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      echo "<span style='color:red'>ERROR: Query FAILED $query</span>";
      exit;
   }
   return $result;
}


function removeCustomMenuItem($name) {

   // get current mantis custom menu entries
   $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
   $result = execQuery($query);

   $serialized = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

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
      echo "no custom menu entries found<br>";
   }


}


/**
 * Add a new entry in MantisBT menu (main_menu_custom_options)
 *
 * ex: addCustomMenuItem('CodevTT', '../codev/index.php')
 *
 * @param string $name
 * @param string $url
 * @return string
 */
function addCustomMenuItem($name, $url) {
   $pos = '10'; // invariant

   // get current mantis custom menu entries
   $query = "SELECT value FROM `mantis_config_table` WHERE config_id = 'main_menu_custom_options'";
   $result = execQuery($query);


   $serialized = (0 != mysql_num_rows($result)) ? mysql_result($result, 0) : NULL;

   // add entry
   if ((NULL != $serialized) && ("" != $serialized)) {
      $menuItems = unserialize($serialized);
   } else {
      $menuItems = array();
   }

   $menuItems[] = array($name, $pos, $url);
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

   return $newSerialized;
}


# ============= MAIN =============

removeCustomMenuItem('CodevTT');
addCustomMenuItem('CodevTT', '../codevtt/index.php');


?>