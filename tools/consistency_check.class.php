<?php


//include_once "constants.php";
//include_once "tools.php";
include_once "../reports/issue.class.php";
include_once "../auth/user.class.php";

class ConsistencyError {
	
	var $bugId;
	var $userId;
	var $teamId;
	var $desc;
   var $timestamp;
	
	public function ConsistencyError($bugId, $userId, $desc) {
		$this->bugId  = $bugId;
      $this->userId = $userId;
	   $this->desc   = $desc;
	}
}


class ConsistencyCheck {
   var $userId;
  
  
   // ----------------------------------------------
   public function ConsistencyCheck($userId) {
  	   $this->userId = $userId;
  	   
  	   $this->initialize();
   }
  
   // ----------------------------------------------
   public function initialize() {  
   }
  
   // ----------------------------------------------
   // fiches analyzed dont BI non renseignes
   public function checkBI() {
      $cerrList = array();
  	   $cerrList[] = new ConsistencyError(332, 7, "DEBUG tamere BI");
      $cerrList[] = new ConsistencyError(330, 8, "DEBUG tamere BI");
  	   return $cerrList;
   }
   // ----------------------------------------------
   // fiches analyzed dont RAE non renseignes
   public function checkRAE() {
      $cerrList = array();
      $cerrList[] = new ConsistencyError(123, 5, "DEBUG tamere RAE missing");
      return $cerrList;
   }
   
   // ----------------------------------------------
   // fiches resolved dont le RAE != 0
   public function checkResolved() {
      $cerrList = array();
      $cerrList[] = new ConsistencyError(222, 6, "DEBUG tamere RAE NOT null");
      return $cerrList;
   }
  
}


?>