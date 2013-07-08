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

   // mantis bugnote types : ( 'BUGNOTE', 0 ), ( 'REMINDER', 1 ) ( 'TIME_TRACKING', 2 );
   const type_timesheetNote = 108;

   const tagid_timesheetNote = 'CODEVTT_TAG_TIMESHEET_NOTE';
   const tagid_NoteReadBy    = 'CODEVTT_TAG_READ_BY';

   const tag_begin = '<!-- ';
   const tag_sep = ' ==== ';
   const tag_end = ' (Do not remove this line) -->';

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
   private $note;
   private $type; // mantis bugnote types : ( 'BUGNOTE', 0 ), ( 'REMINDER', 1 ) ( 'TIME_TRACKING', 2 );


   /**
    * search for the latest bugnote containing tagid_timesheetNote
    *
    * @param type $bug_id
    * @return an IssueNote id
    */
   public static function getTimesheetNote($bug_id) {

/*
      // trust note_type or search for tagid_timesheetNote ?
      $query = "SELECT id ".
               "FROM `mantis_bugnote_table` ".
               "WHERE bug_id = $bug_id ".
               "AND note_type = ".self::type_timesheetNote.' '.
               "ORDER BY date_submitted DESC LIMIT 1;";
*/
      $query2 = "SELECT note.id, note.bugnote_text_id ".
               "FROM `mantis_bugnote_table` as note ".
               "WHERE note.bug_id = $bug_id ".
               "AND 0 <> (SELECT COUNT(id) FROM mantis_bugnote_text_table WHERE id = note.bugnote_text_id AND note LIKE '%".self::tagid_timesheetNote."%') ".
               "ORDER BY note.date_submitted DESC LIMIT 1;";

      $result = SqlWrapper::getInstance()->sql_query($query2);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);

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
   public static function setTimesheetNote($bug_id, $text) {

      // TODO
      self::$logger->error("Task $bug_id setTimesheetNote: $text");

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
      $query = "SELECT note.*, ".
               "bugnote_text.note ".
               "FROM `mantis_bugnote_table` as note ".
               "JOIN `mantis_bugnote_text_table` as bugnote_text ON note.bugnote_text_id = bugnote_text.id ".
               "WHERE note.id = $this->id ".
               "ORDER BY note.date_submitted;";

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      $row = SqlWrapper::getInstance()->sql_fetch_object($result);

      $this->bug_id = $row->bug_id;
      $this->reporter_id = $row->reporter_id;
      $this->bugnote_text_id = $row->bugnote_text_id;
      $this->date_submitted = $row->date_submitted;
      $this->note = $row->note;
   }

   public function getId() {
      return $this->id;
   }

   public function getText() {
      return $this->note;
   }

   public function isTimesheetNote() {
      // TODO trust note_type or search for tagid_timesheetNote ?
      return (self::type_timesheetNote == $this->type);
   }

   /**
    * @param int $tagid
    * @param string $tagComment
    */
   public function addTag($tagid, $tagComment = NULL, $inFront=TRUE) {
      $tag = self::tag_begin . $tagid . self::tag_sep . $tagComment . self::tag_end;

      if ($inFront) {
         $this->note = $tag . '\n' . $this->note;
      } else {
         $this->note = $this->note . '\n' . $tag;
      }
      // TODO update note in DB
   }

   /**
    * add a NoteReadBy tag
    *
    * @param type $userid
    * @param type $timestamp
    */
   public function markAsRead($userid, $timestamp = NULL) {

      if (is_null($timestamp)) {
         $timestamp = now();
      }

      $user = UserCache::getInstance()->getUser($userid);
      $tag =  self::tag_begin .
              self::tagid_NoteReadBy .
              self::tag_sep .
              $user->getName() . ' '.
              date('Y-m-d H:i:s', $timestamp) .
              self::tag_end;

   }

   /**
    * when a TimesheetNote is modified, all ReadBy tags must
    * be removed, so that users are notified
    */
   public function removeAllReadByTags() {
      // TODO
   }

}

IssueNote::staticInit();

?>
