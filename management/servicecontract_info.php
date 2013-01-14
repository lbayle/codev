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

class ServiceContractInfoController extends Controller {

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      // Nothing special
   }

   protected function display() {
      if (Tools::isConnectedUser()) {

         if (0 != $this->teamid) {

            // use the servicecontractid set in the form, if not defined (first page call) use session servicecontractid
            $servicecontractid = 0;
            if(isset($_POST['servicecontractid'])) {
               $servicecontractid = Tools::getSecurePOSTIntValue('servicecontractid');
               $_SESSION['servicecontractid'] = $servicecontractid;
            } else if(isset($_GET['servicecontractid'])) {
               $servicecontractid = Tools::getSecureGETIntValue('servicecontractid');
               $_SESSION['servicecontractid'] = $servicecontractid;
            } else if(isset($_SESSION['servicecontractid'])) {
               $servicecontractid = $_SESSION['servicecontractid'];
            }

            // set TeamList (including observed teams)
            $oTeamList = $this->session_user->getObservedTeamList();
            $mTeamList = $this->session_user->getManagedTeamList();
            $teamList = $oTeamList + $mTeamList;           // array_merge does not work ?!

            if (empty($teamList) || (!array_key_exists($this->teamid, $teamList))) {
               // only managers (and observers) can access this page.
               return;
            }

            $isManager = $this->session_user->isTeamManager($this->teamid);
            if ($isManager) {
               $this->smartyHelper->assign('isManager', true);
            }

            $this->smartyHelper->assign('servicecontracts', ServiceContractTools::getServiceContracts($this->teamid, $servicecontractid));

            if (0 != $servicecontractid) {
               $servicecontract = ServiceContractCache::getInstance()->getServiceContract($servicecontractid);

               if ($this->teamid == $servicecontract->getTeamid()) {

                  $this->smartyHelper->assign('servicecontractid', $servicecontractid);

                  // get selected filters
                  $selectedFilters="";
                  if(isset($_GET['selectedFilters'])) {
                     $selectedFilters = Tools::getSecureGETStringValue('selectedFilters');
                  } else {
                     $selectedFilters = $this->session_user->getServiceContractFilters($servicecontractid);
                  }

                  ServiceContractTools::displayServiceContract($this->smartyHelper, $servicecontract, $isManager, $selectedFilters);

                  // ConsistencyCheck
                  $consistencyErrors = $this->getConsistencyErrors($servicecontract);
                  if (0 != $consistencyErrors) {
                     $this->smartyHelper->assign('ccheckButtonTitle', count($consistencyErrors).' '.T_("Errors"));
                     $this->smartyHelper->assign('ccheckBoxTitle', count($consistencyErrors).' '.T_("Errors"));
                     $this->smartyHelper->assign('ccheckErrList', $consistencyErrors);
                  }

                  // access rights
                  if ($isManager ||
                     ($this->session_user->isTeamLeader($servicecontract->getTeamid()))) {
                     $this->smartyHelper->assign('isEditGranted', true);
                  }
               }
            } else {
               unset($_SESSION['cmdid']);
               unset($_SESSION['commandsetid']);

               $action = Tools::getSecurePOSTStringValue('action', '');
               if ('displayServiceContract' == $action) {
                  header('Location:servicecontract_edit.php?servicecontractid=0');
               }
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
      $cerrList = $serviceContract->getConsistencyErrors();
      if (count($cerrList) > 0) {
         $consistencyErrors = array();

         foreach ($cerrList as $cerr) {

            if (!is_null($cerr->userId)) {
               $user = UserCache::getInstance()->getUser($cerr->userId);
            } else {
               $user = NULL;
            }
            if (Issue::exists($cerr->bugId)) {
               $issue = IssueCache::getInstance()->getIssue($cerr->bugId);
               $projName = $issue->getProjectName();
               $summary = $issue->getSummary();
            } else {
               $projName = '';
               $summary = '';
            }

            $titleAttr = array(
                  T_('Project') => $projName,
                  T_('Summary') => $summary,
            );
            $consistencyErrors[] = array(
               'issueURL' => Tools::issueInfoURL($cerr->bugId, $titleAttr),
               'issueStatus' => Constants::$statusNames[$cerr->status],
               'user' => isset($user) ? $user->getName() : '',
               'severity' => $cerr->getLiteralSeverity(),
               'severityColor' => $cerr->getSeverityColor(),
               'desc' => $cerr->desc);
         }

         return $consistencyErrors;
      }
      return NULL;
   }

}

// ========== MAIN ===========
ServiceContractInfoController::staticInit();
$controller = new ServiceContractInfoController('Service Contract','Management');
$controller->execute();

?>
