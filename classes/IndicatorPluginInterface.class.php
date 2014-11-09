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
 *
 * @author lob
 */
interface IndicatorPluginInterface {

   const INDICATOR_PLUGINS_DIR = 'plugins';

   // CodevTT
   const DOMAIN_HOMEPAGE = 'Homepage';
   const DOMAIN_COMMAND = 'Command';
   const DOMAIN_MACRO_COMMAND = 'MacroCommand';
   const DOMAIN_SERVICE_CONTRACT = 'ServiceContract';
   const DOMAIN_TEAM = 'Team';
   const DOMAIN_USER = 'User';
   const DOMAIN_PROJECT = 'Project';

   // KPI category
   const CATEGORY_PLANNING  = 'Planning';  // backlog
   const CATEGORY_ROADMAP   = 'Roadmap';   // deadlines
   const CATEGORY_QUALITY   = 'Quality';   // bugs
   const CATEGORY_FINANCIAL = 'Financial'; // budgetDrift
   const CATEGORY_TEAM      = 'Team';      // EffortEstim reliability, backlogVariation
   const CATEGORY_RISK      = 'Risk';
   const CATEGORY_ACTIVITY  = 'Activity';  // LoadPerUser, LoadPerJob?

   /**
    * Short name (title)
    */
   public static function getName();

   public static function getDesc();
   public static function getAuthor();
   public static function getVersion();

   /**
    *
    * @return array of applicable domains
    */
   public static function getDomains();
   public static function isDomain($domain);

   /**
    * Only one category should be returned, but who knows...
    *
    * @return array of categories
    */
   public static function getCategories();
   public static function isCategory($category);

   /**
    * returns the SMARTY .html filename that will display the results.
    *
    * The file must be included in the main SMARTY page:
    * {include file="indicator_plugins/myIndicator.html"}
    */
   public static function getSmartyFilename();

   /**
    * html smarty content to be processed by the ajax call
    */
   public static function getSmartySubFilename();

   /**
    * html code for the Dashboard 'add indicator' dialogbox
    * that displays the pluginSettings attributes
    * (called by dashboard_ajax.php)
    */
   public static function getCfgFilemame();

   /**
    * ajax page handling the plugin actions
    */
   public static function getAjaxPhpURL();

   /**
    * the plugin may need some external libraries.
    * this function returns an array of .js files
    * that the dashboard will load when displaying
    * the plugin
    */
   public static function getJsFiles();

   /**
    * the plugin may need some css files.
    * this function returns an array of .css files
    * that the dashboard will load when displaying
    * the plugin
    */
   public static function getCssFiles();

   /**
    * Options must be saved in DB and are specific per [team,user] & dashboard.
    * It is the responsibility of the Dashboard class to save those settings.
    *
    * examples: isGraphOnly, dateRange(defaultRange|currentWeek|currentMonth|noDateLimit), ...
    *
    */
   public function setPluginSettings($pluginSettings);
   //public function getPluginSettings();

   /**
    * Set pluginDataProvider and checks that all the mandatory data is
    * available for the plugin to display correctly
    * 
    * @param PluginDataProviderInterface $pluginDataProvider
    */
   public function initialize(PluginDataProviderInterface $pluginDataProvider);


   /**
    * result of the Indicator
    *
    * @param IssueSelection $inputIssueSel task list
    * @params array $params all other parameters needed by this indicator (timestamp, ...)
    * @return mixed (standard PHP structure)
    */
   public function execute();

   /**
    * Get the result of the execute() method in a SMARTY format.
    * The SMARTY template is furnished by getSmartyFilename().
    *
    * Add to smartyHelper:
    *  foreach ($data as $smartyKey => $smartyVariable) {
    *     $smartyHelper->assign($smartyKey, $smartyVariable);
    *  }
    *
    * @return array structure for SMARTY variables
    *
    */
   public function getSmartyVariables();
   
   /**
    * a subset of variables usefull for the ajax php call
    * @return type
    */
   //public function getSmartyVariablesForAjax();

}
