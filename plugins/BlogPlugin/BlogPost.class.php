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
 * Blog post structure
 */
class BlogPost implements Comparable {

   const severity_low = 1;
   const severity_normal = 2;
   const severity_high = 3;

   const actionType_ack  = 0;
   const actionType_hide = 1;

   // TODO properties
   const activityProp_actionType   = 'actionType';
   const activityProp_userId       = 'userId';
   const activityProp_id           = 'id';
   const activityProp_blogpostId   = 'blogpostId';
   const activityProp_timestamp    = 'timestamp';
   const activityProp_userName     = 'userName';
   const activityProp_actionName   = 'actionName';
   const activityProp_formatedDate = 'formatedDate';

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public $id;
   public $src_user_id;
   public $dest_user_id;
   public $dest_team_id;
   public $severity;
   public $category;
   public $summary;
   public $content;
   public $date_submitted;
   public $date_expire;
   public $color;

   private $activityList;

   public function __construct($post_id, $details = NULL) {
      $this->id = $post_id;
      $this->initialize($details);
   }

   private function initialize($row = NULL) {
      if(NULL == $row) {
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_blog_table WHERE id = ".$this->id.";";
         $result = $sql->sql_query($query);

         if (0 == $sql->getNumRows($result)) {
            $e = new Exception("BlogPost $this->id does not exist");
            self::$logger->error("EXCEPTION BlogPost constructor: " . $e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
            throw $e;
         }

         $row = $sql->fetchObject($result);
      }

      $this->date_submitted  = $row->date_submitted;
      $this->src_user_id = $row->src_user_id;
      $this->dest_user_id = $row->dest_user_id;
      $this->dest_team_id = $row->dest_team_id;
      $this->severity = $row->severity;
      $this->category = $row->category;
      $this->summary = $row->summary;
      $this->content = $row->content;
      $this->date_expire = $row->date_expire;
      $this->color = $row->color;

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
         case self::severity_low:
            return T_('Low');
         case self::severity_normal:
            return T_('Normal');
         case self::severity_high:
            return T_('High');
         default:
            #return T_("unknown $severity");
            return NULL;
      }
   }

   /**
    * Literal name for the given activity_id
    *
    * @param int $actionType
    * @return string actionName or NULL if unknown
    */
   public static function getActionName($actionType) {
      switch ($actionType) {
         case self::actionType_ack:
            return T_('Acknowledged');
         case self::actionType_hide:
            return T_('Hidden');
         default:
            #return T_('unknown');
            return NULL;
      }
   }

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

      $date_submitted = time(); # mktime(0, 0, 0, date('m'), date('d'), date('Y'));

      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO codev_blog_table ".
               "(date_submitted, src_user_id, dest_user_id, dest_project_id, dest_team_id, ".
               "severity, category, summary, content, date_expire, color) ".
               "VALUES (".$sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().",".
                          $sql->db_param().")";

      $result = $sql->sql_query($query, array(
         $date_submitted, 
         $src_user_id, 
         $dest_user_id, 
         $dest_project_id, 
         $dest_team_id,
         $severity, 
         $category, 
         $summary, 
         $content, 
         $date_expire, 
         $color));

