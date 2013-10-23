<?php

require('../include/session.inc.php');
require('../path.inc.php');

$query = "SELECT * FROM `codev_wbselement_table` WHERE parent_id IS NULL";
$result = SqlWrapper::getInstance()->sql_query($query);
if (!$result) {
	echo "<span style='color:red'>ERROR: Query FAILED</span>";
	exit;
}

$treeData = array();
$row = SqlWrapper::getInstance()->sql_fetch_object($result);
$root = new WBSElement($row->id);
$treeData['title'] = $root->getTitle();
$treeData['isFolder'] = true;
$treeData['key'] = $root->getId();

$hasDetail = $_GET['hasDetail'];
$jsonName = '';
if ($hasDetail == 1) {
	$jsonName = 'treeDataDetail.json';
}
else {
	$jsonName = 'treeData.json';
}

$treeData['children'] = $root->getChildren($hasDetail);
//echo var_dump($treeData);
file_put_contents('../tpl/'.$jsonName, json_encode($treeData));

?>