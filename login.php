<?php
include_once('include/session.inc.php');

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


if(isset($_POST['action'])) {
    if ('login' == $_POST['action']) {
       // Ajax return by 'echo'
        echo login($_POST['codev_login'],$_POST['codev_passwd']);
    }
}

function login($user, $password) {
    $logger = Logger::getLogger('login');
	
    // WARN: if logger is LoggerAppenderEcho, then logs will break the login Ajax call !
	 try {
		$appenders = $logger->getParent()->getAllAppenders();
		$isLog = true;

		foreach ($appenders as $appender) {
			if ('LoggerAppenderEcho' === get_class($appender)) {
				$isLog = false;
				break;
			}
		}
 	 } catch (Exception $e) {
		 // logs should never break application
		 $isLog = false;
	 }

    $password = md5($password);
    $now = time();

    $formattedUser = SqlWrapper::sql_real_escape_string($user);
    $formattedPass = SqlWrapper::sql_real_escape_string($password);
    $query = "SELECT id, username, realname, last_visit FROM `{user}` WHERE username = '".$formattedUser."' AND password = '".$formattedPass."' AND enabled = 1;";
    $result = SqlWrapper::getInstance()->sql_query($query);
    if ($result && SqlWrapper::getInstance()->sql_num_rows($result) == 1 && $row_login = SqlWrapper::getInstance()->sql_fetch_object($result)) {
        $_SESSION['userid'] = $row_login->id;
        $_SESSION['username'] = $row_login->username;
        $_SESSION['realname'] = $row_login->realname;
        $lastVisitTimestamp = $row_login->last_visit;

        try {
            $user =  UserCache::getInstance()->getUser($row_login->id);

            $locale = $user->getDefaultLanguage();
            if (NULL != $locale) { $_SESSION['locale'] = $locale; }

            $teamid = $user->getDefaultTeam();
            if (0 != $teamid) {
               $_SESSION['teamid'] = $teamid;
               
            } else {
               // no default team (user's first connection): 
               // find out if user is already affected to a team and set as default team
               $query = "SELECT team_id FROM `codev_team_user_table` WHERE user_id = '".$user->getId().
                       "' ORDER BY arrival_date DESC LIMIT 1;";
               $result = SqlWrapper::getInstance()->sql_query($query);
               if ($result && 1 == SqlWrapper::getInstance()->sql_num_rows($result)) {
                  $row = SqlWrapper::getInstance()->sql_fetch_object($result);
                  $teamid = $row->team_id;
                  $user->setDefaultTeam($teamid);
                  $_SESSION['teamid'] = $teamid;
               }

            }
            
            $projid = $user->getDefaultProject();
            if (0 != $projid) { $_SESSION['projectid'] = $projid; }

            $query2 = "UPDATE `{user}` SET last_visit = ".$now." WHERE username = '".$formattedUser."';";
            SqlWrapper::getInstance()->sql_query($query2);

         } catch (Exception $e) {
            if ($isLog && self::$logger->isDebugEnabled()) {
               $logger->debug("could not load preferences for user $row_login->id");
            }
         }
			if (($isLog) && ($now > ($lastVisitTimestamp + 2))) {
            $ua = Tools::getBrowser();
            $browserStr = $ua['name'] . ' ' . $ua['version'] . ' (' .$ua['platform'].')'; 
            $logger->info('user '.$row_login->id.' '.$row_login->username.' ('.$row_login->realname.'), Team '.$user->getDefaultTeam().', '.$browserStr);
			}
        return TRUE;
    } else {
        #$error = 'login failed !';
        return FALSE;
    }
}