      return $sql->getInsertId();
   }

   /**
    * Delete a post and all it's activities.
    *
    * Note: Only administrators & the owner of the post are allowed to delete.
    */
   public function delete() {
      // TODO check admin/ user access rights

      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_blog_activity_table WHERE blog_id = ".$this->id;
      $sql->sql_query($query);


      $query2 = "DELETE FROM codev_blog_table WHERE id = ".$this->id;
      $sql->sql_query($query2);

   }

   /**
    * Creates/updates an activity for a user
    *
    * @param int $user_id
    * @param int $actionType
    * @param boolean $value  true to add, false to remove
    * @param int $date
    *
    * @throws exception if failed
    */
   public function setAction($user_id, $actionType, $value, $date) {

      // TODO: check if activity_id is valid ?
      
      if (true === $value) {
         // set this action.
         // TODO if already set, do nothing
         $query = "INSERT INTO codev_blog_activity_table (blog_id, user_id, action, date) ".
                  "VALUES ('$this->id','$user_id','$actionType','$date')";
      } else {
         // unset the action
         $query = "DELETE FROM codev_blog_activity_table WHERE user_id = $user_id AND action = $actionType";
      }

      $sql = AdodbWrapper::getInstance();
      $result = $sql->sql_query($query);
/*
      if (!$result) {
         #echo "<span style='color:red'>ERROR: Query FAILED</span>";
         $e = new Exception("ERROR: Query FAILED");
         self::$logger->error("EXCEPTION BlogPost addActivity: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }
 */
      return $sql->getInsertId();
   }

   /**
    * @return array activities[]
    */
   public function getActivityList() {
      if (NULL == $this->activityList) {
         $query = "SELECT * FROM codev_blog_activity_table WHERE blog_id = $this->id ";
         $query .= "ORDER BY date DESC";
         $sql = AdodbWrapper::getInstance();
         $result = $sql->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }

         $this->activityList = array();
         while($row = $sql->fetchObject($result)) {

            $user = UserCache::getInstance()->getUser($row->user_id);

            $this->activityList[$row->id] = array(
                self::activityProp_id => $row->id,
                self::activityProp_blogpostId => $row->blog_id,
                self::activityProp_userId     => $row->user_id,
                self::activityProp_actionType => $row->action,
                self::activityProp_timestamp => $row->date,
                
                self::activityProp_userName => $user->getName(),
                self::activityProp_actionName => self::getActionName($row->action),
                self::activityProp_formatedDate => date("Y-m-d G:i", $row->date), // TODO 1971-01-01
               );
         }
      }
      return $this->activityList;
   }

   public function isAcknowledged($user_id) {
      $activities=$this->getActivityList();
      foreach($activities as $id => $activity) {
         if (($activity[self::activityProp_userId] == $user_id) &&
             ($activity[self::activityProp_actionType ] == self::actionType_ack)) {
            return true;
         }
      }
      return false;
   }

   public function isHidden($user_id) {
      $activities=$this->getActivityList();
      foreach($activities as $id => $activity) {
         if (($activity[self::activityProp_userId] == $user_id) &&
             ($activity[self::activityProp_actionType ] == self::actionType_hide)) {
            return true;
         }
      }
      return false;
   }

   /**
    * Hide  : owner + (others if ack), not already hidden
    * @param type $user_id
    */
   public function isHideGranted($user_id) {
      $isHidden = $this->isHidden($user_id);
      $isAck = $this->isAcknowledged($user_id);

      if ((!$isHidden) && ($user_id === $this->src_user_id) || $isAck) {
         return true;
      }
      return false;
   }

   /**
    * criteria: date_submission, date_expired, severity
    *
    * @param BlogPost $postB the object to compare to
    *
    * @return 1 if $postB higher priority, -1 if lower, 0 if equal
    */
   public static function compare(Comparable $postA, Comparable $postB) {
      // TODO
      return 0;
   }


   /**
    * Return a smarty structure to fill BlogPlugin_ajax.html
    * 
    * @param int sessionUserId
    * @return mixed[]
    */
   public function getSmartyStruct($sessionUserId) {

      $srcUser = UserCache::getInstance()->getUser($this->src_user_id);
      $item = array();

      $item['id'] = $this->id;
      $item['category'] = Config::getVariableValueFromKey(Config::id_blogCategories, $this->category);
      $item['severity'] = BlogPost::getSeverityName($this->severity);
      if (self::severity_high == $this->severity) {
         $item['bgColorSeverity'] = 'LightPink';
      }

      $item['summary'] = $this->summary;
      $item['content'] = nl2br($this->content);
      $item['date_submitted'] = date('Y-m-d G:i',$this->date_submitted);
      $item['from']    = $srcUser->getRealname();

      // find receiver
      if (0 != $this->dest_user_id) {
         $destUser = UserCache::getInstance()->getUser($this->dest_user_id);
         $item['to'] = $destUser->getRealname();
      } else if (0 != $this->dest_team_id) {
         $team = TeamCache::getInstance()->getTeam($this->dest_team_id);
         $item['to'] = $team->getName();
      } else if (0 != $this->dest_project_id) {
         // Note: unused for now
         $destProj = ProjectCache::getInstance()->getProject($this->dest_project_id);
         $item['to'] = $destProj->getName();
      } else if ((0 == $this->dest_user_id) && (0 == $this->dest_team_id )) {
         // This case happens when an Administrator sends a message to "All" users
         $item['to'] = T_("Everybody");
      } else {
         $item['to'] = '?';
         //self::$logger->error("");
      }

      // display only 'Ack' activities, we don't care who's hidden it...
      $item['activity'] = $this->getActivityList();
      foreach($item['activity'] as $id => $activity) {
         if ($activity[self::activityProp_actionType ] != self::actionType_ack) {
            unset($item['activity'][$id]);
         }
      }

      // ----------

      /* button rules:
       * - Delete: only if owner
       * - Ack   : anyone but owner, not already Ack
       * - Hide  : owner + (others if ack), not already hidden
       * - Unhide: anyone, isHidden
       *
       * do not display hide buttons if option displayHiddenPosts is on ?
       *
       */



      $isHidden = $this->isHidden($sessionUserId);
      $isAck = $this->isAcknowledged($sessionUserId);
      $isDisplayHiddenPosts = true;

      $item['isHidden'] = ($isHidden && $isDisplayHiddenPosts) ? true : false;

      // pas tres academique, mais bon...
      if ($sessionUserId == $this->src_user_id) {
         // Delete
         $htmlDeleteButton = "<img class='blogPlugin_btDeletePost pointer' data-bpostId='$this->id' align='absmiddle' src='images/b_drop.png' title='".T_('Delete')."'>";
      } else {
         // Ack
         if (!$isAck) {
            $item['buttons'] .="<img class='blogPlugin_btAckPost pointer' data-bpostId='$this->id' align='absmiddle' src='images/b_markAsRead.png' title='".T_('Mark as read')."'>";
         }
      }
      // hide
      if ((!$isHidden) && (($sessionUserId == $this->src_user_id) || $isAck)) {
         $item['buttons'] .="<img class='blogPlugin_btHidePost pointer' data-bpostId='$this->id' align='absmiddle' src='images/b_ghost.png' title='".T_('Hide')."'>";
      }
      // unhide
      if ($isHidden) {
         $item['buttons'] .="<img class='blogPlugin_btUnhidePost pointer' data-bpostId='$this->id' align='absmiddle' src='images/b_unhide.png' title='".T_('Show')."'>";
      }
      $item['buttons'] .= $htmlDeleteButton; // delete button (if exists) always at last position

      return $item;
   }

}
BlogPost::staticInit();