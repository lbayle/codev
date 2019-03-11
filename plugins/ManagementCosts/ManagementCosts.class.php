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
 * Description of StatusHistoryIndicator
 *
 */
class ManagementCosts extends IndicatorPluginAbstract {

   private static $logger;
   private static $domains;
   private static $categories;

   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   private $teamid;
   private $sessionUserId;

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
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_FINANCIAL
      );
   }

   public static function getName() {
      return T_('Management costs');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Sum elapsed time on management sideTasks and compare to the sum of command provisions. Returns a result in man-days and costs');
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
      );
   }
   public static function getJsFiles() {
      return array(
         'js_min/datepicker.min.js',
         //'js_min/table2csv.min.js',
         'js_min/tabs.min.js',
         'js_min/datatable.min.js',
      );
   }

   public static function getSmartySubFilename2() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax2.html";
   }
   public static function getSmartySubFilename3() {
      $sepChar = DIRECTORY_SEPARATOR;
      return Constants::$codevRootDir.$sepChar.self::INDICATOR_PLUGINS_DIR.$sepChar.get_called_class().$sepChar.get_called_class()."_ajax3.html";
   }

   /**
    *
    * @param \PluginDataProviderInterface $pluginDataProv
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      //self::$logger->error("Params = ".var_export($pluginDataProv, true));

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION)) {
         $this->inputIssueSel = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamid = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID)) {
         $this->serviceContractId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
      } else {
         throw new Exception('Missing parameter: '.PluginDataProviderInterface::PARAM_SERVICE_CONTRACT_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
          $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
          throw new Exception('Missing parameter: ' . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         $this->startTimestamp = strtotime("first day of this year");
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         $this->endTimestamp = strtotime("last day of this month");
      }
   }

   /**
    * settings are saved by the Dashboard
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
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params
    */
   public function execute() {
      $team = TeamCache::getInstance()->getTeam($this->teamid);
      $teamCurrency = $team->getTeamCurrency();
      $contract = ServiceContractCache::getInstance()->getServiceContract($this->serviceContractId);

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

      // ----------------------
      // find all sideTasks with 'management' categoty
      // foreach management sidetask, get elapsed & costs for each user (timetracks in period only)
      $sidetasksPerCategory = $contract->getSidetasksPerCategoryType(true);
      $iSel = $sidetasksPerCategory[Project::cat_mngt_regular];
      $iselTimetracks = $iSel->getTimetracks(NULL, $this->startTimestamp, $this->endTimestamp);

      $userData = array();
      foreach ($iselTimetracks as $tt) {
         // TODO we want per user and per task
         $ttDays = $tt->getDuration();
         $ttCost = $tt->getCost($teamCurrency, $this->teamid);
         $ttUserId = $tt->getUserId();

         $totalUserDays += $ttDays;
         $totalUserCost += $ttCost;

         if (!array_key_exists($ttUserId, $userData)) {
               $user = UserCache::getInstance()->getUser($ttUserId);
               $userData[$ttUserId] = array(
                  'userName' => $user->getRealname(),
                  'days'     => $ttDays,
                  'costs'    => $ttCost,
               );
         } else {
            $userData[$ttUserId]['days'] += $ttDays;
            $userData[$ttUserId]['costs'] += $ttCost;
         }

      }



      // ----------------------
      // find all mngt provisions declared in the period
      $provisions = $contract->getProvisionList(CommandSet::type_general,
                                                Command::type_general,
                                                CommandProvision::provision_mngt);
      $provData = array();
      foreach ($provisions as $prov) {

         # check date in period
         $provTimestamp = $prov->getDate();
         if (($provTimestamp < $this->startTimestamp) ||
             ($provTimestamp > $this->endTimestamp)) {
            continue;
         }
         $provDays = $prov->getProvisionDays();
         $provCost = $prov->getProvisionBudget($teamCurrency);
         $totalProvDays += $provDays;
         $totalProvCost += $provCost;

         $provData[] = array(
            'date'        => date('Y-m-d', $prov->getDate()),
            'commandName' => $prov->getCommandName(),
            'days'        => $provDays,
            'costs'       => $provCost,
            'description' => $prov->getSummary(),
         );
      }


      // ----------------------
      $overviewData = array();
      $overviewData[] = array(
        'type' => T_('Costs'),
        'days' => $totalUserDays,
        'costs' => sprintf("%01.2f", $totalUserCost),
      );
      $overviewData[] = array(
        'type' => T_('Provisions'),
        'days' => $totalProvDays,
        'costs' => sprintf("%01.2f", $totalProvCost),
      );


      $overviewFooter = array(
         'days' => ($totalProvDays - $totalUserDays),
         'costs' => sprintf("%01.2f", ($totalProvCost - $totalUserCost)),
         'daysFontColor' =>  (($totalProvDays - $totalUserDays) < 0) ? 'ff0000' : '009900',
         'costsFontColor' => (($totalProvCost - $totalUserCost) < 0) ? 'ff0000' : '009900',
      );
      $costsFooter = array(
         'days' => $totalUserDays,
         'costs' => sprintf("%01.2f", $totalUserCost),
      );
      $provFooter = array(
         'days' => $totalProvDays,
         'costs' => sprintf("%01.2f", $totalProvCost)
      );

      foreach ($provData as $i => $p) {
         $provData[$i]['costs'] = sprintf("%01.2f", $p['costs']);
      }
      foreach ($userData as $i => $u) {
         $userData[$i]['costs'] = sprintf("%01.2f", $u['costs']);
      }


      $this->execData = array (
         'startDate' => date('Y-m-d', $this->startTimestamp),
         'endDate' => date('Y-m-d', $this->endTimestamp),
         'teamCurrency' => $teamCurrency,
         'tableOverviewData' => $overviewData,
         'tableOverviewFooter' => $overviewFooter,
         'tableCostsData' => $userData,
         'tableCostsFooter' => $costsFooter,
         'tableProvData' => $provData,
         'tableProvFooter' => $provFooter,
         );

   }


   public function getSmartyVariables($isAjaxCall = false) {

      $smartyPrefix = 'managementCosts_';
      foreach ($this->execData as $key => $val) {
         $smartyVariables[$smartyPrefix.$key] = $val;
      }

      if (false == $isAjaxCall) {
         $smartyVariables[$smartyPrefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$smartyPrefix.'ajaxFile2'] = self::getSmartySubFilename2();
         $smartyVariables[$smartyPrefix.'ajaxFile3'] = self::getSmartySubFilename3();
         $smartyVariables[$smartyPrefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }
}

// Initialize complex static variables
ManagementCosts::staticInit();

