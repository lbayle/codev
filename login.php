<?php
include_once('./include/session.inc.php');

/**
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

require('super_header.inc.php');

$logger = Logger::getLogger('login');

if(isset($_POST['action'])) {
    if ('login' == $_POST['action']) {
        echo login($_POST['codev_login'],$_POST['codev_passwd']);
    }
}

function login($user, $password) {
    global $logger;
    $password = md5($password);

    $formattedUser = SqlWrapper::getInstance()->sql_real_escape_string($user);
    $formattedPass = SqlWrapper::getInstance()->sql_real_escape_string($password);
    $query= 'SELECT id, username, realname FROM `mantis_user_table` WHERE username = \''.$formattedUser.'\' and password = \''.$formattedPass.'\'';
    $result = SqlWrapper::getInstance()->sql_query($query);
    if ($result && SqlWrapper::getInstance()->sql_num_rows($result) == 1 && $row_login = SqlWrapper::getInstance()->sql_fetch_object($result)) {
        $_SESSION['userid']=$row_login->id;
        $_SESSION['username']=$row_login->username;
        $_SESSION['realname']=$row_login->realname;

        $logger->info('user '.$row_login->id.' logged in: '.$row_login->username.' ('.$row_login->realname.')');
        return TRUE;
    } else {
        $error = 'login failed !';
        return FALSE;
    }
}
