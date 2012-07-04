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

require('classes/smarty_helper.class.php');

include_once('classes/user_cache.class.php');
include_once('classes/holidays.class.php');

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
         if (!Holidays::save($timestamp, $hol_desc, $hol_color)) {
            $smartyHelper->assign('error', "Couldn't add the holiday");
         }
      } elseif (isset($_POST['hol_id'])) {
         $hol_id = getSecurePOSTIntValue('hol_id');
         if (!Holidays::delete($hol_id)) {
            $smartyHelper->assign('error', "Couldn't remove the holiday");
         }
      }

      $smartyHelper->assign('holidays', Holidays::getHolidays());
   }
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
