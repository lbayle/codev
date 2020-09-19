
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
 * Description of HelloWorldIndicator
 *
 * @author lob
 */
class ImportRelationshipTreeToCommand extends IndicatorPluginAbstract {

   const OPTION_ISSUE_ID = 'issueId';
   const OPTION_CMD_ID = 'commandId';
   const OPTION_IS_INCLUDE_PARENT_ISSUE = 'isIncludeParentIssue';

   private static $logger;
   private static $domains;
   private static $categories;

   // params from PluginDataProvider

   // config options from Dashboard
   private $teamId;
   private $issueId;
   private $commandId;
   private $command;
   private $isIncludeParentIssue;

   // internal
   private $sessionUserId;
   protected $execData;


   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);

      self::$domains = array (
         self::DOMAIN_IMPORT_EXPORT,
      );
      self::$categories = array (
         self::CATEGORY_IMPORT
      );
   }

   public static function getName() {
      return T_('Add issues to a command by following the relationship structure');
   }
   public static function getDesc($isShortDesc = true) {
      return T_('Import a mantis parent-child relationship issue structure to a command WBS structure');
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
         'js_min/datepicker.min.js',
         'lib/select2/select2.min.js',
         'js_min/tabs.min.js',
      );
   }


   /**
    *
    * @param \PluginDataProviderInterface $pluginMgr
    * @throws Exception
    */
   public function initialize(PluginDataProviderInterface $pluginDataProv) {

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
         $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      } else {
         throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
      }

      if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID)) {
         $this->teamId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_TEAM_ID);
      } else {
         throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_TEAM_ID);
      }
      $this->isIncludeParentIssue = false;
   }

   /**
    * User preferences are saved by the Dashboard
    *
    * @param type $pluginSettings
    */
   public function setPluginSettings($pluginSettings) {

      if (NULL != $pluginSettings) {
         // override default with user preferences
         if (array_key_exists(self::OPTION_ISSUE_ID, $pluginSettings)) {
            $this->issueId = $pluginSettings[self::OPTION_ISSUE_ID];
         }
         if (array_key_exists(self::OPTION_CMD_ID, $pluginSettings)) {
            $this->commandId = $pluginSettings[self::OPTION_CMD_ID];
         }
         if (array_key_exists(self::OPTION_IS_INCLUDE_PARENT_ISSUE, $pluginSettings)) {
            $this->isIncludeParentIssue = $pluginSettings[self::OPTION_IS_INCLUDE_PARENT_ISSUE];
         }
      }
   }

   /**
    *
    * @return type
    */
   public function importIssues() {

      $this->command = CommandCache::getInstance()->getCommand($this->commandId);
      $wbsRootId = $this->command->getWbsid();

      $strActionLogs = "-------------\n";
      $strActionLogs .= $this->addChild($this->issueId, $wbsRootId, $wbsRootId);

      $data = array (
         'actionLogs' => htmlentities($strActionLogs),
         );
      return $data;
   }

   /**
    * /!\ recursive function /!\
    *
    * @param type $issueId     will be wbsFolder if has child, or a leaf if none
    * @param type $wbsRootId   root WBS of the command
    * @param type $wbsParentId wbsFolder I'll be add to
    * @return string
    */
   private function addChild($issueId, $wbsRootId, $wbsParentId) {

      $issue = IssueCache::getInstance()->getIssue($issueId);
      $relationships = $issue->getRelationships();
      $relType = Constants::$relationship_parent_of;

      if (array_key_exists($relType, $relationships)) {
         // --- Yes, I do have children, so I'm a wbsFolder

         // (Optional) add myself to the command
         if ($this->isIncludeParentIssue) {
            $this->command->addIssue($issueId, true, $wbsParentId);
            $strActionLogs .= "add issue: [$issueId] ".$issue->getSummary()."\n";
         }

         // add myself as a wbsFolder
         $folderName = "[$issueId] ".$issue->getSummary();
         $subFolderId = WBSElement::getIdByTitle("[$issueId]%", $wbsRootId, $wbsParentId, TRUE, TRUE);
         if (NULL !== $subFolderId) {
            $folderId = $subFolderId;

            $subFolder = new WBSElement($subFolderId, $wbsRootId);
            $prevTitle = $subFolder->getTitle();
            //$strActionLogs .= "wbsFolder already exists : $prevTitle\n";

            if ($folderName !== $subFolder->getTitle()) {
               $strActionLogs .= "rename wbsFolder to : $folderName\n";
               $subFolder->setTitle($folderName);
               $subFolder->update();
            }
         } else {
            // subFolder does not exist, create it.
            $newSubFolder = new WBSElement(null, $wbsRootId, null, $wbsParentId, NULL, $folderName);
            $folderId = $newSubFolder->getId();
            $strActionLogs .= "create wbsFolder: $folderName\n";
         }
         // now recursively add my children !
         foreach ($relationships[$relType] as $childId) {
            $strActionLogs .= $this->addChild($childId, $wbsRootId, $folderId);
         }
      } else {
         // --- I don't have children, I'm a leaf
         if (!array_key_exists($issueId, $this->command->getIssueSelection()->getIssueList())) {
            $this->command->addIssue($issueId, true, $wbsParentId);
            $strActionLogs .= "add issue: [$issueId] ".$issue->getSummary()."\n";
         }
//         else {
//            $strActionLogs .= "issue already in command : [$issueId] ".$issue->getSummary()."\n";
//         }
      }
      return $strActionLogs;
   }

  /**
    *
    */
   public function execute() {

      // check sesionUser must be Manager !
      $sessionUser = UserCache::getInstance()->getUser($this->sessionUserId);
      $accessDenied = $sessionUser->isTeamManager($this->teamId) ? '0' : '1';

      // --- get command list
      $team = TeamCache::getInstance()->getTeam($this->teamId);
      $cmdList= $team->getCommands();
      $teamCommands = array();
      foreach($cmdList as $cmdId => $cmd) {
         $teamCommands[$cmdId] = $cmd->getName();
      }


      // --- get all tasks (regularProjects + sidetasksProjects)
      $hideStatusAndAbove = 0;
      $isHideResolved = false;
      $issueList = array();
      $teamProjects = $team->getProjects();
      foreach ($teamProjects as $projectid => $pname) {
         $project = ProjectCache::getInstance()->getProject($projectid);
         $prjIssueList = $project->getIssues(0, $isHideResolved, $hideStatusAndAbove);
         $issueList = array_merge($issueList, $prjIssueList);
      }
      $taskList = array();
      foreach ($issueList as $issue) {
         $taskList[$issue->getId()] = $issue->getId().' : '.$issue->getSummary();
      }

      $this->execData = array (
         'teamCommands' => $teamCommands,
         'taskList' => $taskList,
         'accessDenied' => $accessDenied,
         );
      return $this->execData;

   }

   /**
    *
    * @param boolean $isAjaxCall
    * @return array
    */
   public function getSmartyVariables($isAjaxCall = false) {

      $prefix='ImportRelationshipTreeToCommand_';

      $taskListSmarty = SmartyTools::getSmartyArray($this->execData['taskList'], $this->issueId);


      $smartyVariables = array(
         $prefix.'teamCommands' => $this->execData['teamCommands'],
         $prefix.'taskList' => $taskListSmarty,
         $prefix.'accessDenied' => $this->execData['accessDenied'],
      );

      if (false == $isAjaxCall) {
         $smartyVariables[$prefix.'ajaxFile'] = self::getSmartySubFilename();
         $smartyVariables[$prefix.'ajaxPhpURL'] = self::getAjaxPhpURL();
      }
      return $smartyVariables;
   }

   public function getSmartyVariablesForAjax() {
      return $this->getSmartyVariables(true);
   }

}

// Initialize static variables
ImportRelationshipTreeToCommand::staticInit();
