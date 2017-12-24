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
        $retVal = login($_POST['codev_login'],$_POST['codev_passwd']);
        echo $retVal;
    }
}

function login($username, $password) {
    $logger = Logger::getLogger('login');
    $sql = AdodbWrapper::getInstance();

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

    $query = "SELECT id, username, realname, last_visit FROM {mantis_user_table} " .
             " WHERE username = " . $sql->db_param() .
             " AND password = " . $sql->db_param() .
             " AND enabled = 1";

    $result = $sql->sql_query($query, array($username, $password));

    if ($result &&
        1 == $sql->getNumRows($result) &&
        $row_login = $sql->fetchArray($result)) {

         $_SESSION['userid'] = $row_login['id'];
         $_SESSION['username'] = $row_login['username'];
         $_SESSION['realname'] = $row_login['realname'];
         $lastVisitTimestamp = $row_login['last_visit'];

         try {
            $user = UserCache::getInstance()->getUser($row_login['id']);

            $locale = $user->getDefaultLanguage();
            if (NULL != $locale) { $_SESSION['locale'] = $locale; }

            $teamid = $user->getDefaultTeam();
            if (0 != $teamid) {
               $_SESSION['teamid'] = $teamid;
               
            } else {
               // no default team (user's first connection): 
               // find out if user is already affected to a team and set as default team
               $query = 'SELECT team_id FROM {codev_team_user_table} WHERE user_id = ' . $sql->db_param() .
                        ' ORDER BY arrival_date DESC';
               $query_params = array($user->getId());

               $result = $sql->sql_query($query, $query_params, TRUE, 1); // LIMIT 1
               if ($result && 1 == $sql->getNumRows($result)) {
                  $row = $sql->fetchArray($result);
                  $teamid = $row['team_id'];
                  $user->setDefaultTeam($teamid);
                  $_SESSION['teamid'] = $teamid;
               }
            }
            
            $projid = $user->getDefaultProject();
            if (0 != $projid) { $_SESSION['projectid'] = $projid; }

            $query2 = "UPDATE {mantis_user_table} SET last_visit = ".$sql->db_param().
                      " WHERE username = ".$sql->db_param();
            $sql->sql_query($query2, array($now, $username));

         } catch (Exception $e) {
            if ($isLog && $logger->isDebugEnabled()) {
               $logger->debug("could not load preferences for user " . $row_login['id']);
            }
         }
			if (($isLog) && ($now > ($lastVisitTimestamp + 2))) {
            $ua = Tools::getBrowser();
            $browserStr = $ua['name'] . ' ' . $ua['version'] . ' (' .$ua['platform'].')'; 
            $logger->info('user ' . $row_login['id'] . ' ' . $row_login['username'] . ' (' . $row_login['realname'] . '), Team ' . $user->getDefaultTeam() . ', ' . $browserStr);
         }
         return TRUE;
    } else {
        #$logger->error('login failed !');
        return FALSE;
    }
}
