<?php
/*
 This file is part of CoDevTT.

CoDev-Timetracking is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

CoDevTT is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

include_once('Logger.php');

include_once('user.class.php');
include_once('project.class.php');
include_once('team.class.php');
include_once('blogpost_cache.class.php');


// ================================================
/**
 * container class
 */
class BlogActivity {

   const action_read = 0;

   public $id;
   public $blogPost_id;
   public $user_id;
   public $action;
   public $date;

   public function __construct($id, $blogPost_id, $user_id, $action, $date) {
      $this->id          = $id;
      $this->blogPost_id = $blogPost_id;
      $this->user_id     = $user_id;
      $this->action      = $action;
      $this->date        = $date;
   }

   /**
    * Literal name for the given action id
    *
    * @param int $action
    * @return string actionName or NULL if unknown
    */
   public static function getActionName($action) {

      switch ($action) {
         case action_read:
            return T_('Read');
         default:
            #return T_('unknown');
            return NULL;
      }
   }

} // class BlogActivity



// ================================================
/**
 * Blog post structure
 *
 */
class BlogPost {

   const severity_low    = 0;
   const severity_normal = 1;
   const severity_high   = 2;

   private $logger;

   public $id;
   public $src_user_id;
   public $dest_user_id;
   public $dest_project_id;
   public $dest_team_id;
   public $severity;
   public $category;
   public $summary;
   public $content;
   public $date_submitted;
   public $date_expire;
   public $color;

   private $activityList;


   // -----------------------------------------
   public function __construct($post_id) {
      $this->id = $post_id;
      $this->logger = Logger::getLogger(__CLASS__);

      $query = "SELECT * FROM `codev_blog_table` WHERE id = $this->id";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = mysql_fetch_object($result);

      $this->date_submitted  = $row->date_submitted;
      $this->src_user_id     = $row->src_user_id;
      $this->dest_user_id    = $row->dest_user_id;
      $this->dest_project_id = $row->dest_project_id;
      $this->dest_team_id    = $row->dest_team_id;
      $this->severity        = $row->severity;
      $this->category        = $row->category;
      $this->summary         = $row->summary;
      $this->content         = $row->content;
      $this->date_expire     = $row->date_expire;
      $this->color           = $row->color;

      #$this->activityList = $this->getActivityList();
   }

   /**
    * Literal name for the given severity id
    *
    * @param int $severity
    * @return string severityName or NULL if unknown
    */
   public static function getSeverityName($severity) {
      switch ($severity) {
         case severity_low:
            return T_('Low');
         case severity_normal:
            return T_('Normal');
         case severity_high:
            return T_('High');
         default:
            #return T_('unknown');
            return NULL;
      }

   }


   // -----------------------------------------
   /**
    * create a new post
    *
    * @param int $src_user_id
    * @param int $severity
    * @param string $category
    * @param string $summary
    * @param string $content
    * @param int $dest_user_id
    * @param int $dest_project_id
    * @param int $dest_team_id
    * @param int $date_expire
    * @param int $color
    *
    * @return blogPost id or '0' if failed
    */
   public static function create($src_user_id, $severity, $category, $summary, $content,
         $dest_user_id=0, $dest_project_id=0, $dest_team_id=0,
         $date_expire=0, $color=0) {

      global $logger;

      // format values to avoid SQL injections
      $fSeverity   = mysql_real_escape_string($severity);
      $fCategory   = mysql_real_escape_string($category);
      $fSummary    = mysql_real_escape_string($summary);
      $fContent    = mysql_real_escape_string($content);
      $fDateExpire = mysql_real_escape_string($date_expire);

      $date_submitted = time(); # mktime(0, 0, 0, date('m'), date('d'), date('Y'));

      $query = "INSERT INTO `codev_blog_table` ".
               "(`date_submitted`, `src_user_id`, `dest_user_id`, `dest_project_id`, `dest_team_id`, ".
               "`severity`, `category`, `summary`, `content`, `date_expire`, `color`) ".
               "VALUES ('$date_submitted','$src_user_id','$dest_user_id','$dest_project_id','$dest_team_id',".
               "'$fSeverity','$fCategory','$fSummary','$fContent','$fDateExpire','$color');";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED $query</span><br>";
         return 0;
      }
      $blogPost_id = mysql_insert_id();

