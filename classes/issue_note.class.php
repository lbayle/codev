<?php
/*
   This file is part of CodevTT.

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

class IssueNote {

   const type_bugnote = 0;         // Mantis ( 'BUGNOTE', 0 )
   const type_reminder = 1;        // Mantis ( 'REMINDER', 1 )
   const type_timetracking = 2;    // Mantis ( 'TIME_TRACKING', 2 )
   const type_timesheetNote = 108; // CodevTT
   const type_timetrackNote = 109;

   const history_BugnoteAdded   = 2; // Mantis ('BUGNOTE_ADDED', 2 )
   const history_BugnoteUpdated = 3; // Mantis ('BUGNOTE_UPDATED', 3 )
   const history_BugnoteDeleted = 4; // Mantis ('BUGNOTE_DELETED', 4 )

   const viewState_public = 10;
   const viewState_private = 50;

   const rev_type_bugnote = 4; // MANTIS

   const tagid_timesheetNote = 'CODEVTT_TAG_TIMESHEET_NOTE';
   const tagid_NoteReadBy    = 'CODEVTT_TAG_READ_BY';
   const tagid_timetrackNote = 'CODEVTT_TAG_TIMETRACKING_NOTE';

   const tag_begin = '<!-- ';
   const tag_sep = ' --- ';
   const tag_doNotRemove = ' (Do not remove this line)';
   const tag_end = ' -->';

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

   /**
    * @var int Issue note id
    */
   private $id;
   private $bug_id;
   private $bugnote_text_id;
   private $reporter_id;
   private $date_submitted;
   private $last_modified;
   private $note;
   private $tag;
   private $type; // mantis bugnote types : ( 'BUGNOTE', 0 ), ( 'REMINDER', 1 ) ( 'TIME_TRACKING', 2 );

   private $readByList; // array(userid => timestamp)

   /**
    *
    * create a new note for an issue
    *
    * @param type $bug_id
    * @param type $reporter_id
    * @param type $text
    * @param type $type
    * @param type $private
    */
   public static function create($bug_id, $reporter_id, $text='', $type=self::type_bugnote, $private=FALSE, $date_submitted=NULL) {

      $view_state = ($private) ? self::viewState_private : self::viewState_public;
      $sql = AdodbWrapper::getInstance();
      $query2 = "INSERT INTO {bugnote_text} (note)".
                " VALUES (".$sql->db_param().")";
      $sql->sql_query($query2, array($text));
      $bugnote_text_id = $sql->getInsertId();

      $timestamp = time();

      if(NULL == $date_submitted){
         $date_submitted = $timestamp;
      }

      $query = 'INSERT INTO {bugnote} '.
              '(bug_id, reporter_id, view_state, note_type, bugnote_text_id, date_submitted, last_modified) '.
              "VALUES (".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().")";

      $q_params[]=$bug_id;
      $q_params[]=$reporter_id;
      $q_params[]=$view_state;
      $q_params[]=$type;
      $q_params[]=$bugnote_text_id;
      $q_params[]=$date_submitted;
      $q_params[]=$timestamp;
      $sql->sql_query($query, $q_params);
      $bugnote_id = $sql->getInsertId();

      // log BUGNOTE_ADD in Issue history
      $query3 = 'INSERT INTO {bug_history} '.
				'( user_id, bug_id, date_modified, type, old_value ) '.
				"VALUES ( ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().', '.
                        $sql->db_param().", ".$sql->db_param().")";
      $q_params3[]=$reporter_id;
      $q_params3[]=$bug_id;
      $q_params3[]=$timestamp;
      $q_params3[]=self::history_BugnoteAdded;
      $q_params3[]=$bugnote_id;
      $sql->sql_query($query3, $q_params3);

      return $bugnote_id;
   }

   /**
    * delete bugnote
    */
   public static function delete($id, $bugid, $userid) {
      // TODO
      //self::$logger->error("Delete note $id");

      # Remove the bugnote text
      $sql = AdodbWrapper::getInstance();
      $query = 'DELETE FROM {bugnote_text} WHERE id=' .
            " (SELECT bugnote_text_id FROM {bugnote} WHERE id=".$sql->db_param().")";
      $sql->sql_query($query, array($id));

      # Remove the bugnote
      $query2 = 'DELETE FROM {bugnote} WHERE id='.$sql->db_param();
      $sql->sql_query($query2, array($id));

      // log BUGNOTE_DELETED in Issue history
      $query3 = 'INSERT INTO {bug_history} '.
				'( user_id, bug_id, date_modified, type, old_value ) '.
				"VALUES ( ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().', '.
                        $sql->db_param().", ".$sql->db_param().")";
      $q_params3[]=$userid;
      $q_params3[]=$bugid;
      $q_params3[]=time();
      $q_params3[]=self::history_BugnoteDeleted;
      $q_params3[]=$id;
      $sql->sql_query($query3, $q_params3);
   	return true;
   }

   /**
    * search for the latest bugnote containing tagid_timesheetNote
    *
    * @param type $bug_id
    * @return an IssueNote or NULL if not found
    */
   public static function getTimesheetNote($bug_id) {

      $sql = AdodbWrapper::getInstance();
      $query2 = "SELECT note.id, note.bugnote_text_id ".
               "FROM {bugnote} as note ".
               " WHERE note.bug_id = ".$sql->db_param().
               " AND 0 <> (SELECT COUNT(id) FROM {bugnote_text}".
               "            WHERE id = note.bugnote_text_id".
               "            AND note LIKE ".$sql->db_param().") ".
               "ORDER BY note.date_submitted DESC";
      $q_params[]=$bug_id;
      $q_params[]='%'.self::tagid_timesheetNote.$sql->db_param().'%';

      $result = $sql->sql_query($query2, $q_params, TRUE, 1); // LIMIT 1

      $row = $sql->fetchObject($result);

      #echo "getTimesheetNote($bug_id) Note $row->id, bugnote_text = $row->bugnote_text_id <br>";
      $issueNote = NULL;
      if (!is_null($row->id)) {
         $issueNote = new IssueNote($row->id);
      }

      return $issueNote;
   }

   /**
    * add/update the TimetheetNote of an Issue
    * @param type $bug_id
    * @param type $text
    */
   public static function setTimesheetNote($bug_id, $text, $reporter_id) {

      self::$logger->debug("Task $bug_id setTimesheetNote:[$text]");



      // add TAG in front (if not found)
      if (FALSE === strpos($text, self::tagid_timesheetNote)) {
         $tag = self::tag_begin . self::tagid_timesheetNote . self::tag_doNotRemove . self::tag_end;
         $text = $tag . "\n" . $text;
      }

      $issueNote = self::getTimesheetNote($bug_id);
      if (is_null($issueNote)) {
         $bugnote_id = self::create($bug_id, $reporter_id, $text, self::type_timesheetNote, TRUE);

      } else {
         # notify users that the note has changed
         $text = self::removeAllReadByTags($text);

         $issueNote->setText($text, $reporter_id);
         $bugnote_id = $issueNote->getId();
      }
   }

   /**
    * when a TimesheetNote is modified, all ReadBy tags must
    * be removed, so that users are notified
    *
    * @param string $text
    * @return string $text without tags
    */
   public static function removeAllReadByTags($text) {
      //$regex_remove = '/<!-- CODEVTT_TAG_READ_BY.* -->/';
      $regex_remove = '/'. self::tag_begin . self::tagid_NoteReadBy . '.*' . self::tag_end . '/';
      $note2 = preg_replace ( $regex_remove , '' , $text);
      return $note2;
   }

   /**
    * @param int $id Issue note id
    * @throws Exception
    */
   public function __construct($id) {
      if ((!is_numeric($id)) || (0 == $id)) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a IssueNote with id=0 is not allowed.");
         self::$logger->error("EXCEPTION IssueNote constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize();

      #echo 'IssueNote constructor, text: '.$this->note.'<br>';
   }

   private function initialize() {
      // Get bugnote info
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT note.*, ".
               "bugnote_text.note ".
               "FROM {bugnote} as note ".
               "JOIN {bugnote_text} as bugnote_text ON note.bugnote_text_id = bugnote_text.id ".
               "WHERE note.id = ".$sql->db_param().
               " ORDER BY note.date_submitted";

      $result = $sql->sql_query($query, array( $this->id));

      $row = $sql->fetchObject($result);

      $this->bug_id = $row->bug_id;
      $this->reporter_id = $row->reporter_id;
      $this->bugnote_text_id = $row->bugnote_text_id;
      $this->date_submitted = $row->date_submitted;
      $this->last_modified = $row->last_modified;
      $this->note = $row->note;

      // parse ReadBy TAGs
      $this->parseReadByTags();

   }

   public function getId() {
      return $this->id;
   }

   public function getBugId() {
      return $this->bug_id;
   }

   public function getReporterId() {
      return $this->reporter_id;
   }

   public function getLastModified() {
      return $this->last_modified;
   }

   public function getDateSubmitted() {
      return $this->date_submitted;
   }

   public function getNote() {
      return $this->note;
   }

   /**
    *
    * @param type $raw if TRUE, remove tagid_timesheetNote (NOT readBy tags)
    * @return type
    */
   public function getText($raw=FALSE, $removeReadBy=FALSE) {

      // check id != 0
      if (0 == $this->id) { return ''; }

      $text = $this->note;

      // remove tagid_timesheetNote & tagid_NoteReadBy
      if (!$raw) {
         
         // remove CODEVTT_TAG_TIMESHEET_NOTE
         $tag = self::tag_begin . self::tagid_timesheetNote . self::tag_doNotRemove . self::tag_end;
         $text = trim(str_replace($tag, '', $text));

         //$regex_remove = '/<!-- CODEVTT_TAG_TIMETRACKING_NOTE.* -->/';
         $regex_remove = '/'. self::tag_begin . self::tagid_timetrackNote . '.*' . self::tag_end . '/';
         $text = preg_replace ( $regex_remove , '' , $text);
      }

      // remove ReadBy tags
      if ($removeReadBy) {
         $text = self::removeAllReadByTags($text);
      }

      return trim($text);
   }

   /**
    *
    */
   public function setText($text, $user_id) {

      $oldText = $this->note;
      if ( $oldText == $text ) {
         return true;
      }

      # insert an 'original' revision if needed
      if ( $this->revisionCount() < 1 ) {
         $this->revisionAdd($oldText, $this->reporter_id, $this->last_modified);
      }
      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE {bugnote_text} SET note=".$sql->db_param().
               " WHERE id=" . $sql->db_param();
      $sql->sql_query($query, array($text, $this->bugnote_text_id));

	   # updated the last_updated date
      $now = time();
   	$query2 = "UPDATE {bugnote} SET last_modified=".$sql->db_param().
                " WHERE id= ".$sql->db_param();
      $sql->sql_query($query2, array($now, $this->id));

      # insert a new revision
      $revision_id = $this->revisionAdd($text, $user_id, $now);

      // log BUGNOTE_UPDATED in Issue history
      $query3 = 'INSERT INTO {bug_history} '.
				'( user_id, bug_id, date_modified, type, old_value, new_value ) '.
				" VALUES ( ".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().', '.$sql->db_param().', '.$sql->db_param().", ".$sql->db_param().")";
      $q_params3[]=$user_id;
      $q_params3[]=$this->bug_id;
      $q_params3[]=time();
      $q_params3[]=self::history_BugnoteUpdated;
      $q_params3[]=$this->id;
      $q_params3[]=$revision_id;

      $sql->sql_query($query3, $q_params3);

      return true;

   }

   private function revisionCount() {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT COUNT(id) FROM {bug_revision} ".
              "WHERE bug_id= ".$sql->db_param().
              "AND bugnote_id= ".$sql->db_param().
		        "AND type= ".$sql->db_param();
      $result = $sql->sql_query($query, array($this->bug_id, $this->id, self::rev_type_bugnote));
      #$found  = (0 != $sql->getNumRows($result)) ? true : false;
      $nbTuples  = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;

      return $nbTuples;
   }

   /**
    * @param type $text
    * @param type $user_id
    * @param type $timestamp
    * @return int revision_id
    */
   private function revisionAdd($text, $user_id, $timestamp) {

      $sql = AdodbWrapper::getInstance();
      $query = "INSERT INTO {bug_revision} (bug_id, bugnote_id, user_id, timestamp, type, value) ".
               "VALUES (".$sql->db_param().", ".$sql->db_param().", ".$sql->db_param().", ".
                          $sql->db_param().", ".$sql->db_param().", ".$sql->db_param().")";
      $q_params[]=$this->bug_id;
      $q_params[]=$this->id;
      $q_params[]=$user_id;
      $q_params[]=$timestamp;
      $q_params[]=self::rev_type_bugnote;
      $q_params[]=$text;
      
      $sql->sql_query($query, $q_params);

      $revision_id = AdodbWrapper::getInstance()->getInsertId();
      return $revision_id;
   }

   /**
    * parse note for ReadBy tags
    * and set $readByList = array(userid => timestamp)
    *
    * <!-- CODEVTT_TAG_READ_BY <username> --- 2013-07-18 23:12:55 -->
    */
   private function parseReadByTags() {

      $this->readByList = array();

      // --- get ReadBy TAGs
      //$regex = '/<!-- CODEVTT_TAG_READ_BY (?P<username>.*) --- (?P<date>.*) -->/';
      $regex = '/'. self::tag_begin . self::tagid_NoteReadBy . ' (?P<username>.*) --- (?P<date>.*)' . self::tag_end . '/';

      preg_match_all ( $regex , $this->note, $matches);

      // --- extract user & date from ReadBy TAGs
      for ($i=0; $i< count($matches[0]); ++$i) {

         $username = trim($matches['username'][$i]);
         $dateTime = trim($matches['date'][$i]);
         $timestamp = Tools::datetime2timestamp($dateTime);
         $userid = User::getUserId($username);

         $error = 0;
         if (!is_numeric($userid)) {
            self::$logger->error("issue $this->bug_id parseReadByTags: unknown user <$username>");
            $error++;
         }
         if (FALSE === $timestamp) {
            self::$logger->error("issue $this->bug_id parseReadByTags: wrong date : <$dateTime>");
            $error++;
         }

         if (0 == $error) {
            //echo "issue $this->bug_id userid = $userid user ".$matches['username'][$i].' date '.$matches['date'][$i]." ($timestamp)<br>";
            $this->readByList["$userid"] = $timestamp;
         }
      }
      return $this->readByList;
   }


   /**
    * add a ReadBy tag
    *
    * <!-- CODEVTT_TAG_READ_BY <username> --- 2013-07-18 23:12:55 -->
    *
    * @param type $userid
    * @param type $timestamp (now if NULL)
    */
   public function markAsRead($userid, $timestamp = NULL) {

      if (is_null($timestamp)) {
         $timestamp = time();
      }
      if (!array_key_exists($userid, $this->readByList)) {
         $user = UserCache::getInstance()->getUser($userid);
         $tag =  self::tag_begin .
               self::tagid_NoteReadBy . ' ' .
               $user->getName() . ' '.
               self::tag_sep .
               date('Y-m-d H:i:s', $timestamp) .
               self::tag_end;

         $note = $this->note."\n".$tag;

         $this->setText($note, $userid);
         $this->readByList["$userid"] = $timestamp;

      //} else {
      //   self::$logger->debug("issue $this->bug_id markAsRead: user $userid already marked.");
      }

   }

   /**
    *
    * @param type $userid
    * @return int timestamp date of read OR 0 if user did not read
    */
   public function isReadBy($userid) {
      if (!array_key_exists($userid, $this->readByList)) {
         return 0;
      }
      return $this->readByList["$userid"];
   }

   public function getReadByList($formatted = FALSE) {

      if ($formatted) {
         $list = array();
         foreach ($this->readByList as $uid => $t) {
            $user = UserCache::getInstance()->getUser($uid);
            $list[$user->getRealname()] = date('Y-m-d  H:i:s', $t);
         }
         return $list;
      } else {
         return $this->readByList;
      }
   }

   /**
    * get Note origin: MANTIS, TIMESHEET, TIMETRACK
    */
   public function getOriginTag() {
      if (NULL == $this->tag) {
         if (FALSE !== strpos ( $this->note , self::tagid_timesheetNote)) {
            $this->tag = 'Timesheet';
         } else if (FALSE !== strpos ( $this->note , self::tagid_timetrackNote)) {
            $this->tag = 'Timetrack';
         } else {
            $this->tag =  'Mantis';
         }
      }
      return $this->tag;
   }
}

IssueNote::staticInit();


