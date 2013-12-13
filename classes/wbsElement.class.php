<?php

class WBSElement extends Model {

   /**
    * @var Logger The logger
    */
   private static $logger;

   /**
    * contains a flat list of all the wbse elements of the root.
    *
    * @var type array('root_id' => array('wbse_id' => array(parent_id, bug_id)))
    */
   private static $wbs_idList;

   /**
    * Initialize complex static variables
    * @static
    */
   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
      self::$wbs_idList = array();
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
   private $expand;

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
   public function __construct($id, $root_id = NULL, $bug_id = NULL, $parent_id = NULL, $order = NULL,
			  $title = NULL, $icon = NULL, $font = NULL, $color = NULL, $expand=NULL) {

      if (is_null($id)) {
         $this->id = self::create($bug_id, $parent_id, $root_id, $order, $title, $icon, $font, $color, $expand);
         $this->initialize();
      } else {
         $this->id = $id;

         // get (old) data from DB
         $this->initialize();

			// check $root_id
         if (($this->rootId != $root_id) && ($this->id != $root_id)) {
            $e = new Exception("Constructor: WBSElement $id exists with root_id = $this->rootId (expected $root_id)");
            self::$logger->error("EXCEPTION WBSElement constructor: " . $e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
            throw $e;
         }

         // update data
			$isModified = false;
         if ($this->isFolder() && !is_null($title)) { $this->title = $title; }
         if (!is_null($parent_id)) { $this->parentId = $parent_id; $isModified = true;}
         if (!is_null($order))     { $this->order = $order; $isModified = true;}
         if (!is_null($icon))      { $this->icon = $icon; $isModified = true;}
         if (!is_null($font))      { $this->font = $font; $isModified = true;}
         if (!is_null($color))     { $this->color = $color; $isModified = true;}
         if (!is_null($expand))     { $this->expand = $expand; $isModified = true;}
			if ($isModified) {
				$this->update(); // TODO do it now ?
			}
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
         $query = "SELECT * FROM `codev_wbs_table` " .
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
         $this->expand = (1 == $row->expand);
         $this->icon = $row->icon;
         $this->font = $row->font;
         $this->color = $row->color;
      } else {
         $e = new Exception("Constructor: WBSElement $this->id does not exist in Mantis DB.");
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
   public static function create($bug_id, $parent_id, $root_id, $order, $title, $icon=NULL, $font=NULL, $color=NULL, $expand=NULL) {

		// --- check values
		if (!is_null($parent_id)) {
			// check parrent exists and is a folder
			$queryP = "SELECT bug_id FROM `codev_wbs_table` WHERE id = $parent_id";
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
			if (!is_null($rowP->bug_id)) {
				$e = new Exception("create WBSElement: parrent_id $parent_id should be a Folder (bug_id = $rowP->bug_id).");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
		}
		if (is_null($bug_id)) {
			// new Folder (source: wbs_editor)
			if (is_null($title)) {
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
			if (is_null($parent_id)) {
				$e = new Exception("create WBSElement: issue $bug_id must have a parent_id (Folder).");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
			$title = null; // issue summary is stored in mantis_bug_table
		}

		if (is_null($order)) { $order = 1; }

		// --- insert new element
      $query  = 'INSERT INTO `codev_wbs_table` (`order`';
		if (!is_null($bug_id)) { $query .= ', `bug_id`'; }
		if (!is_null($parent_id)) { $query .= ', `parent_id`'; }
		if (!is_null($root_id)) { $query .= ', `root_id`'; }
		if (!is_null($title)) { $query .= ', `title`'; }
		if (!is_null($icon)) { $query .= ', `icon`'; }
		if (!is_null($font)) { $query .= ', `font`'; }
		if (!is_null($color)) { $query .= ', `color`'; }
		if (!is_null($expand)) { $query .= ', `expand`'; }
		$query .= ") VALUES ('$order'";
		if (!is_null($bug_id)) { $query .= ", '$bug_id'"; }
		if (!is_null($parent_id)) { $query .= ", '$parent_id'"; }
		if (!is_null($root_id)) { $query .= ", '$root_id'"; }
		if (!is_null($title)) { $query .= ", '$title'"; }
		if (!is_null($icon)) { $query .= ", '$icon'"; }
		if (!is_null($font)) { $query .= ", '$font'"; }
		if (!is_null($color)) { $query .= ", '$color'"; }
		if (!is_null($expand)) { $query .= ", '".($expand ? '1' : '0')."'"; }
		$query .= ')';

      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      return SqlWrapper::getInstance()->sql_insert_id();
   }

   public function getChildrenIds() {
      $childrenIds = array();

      $query = "SELECT id FROM `codev_wbs_table` ".
              "WHERE `parent_id` = $this->id ".
              //"AND bug_id IS NULL ".
              "AND root_id = $this->rootId ".
              "AND id <> $this->id ".
              "ORDER BY `order` ";
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
      while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
         $childrenIds[] = $row->id;
      }
      return $childrenIds;
   }

   public function delete($root_id) {

      if ($this->rootId != $root_id) {
            $e = new Exception("delete: WBSElement $id exists with root_id = $this->rootId (expected $root_id)");
            self::$logger->error("EXCEPTION WBSElement constructor: " . $e->getMessage());
            self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
            throw $e;
      }

      // if Folder, must be empty
      $childrenIds = $this->getChildrenIds();
      if ($this->isFolder() && (0 != count($childrenIds))) {
         $e = new Exception("delete: Folder $id must have no Children. (found ".implode(',', $childrenIds).")");
         self::$logger->error("EXCEPTION: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      // delete
      $query = "DELETE FROM `codev_wbs_table` WHERE `id` = " . $this->id;
      $result = SqlWrapper::getInstance()->sql_query($query);
      if (!$result) {
         echo "<span style='color:red'>ERROR: Query FAILED</span>";
         exit;
      }
   }

   public function update() {

      $query = "UPDATE `codev_wbs_table` SET ".
              "`title` = '" . $this->title . "'" .
              ", `order` = " . $this->order .
              ", `parent_id` = " . (is_null($this->parentId) ? "NULL" : $this->parentId) .
              ", `icon` = " . (is_null($this->icon) ? "NULL" : $this->icon).
              ", `font` = " . (is_null($this->font) ? "NULL" : $this->font).
              ", `color` = " . (is_null($this->color) ? "NULL" : $this->color).
              ", `expand` = " . ($this->expand ? '1' : '0').
              " WHERE `id` = " . $this->id;

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
   }

   public function getIcon() {
      return $this->icon;
   }

   public function setIcon($icon) {
      $this->icon = $icon;
   }

   public function getFont() {
      return $this->font;
   }

   public function setFont($font) {
      $this->font = $font;
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
		// Note: no setter

		// if root_id is NULL, then I am the root !
      return (is_null($this->rootId)) ? $this->id : $this->rootId;
   }

   public function getParentId() {
      return $this->parentId;
   }

   public function setParentId($parentId) {
      $this->parentId = $parentId;
   }

   public function getOrder() {
      return $this->order;
   }

   public function setOrder($order) {
      $this->order = $order;
   }

   public function isFolder() {
      return is_null($this->bugId);
   }

   public function isExpand() {
      return $this->expand;
   }

   public function setExpand($isExp) {
      $this->expand = $isExp;
   }

   /**
    * @return array of all the bugids of this branch (recursive calls)
    *
    */
   public function getBugidList($id = NULL, $wbseList = NULL) {
      $bugidList = array();

      // if $this->rootId is null then I am the root
      $rootId = is_null($this->rootId) ? $this->id : $this->rootId;

      if (is_null($id)) { $id = $this->id; }

      if (is_null($wbseList)) {
         #self::$logger->debug("wbs_idList = ".var_export(self::$wbs_idList, true));

         if (array_key_exists($rootId, self::$wbs_idList)) {
            $wbseList = self::$wbs_idList[$rootId];
         } else {
            // get all elements of this root
            // $wbseList[id] = array(parent_id, bug_id)
            $wbseList = array();

            $query = "SELECT id, parent_id, bug_id FROM `codev_wbs_table` ".
                    "WHERE `root_id` = $rootId ".
                    "ORDER BY `parent_id`, `id` ";
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
               echo "<span style='color:red'>ERROR: Query FAILED</span>";
               exit;
            }
            while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
               $wbseList[$row->id] = array('parent_id' => $row->parent_id, 'bug_id' =>  $row->bug_id);
            }
            self::$wbs_idList[$rootId] = $wbseList;
            #self::$logger->debug("INIT getBugidList: root=$rootId, ids = ".implode(',', array_keys($wbseList)));
         }
      }
      #self::$logger->debug("id=$id wbseList = ".implode(',', array_keys($wbseList)));

      // shorten list for the next recursive call
      unset($wbseList[$id]);

      foreach ($wbseList as $i => $e) {
         if ($id == $e['parent_id']) {
            if (!is_null($e['bug_id'])) {
               $bugidList[] = $e['bug_id'];
               unset($wbseList[$i]); // shorten list for the next recursive call
            } else {
               $bugids = $this->getBugidList($i, $wbseList);
               #$bugidList += $bugids;
               $bugidList = array_merge($bugidList, $bugids);
            }
         }
      }
      return $bugidList;
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
         $query  = "SELECT COUNT(id), bug_id FROM `codev_wbs_table` WHERE id=$id ";
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
	 * @param boolean $hasDetail if true, add [Progress, EffortEstim, Elapsed, Backlog, Drift]
	 * @param boolean $isManager
	 * @param int $userid
	 * @return array
	 */
   public function getDynatreeData($hasDetail = false, $isManager = false, $teamid = 0) {

      // TODO AND root_id = $this->getRootId()
      $query = "SELECT * FROM `codev_wbs_table` WHERE `parent_id` = " . $this->getId() . " ORDER BY `order`";
      $result = SqlWrapper::getInstance()->sql_query($query);
      //file_put_contents('/tmp/loadWBS.txt', "$query \n", FILE_APPEND);
      if ($result) {

         $parentArray = array();

         while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
            $wbselement = new WBSElement($row->id, $this->getRootId());

            $childArray = array();

            if ($wbselement->isFolder()) {

               $childArray['isFolder'] = true;
               $childArray['expand'] = $wbselement->isExpand();
               $childArray['key'] = $wbselement->getId();

               $detail = '';
               if ($hasDetail) {
                  $bugids = $this->getBugidList($wbselement->getId());
                  $isel = new IssueSelection("wbs_".$wbselement->getId());
                  foreach($bugids as $bugid) {
                     $isel->addIssue($bugid);
                  }
                  $mgrDriftInfo = $isel->getDriftMgr();
                  $detail = '~' . round(100 * $isel->getProgress())
                          . '~' . $isel->getMgrEffortEstim()
                          . '~' . $isel->getReestimated()
                          . '~' . $isel->getElapsed()
                          . '~' . $isel->duration
                          . '~' . $mgrDriftInfo['nbDays']
                          . '~' . $isel->getDriftColor($mgrDriftInfo['nbDays']);
               }

               $childArray['title'] = $wbselement->getTitle().$detail;
               $childArray['children'] = $wbselement->getDynatreeData($hasDetail, $isManager, $teamid);
            } else {

               $issue = IssueCache::getInstance()->getIssue($wbselement->getBugId());
               if ($issue) {
                  $detail = '';
                  if ($hasDetail) {
							// TODO if isManager...
                     $detail = '~' . round(100 * $issue->getProgress())
                             . '~' . $issue->getMgrEffortEstim()
                             . '~' . $issue->getReestimated()
                             . '~' . $issue->getElapsed()
                             . '~' . $issue->getBacklog()
                             . '~' . $issue->getDriftMgr()
                             . '~' . $issue->getDriftColor($issue->getDriftMgr());
                  }

                  $formattedSummary = $issue->getId().' '.$issue->getSummary();

                  if ($hasDetail) {
                     mb_internal_encoding("UTF-8");
                     $formattedSummary = mb_strimwidth($formattedSummary, 0, 60, "...");
                  }
                  $childArray['title'] = $formattedSummary.$detail;
                  $childArray['isFolder'] = false;
                  $childArray['key'] = $issue->getId(); // yes, bugid !

                  // add tooltip
                  $user = UserCache::getInstance()->getUser($issue->getHandlerId());
                  $titleAttr = array(
                      T_('Project') => $issue->getProjectName(),
                      T_('Category') => $issue->getCategoryName(),
                      T_('Status') => Constants::$statusNames[$issue->getStatus()],
                      T_('Assigned to') => $user->getRealname(),
                      T_('Tags') => implode(',', $issue->getTagList()),
                  );
                  $childArray['href'] = Constants::$codevURL.'/reports/issue_info.php?bugid='.$issue->getId();
                  #$childArray['htmlTooltip'] = Tools::getTooltip($issue->getTooltipItems($teamid, 0, $isManager));
                  $childArray['htmlTooltip'] = Tools::getTooltip($titleAttr);

                  #$childArray['icon'] = 'mantis_ico.gif';

               } else {
                  $childArray['title'] = 'ERROR';
                  $childArray['isFolder'] = false;
                  self::$logger->error("The issue does not exist!");
               }
            }
            if (sizeof($childArray) > 0)
               array_push($parentArray, $childArray);
         }

         // root element not only has children !
         if ($this->id === $this->getRootId()) {
            $detail='';
            if ($hasDetail) {
               $bugids = $this->getBugidList($this->id);
               $isel = new IssueSelection("wbs_".$wbselement->getId());
               foreach($bugids as $bugid) {
                  $isel->addIssue($bugid);
               }
               $mgrDriftInfo = $isel->getDriftMgr();
               $detail = '~' . round(100 * $isel->getProgress())
                       . '~' . $isel->getMgrEffortEstim()
                       . '~' . $isel->getReestimated()
                       . '~' . $isel->getElapsed()
                       . '~' . $isel->duration
                       . '~' . $mgrDriftInfo['nbDays']
                       . '~' . $isel->getDriftColor($mgrDriftInfo['nbDays']);
            }
            $rootArray = array(
                  'title'    => $this->getTitle().$detail,
                  'isFolder' => true,
                  'expand'      => $this->isExpand(),
                  'key'      => $this->getId(),
                  'children' => $parentArray);
            return $rootArray;
         } else {
            return $parentArray;
         }
      } else {
         self::$logger->error("Query failed!");
      }
   }


	/**
	 *
	 * @param type $dynatreeDict
	 */
	public static function updateFromDynatree($dynatreeDict, $root_id = NULL, $parent_id = NULL, $order = 1) {

      file_put_contents('/tmp/tree.txt', "=============\n", FILE_APPEND);
      file_put_contents('/tmp/tree.txt', "root $root_id, parent $parent_id, order $order \n", FILE_APPEND);
      $aa = var_export($dynatreeDict, true);
      file_put_contents('/tmp/tree.txt', "aa=".$aa."\n", FILE_APPEND);
      if (self::$logger->isDebugEnabled()) {
         self::$logger->debug("updateFromDynatree(root=$root_id, parent=$parent_id, order=$order) : \n$aa");
         //self::$logger->debug($aa);
      }

		$id = NULL;
		$title = $dynatreeDict['title'];
		$icon = $dynatreeDict['icon'];
		$font = $dynatreeDict['font'];
		$color = $dynatreeDict['color'];
		$isExpand = $dynatreeDict['expand'];

		$isFolder = $dynatreeDict['isFolder'];
		if ($isFolder) {
			$id = $dynatreeDict['key']; // (null if new folder)

         // new created folders have an id starting with '_'
         if (substr($id, 0, 1) === '_') {
            file_put_contents('/tmp/tree.txt', "is new Folder !\n", FILE_APPEND);
            $id = NULL;
         }

			$bug_id = NULL;
         file_put_contents('/tmp/tree.txt', "isFolder, id = $id\n", FILE_APPEND);
		} else {
			$bug_id = $dynatreeDict['key'];

			// find $id (if exists)
			// Note: parent_id may have changed (if issue moved)
			// Note: $root_id cannot be null because a WBS always starts with a folder (created at Command init)
			$query  = "SELECT id FROM `codev_wbs_table` WHERE bug_id = $bug_id AND root_id = $root_id";
         $result = SqlWrapper::getInstance()->sql_query($query);
         if (!$result) {
            echo "<span style='color:red'>ERROR: Query FAILED</span>";
            exit;
         }
         $row = SqlWrapper::getInstance()->sql_fetch_object($result);
			if (!is_null($row)) {
				$id = $row->id;
			}
         file_put_contents('/tmp/tree.txt', "Issue id = $id, bug_id = $bug_id, \n", FILE_APPEND);
		}

		// create Element
		$wbse = new WBSElement($id, $root_id, $bug_id, $parent_id, $order, $title, $icon, $font, $color, $isExpand);

		// create children
		$children = $dynatreeDict['children'];
      if (!is_null($children)) {
         $childOrder = 1;
         foreach($children as $childDict) {
            self::updateFromDynatree(get_object_vars($childDict), $root_id, $wbse->getId(), $childOrder);
            $childOrder += 1;
         }
      }
	}
}

WBSElement::staticInit();
?>
