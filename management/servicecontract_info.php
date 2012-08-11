<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

require('include/super_header.inc.php');

require('management/servicecontract_tools.php');

include_once('constants.php');

require('smarty_tools.php');
require_once('tools.php');

class ServiceContractInfoController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (isset($_SESSION['userid'])) {
         $session_user = UserCache::getInstance()->getUser($_SESSION['userid']);

         $teamid = 0;
         if (isset($_POST['teamid'])) {
            $teamid = Tools::getSecurePOSTIntValue('teamid');
         } else if (isset($_SESSION['teamid'])) {
            $teamid = $_SESSION['teamid'];
         }
         $_SESSION['teamid'] = $teamid;

         // ---
         // use the servicecontractid set in the form, if not defined (first page call) use session servicecontractid
         $servicecontractid = 0;
         if(isset($_POST['servicecontractid'])) {
            $servicecontractid = $_POST['servicecontractid'];
         } else if(isset($_SESSION['servicecontractid'])) {
            $servicecontractid = $_SESSION['servicecontractid'];
         }
         $_SESSION['servicecontractid'] = $servicecontractid;

         // set TeamList (including observed teams)
         $teamList = $session_user->getTeamList();
         $this->smartyHelper->assign('teamid', $teamid);
         $this->smartyHelper->assign('teams', SmartyTools::getSmartyArray($teamList, $teamid));

         $this->smartyHelper->assign('servicecontractid', $servicecontractid);
         $this->smartyHelper->assign('servicecontracts', ServiceContractTools::getServiceContracts($teamid, $servicecontractid));

         $action = Tools::getSecurePOSTStringValue('action', '');

         if (0 != $servicecontractid) {
            $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

            ServiceContractTools::displayServiceContract($this->smartyHelper, $servicecontract);

            // ConsistencyCheck
            $consistencyErrors = $this->getConsistencyErrors($servicecontract);
            if (0 != $consistencyErrors) {
               $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
               $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
               $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
            }

            // access rights
            if (($session_user->isTeamManager($servicecontract->getTeamid())) ||
               ($session_user->isTeamLeader($servicecontract->getTeamid()))) {
               $this->smartyHelper->assign('isEditGranted', true);
            }
         } else {
            unset($_SESSION['cmdid']);
            unset($_SESSION['commandsetid']);

            if ('displayServiceContract' == $action) {
               header('Location:servicecontract_edit.php?servicecontractid=0');
            }
         }
      }
   }

   /**
    * Get consistency errors
    * @param ServiceContract $serviceContract
    * @return mixed[]
    */
   private function getConsistencyErrors(ServiceContract $serviceContract) {
      global $statusNames;

      $consistencyErrors = array(); // if null, array_merge fails !

      $cerrList = $serviceContract->getConsistencyErrors();
      if (count($cerrList) > 0) {
         foreach ($cerrList as $cerr) {
            $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
            $user = UserCache::getInstance()->getUser($cerr->userId);
            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, '[' . $issue->getProjectName() . '] ' . $issue->summary),
               'issueStatus' => $statusNames[$cerr->status],
               'user' => $user->getName(),
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);
         }
      }

      return $consistencyErrors;
   }

}

// ========== MAIN ===========
ServiceContractInfoController::staticInit();
$controller = new ServiceContractInfoController('Service Contract','Management');
$controller->execute();

?>
