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

require_once('i18n/i18n.inc.php');

abstract class Controller {

   /**
    * @var SmartyHelper
    */
   protected $smartyHelper;

   protected $session_userid;
   protected $session_user;
   protected $teamid;
   protected $teamList;


   public function __construct($relativePath, $title, $menu = NULL) {
      $this->smartyHelper = new SmartyHelper();

      // relative path ("./" or "../")
      // absolute path is preferable for IE/W3C compatibility
      $rootWebSite = is_null(Constants::$codevURL) ? $relativePath : Constants::$codevURL;

      if ('/' !== substr($rootWebSite, -1)) {
         $rootWebSite .= '/';
      }

      $this->smartyHelper->assign('rootWebSite', $rootWebSite);

      $this->smartyHelper->assign('pageName', T_($title));
      if(NULL != $menu) {
         $this->smartyHelper->assign('activeGlobalMenuItem', $menu);
      }
   }

   protected function updateTeamSelector() {

      if (Tools::isConnectedUser()) {
         // use the teamid set in the form, if not defined (first page call) use session teamid
         if (isset($_GET['teamid'])) {
            $this->teamid = Tools::getSecureGETIntValue('teamid');
            $_SESSION['teamid'] = $this->teamid;
         } else {
            $this->teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
         }
         $this->smartyHelper->assign('teamid', $this->teamid);

         $team = TeamCache::getInstance()->getTeam($this->teamid);
         $this->smartyHelper->assign('teamName', $team->getName());

         $this->session_userid = $_SESSION['userid'];
         $this->session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $this->teamList = $this->session_user->getTeamList();

         if (count($this->teamList) > 0) {
            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($this->teamList, $_SESSION['teamid']));
         }

         $this->session_user->setDefaultTeam($this->teamid);

         // used to disable some menu items
         if ($this->session_user->isTeamMember(Config::getInstance()->getValue(Config::id_adminTeamId))) {
            $this->smartyHelper->assign('isAdmin', true);
         }

         $locale = getLocale();
         $this->smartyHelper->assign('locale', $locale);
      }

   }

   public function execute() {

      // set variables: session_userid, session_user, teamid, teamList and update teamSelector
      $this->updateTeamSelector();

      $this->display();
      $this->smartyHelper->displayTemplate();
   }

   protected abstract function display();

}

?>
