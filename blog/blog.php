<?php
include_once('../include/session.inc.php');
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

require '../path.inc.php';

require('super_header.inc.php');


include_once('user.class.php');
include_once('blog_manager.class.php');

require('display.inc.php');


// ================ MAIN =================

$logger = Logger::getLogger("blog");

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_("Blog"));

if (isset($_SESSION['userid'])) {

   $session_user = new User($_SESSION['userid']);

   $blogManager = new BlogManager();

   print_r($blogManager->getCategoryList());


}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
