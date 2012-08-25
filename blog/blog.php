<?php
require('../include/session.inc.php');

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

require('../path.inc.php');

class BlogController extends Controller {

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

         $blogManager = new BlogManager();

         $src_user_id  = $session_user->getId();
         $severity = BlogPost::severity_normal;
         $category = 0;
         $summary = 'Welcome to the real world';
         $content = 'Hello world !<br>The quick brown fox jumps over the lazy dog<br>Casse toi pauv\' con !';
         $dest_team_id = 4;
         $dest_user_id = 0;
         $dest_project_id = 0;
         $date_expire = 0;
         $color = 0;

         $blogPost_id = BlogPost::create($src_user_id, $severity, $category, $summary, $content,
            $dest_user_id, $dest_project_id, $dest_team_id, $date_expire, $color);

         $postList = $blogManager->getPosts($session_user->getId());
         $blogPosts = $this->getBlogPosts($postList);
         $this->smartyHelper->assign('blogPosts', $blogPosts);

         $categories = $blogManager->getCategoryList();
         $this->smartyHelper->assign('categoryList', $categories);

         $severities = $blogManager->getSeverityList();
         $this->smartyHelper->assign('severityList', $severities);
      }
   }

   /**
    * @param BlogPost[] $postList
    * @return mixed[]
    */
   private function getBlogPosts(array $postList) {
      $blogPosts = array();

      foreach ($postList as $id => $bpost) {
         $srcUser = UserCache::getInstance()->getUser($bpost->src_user_id);

         $item = array();

         // TODO
         $item['category'] = Config::getVariableValueFromKey(Config::id_blogCategories, $bpost->category);
         $item['severity'] = BlogPost::getSeverityName($bpost->severity);
         $item['summary'] = $bpost->summary;
         $item['content'] = $bpost->content;
         $item['date_submitted'] = date('Y-m-d G:i',$bpost->date_submitted);
         $item['from']    = $srcUser->getRealname();

         // find receiver
         if (0 != $bpost->dest_user_id) {
            $destUser = UserCache::getInstance()->getUser($bpost->dest_user_id);
            $item['to'] = $destUser->getRealname();
         } else if (0 != $bpost->dest_team_id) {
            $team = TeamCache::getInstance()->getTeam($bpost->dest_team_id);
            $item['to'] = $team->getName();
         } else if (0 != $bpost->dest_project_id) {
            $destProj = ProjectCache::getInstance()->getProject($bpost->dest_project_id);
            $item['to'] = $destProj->getName();
         } else {
            $item['to'] = '?';
         }

         $item['activity'] = 'activities...';
         $item['buttons'] = "<input type='button' value='".T_('Ack')."' onclick='javascript: ackPost(".$bpost->id.")' />";
         $item['buttons'] .= "<input type='button' value='".T_('Hide')."' onclick='javascript: hidePost(".$bpost->id.")' />";

         // TODO only if i'm the owner
         $item['buttons'] .= "<input type='button' value='".T_('Delete')."' onclick='javascript: deletePost(".$bpost->id.")' />";

         $item['isHidden'] = '0';

         $blogPosts[$id] = $item;
      }
      return $blogPosts;
   }

}

// ========== MAIN ===========
BlogController::staticInit();
$controller = new BlogController('Blog','Blog');
$controller->execute();

?>
