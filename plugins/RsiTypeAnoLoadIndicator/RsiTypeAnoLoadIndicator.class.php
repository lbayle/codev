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
 * Description of EffortEstimReliability
 *
 */
class RsiTypeAnoLoadIndicator extends IndicatorPluginAbstract {

   const OPTION_INTERVAL = 'interval';

   private static $logger;
   private static $domains;
   private static $categories;

   // config options from dataProvider
   private $inputIssueSel;
   private $startTimestamp;
   private $endTimestamp;

   // config options from Dashboard
   private $interval; // 'monthly' or 'weekly'

   // internal
   private $formatedBugidList;
   private $bugResolvedStatusThreshold;
   protected $execData;


   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_TEAM,
         self::DOMAIN_PROJECT,
         self::DOMAIN_COMMAND,
         self::DOMAIN_COMMAND_SET,
         self::DOMAIN_SERVICE_CONTRACT,
      );
      self::$categories = array (
         self::CATEGORY_QUALITY
      );
   }

   public static function getName() {
      return '==RSI== Historique du consommé par type anomalie';
   }
   public static function getDesc($isShortDesc = true) {
      $desc = "Consommé par type d'anomalie (Prod vs Recette)";
      if (!$isShortDesc) {
         $desc .= "<br><br>".
            "<br>".
            "";
      }
      return $desc;
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
          'lib/jquery.jqplot/jquery.jqplot.min.css'
      );
   }
   public static function getJsFiles() {
      return array(
         'lib/jquery.jqplot/jquery.jqplot.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasTextRenderer.min.js',
         'lib/jquery.jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
         'js_min/chart.min.js',
         'js_min/tabs.min.js',

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
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_ISSUE_SELECTION);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP)) {
         $this->startTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_START_TIMESTAMP);
      }
      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP)) {
         $this->endTimestamp = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      } else {
         throw new Exception("Missing parameter: ".PluginDataProviderInterface::PARAM_END_TIMESTAMP);
      }

      $bugidList = array_keys($this->inputIssueSel->getIssueList());
      $this->formatedBugidList = implode(', ', $bugidList);
      if (empty($this->formatedBugidList)) {
         throw new Exception('No issues in IssueSelection !');
      }
      $this->bugResolvedStatusThreshold = Config::getInstance()->getValue(Config::id_bugResolvedStatusThreshold);


      // set default pluginSettings (not provided by the PluginDataProvider)
      $this->interval = 'monthly';

   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_INTERVAL, $pluginSettings)) {
            switch ($pluginSettings[self::OPTION_INTERVAL]) {
               case 'weekly':
                  $this->interval = 'weekly';
                  break;
               case 'monthly':
                  $this->interval = 'monthly';
                  break;
               default:
                  self::$logger->warn('option '.self::OPTION_INTERVAL.'= '.$pluginSettings[self::OPTION_INTERVAL]." (unknown value)");
            }
         }
      }
   }

   /**
    *
    * @param int $startTimestamp
    * @param int $endTimestamp
    * @param string $interval [weekly,monthly]
    */
   private function createTimestampRangeList($interval = 'monthly') {
      $timestampRangeList = array();

      $startT = $this->startTimestamp;

      switch ($interval) {
         case 'weekly':
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("next sunday", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("next monday", $startT);
            }
            break;
         default:
            // monthly
            while ($startT < $this->endTimestamp) {
               $endT = strtotime("last day of this month", $startT);
               if ($endT > $this->endTimestamp) {
                  $endT = $this->endTimestamp;
               }
               $timestampRangeList[date('Y-m-d', $startT)] = array(
                   'start' => mktime(0, 0, 0, date('m', $startT), date('d', $startT), date('Y', $startT)),
                   'end' =>   mktime(23, 59, 59, date('m', $endT), date('d',$endT), date('Y', $endT))
               );
               $startT = strtotime("first day of next month", $startT);
            }
      }
      return $timestampRangeList;
   }



   /**
    *
    *
    */
   public function execute() {

      $iselAnoRecette = new IssueSelection('AnoRecette');
      $iselAnoProduction = new IssueSelection('AnoProduction');

      $iselAno =  new IssueSelection('Ano');

      // remove Tasks (keep only bugs)
      $issueList = $this->inputIssueSel->getIssueList();
      foreach ($issueList as $bugid => $issue) {
         if ('Bug' === $issue->getType()) {
            $iselAno->addIssue($bugid);
         }
      }

      // filter on RSI_Type_Ano
      $bugidList = array_keys($iselAno->getIssueList());
      $formatted_bugid_list = implode(',', $bugidList);
      $customFieldName = 'RSI_Type_Ano';

      $sql = AdodbWrapper::getInstance();
      $query = "SELECT bug_id, value FROM {custom_field_string} ".
              "WHERE bug_id IN (".$formatted_bugid_list.") ".
              "AND field_id = (SELECT id FROM {custom_field} where name = ".$sql->db_param() . ")";
      $result = $sql->sql_query($query, array($customFieldName));
      while ($row = $sql->fetchObject($result)) {
         switch ($row->value) {
            case 'Recette':
               $iselAnoRecette->addIssue($row->bug_id);
               $iselAno->removeIssue($row->bug_id);
               break;
            case 'Production':
               $iselAnoProduction->addIssue($row->bug_id);
               $iselAno->removeIssue($row->bug_id);
               break;
         }
      }
/*
      self::$logger->error("all = ".$formatted_bugid_list);
      self::$logger->error("iselAnoRecette = ".implode(',', array_keys($iselAnoRecette->getIssueList())));
      self::$logger->error("iselAnoProduction = ".implode(',', array_keys($iselAnoProduction->getIssueList())));
      self::$logger->error("iselAno = ".implode(',', array_keys($iselAno->getIssueList())));
*/
      $timestampRangeList = $this->createTimestampRangeList($this->interval);

      foreach ($timestampRangeList as $label => $ttRange) {
         $periodStartTimestamp = $ttRange['start'];
         $periodEndTimestamp   = $ttRange['end'];

         // --- compute
         $anoRecetteElapsed[$periodStartTimestamp] = $iselAnoRecette->getElapsed($periodStartTimestamp, $periodEndTimestamp);
         $anoProductionElapsed[$periodStartTimestamp] = $iselAnoProduction->getElapsed($periodStartTimestamp, $periodEndTimestamp);
         $anoOtherElapsed[$periodStartTimestamp] = $iselAno->getElapsed($periodStartTimestamp, $periodEndTimestamp);
         $anoTotalElapsed[$periodStartTimestamp] = $anoRecetteElapsed[$periodStartTimestamp] +
                                                   $anoProductionElapsed[$periodStartTimestamp] +
                                                   $anoOtherElapsed[$periodStartTimestamp];

         $anoRecetteIssueList[$periodStartTimestamp] = $iselAnoRecette->getIssuesWithElapsed($periodStartTimestamp, $periodEndTimestamp);
         $anoProductionIssueList[$periodStartTimestamp] = $iselAnoProduction->getIssuesWithElapsed($periodStartTimestamp, $periodEndTimestamp);
         $anoOtherIssueList[$periodStartTimestamp] = $iselAno->getIssuesWithElapsed($periodStartTimestamp, $periodEndTimestamp);

         $anoRecetteNbIssues[$periodStartTimestamp] = count($anoRecetteIssueList[$periodStartTimestamp]);
         $anoProductionNbIssues[$periodStartTimestamp] = count($anoProductionIssueList[$periodStartTimestamp]);
         $anoOtherNbIssues[$periodStartTimestamp] = count($anoOtherIssueList[$periodStartTimestamp]);
         $anoTotalNbIssues[$periodStartTimestamp] = $anoRecetteNbIssues[$periodStartTimestamp] +
                                                    $anoProductionNbIssues[$periodStartTimestamp]+
                                                    $anoOtherNbIssues[$periodStartTimestamp];
      }

      $this->execData = array();
      $this->execData['anoRecetteElapsed'] = $anoRecetteElapsed;
      $this->execData['anoProductionElapsed'] = $anoProductionElapsed;
      $this->execData['anoOtherElapsed'] = $anoOtherElapsed;
      $this->execData['anoTotalElapsed'] = $anoTotalElapsed;

      $this->execData['anoRecetteIssueList']    = $anoRecetteIssueList;
      $this->execData['anoProductionIssueList'] = $anoProductionIssueList;
      $this->execData['anoOtherIssueList']      = $anoOtherIssueList;
      
      $this->execData['anoRecetteNbIssues'] = $anoRecetteNbIssues;
      $this->execData['anoProductionNbIssues'] = $anoProductionNbIssues;
      $this->execData['anoOtherNbIssues'] = $anoOtherNbIssues;
      $this->execData['anoTotalNbIssues'] = $anoTotalNbIssues;
   }

   public function getSmartyVariables($isAjaxCall = false) {

      $anoRecetteElapsed = $this->execData['anoRecetteElapsed'];
      $anoProductionElapsed = $this->execData['anoProductionElapsed'];
      $anoOtherElapsed = $this->execData['anoOtherElapsed'];
      $anoTotalElapsed = $this->execData['anoTotalElapsed'];
      $anoRecetteNbIssues = $this->execData['anoRecetteNbIssues'];
      $anoProductionNbIssues = $this->execData['anoProductionNbIssues'];
      $anoOtherNbIssues = $this->execData['anoOtherNbIssues'];
      $anoTotalNbIssues = $this->execData['anoTotalNbIssues'];

      $anoRecetteIssueList = $this->execData['anoRecetteIssueList'];
      $anoProductionIssueList = $this->execData['anoProductionIssueList'];
      $anoOtherIssueList = $this->execData['anoOtherIssueList'];


      $xaxis = array();
      foreach(array_keys($anoRecetteElapsed) as $timestamp) {
         $xaxis[] = '"'.date(T_('Y-m-d'), $timestamp).'"';
      }
      $json_xaxis = Tools::array2plot($xaxis);

      $jsonRecette = Tools::array2plot(array_values($anoRecetteElapsed));
      $jsonProd  = Tools::array2plot(array_values($anoProductionElapsed));
      $jsonOther = Tools::array2plot(array_values($anoOtherElapsed));
      //$jsonTotal    = Tools::array2plot(array_values($anoTotalElapsed));

      $graphData = "[$jsonRecette,$jsonProd,$jsonOther]";
      $graphDataColors = '["#FFF494", "#fcbdbd", "#C2DFFF"]';
      $labels1 = '["Ano Recette", "Ano Prod", "Ano Autre"]';

      // --------------
      $tableData = array();
      foreach ($anoRecetteElapsed as $timestamp => $elapsedRecette) {
         $elapsedProduction = $anoProductionElapsed[$timestamp];
         $elapsedOther = $anoOtherElapsed[$timestamp];
         $elapsedTotal = $anoTotalElapsed[$timestamp];

         $formattedDate = date(T_('Y-m-d'), $timestamp);

         $anoRecetteExtRefList = array();
         foreach ($anoRecetteIssueList[$timestamp] as $issue) {
            $anoRecetteExtRefList[] = $issue->getTcId() ? $issue->getTcId() : 'm'.$issue->getId();
         }
         $anoProductionExtRefList = array();
         foreach ($anoProductionIssueList[$timestamp] as $issue) {
            $anoProductionExtRefList[] = $issue->getTcId() ? $issue->getTcId() : 'm'.$issue->getId();
         }
         $anoOtherExtRefList = array();
         foreach ($anoOtherIssueList[$timestamp] as $issue) {
            $anoOtherExtRefList[] = $issue->getTcId() ? $issue->getTcId() : 'm'.$issue->getId();
         }
         sort($anoRecetteExtRefList);
         sort($anoProductionExtRefList);
         sort($anoOtherExtRefList);


         $tableData[$formattedDate] = array(
             'anoRecetteElapsed' => round($elapsedRecette, 2),
             'anoProductionElapsed' => round($elapsedProduction, 2),
             'anoOtherElapsed' => round($elapsedOther, 2),
             'anoTotalElapsed' => round($elapsedTotal, 2),
             'anoRecetteNbIssues' => $anoRecetteNbIssues[$timestamp],
             'anoProductionNbIssues' => $anoProductionNbIssues[$timestamp],
             'anoOtherNbIssues' => $anoOtherNbIssues[$timestamp],
             'anoTotalNbIssues' => $anoTotalNbIssues[$timestamp],
             'anoRecetteBugidList' => implode(', ', $anoRecetteExtRefList),
             'anoProductionBugidList' => implode(', ', $anoProductionExtRefList),
             'anoOtherBugidList' => implode(', ', $anoOtherExtRefList),
         );
      }


      $smartyVariables = array(
         'rsiTypeAnoLoadIndicator_tableData' => $tableData,
         'rsiTypeAnoLoadIndicator_jqplotData' => $graphData,
         'rsiTypeAnoLoadIndicator_jqplotSeriesColors' => $graphDataColors,
         'rsiTypeAnoLoadIndicator_jqplotLegendLabels' => $labels1,
         'rsiTypeAnoLoadIndicator_jqplotXaxis' => $json_xaxis,
      );

      if (false == $isAjaxCall) {
         $smartyVariables['rsiTypeAnoLoadIndicator_ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables['rsiTypeAnoLoadIndicator_ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

RsiTypeAnoLoadIndicator::staticInit();
