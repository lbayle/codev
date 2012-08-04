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

include_once('classes/sqlwrapper.class.php');

require_once('lib/log4php/Logger.php');

class IssueNote {

   const tagid_trackNote = 'CODEVTT_TAG_TALLYSHEET_NOTE';

   const tag_begin = '<==== ';
   const tag_sep = ' ==== ';
   const tag_end = ' (Do not remove this line) ====>';

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
   public $id;

   /**
    * @var int Issue id
    */
   public $bug_id;
   public $reporter_id;
   public $date_submitted;
   public $note;

   private $bugnote_text_id;

   /**
    * @param int $id Issue note id
    * @throws Exception
    */
   public function __construct($id) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Creating a IssueNote with id=0 is not allowed.");
         self::$logger->error("EXCEPTION IssueNote constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;
      $this->initialize();
   }

   private function initialize() {
      // Get bugnote info
      $query = "SELECT note.bug_id, note.reporter_id, note.bugnote_text_id, note.date_submitted, mantis_bugnote_text_table.note ".
               "FROM `mantis_bugnote_table` as note, `mantis_bugnote_text_table` ".
               "WHERE mantis_bugnote_table.id = $this->id ".
               "AND mantis_bugnote_table.bugnote_text_id = mantis_bugnote_text_table.id ".
               "ORDER BY mantis_bugnote_table.date_submitted";

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

   /**
    * @param int $tagid
    * @param string $tagComment
    */
   public function addTag($tagid, $tagComment) {
      $tag = self::tag_begin . $tagid . self::tag_sep . $tagComment . self::tag_end;

      $this->note = $tag . '\n' . $this->note;
      // TODO update note in DB
   }

   /**
    * @param int $tagid
    */
   public function removeTag($tagid) {
      // search tag in $this->note
      // remove all between tag_begin and tag_end
      // TODO update note in DB
   }

}

IssueNote::staticInit();

?>
