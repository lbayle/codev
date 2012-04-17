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

require '../path.inc.php';

include_once('Logger.php');

include_once('user.class.php');
include_once('project.class.php');
include_once('team.class.php');


// ================================================
/**
 * container class
 */
class BlogActivity {

   const action_read = 1;

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
         $dest_user_id=NULL, $dest_project_id=NULL, $dest_team_id=NULL,
         $date_expire=NULL, $color=0) {

      // format values to avoid SQL injections
      $fSeverity   = mysql_real_escape_string($severity);
      $fCategory   = mysql_real_escape_string($category);
      $fSummary    = mysql_real_escape_string($summary);
      $fContent    = mysql_real_escape_string($content);
      $fDateExpire = mysql_real_escape_string($date_expire);

      $date_submitted = mktime(0, 0, 0, date('m'), date('d'), date('Y'));

      $query = "INSERT INTO `codev_blog_table` ".
               "(`date_submitted`, `src_user_id`, `dest_user_id`, `dest_project_id`, `dest_team_id`, ".
               "`severity`, `category`, `summary`, `content`, `date_expire`, `color`) ".
               "VALUES ('$date_submitted','$src_user_id','$dest_user_id','$dest_project_id','$dest_team_id',".
               "'$fSeverity','$fCategory','$fSummary','$fContent','$fDateExpire','$color');";

      $result = mysql_query($query);
      if (!$result) {
         $logger->error("Query FAILED: $query");
         $logger->error(mysql_error());
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
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
    * @param unknown_type $blogPost_id
    */
   public static function delete($blogPost_id) {

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

      // check if $blogPost_id exists (foreign keys do not exist in MyISAM)

      // add activity

      return $activity_id;
   }

   // -----------------------------------------
   /**
    *
    */
   public function getActivityList() {
      if (NULL == $this->activityList) {
         $query = "SELECT * FROM `codev_blog_activity_table` WHERE blog_id = $this->id";
         $result = mysql_query($query);
         if (!$result) {
            $this->logger->error("Query FAILED: $query");
            $this->logger->error(mysql_error());
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = mysql_fetch_object($result);

         $this->activityList = array();


      }
      return $this->activityList;
   }


   // -----------------------------------------
   /**
    * QuickSort compare method.
    * returns true if $this has higher priority than $postB
    *
    * @param BlogPost $postB the object to compare to
    */
   public function compareTo($postB) {

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

   // -----------------------------------------
   public function __construct($id) {
      $this->logger = Logger::getLogger(__CLASS__);

   }


   // -----------------------------------------
   /**
    * available categories are stored in codev_config_table.
    * @return array (id => name)
    */
   public function getCategoryList() {

   }

   // -----------------------------------------
   /**
    * available severity values
    * @return array (id => name)
    */
   public function getSeverityList() {

   }


   // -----------------------------------------
   /**
    * return the posts to be displayed for a given user,
    * depending on it's [userid, teams, projects] and personal filter preferences.
    *
    * @param int $user_id
    *
    * @return array BlogPost
    */
   public function getPosts($user_id) {

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

   }

} // class BlogManager
























