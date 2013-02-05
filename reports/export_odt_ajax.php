<?php

require('../include/session.inc.php');
require('../path.inc.php');

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


function getProjectCategories($projectid) {

   $categories = array();
   $categories[0] = array(
         'id' => 0,
         'name' => T_('(all)'),
         'selected' => true
      );
   if (0 != $projectid) {
      $project = ProjectCache::getInstance()->getProject($projectid);
      $categoryList = $project->getCategories();

      foreach($categoryList as $id => $name) {
         $categories[] = array(
            'id' => $id,
            'name' => $name,
            'selected' => false
         );
      }
   }
   return $categories;
}


// ----------- MAIN ------------

   
if(Tools::isConnectedUser() && (isset($_GET['action']))) {
   if(isset($_GET['action'])) {
      $smartyHelper = new SmartyHelper();

      if ($_GET['action'] == 'updateCategories') {

         $projectid = Tools::getSecureGETIntValue('projectid');
         $categories = getProjectCategories($projectid);

         $jsonResponse = Tools::array2json($categories);
         echo "$jsonResponse";

      } else {
         Tools::sendNotFoundAccess();
      }
   }
}
else {
   Tools::sendUnauthorizedAccess();
}

?>
