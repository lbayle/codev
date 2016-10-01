<?php

require('../../include/session.inc.php');

/*
  This file is part of CodevTT

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
require('../../path.inc.php');

// Note: i18n is included by the Controler class, but Ajax dos not use it...
require_once('i18n/i18n.inc.php');

if (Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {
    $logger = Logger::getLogger("ImportUsers_ajax");
    $action = filter_input(INPUT_POST, 'action');
    $dashboardId = filter_input(INPUT_POST, 'dashboardId');
    $sessionUserId = $_SESSION['userid'];
    $sessionUser = UserCache::getInstance()->getUser($sessionUserId);
    $sessionTeam = TeamCache::getInstance()->getTeam($_SESSION['teamid']);

    if (!isset($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId])) {
        $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
        Tools::sendBadRequest("PluginDataProvider not set");
    }
    $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID . $dashboardId]);
    if (FALSE == $pluginDataProvider) {
        $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
        Tools::sendBadRequest("PluginDataProvider unserialize error");
    }

    if ('uploadCsvFile' === $action) {
        $smartyHelper = new SmartyHelper();
        $selectedTeamId = Tools::getSecurePOSTIntValue('teamId');
        $filename = null;
        $users = null;
        $errMsg = null;
        $teamName = null;

        // If user is allowed to import
        if (($sessionUser->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) ||
                ($sessionUserId == $sessionTeam->getLeaderId())) {
            
            $indicator = new ImportUsers($pluginDataProvider);

            if (0 != $selectedTeamId) {
                $team = TeamCache::getInstance()->getTeam($selectedTeamId);
                $teamName = $team->getName();

                if (isset($_FILES['uploaded_csv'])) {
                    try {
                        // Get file datas
                        $filename = $indicator->getSourceFile();
                        $users = $indicator->getUsersFromCSV($filename);
                    } catch (Exception $ex) {
                        $errMsg = $ex->getMessage();
                    }
                }
            }

            // get list of project access levels (link to mantis)
            $mantisAccessLevelList = $indicator->getMantisAccessLevelList();
            $projectAccessLevels = Tools::array2json($mantisAccessLevelList);

            // get list of codevTT roles
            $teamAccessLevels = Team::$accessLevelNames;
            $codevTTRoles = Tools::array2json($teamAccessLevels);


            $newUsers = array();
            if (null != $users) {
                // Check if users already exists
                foreach ($users as $user) {
                    $user['alreadyExist'] = User::exists($user['username']);
                    $user['projectAccessLevel']['id'] = 55; // default id
                    $user['projectAccessLevel']['name'] = $mantisAccessLevelList[55]; // default name from id
                    $user['codevTTRole']['id'] = 10; // default id
                    $user['codevTTRole']['name'] = $teamAccessLevels[10]; // default name from id
                    $newUsers[] = $user;
                }
            }
            
            $data = array(
                'importUsers_errMsg' => $errMsg,
                'importUsers_filename' => $filename,
                'importUsers_newUsers' => $newUsers,
                'importUsers_teamId' => $selectedTeamId,
                'importUsers_teamName' => $teamName,
                'importUsers_projectAccessLevels' => $projectAccessLevels,
                'importUsers_codevTTRoles' => $codevTTRoles,
                'importUsers_ajaxPhpURL' => $indicator->getAjaxPhpURL(),
            );
            
        } else {
            $errMsg = T_("Your are not allowed to import users");
            
            $data = array(
                'importUsers_errorMsg' => $errMsg,
            );
        }



        // construct the html table
        foreach ($data as $smartyKey => $smartyVariable) {
            $smartyHelper->assign($smartyKey, $smartyVariable);
        }
        $html = $smartyHelper->fetch(ImportUsers::getSmartySubFilename());
        $data['importUsers_htmlContent'] = $html;

        // set JS libraries that must be load
        $data['importUsers_jsFiles'] = ImportUsers::getJsFiles();
        $data['importUsers_cssFiles'] = ImportUsers::getCssFiles();

        // return data
        $jsonData = json_encode($data);
        echo $jsonData;
    } else if ("importRow" == $action) {
        $password = null;
        $usersStatus = [];

        $selectedTeamId = Tools::getSecurePOSTStringValue('teamId');
        $passwordByMail = Tools::getSecurePOSTStringValue('passwordByMail');

        $usersStr = Tools::getSecurePOSTStringValue('users');
        $users = json_decode(stripslashes($usersStr), true);

        // generated a common password for all users
        $crypto = new Crypto();
        if ('0' == $passwordByMail) {
            $password = $crypto->crypto_generate_uri_safe_nonce(12);
        }

        foreach ($users as $userData) {
            $userStatus['lineNum'] = $userData['lineNum'];
            if ('1' == $passwordByMail) {
               // generated a unique password per user
                $password = $crypto->crypto_generate_uri_safe_nonce(12);
            }

            $userData['entryDate'] = Tools::date2timestamp($userData['entryDate']);

            // Create user in Mantis DB
            $realName = $userData['firstname'] . " " . $userData['name'];
            $username = str_replace(' ', '', $userData['username']);
            try {
                User::createUserInMantisDB($username, $realName, $userData['email'], $password, 25, $userData['entryDate']);
                if ('1' == $passwordByMail) {
                    $message = T_('Dear ') . $realName . ",\n\n" .
                            T_('Here is your password for CodevTT and Mantis : ') . $password . " \n\n" .
                            T_('Please, change your password on your first connection.') . " \n\n" .
                            T_('Regards \n\n') .
                            T_('CodevTT Team');
                    Email::getInstance()->sendEmail($realName, T_("[CodevTT] Your password"), $message);
                }
            } catch (Exception $ex) {
                $userStatus['creationFailed'] = true;
            }

            if (User::exists($username)) {
                // Add user to CodevTT team
                try {
                    $userId = User::getUserId($username);
                    $user = UserCache::getInstance()->getUser($userId);

                    $team = TeamCache::getInstance()->getTeam($selectedTeamId);
                    $memberAdded = $team->addMember($userId, $userData['entryDate'], $userData['codevTTRole']);

                    if (!$memberAdded) {
                        $userStatus['alreadyBelongToTeam'] = true;
                    }
                } catch (Exception $ex) {
                    $userStatus['addToTeamFailed'] = true;
                }

                // Affect team projects to user
                $projectList = $team->getProjects(false, false, false);
                if (0 != count($projectList)) {
                    try {
                        foreach ($projectList as $projectId => $projectName) {
                            // TODO: check projectAccessLevel is valid
                            $affectedToProject = $user->affectToProject($projectId, $userData['projectAccessLevel']);
                            if (!$affectedToProject) {
                                $userStatus['addToProjectsFailed'] = true;
                            }
                        }
                    } catch (Exception $ex) {
                        $userStatus['addToProjectsFailed'] = true;
                    }
                }
            }
            $usersStatus[] = $userStatus;
        }

        $data = array(
            'importUsers_password' => ('1' == $passwordByMail) ? null : $password,
            'importUsers_usersStatus' => $usersStatus
        );
        
        // return data
        $jsonData = json_encode($data);
        echo $jsonData;
    } else {
        Tools::sendNotFoundAccess();
    }
} else {
    Tools::sendUnauthorizedAccess();
}