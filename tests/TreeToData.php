<?php
require('../include/session.inc.php');
require('../path.inc.php');

if (Tools::isConnectedUser() && (isset($_POST['data']))) {

   if (isset($_POST['data'])) {

   	$jsonAfter = $_POST['data'];   	
   	file_put_contents('../tpl/treeDataEdited.json', $jsonAfter);
   	$treeArrayAfter = json_decode($jsonAfter, true);
   	$treeDataAfter = $treeArrayAfter[0];
   	//echo var_dump($treeDataAfter);
   	
   	$jsonBefore = file_get_contents('../tpl/treeData.json');
   	$treeDataBefore = json_decode($jsonBefore, true);
   	//echo var_dump($treeDataBefore);
   	
   	$query = "SELECT * FROM `codev_wbselement_table` WHERE parent_id IS NULL";
   	$result = SqlWrapper::getInstance()->sql_query($query);
   	if (!$result) {
   		echo "<span style='color:red'>ERROR: Query FAILED</span>";
   		exit;
   	}
   	$row = SqlWrapper::getInstance()->sql_fetch_object($result);
   	$root = new WBSElement($row->id);
   	
   	$arr_wbselement = compare($treeDataBefore, $treeDataAfter, 0, $root, null, 1);
   	
   	foreach ($arr_wbselement as $wbs) {
   		file_put_contents('../tpl/test.txt', '------'.$wbs->getId().' '.$wbs->getParentId().' '.$wbs->getOrder().' '.$wbs->getTitle().' '.($wbs->isAdded()?'yes':'no').' '.($wbs->isRemoved()?'yes':'no')."\n", FILE_APPEND);
   		/*
   		if ($wbs->isAdded())
   			$wbs->update();
   		else if ($wbs->isRemoved())
   			$wbs->remove();
   			*/
   		$wbs->update();	
   	}
   }else {
         Tools::sendNotFoundAccess();
      }
} else {
   Tools::sendUnauthorizedAccess();
}


function compare($objA, $objB, $tagName, WBSElement $wbselement, $parent, $tour) {
	
	$arr = array();
	
	file_put_contents('../tpl/test.txt', $tour.'tagName '.$tagName."\n", FILE_APPEND);
	
	if (isset($wbselement))
		file_put_contents('../tpl/test.txt', $tour.'ID '.$wbselement->getId()."\n", FILE_APPEND);
	
	$typeA = gettype($objA);
	$typeB = gettype($objB);
	file_put_contents('../tpl/test.txt', $tour.'typeA '.$typeA."\n", FILE_APPEND);
	file_put_contents('../tpl/test.txt', $tour.'typeB '.$typeB."\n", FILE_APPEND);
	
	$aString = "";
	$bString = "";
	
	switch ($typeA) {
		case "string" :
			$aString = $objA;
			break;
		case "integer" :
			$aString = strval($objA);
			break;
		case "boolean" : 
			$aString = ($objA) ? "true" : "false";
			break;
	}
	
	switch ($typeB) {
		case "string" :
			$bString = $objB;
			break;
		case "integer" :
			$bString = strval($objB);
			break;
		case "boolean" :
			$bString = ($objB) ? "true" : "false";
			break;
	}
	
	if ($typeA !== $typeB || ($typeA !== "object" && $typeA !== "array" && $objA !== $objB)) {
		file_put_contents('../tpl/test.txt', $aString.' changeto '.$bString."\n", FILE_APPEND);
		if ($tagName === "title") {
			$wbselement->setTitle($bString);
			$wbselement->setAdded();
			$wbselement->setRemoved();
		}
	}

	if ($typeA === "array" || $typeB === "array") {

		$keys = [];
		
		foreach ($objA as $a=>$value) {
			if (array_key_exists($a, $objA)) {
				array_push($keys, $a);
			}
		}
		
		foreach ($objB as $b=>$value) {
			if (array_key_exists($b, $objB)) {
				array_push($keys, $b);
			}
		}
	
		sort($keys);
		
		if (isset($objB)){
			
			if (is_numeric($tagName)) {
				
				$wbselementId = $objB['key'];
			
				if (array_key_exists($wbselementId, $arr)) {
					$wbselement = $arr[$wbselementId];
				}
				else{
					$wbselement = new WBSElement($wbselementId);
					$arr[$wbselementId] = $wbselement;
					file_put_contents('../tpl/test.txt', $tour.'--ADD--'.$wbselement->getId()."\n", FILE_APPEND);
				}
				
				$wbselement->setOrder(intval($tagName) + 1);
				
				if (isset($parent))
					$wbselement->setParentId($parent);
			}
			
			else if ($tagName === "title") {
				$wbselement->setTitle($bString);
				$wbselement->setAdded();
			}
			
			else if ($tagName === "children") {
				$parent = $wbselement->getId();
			}				
		}		
		
		for ($i = 0; $i < count($keys); $i++) {
			
			if ($keys[$i] === $keys[$i-1]) {
				continue;
			}
				
			file_put_contents('../tpl/test.txt', $tour.'hello ID'.$wbselement->getId().' PARENT '.$wbselement->getParentId().' ORDER '.$wbselement->getOrder().' '.$keys[$i].' valueA '.$objA[$keys[$i]].' valueB '.$objB[$keys[$i]]."\n\n", FILE_APPEND);					
			
			$arr = array_merge($arr, compare($objA[$keys[$i]], $objB[$keys[$i]], $keys[$i], $wbselement, $parent, $tour+1));
				
		}
		
		file_put_contents('../tpl/test.txt', '-----------FIN DE TOUR '.$tour."\n", FILE_APPEND);
		
	}
	
	else {
		
	}
	
	return $arr;
	
}


?>