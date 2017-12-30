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
         $sql = AdodbWrapper::getInstance();
         $query = "SELECT * FROM codev_wbs_table " .
                 "WHERE id = ".$sql->db_param();
         $result = $sql->sql_query($query, array($this->id));
         $row = $sql->fetchObject($result);
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

      $sql = AdodbWrapper::getInstance();

		// --- check values
		if (!is_null($parent_id)) {
			// check parrent exists and is a folder
			$queryP = "SELECT bug_id FROM codev_wbs_table WHERE id = ".$sql->db_param();
         $resultP = $sql->sql_query($queryP, array($parent_id));
         $found  = (0 != $sql->getNumRows($resultP)) ? true : false;
			if (!$found) {
				$e = new Exception("create WBSElement: parrent_id $parent_id does not exist.");
				self::$logger->error("EXCEPTION: " . $e->getMessage());
				self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
				throw $e;
			}
			$rowP = $sql->fetchObject($resultP);
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
      $query  = 'INSERT INTO codev_wbs_table (order';
		if (!is_null($bug_id))    { $query .= ', bug_id'; }
		if (!is_null($parent_id)) { $query .= ', parent_id'; }
		if (!is_null($root_id))   { $query .= ', root_id'; }
		if (!is_null($title))     { $query .= ', title'; }
		if (!is_null($icon))      { $query .= ', icon'; }
		if (!is_null($font))      { $query .= ', font'; }
		if (!is_null($color))     { $query .= ', color'; }
		if (!is_null($expand))    { $query .= ', expand'; }
		$query .= ") VALUES (".$sql->db_param();
      $q_params[]=$order;
		if (!is_null($bug_id))    { $query .= ", ".$sql->db_param(); $q_params[]=$bug_id; }
		if (!is_null($parent_id)) { $query .= ", ".$sql->db_param(); $q_params[]=$parent_id; }
		if (!is_null($root_id))   { $query .= ", ".$sql->db_param(); $q_params[]=$root_id; }
		if (!is_null($title))     { $query .= ", ".$sql->db_param(); $q_params[]=$title; }
		if (!is_null($icon))      { $query .= ", ".$sql->db_param(); $q_params[]=$icon; }
		if (!is_null($font))      { $query .= ", ".$sql->db_param(); $q_params[]=$font; }
		if (!is_null($color))     { $query .= ", ".$sql->db_param(); $q_params[]=$color; }
		if (!is_null($expand))    { $query .= ", ".$sql->db_param(); $q_params[]=($expand ? '1' : '0'); }
		$query .= ')';

      if(self::$logger->isDebugEnabled()) {
         self::$logger->debug("create SQL ".$query);
      }

      $sql->sql_query($query, $q_params);
      return $sql->getInsertId();
   }

   /**
    * parse all WBSs for issues not found in mantis_bug_table. if any, remove them from the WBS.
    */
   public static function checkWBS() {
      $sql = AdodbWrapper::getInstance();
      $query0 = "SELECT root_id, bug_id FROM codev_wbs_table WHERE bug_id NOT IN (SELECT id FROM {bug})";
      $result0 = $sql->sql_query($query0);
      while ($row = $sql->fetchObject($result0)) {
         self::$logger->warn("Issue $row->bug_id does not exist in Mantis: now removed from WBS (root = $row->root_id)");

         // remove from WBS
         $query = "DELETE FROM codev_wbs_table WHERE bug_id = ".$sql->db_param();
         $sql->sql_query($query, array($row->bug_id));
      }
   }

   /**
    * 
    * @param string $title title of the wbsElement
    * @param int $rootId
    * @param int $parentId
    * @param int $isFolder search for folders only
    * @return int id or NULL if not found
    * @throws Exception if multiple rows found
    */
   public static function getIdByTitle($title, $rootId = NULL, $parentId = NULL, $isFolder = FALSE) {
      $sql = AdodbWrapper::getInstance();
      $query = "SELECT id FROM codev_wbs_table WHERE title =  ".$sql->db_param();
      $q_params[]=$title;

      if (NULL !== $rootId) {
         $query .= " AND root_id = ".$sql->db_param();
         $q_params[]=$rootId;
      }
      if (NULL !== $parentId) {
         // usefull if tree has parents with same name: /name1/name1/name1
         $query .= " AND parent_id = ".$sql->db_param();
         $q_params[]=$parentId;
      }
      if ($isFolder) {
         $query .= " AND bug_id IS NULL ";
      }
      $result = $sql->sql_query($query, $q_params);
      $nbRows  = $sql->getNumRows($result);
      switch ($nbRows) {
         case 0:
            // not found
            $id = NULL;
            break;
         case 1:
            $id = $sql->sql_result($result, 0);
            break;
         default:
            throw new Exception("Found multiple wbsElement with title=$title and rootId=$rootId");
      }
      return $id;
   }

   /**
    *
    * @return array
    */
   public function getChildrenIds() {
      $childrenIds = array();
      $sql = AdodbWrapper::getInstance();

      $query = "SELECT id FROM codev_wbs_table ".
              "WHERE parent_id = ".$sql->db_param().
              //" AND bug_id IS NULL ".
              " AND root_id = ".$sql->db_param().
              " AND id <> ".$sql->db_param().
              'ORDER BY "order" ';
      $result = $sql->sql_query($query, array($this->id, $this->rootId, $this->id));

      while ($row = $sql->fetchObject($result)) {
         $childrenIds[] = $row->id;
      }
      return $childrenIds;
   }

   /**
    * 
    * @param type $root_id
    * @throws Exception
    */
   public function delete($root_id) {

      if ($this->rootId != $root_id) {
            $e = new Exception("delete: WBSElement $id exists with root_id = $this->rootId (expected $root_id)");
            //self::$logger->error("EXCEPTION WBSElement delete: " . $e->getMessage());
            //self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
            throw $e;
      }

      // if Folder, must be empty
      $childrenIds = $this->getChildrenIds();
      if ($this->isFolder() && (0 != count($childrenIds))) {
         $e = new Exception("delete: Folder $id must have no Children. (found ".implode(',', $childrenIds).")");
         //self::$logger->error("EXCEPTION: " . $e->getMessage());
         //self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }

      // delete
      $sql = AdodbWrapper::getInstance();
      $query = "DELETE FROM codev_wbs_table WHERE id = " . $sql->db_param();
      $sql->sql_query($query, array($this->id));
   }

   public function update() {

      $sql = AdodbWrapper::getInstance();
      $query = "UPDATE codev_wbs_table SET ".
              " title = " . $sql->db_param().
              ', "order" = ' . $sql->db_param().
              ", parent_id = " . $sql->db_param().
              ", icon = " . $sql->db_param().
              ", font = " . $sql->db_param().
              ", color = " . $sql->db_param().
              ", expand = " . $sql->db_param().
              " WHERE id = " . $sql->db_param();
      $q_params[]=$this->title;
      $q_params[]=$this->order;
      $q_params[]=(is_null($this->parentId) ? "NULL" : $this->parentId);
      $q_params[]=(is_null($this->icon)     ? "NULL" : $this->icon);
      $q_params[]=(is_null($this->font)     ? "NULL" : $this->font);
      $q_params[]=(is_null($this->color)    ? "NULL" : $this->color);
      $q_params[]=($this->expand            ? '1' : '0');
      $q_params[]=$this->id;

      $sql->sql_query($query, $q_params);
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

            $sql = AdodbWrapper::getInstance();
            $query = "SELECT id, parent_id, bug_id FROM codev_wbs_table ".
                    " WHERE root_id = ".$sql->db_param().
                    " ORDER BY parent_id, id ";
            $result = $sql->sql_query($query, array($rootId));

            while ($row = $sql->fetchObject($result)) {
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
         $sql = AdodbWrapper::getInstance();
         $query  = "SELECT COUNT(id), bug_id FROM codev_wbs_table WHERE id= ".$sql->db_param();
         $result = $sql->sql_query($query, array($id));

         #$found  = (0 != $sql->getNumRows($result)) ? true : false;
         $nbTuples  = (0 != $sql->getNumRows($result)) ? $sql->sql_result($result, 0) : 0;
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
      try {
         $sql = AdodbWrapper::getInstance();
         $query = 'SELECT * FROM codev_wbs_table WHERE parent_id = '.$sql->db_param().' ORDER BY "order"';
         $result = $sql->sql_query($query, array($this->getId()));
         //file_put_contents('/tmp/loadWBS.txt', "$query \n", FILE_APPEND);

         $parentArray = array();

         while ($row = $sql->fetchObject($result)) {
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
                     try {
                        $isel->addIssue($bugid);
                     } catch (Exception $e) {
                        self::$logger->error("Issue $bugid does not exist in Mantis DB.");
                     }
                  }
                  if ($isManager) {
                     $mgrEffortEstim = $isel->getMgrEffortEstim();
                     $effortEstim = $isel->getEffortEstim();
                     $driftInfo = $isel->getDrift();
                     $mgrDriftInfo = $isel->getDriftMgr();
                     $reestimated = $isel->getReestimated();
                  } else {
                     $mgrEffortEstim = '0';
                     $effortEstim = $isel->getEffortEstim();
                     $driftInfo = $isel->getDrift();
                     $mgrDriftInfo = array('nbDays' => 0, 'percent' => 0);
                     $reestimated = '0';
                  }

                  $detail = '~' . round(100 * $isel->getProgress())
                          . '~' . $mgrEffortEstim
                          . '~' . $effortEstim
                          . '~' . $reestimated
                          . '~' . $isel->getElapsed()
                          . '~' . $isel->duration
                          . '~' . $mgrDriftInfo['nbDays']
                          . '~' . $isel->getDriftColor($mgrDriftInfo['nbDays'])
                          . '~' . $driftInfo['nbDays']
                          . '~' . $isel->getDriftColor($driftInfo['nbDays']);
               }

               $childArray['title'] = $wbselement->getTitle().$detail;
               $childArray['children'] = $wbselement->getDynatreeData($hasDetail, $isManager, $teamid);
            } else {

               try {
                  // avoid logging an exception...
                  if (!Issue::exists($wbselement->getBugId())) {
                     $e = new Exception("Issue with id=".$wbselement->getBugId()." not found.");
                     throw $e;
                  }

                  $issue = IssueCache::getInstance()->getIssue($wbselement->getBugId());
                  $detail = '';
                  if ($hasDetail) {

                     if ($isManager) {
                        $mgrEffortEstim = $issue->getMgrEffortEstim();
                        $effortEstim = $issue->getEffortEstim();
                        $mgrDrift = $issue->getDriftMgr();
                        $drift = $issue->getDrift();
                        $reestimated = $issue->getReestimated();
                     } else {
                        $mgrEffortEstim = '0';
                        $effortEstim = $issue->getEffortEstim();
                        $mgrDrift = '0';
                        $drift = $issue->getDrift();
                        $reestimated = '0';
                     }

                     $detail = '~' . round(100 * $issue->getProgress())
                             . '~' . $mgrEffortEstim
                             . '~' . $effortEstim
                             . '~' . $reestimated
                             . '~' . $issue->getElapsed()
                             . '~' . $issue->getBacklog()
                             . '~' . $mgrDrift
                             . '~' . $issue->getDriftColor($mgrDrift)
                             . '~' . $drift
                             . '~' . $issue->getDriftColor($drift);
                  }

                  $formattedSummary = '<b>'.$issue->getId().'</b> '.$issue->getSummary();

//                  if ($hasDetail) {
//                     mb_internal_encoding("UTF-8");
//                     $formattedSummary = mb_strimwidth($formattedSummary, 0, 70, "...");
//                  }
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

               } catch (Exception $e) {
                  //$childArray['title'] = $wbselement->getBugId().' - '.T_('Error: Task not found in Mantis DB !');
                  //$childArray['isFolder'] = false;
                  self::$logger->warn("Issue $bugid does not exist in Mantis DB: calling checkWBS()");
                  $childArray = array();

                  // remove from WBS
                  self::checkWBS();
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
               $isel = new IssueSelection("wbs_".$this->id);
               foreach($bugids as $bugid) {
                  try {
                     $isel->addIssue($bugid);
                  } catch (Exception $e) {
                     self::$logger->error("Issue $bugid does not exist in Mantis DB.");
                  }
               }
               if ($isManager) {
                  $mgrEffortEstim = $isel->getMgrEffortEstim();
                  $effortEstim = $isel->getEffortEstim();
                  $driftInfo = $isel->getDrift();
                  $mgrDriftInfo = $isel->getDriftMgr();
                  $reestimated = $isel->getReestimated();
               } else {
                  $mgrEffortEstim = '0';
                  $effortEstim = $isel->getEffortEstim();
                  $driftInfo = $isel->getDrift();
                  $mgrDriftInfo = array('nbDays' => 0, 'percent' => 0);
                  $reestimated = '0';
               }

               $mgrDriftInfo = $isel->getDriftMgr();
               $detail = '~' . round(100 * $isel->getProgress())
                       . '~' . $mgrEffortEstim
                       . '~' . $effortEstim
                       . '~' . $reestimated
                       . '~' . $isel->getElapsed()
                       . '~' . $isel->duration
                       . '~' . $mgrDriftInfo['nbDays']
                       . '~' . $isel->getDriftColor($mgrDriftInfo['nbDays'])
                       . '~' . $driftInfo['nbDays']
                       . '~' . $isel->getDriftColor($driftInfo['nbDays']);            }
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
      } catch (Exception $e) {
         self::$logger->error("Query failed!");
      }
   }


	/**
	 *
	 * @param type $dynatreeDict
	 */
	public static function updateFromDynatree($dynatreeDict, $root_id = NULL, $parent_id = NULL, $order = 1) {

      $aa = var_export($dynatreeDict, true);
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
            $id = NULL;
         }

			$bug_id = NULL;
		} else {
			$bug_id = $dynatreeDict['key'];

			// find $id (if exists)
			// Note: parent_id may have changed (if issue moved)
			// Note: $root_id cannot be null because a WBS always starts with a folder (created at Command init)
         $sql = AdodbWrapper::getInstance();
			$query  = "SELECT id FROM codev_wbs_table WHERE bug_id = ".$sql->db_param().
                   " AND root_id = ".$sql->db_param();
         $result = $sql->sql_query($query, array($bug_id, $root_id));
         
         $row = $sql->fetchObject($result);
			if (!is_null($row)) {
				$id = $row->id;
			}
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

