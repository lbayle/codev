<?php

class WBSElement2 extends Model {

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

   private $id;
   private $title;
   private $icon;
   private $font;
   private $color;
   private $bugId;
   private $parentId;
   private $rootId;
   private $order;

   private $isFolder;
   private $isModified;

   /**
    *
    * @param int $id
    * @param String $title
    * @param int $bug_id
    * @param boolean $isFolder
    * @param int $parent_id
    * @param int $order
    * @param type $icon
    * @param type $font
    * @param type $color
    */
   public function __construct($id, $bug_id, $parent_id, $root_id, $order, $title, $icon, $font, $color) {
      if (isNull($id)) {
         $this->id = self::create($bug_id, $parent_id, $root_id, $order, $title, $icon, $font, $color);
         $this->initialize();
      } else {
         $this->id = $id;

         // get data from DB
         $this->initialize();

			// TODO check $root_id

         // update data
         if ($this->isFolder() && !is_null($title)) { $this->title = $title; }
         if (!is_null($parent_id)) { $this->parentId = $parent_id; $isModified = true;}
         if (!is_null($order))     { $this->order = $order; $isModified = true;}
         if (!is_null($icon))      { $this->icon = $icon; $isModified = true;}
         if (!is_null($font))      { $this->font = $font; $isModified = true;}
         if (!is_null($color))     { $this->color = $color; $isModified = true;}
			$this->update(); // TODO do it now ?
      }
   }

