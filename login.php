<?php if (!isset($_SESSION)) { session_start(); header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"'); } ?>
<?php

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

require('path.inc.php');

if (isset($_SESSION['userid'])) {
    // load homepage
    header('Location: '.getServerRootURL());
    exit;
}

require('super_header.inc.php');


$logger = Logger::getLogger('login');

$action = isset($_POST['action']) ? $_POST['action'] : '';
if ('pleaseLogin' == $action) {
    $user = $_POST['codev_login'];
    $password = md5($_POST['codev_passwd']);
    
    $formattedUser = mysql_real_escape_string($user);
    $formattedPass = mysql_real_escape_string($password);
    $query= 'SELECT id, username, realname FROM `mantis_user_table` WHERE username = \''.$user.'\' and password = \''.$formattedPass.'\'';
    $result = mysql_query($query);
    if ($result && mysql_num_rows($result) == 1) {
        if ($row_login = mysql_fetch_object($result)) {
            $_SESSION['userid']=$row_login->id;
            $_SESSION['username']=$row_login->username;
            $_SESSION['realname']=$row_login->realname;

            $logger->info('user '.$row_login->id.' logged in: '.$row_login->username.' ('.$row_login->realname.')');

            /*
                // TODO: remove hardcoded file path
                // trace login
                $logFile = "/homez.466/codevtt/codevttReports/login.log";
                $ip=$_SERVER['REMOTE_ADDR'];
                $str= date("Y-m-d H:i:s")." [$ip] ".$row_login->username." (".$row_login->realname.")";

                $fh = fopen($logFile, 'a+');
                fwrite($fh, $str);
                fclose($fh);
            */

            // load homepage
            header('Location: '.getServerRootURL());
            exit;
        } else {
            $logger->error('Query FAILED: '.$query);
            $logger->error(mysql_error());
            $error = 'login failed !';
        }
    } else {
        $error = 'login failed !';
    }
}

require('display.inc.php');

$smartyHelper = new SmartyHelper();
$smartyHelper->assign('pageName', T_('CoDev Login'));
if(isset($error)) {
    $smartyHelper->assign('error', T_($error));
}

$smartyHelper->displayTemplate($codevVersion, $_SESSION['username'], $_SESSION['realname'],$mantisURL);

?>