      return $blogPost_id;
   }

   // -----------------------------------------
   /**
    * Delete a post and all it's activities.
    *
    * Note: Only administrators & the owner of the post are allowed to delete.
    *
    * @param int $blogPost_id
    */
   public static function delete($blogPost_id) {

      global $logger;

      // TODO check admin/ user access rights

      $query = "DELETE FROM `codev_blog_activity_table` WHERE blog_id = $this->id;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
         $query = "DELETE FROM `codev_blog_table` WHERE id = $this->id;";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }


   // -----------------------------------------
   /**
    *
    * @param int $user_id
    * @param string $action
    * @param int $date
    *
    * @return activity id or '0' if failed
    */
   public static function addActivity($blogPost_id, $user_id, $action, $date) {

      global $logger;

      // check if $blogPost_id exists (foreign keys do not exist in MyISAM)

      $fPostId    = mysql_real_escape_string($blogPost_id);


      $query = "SELECT id FROM `codev_blog_table` where id= $fPostId";
      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      if (0 == mysql_num_rows($result)) {
         $logger->error("addActivity: blogPost '$fPostId' does not exist !");
         return 0;
      }

      // add activity
      $fUserId   = mysql_real_escape_string($user_id);
      $fAction   = mysql_real_escape_string($action);
      $fDate     = mysql_real_escape_string($date);
      $query = "INSERT INTO `codev_blog_activity_table` ".
            "(`blog_id`, `user_id`, `action`, `date`) ".
            "VALUES ('$fPostId','$fUserId','$fAction','$fDate')";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         return 0;
      }
      $activity_id = mysql_insert_id();

      return $activity_id;
   }

   // -----------------------------------------
   /**
    *
    */
   public function getActivityList() {

      if (NULL == $this->activityList) {
         $query = "SELECT * FROM `codev_blog_activity_table` WHERE blog_id = $this->id ORDER BY date DESC";
         $result = mysql_query($query);
         if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->activityList = array();
         while($row = mysql_fetch_object($result)) {
            $activity = new BlogActivity($row->id, $row->blog_id, $row->user_id, $row->action, $row->date);
            $this->activityList[$row->id] = $activity;
         }
      }
      return $this->activityList;
   }


   // -----------------------------------------
   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $postB
    *
    * criteria: date_submission, date_expired, severity
    *
    * @param BlogPost $postB the object to compare to
    */
   public function compareTo($postB) {

      // TODO
      return true;
   }

} // class BlogPost


// ================================================
/**
 *
 *
 *
 */
class BlogManager {

   private $logger;

   private $categoryList;
   private $severityList;

   // -----------------------------------------
   public function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

   }


   // -----------------------------------------
   /**
    * available categories are stored in codev_config_table.
    * @return array (id => name)
    */
   public function getCategoryList() {

      if (NULL == $this->categoryList) {
         $this->categoryList = Config::getValue(Config::id_blogCategories);
         ksort($this->categoryList);
      }
      return $this->categoryList;
   }

   // -----------------------------------------
   /**
    * available severity values
    * @return array (id => name)
    */
   public function getSeverityList() {

      if (NULL == $this->severityList) {
         $this->severityList = array();

         for ($i = 0; $i < 10; $i++) {
            $sevName =  BlogPost::getSeverityName($i);
            if (NULL == $sevName) {
               break;
            }
            $this->severityList[$i] = $sevName;
         }
      }
      return $this->severityList;
   }


   // -----------------------------------------
   /**
    * return the posts to be displayed for a given user,
    * depending on it's [userid, teams, projects] and personal filter preferences.
    *
    * we want:
    * - all posts assigned to the user
    * - all posts assigned to a team where the user is member
    * - all posts assigned to a project that is in one of the user's teams
    *
    *
    * @param int $user_id
    *
    * @return array BlogPost
    */
   public function getPosts($user_id) {

      $user = UserCache::getInstance()->getUser($user_id);
      $teamList = $user->getTeamList();
      $projList = $user->getProjectList();

      $formattedTeamList = implode(',', array_keys($teamList));
      $formattedProjList = implode(',', array_keys($projList));


      $query = "SELECT id FROM `codev_blog_table` ";
      $query .= "WHERE dest_user_id = $user_id ";
      $query .= "OR (dest_user_id = 0 AND dest_team_id IN ($formattedTeamList)) ";
      $query .= "OR (dest_user_id = 0 AND dest_team_id IN (0,$formattedTeamList) AND dest_project_id IN ($formattedProjList)) ";
      $query .= "ORDER BY date_submitted DESC";

      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $postList = array();
      while($row = mysql_fetch_object($result)) {
         $post = BlogPostCache::getInstance()->getBlogPost($row->id);
         $postList[$row->id] = $post;
      }

      return $postList;
   }

   // -----------------------------------------
   /**
    * return the posts submitted by a given user,
    *
    * @param int $user_id
    *
    * @return array BlogPost
    */
   public function getSubmittedPosts($user_id) {

      $query = "SELECT id FROM `codev_blog_table` where src_user_id= $user_id";
      $result = mysql_query($query);
      if (!$result) {
         $this->logger->error("Query FAILED: $query");
         $this->logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      $submittedPosts = array();
      while($row = mysql_fetch_object($result)) {
         $submittedPosts[$row->id] = BlogPostCache::getInstance()->getBlogPost($row->id);
      }

      return $submittedPosts;
   }

} // class BlogManager
























