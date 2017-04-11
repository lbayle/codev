
<?php
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

/**
 * Description of CostsIndicator
 *
 * @author lob
 */
class CostsIndicator extends IndicatorPluginAbstract {


   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider
   private $inputIssueSel;
   private $teamid;
   private $sessionUserId;
   private $domain;

   private $cmdid;
   private $cmdsetid;
   private $serviceContractId;

   // internal
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TASK,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_FINANCIAL
      );
   }

   public static function getName() {
      return T_('Costs');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Compute costs, using the UserDailyCosts defined in team settings');
   }
   public static function getAuthor() {
      return 'CodevTT (GPL v3)';
   }
   public static function getVersion() {
      return '1.0.0';
   }
   public static function getDomains() {
      return self::$domains;
   }
   public static function getCategories() {
      return self::$categories;
   }
   public static function isDomain($domain) {
      return in_array($domain, self::$domains);
   }
   public static function isCategory($category) {
      return in_array($category, self::$categories);
   }
   public static function getCssFiles() {
      return array(
          //'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         //'js_min/datepicker.min.js',
         'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
          $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
          throw new Exception('Missing parameter: ' . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN)) {
         $this->domain = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_DOMAIN);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_DOMAIN);
      }
      switch ($this->domain) {
         case IndicatorPluginInterface::DOMAIN_TASK:
            // none
            break;
         case IndicatorPluginInterface::DOMAIN_COMMAND:
            if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID)) {
               $this->cmdid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_ID);
            } else {
               throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_COMMAND_ID);
            }
            break;
         case IndicatorPluginInterface::DOMAIN_COMMAND_SET:
            if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_SET_ID)) {
               $this->cmdsetid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_COMMAND_SET_ID);
            } else {
               throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_COMMAND_SET_ID);
            }
            break;
         case IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT:
            if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID)) {
               $this->serviceContractId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
            } else {
               throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
            }
            break;
         default:
            throw new Exception('Missing parameter related to domain : '.$this->domain);
      }

      // set default pluginSettings
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
      }
   }

   /**
    *
    * @return array execData
    */
   public function execute() {

      $tableSumLines = array();
      $tableDetailedLines = array();
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamCurrency = $team->getTeamCurrency();

      if (NULL === $team->getAverageDailyCost()) {
         return $this->execData = array(
             'generalErrorMsg' => T_('No AverageDailyCost defined in team settings')
         );
      }
      $session_user = UserCache::getInstance()->getUser($this->sessionUserId);
      if ((!$session_user->isTeamManager($this->teamid)) &&
              (!$session_user->isTeamObserver($this->teamid))) {
         return $this->execData = array(
             'generalErrorMsg' => T_('Sorry, only managers can access this plugin')
         );
      }

      $issueList = $this->inputIssueSel->getIssueList();
      $issueCostSums = array();
      foreach ($issueList as $issue) {
         try {
            $issueCosts = $issue->getCostStruct($teamCurrency, $this->teamid);

            // Displaying All issues in service contract is not relevant
            if (IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT != $this->domain ) {
               $issueFormatedLine = array(
                  'issueId'     => Tools::issueInfoURL(sprintf("%07d\n", $issue->getId())),
                  'extRef'      => $issue->getTcId(),
                  'driftMgrColor' => $issue->getDriftColor($issueCosts['driftMgr']),
                  'description' => htmlspecialchars($issue->getSummary()),
               );
               foreach($issueCosts as $key => $value) {
                  $issueFormatedLine[$key] = sprintf("%01.2f", $value);
               }
               $tableDetailedLines[] = $issueFormatedLine;
            }
            foreach($issueCosts as $key => $value) {
               $issueCostSums[$key]    += $issueCosts[$key];
            }
         } catch (Exception $e) {
            self::$logger->error('issue '.$issue->getId().': '.$e->getMessage());
         }
      }

      // construct the SUM table data
      $issueSumFormatedLine = array(
         'driftMgrColor' => ($issueCostSums['driftMgr'] < 0) ? 'bdfcbd' : 'fcbdbd',
         'description' => count($issueList).' '.T_('Tasks')
      );
      foreach($issueCostSums as $key => $value) {
         $issueSumFormatedLine[$key] = sprintf("%01.2f", $value);
      }
      $tableSumLines[] = $issueSumFormatedLine;
      $TableSumFooter = $issueCostSums;

      // add provisions & sidetasks
      switch ($this->domain) {
         case IndicatorPluginInterface::DOMAIN_COMMAND:
            $cmd = CommandCache::getInstance()->getCommand($this->cmdid);
            $provisions = $cmd->getProvisionList();
            foreach ($provisions as $prov) {
               if ($prov->isInCheckBudget()) {
                  $provBudget += $prov->getProvisionBudget($teamCurrency);
               }
            }
            break;
         case IndicatorPluginInterface::DOMAIN_COMMAND_SET:
            $provBudget = 0;
            $cmdSet = CommandSetCache::getInstance()->getCommandSet($this->cmdsetid);
            $provisions = $cmdSet->getProvisionList(Command::type_general);
            foreach ($provisions as $prov) {
               if ($prov->isInCheckBudget()) {
                  $provBudget += $prov->getProvisionBudget($teamCurrency);
               }
            }
            break;
         case IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT:
            $contract = ServiceContractCache::getInstance()->getServiceContract($this->serviceContractId);
            $provisions = $contract->getProvisionList(CommandSet::type_general, Command::type_general);
            foreach ($provisions as $prov) {
               // all provisions, not only isInCheckBudget
               $provBudget += $prov->getProvisionBudget($teamCurrency);
            }

            // ServiceContract also has SideTasks !
            $sidetasksPerCategory = $contract->getSidetasksPerCategoryType(true);
            foreach ($sidetasksPerCategory as $categoryId => $iSel) {
               $categoryCostStruct = $iSel->getCostStruct($teamCurrency, $this->teamid);
               $catElapsedCost = $categoryCostStruct['elapsed'];
               $category = Project::$catTypeNames[$categoryId];
               
               $tableSumLines[] = array(
                  'costsMgr'    => '',
                  'costs'       => '',
                  'reestimated' => '',
                  'elapsed'     => sprintf("%01.2f", $catElapsedCost),
                  'backlog'     => '',
                  'driftMgr'    => sprintf("%01.2f", $catElapsedCost),
                  'description' => $iSel->getNbIssues().' '.T_('Sidetasks')." ($category)",
               );
               $TableSumFooter['elapsed']   += $catElapsedCost;
               $TableSumFooter['driftMgr'] += $catElapsedCost;

            }
            break;
         default:
            $provisions = array();
      }
      if (0 !== count($provisions)) {
         /* @var $prov CommandProvision */

         $tableSumLines[] = array(
            'costsMgr'    => '',
            'costs'       => '',
            'reestimated' => sprintf("%01.2f", ( - $provBudget)),
            'elapsed'     => '',
            'backlog'     => '',
            'driftMgr'    => sprintf("%01.2f", ( - $provBudget)),
            'description' => count($provisions).' '.T_('Provisions'),
         );
         $TableSumFooter['reestimated'] -= $provBudget;
         $TableSumFooter['driftMgr'] -= $provBudget;
      }

      $globalCostEstim = $TableSumFooter['reestimated'];
      foreach($TableSumFooter as $key => $val) {
         $TableSumFooter[$key] = sprintf("%01.2f", $val);
      }

      $this->execData= array(
         'teamCurrency'    => $teamCurrency,
         'globalCostEstim' => number_format($globalCostEstim, 2, '.', ' '),
         'tableSumLines'   => $tableSumLines,
         'tableSumFooter'  => $TableSumFooter,
      );

      // Displaying All issues in service contract is not relevant
      if (IndicatorPluginInterface::DOMAIN_SERVICE_CONTRACT != $this->domain ) {
         $this->execData['tableValuesLines']  = $tableDetailedLines;
      }

      return $this->execData;
   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $smartyVariables= array();
      foreach ($this->execData as $key => $val) {
         $smartyVariables['costsIndicator_'.$key] = $val;
      }

      if (false == $isAjaxCall) {
         $smartyVariables['costsIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['costsIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
CostsIndicator::staticInit();
