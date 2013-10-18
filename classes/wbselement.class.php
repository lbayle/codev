<?php

class WBSElement extends Model {

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
   private $order;

   public function __construct($id, $details = NULL) {
      if (0 == $id) {
         echo "<span style='color:red'>ERROR: Please contact your CodevTT administrator</span>";
         $e = new Exception("Constructor: Creating a WBSElement with id=0 is not allowed.");
         self::$logger->error("EXCEPTION WBSElement constructor: ".$e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n".$e->getTraceAsString());
         throw $e;
      }

      $this->id = $id;

      $this->initialize($details);
   }

   /**
    * Initialize
    * @param resource $row The issue details
    * @throws Exception If wbselement doesn't exists
    */
   public function initialize($row = NULL) {
      if($row == NULL) {
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
         $this->icon = $row->icon;
         $this->font = $row->font;
         $this->color = $row->color;
         $this->bugId = $row->bug_id;
         $this->parentId = $row->parent_id;
         $this->order = $row->order;
      } else {
         $e = new Exception("Constructor: WBSElement $this->wbselementId does not exist in Mantis DB.");
         self::$logger->error("EXCEPTION WBSElement constructor: " . $e->getMessage());
         self::$logger->error("EXCEPTION stack-trace:\n" . $e->getTraceAsString());
         throw $e;
      }
   }
   
   public function getId() {
   	  return $this->id;
   }
   
   public function getTitle() {
   	return $this->title;
   }
   
   public function getIcon() {
      return $this->icon;
   }
   
   public function getFont() {
      return $this->font;
   }
   
   public function getColor() {
      return $this->color;
   }
   
   public function getBugId() {
      return $this->bugId;
   }
   
   public function getParentId() {
   	return $this->parentId;
   }
   
   public function getOrder() {
      return $this->order;
   }
   
   public function isRoot() {
   	  return ($this->getParentId() == null) ? true : false;
   }
   
   public function isFolder() {
   	  return ($this->getBugId() == null) ? true : false;
   }
   
   public function getChildren($hasDetail) {
   
   	$query = "SELECT * FROM `codev_wbselement_table` WHERE parent_id = " . $this->getId();
   	$result = SqlWrapper::getInstance()->sql_query($query);
   
   	if ($result) {

   		$parentArray = array();
   		
   		while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
	   		$wbselement = new WBSElement($row->id);	
	   		$childArray = array();	   
	   		
	   		if ($wbselement->isFolder()) {	   			
	   			
	   			$childArray['title'] = $wbselement->getTitle();
	   			$childArray['isFolder'] = true;
	   			$childArray['children'] = $wbselement->getChildren($hasDetail);
		
	   		}
	   		else {

   				$issue = IssueCache::getInstance()->getIssue($wbselement->getBugId());
   				
   				if ($issue) {
   					
   					$detail = '';
   					
   					if ($hasDetail) {
   						
   						$detail = (($issue->getProgress() != null) ? ('~'.$issue->getProgress()) : '')
	   					. (($issue->getMgrEffortEstim() != null) ? ('~'.$issue->getMgrEffortEstim()) : '')
	   					. (($issue->getElapsed() != null) ? ('~'.$issue->getElapsed()) : '')
	   					. (($issue->getBacklog() != null) ? ('~'.$issue->getBacklog()) : '')
	   					. (($issue->getDriftMgr() != null) ? ('~'.$issue->getDriftMgr()) : '');
   						
   					}

	   				$childArray['title'] = $issue->getSummary() . $detail; 
	   				$childArray['isFolder'] = false;

   				}
   				
   				else {
   					
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

}

WBSElement::staticInit();
?>
