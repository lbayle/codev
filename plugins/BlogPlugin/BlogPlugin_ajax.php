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

if(Tools::isConnectedUser() && filter_input(INPUT_POST, 'action')) {

   $logger = Logger::getLogger("BlogPlugin_ajax");
   $action = filter_input(INPUT_POST, 'action');
   $dashboardId = Tools::getSecurePOSTStringValue('dashboardId');
   $sessionUserid = $_SESSION['userid'];
   $sessionTeamid = $_SESSION['teamid'];

   if(!isset($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId])) {
      $logger->error("PluginDataProvider not set (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider not set");
   }
   $pluginDataProvider = unserialize($_SESSION[PluginDataProviderInterface::SESSION_ID.$dashboardId]);
   if (FALSE == $pluginDataProvider) {
      $logger->error("PluginDataProvider unserialize error (dashboardId = $dashboardId");
      Tools::sendBadRequest("PluginDataProvider unserialize error");
   }
   
   // --------------------------------------
   if ('getUserList' === $action) {

      echo $jsonData;
   } else if ('getTeamList' === $action) {

      echo $jsonData;

   // --------------------------------------
   } else if ('getUserSettings' === $action) {
      // send values for blogPluginOptionDialog
      $blogManager  = new BlogManager();
      $userSettings = $blogManager->getUserOptions($sessionUserid);

      $statusMsg = 'SUCCESS';
      $data = array(
        'statusMsg' => $statusMsg,
        'userSettings' => $userSettings,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('refreshAllPosts' === $action) {
      // returns all posts in Html (content of div: blogPlugin_bpostsContainer)
      try {
         // reload all bposts with new filters applied
         $blogManager = new BlogManager();
         $smartyHelper = new SmartyHelper();
         $postList   = $blogManager->getPosts($sessionUserid, $sessionTeamid);
         foreach ($postList as $id => $bpost) {
            $smartyVariable = $bpost->getSmartyStruct($sessionUserid);
            $smartyHelper->assign('bpost', $smartyVariable);
            $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());
            $blogPosts_htmlContent .= $html;
         }
         $statusMsg = 'SUCCESS';

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }
      $data = array(
        'statusMsg' => $statusMsg,
        'blogPosts_htmlContent' => $blogPosts_htmlContent,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('updateUserSettings' === $action) {

      $recipient          = Tools::getSecurePOSTStringValue('recipient');
      $category           = Tools::getSecurePOSTIntValue('category');
      $severity           = Tools::getSecurePOSTIntValue('severity');
      $displayHiddenPosts = Tools::getSecurePOSTIntValue('displayHiddenPosts');

      // TODO check options have valid values

      $userSettings = array(
          BlogManager::OPTION_FILTER_RECIPIENT => $recipient,
          BlogManager::OPTION_FILTER_CATEGORY => $category,
          BlogManager::OPTION_FILTER_SEVERITY => $severity,
          BlogManager::OPTION_FILTER_DISPLAY_HIDDEN_POSTS => $displayHiddenPosts,
      );

      // Note: settings will not be saved in dashboardSettings, we want them to be
      //       more persistent. otherwise, removing the plugin from the dashboard looses
      //       the settings.
      $userSettingsJson = json_encode($userSettings);
      Config::setValue(Config::id_blogPluginOptions, $userSettingsJson, Config::configType_string, NULL, 0, $sessionUserid, 0);

      try {
         // reload all bposts with new filters applied
         $blogManager = new BlogManager();
         $smartyHelper = new SmartyHelper();
         $postList   = $blogManager->getPosts($sessionUserid, $sessionTeamid);
         foreach ($postList as $id => $bpost) {
            $smartyVariable = $bpost->getSmartyStruct($sessionUserid);
            $smartyHelper->assign('bpost', $smartyVariable);
            $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());
            $blogPosts_htmlContent .= $html;
         }
         $statusMsg = 'SUCCESS';

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }
      $data = array(
        'statusMsg' => $statusMsg,
        'blogPosts_htmlContent' => $blogPosts_htmlContent,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('addBlogPost' === $action) {

      $category        = Tools::getSecurePOSTIntValue('category');
      $severity        = Tools::getSecurePOSTIntValue('severity');
      $blogpostSummary = Tools::getSecurePOSTStringValue('summary');
      $blogpostText    = Tools::getSecurePOSTStringValue('text');
      $expDate         = Tools::getSecurePOSTStringValue('expDate', 'undefined'); // empty is allowed
      $grpDest         = Tools::getSecurePOSTStringValue('grpDest');

/*
      $logger->error("summary $blogpostSummary");
      $logger->error("text $blogpostText");
      $logger->error("expDate $expDate");
      $logger->error("grpDest $grpDest");
*/
      $statusMsg = 'SUCCESS';

      $catName = Config::getVariableValueFromKey(Config::id_blogCategories, $category);
      if (NULL == $catName) {
         $logger->error("addBlogPost: unknown category: $category");
         Tools::sendBadRequest('Add blogpost: invalid data');
      }
      $severityName = BlogPost::getSeverityName($severity);
      if (NULL == $severityName) {
         $logger->error("addBlogPost: unknown severity: $severity");
         Tools::sendBadRequest('Add blogpost: invalid data');
      }
      if (('undefined' === $expDate) || ('' === $expDate)) {
         $expireTimestamp = 0; // no expire
      } else {
         $expireTimestamp = Tools::date2timestamp($expDate);
         if (0 === $expireTimestamp) {
            $logger->error("addBlogPost: invalid extDate: $expDate");
            Tools::sendBadRequest('Add blogpost: invalid data');
         }
      }
      switch ($grpDest) {
         case 'dest_team':
            $destUserid=0;
            $destTeamid = $sessionTeamid;
            break;
         case 'dest_user':
            $destTeamid=0;
            $destUserid = Tools::getSecurePOSTIntValue('userDest');
            break;
         default:
            $logger->error("addBlogPost: unknown grpDest: $grpDest");
            Tools::sendBadRequest('Add blogpost: invalid data');
      }

      try {
         $blogpostId = BlogPost::create($sessionUserid, $severity, $category, $blogpostSummary, $blogpostText, $destUserid, 0, $destTeamid, $expireTimestamp);
         if (NULL === $blogpostId) {
            $logger->error("BlogPost::create($sessionUserid, $severity, $category, $blogpostSummary, $blogpostText, $destUserid, 0, $destTeamid, $expireTimestamp)");
            $statusMsg = 'ERROR: Failed to add blog post';
         } else {
            // get new html post
            $blogpost = BlogPostCache::getInstance()->getBlogPost($blogpostId);
            $smartyVariable = $blogpost->getSmartyStruct($sessionUserid);
            $smartyHelper = new SmartyHelper();
            $smartyHelper->assign('bpost', $smartyVariable);
            $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());
         }
      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }

      $data = array(
        'statusMsg' => $statusMsg,
        'blogpostId' => $blogpostId,
        'bpost_htmlContent' => $html,
      );
      
      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('AckPost' === $action) {
      $blogpostId = Tools::getSecurePOSTIntValue('blogpostId');
      $statusMsg = 'SUCCESS';
      try {
         $blogpost = new BlogPost($blogpostId);

         // TODO check user rights

         // do the job
         $blogpost->setAction($sessionUserid, BlogPost::actionType_ack, true, time());

         // get updated Html post
         $smartyVariable = $blogpost->getSmartyStruct($sessionUserid);
         $smartyHelper = new SmartyHelper();
         $smartyHelper->assign('bpost', $smartyVariable);
         $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }

      $data = array(
        'statusMsg' => $statusMsg,
        'blogpostId' => $blogpostId,
        'bpost_htmlContent' => $html,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('DeletePost' === $action) {
      $blogpostId = Tools::getSecurePOSTIntValue('blogpostId');
      $statusMsg = 'SUCCESS';
      try {
         $blogpost = new BlogPost($blogpostId);

         // TODO check user rights

         // do the job
         $blogpost->delete();

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }

      $data = array(
        'statusMsg' => $statusMsg,
        'blogpostId' => $blogpostId,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('HidePost' === $action) {
      $blogpostId = Tools::getSecurePOSTIntValue('blogpostId');
      $statusMsg = 'SUCCESS';
      try {
         $blogpost = new BlogPost($blogpostId);

         // TODO check user rights

         // do the job
         $blogpost->setAction($sessionUserid, BlogPost::actionType_hide, true, time());

         $blogManager = new BlogManager();
         $userOptions = $blogManager->getUserOptions($sessionUserid);
         $isDisplayHiddenPosts = $userOptions[BlogManager::OPTION_FILTER_DISPLAY_HIDDEN_POSTS];

         if ($isDisplayHiddenPosts) {
            // get updated Html post
            $smartyVariable = $blogpost->getSmartyStruct($sessionUserid);
            $smartyHelper = new SmartyHelper();
            $smartyHelper->assign('bpost', $smartyVariable);
            $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());
         } else {
            // not to be displayed
            $html = 'isDisplayHiddenPosts=false, no data will be displayed';
         }

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }

      $data = array(
        'statusMsg' => $statusMsg,
        'blogpostId' => $blogpostId,
        'isDisplayHiddenPosts' => $isDisplayHiddenPosts,
        'bpost_htmlContent' => $html,
      );

      // return data
      $jsonData = json_encode($data);
      echo $jsonData;

   // --------------------------------------
   } else if ('UnhidePost' === $action) {
      $blogpostId = Tools::getSecurePOSTIntValue('blogpostId');
      $statusMsg = 'SUCCESS';
      try {
         $blogpost = new BlogPost($blogpostId);

         // TODO check user rights

         // do the job
         $blogpost->setAction($sessionUserid, BlogPost::actionType_hide, false, time());

         // get updated Html post
         $smartyVariable = $blogpost->getSmartyStruct($sessionUserid);
         $smartyHelper = new SmartyHelper();
         $smartyHelper->assign('bpost', $smartyVariable);
         $html = $smartyHelper->fetch(BlogPlugin::getSmartySubFilename());

      } catch (Exception $ex) {
         $statusMsg = "ERROR: ".$ex->getMessage();
      }

      $data = array(
        'statusMsg' => $statusMsg,
        'blogpostId' => $blogpostId,
        'bpost_htmlContent' => $html,
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


