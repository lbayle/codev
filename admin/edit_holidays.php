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

require('include/super_header.inc.php');

require('include/display.inc.php');

include_once('classes/user_cache.class.php');
include_once('classes/holidays.class.php');

/**
 * Get holidays
 * @return mixed[int] The holidays
 */
function getHolidays() {
   $query = "SELECT * ".
      "FROM `codev_holidays_table` ".
      "ORDER BY date DESC";
   $result = SqlWrapper::getInstance()->sql_query($query);
   if (!$result) {
      return NULL;
   }

   $holidays = array();
   while($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
      $holidays[$row->id] = array(
         "date" => formatDate("%d %b %Y (%a)", $row->date),
         "type" => $row->type,
         "desc" => $row->description,
         "color" => $row->color
      );
   }

   return $holidays;
}

// ========== MAIN ===========
$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', 'CoDev Administration : Holidays');

// Admins only
if(isset($_SESSION['userid'])) {
   $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
   if ($session_user->isTeamMember($admin_teamid)) {
      $smartyHelper->assign('defaultColor', Holidays::$defaultColor);

      if (isset($_POST['hol_color'])) {
         $formatedDate = getSecurePOSTStringValue('date');
         $timestamp = date2timestamp($formatedDate);
         $hol_desc = getSecurePOSTStringValue('hol_desc');
         $hol_color = getSecurePOSTStringValue('hol_color');

         // save to DB
         $query = "INSERT INTO `codev_holidays_table` (`date`, `description`, `color`) VALUES ('".$timestamp."','".$hol_desc."','".$hol_color."');";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            $smartyHelper->assign('error', "Couldn't add the holiday");
         }
      } elseif (isset($_POST['hol_id'])) {
         $hol_id = getSecurePOSTIntValue('hol_id');

         $query = "DELETE FROM `codev_holidays_table` WHERE id=".$hol_id.';';
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            $smartyHelper->assign('error', "Couldn't remove the holiday");
         }
      }

      $smartyHelper->assign('holidays', getHolidays());
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
