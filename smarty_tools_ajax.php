<?php
require('include/session.inc.php');
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

if(isset($_SESSION['userid']) && isset($_GET['action'])) {
   
   require('path.inc.php');
   require('super_header.inc.php');
   require('smarty_tools.php');
   require('display.inc.php');
   require('i18n.inc.php');
   include('team.class.php');
   
   $smartyHelper = new SmartyHelper();
   
   if($_GET['action'] == 'getTeamProjects') {
      $allProject[] = array('id' => T_('All projects'),
                            'name' => T_('All projects')
      );
      $projects = Team::getProjectList($_GET['teamid'], false);
      $smartyHelper->assign('projects', $allProject + getProjects($projects));
      $smartyHelper->display('form/projectSelector');
   }
}
else {
   header('HTTP/1.1 403 Forbidden');
   exit;
}

?>
