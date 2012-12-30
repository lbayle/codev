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
   protected $temList;


   public function __construct($title, $menu = NULL) {
      $this->smartyHelper = new SmartyHelper();
      $this->smartyHelper->assign('pageName', T_($title));
      if(NULL != $menu) {
         $this->smartyHelper->assign('activeGlobalMenuItem', $menu);
      }
   }

   private function updateTeamSelector() {

      if (Tools::isConnectedUser()) {
         // use the teamid set in the form, if not defined (first page call) use session teamid
         if (isset($_GET['teamid'])) {
            $this->teamid = Tools::getSecureGETIntValue('teamid');
            $_SESSION['teamid'] = $this->teamid;
         } else {
            $this->teamid = isset($_SESSION['teamid']) ? $_SESSION['teamid'] : 0;
         }
         $this->smartyHelper->assign('teamid', $this->teamid);

         $this->session_userid = $_SESSION['userid'];
         $this->session_user = UserCache::getInstance()->getUser($_SESSION['userid']);
         $this->teamList = $this->session_user->getTeamList();

         if (count($this->teamList) > 0) {
            $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($this->teamList, $_SESSION['teamid']));
         }
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