   /**
    * Initialize
    * @param resource $row The issue details
    * @throws Exception If wbselement doesn't exists
    */
   public function initialize($row = NULL) {
      if ($row == NULL) {
         // Get info
         $query = "SELECT * FROM `codev_wbselement_table` " .
                 "WHERE id = $this->id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
      }

      if (NULL != $row) {
         $this->id = $row->id;
         $this->title = $row->title;
         $this->bugId = $row->bug_id;
         $this->parentId = $row->parent_id;
         $this->rootId = $row->root_id;
         $this->order = $row->order;
         $this->icon = $row->icon;
         $this->font = $row->font;
         $this->color = $row->color;
      } else {
         $e = new Exception("Constructor: WBSElement $this->wbselementId does not exist in Mantis DB.");
         self::$logger->error("EXCEPTION WBSElement constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }
   }


   /**
    *
    * @param int $bug_id
    * @param int $parent_id
    * @param int $root_id
    * @param int $order
    * @param String $title
    * @param String $icon
    * @param String $font
    * @param String $color
    * @return int id
    */
   public static function create($bug_id, $parent_id, $root_id, $order, $title, $icon=NULL, $font=NULL, $color=NULL) {

		// --- check values
		if (!isNull($parent_id)) {
			// check parrent exists and is a folder
			$queryP = "SELECT bug_id FROM `codev_wbselement_table` WHERE id = $parent_id";
         $resultP = SqlWrapper::getInstance()->sql_query($queryP);
         if (!$resultP) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $found  = (0 != SqlWrapper::getInstance()->sql_num_rows($resultP)) ? true : false;
			if (!$found) {
				$e = new Exception("create WBSElement: parrent_id $parent_id does not exist.");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
			$rowP = SqlWrapper::getInstance()->sql_fetch_object($resultP);
			if (!isNull($rowP->bug_id)) {
				$e = new Exception("create WBSElement: parrent_id $parent_id should be a Folder (bug_id = $rowP->bug_id).");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
		}
		if (isNull($bug_id)) {
			// new Folder (source: wbs_editor)
			if (isNull($title)) { 
				$e = new Exception("create WBSElement: Folder needs a title.");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
		} else {
			if (!Issue::exists($bug_id)) {
				$e = new Exception("create WBSElement: issue $bug_id does not exist in Mantis DB.");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
			if (isNull($parent_id)) {
				$e = new Exception("create WBSElement: issue $bug_id must have a parent_id (Folder).");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
			$title = null; // issue summary is stored in mantis_bug_table
		}

		if (isNull($order)) { $order = 1; }

		// --- insert new element
      $query  = 'INSERT INTO `codev_wbselement_table` (`order`';
		if (!isNull($bug_id)) { $query .= ', `bug_id`'; }
		if (!isNull($parent_id)) { $query .= ', `parent_id`'; }
		if (!isNull($root_id)) { $query .= ', `root_id`'; }
		if (!isNull($title)) { $query .= ', `title`'; }
		if (!isNull($icon)) { $query .= ', `icon`'; }
		if (!isNull($font)) { $query .= ', `font`'; }
		if (!isNull($color)) { $query .= ', `color`'; }
		$query .= ") VALUES ('$order'";
		if (!isNull($bug_id)) { $query .= ", '$bug_id'"; }
		if (!isNull($parent_id)) { $query .= ", '$parent_id'"; }
		if (!isNull($root_id)) { $query .= ", '$root_id'"; }
		if (!isNull($title)) { $query .= ", '$title'"; }
		if (!isNull($icon)) { $query .= ", '$icon'"; }
		if (!isNull($font)) { $query .= ", '$font'"; }
		if (!isNull($color)) { $query .= ", '$color'"; }
		$query .= ')';

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   public function remove() {

      $query = "DELETE FROM `codev_wbselement_table` WHERE `id` = " . $this->getId();
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }

      // TODO remove children recursively ?
   }

   public function update() {

      $query = "UPDATE `codev_wbselement_table`" .
              " SET `title` = '" . $this->getTitle() . "'" .
              ", `parent_id` = " . (($this->getParentId() == null) ? "NULL" : $this->getParentId()) .
              ", `order` = " . $this->getOrder() .
              " WHERE `id` = " . $this->getId();

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function getId() {
      return $this->id;
   }

   public function getTitle() {
      return $this->title;
   }

   public function setTitle($title) {
      $this->title = $title;
		$isModified = true;
   }

   public function getIcon() {
      return $this->icon;
   }

   public function setIcon($icon) {
      $this->icon = $icon;
		$isModified = true;
   }

   public function getFont() {
      return $this->font;
   }

   public function setFont($font) {
      $this->font = $font;
		$isModified = true;
   }

   public function getColor() {
      return $this->color;
   }

   public function setColor($color) {
      $this->color = $color;
   }

   public function getBugId() {
      return $this->bugId;
   }

   public function setBugId($bugId) {
      $this->bugId = $bugId;
   }

   public function getRootId() {
		// Note: ne setter
      return $this->rootId;
   }

   public function getParentId() {
      return $this->parentId;
   }

   public function setParentId($parentId) {
      $this->parentId = $parentId;
		$isModified = true;
   }

   public function getOrder() {
      return $this->order;
   }

   public function setOrder($order) {
      $this->order = $order;
		$isModified = true;
   }

   public function isFolder() {
      return isNull($this->bugId);
   }

	/**
	 *
	 * @param boolean $hasDetail if true, add [Progress, EffortEstim, Elapsed, Backlog, Drift]
	 * @param boolean $isManager
	 * @return array
	 */
   public function getDynatreeData($hasDetail, $isManager = false) {

      $query = "SELECT * FROM `codev_wbselement_table` WHERE `parent_id` = " . $this->getId() . " ORDER BY `order`";
      $result = SqlWrapper::getInstance()->sql_query($query);

      if ($result) {

         $parentArray = array();

         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $wbselement = new WBSElement($row->id);
            $childArray = array();

            if ($wbselement->isFolder()) {

               $childArray['title'] = $wbselement->getTitle();
               $childArray['isFolder'] = true;
               $childArray['key'] = $wbselement->getId();
               $childArray['children'] = $wbselement->getDynatreeData($hasDetail);
            } else {

               $issue = IssueCache::getInstance()->getIssue($wbselement->getBugId());

               if ($issue) {

                  $detail = '';

                  if ($hasDetail) {
							// TODO if isManager...
                     $detail = (($issue->getProgress() != null) ? ('~' . $issue->getProgress()) : '')
                             . (($issue->getMgrEffortEstim() != null) ? ('~' . $issue->getMgrEffortEstim()) : '')
                             . (($issue->getElapsed() != null) ? ('~' . $issue->getElapsed()) : '')
                             . (($issue->getBacklog() != null) ? ('~' . $issue->getBacklog()) : '')
                             . (($issue->getDriftMgr() != null) ? ('~' . $issue->getDriftMgr()) : '');
                  }

                  $childArray['title'] = $issue->getSummary() . $detail;
                  $childArray['isFolder'] = false;
                  $childArray['key'] = $wbselement->getId();
               } else {

                  $childArray['title'] = 'ERROR';
                  $childArray['isFolder'] = false;
                  self::$logger->error("The issue does not exist!");
               }
            }
            if (sizeof($childArray) > 0)
               array_push($parentArray, $childArray);
         }

         return $parentArray;
      }
      else {

         self::$logger->error("Query failed!");
      }
   }

	/**
	 * @param type $id
	 * @return boolean
	 */
   public static function exists($id) {
      if (NULL == $id) {
         self::$logger->warn("exists(): id == NULL.");
         return FALSE;
      }

      if (NULL == self::$existsCache) { self::$existsCache = array(); }

      if (!array_key_exists($id,self::$existsCache)) {
         $query  = "SELECT COUNT(id), bug_id FROM `codev_wbselement_table` WHERE id=$id ";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         #$found  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? true : false;
         $nbTuples  = (0 != SqlWrapper::getInstance()->sql_num_rows($result)) ? SqlWrapper::getInstance()->sql_result($result, 0) : 0;
         self::$existsCache[$id] = (0 != $nbTuples);
      }
      return self::$existsCache[$id];
   }

	/**
	 *
	 * @param type $dynatreeDict
	 */
	public static function createTreeFromDynatreeData($dynatreeDict, $order = 1, $parent_id = NULL, $root_id = NULL) {

		// {"title":"","isFolder":true,"key":"1","children":[{"title":"Sub1","isFolder":true,"key":"2","children":[]}]}
		$id = NULL;
		$title = $dynatreeDict['title'];
		$icon = $dynatreeDict['icon'];
		$font = $dynatreeDict['font'];
		$color = $dynatreeDict['color'];

		$isFolder = $dynatreeDict['isFolder'];
		if ($isFolder) {
			$id = $dynatreeDict['key']; // (null if new folder)
			$bug_id = NULL;
		} else {
			$bug_id = $dynatreeDict['key'];

			// find $id (if exists)
			// Note: parent_id may have changed (if issue moved)
			// Note: $root_id cannot be null because a WBS always starts with a folder
			$query  = "SELECT id FROM `codev_wbselement_table` WHERE bug_id = $bug_id AND root_id = $root_id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
			if (!isNull($row)) {
				$id = $row->id;
			}

		}

		// create Element
		$wbse = new WBSElement2($id, $bug_id, $parent_id, $root_id, $order, $title, $icon, $font, $color);

		// create children
		$children = $dynatreeDict['children'];
		$childOrder = 1;
		foreach($children as $childDict) {
			self::createTreeFromDynatreeData($childDict, $childOrder, $id);
		}

	}


}

WBSElement2::staticInit();
?>
