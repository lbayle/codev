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

$treeData['children'] = $root->getChildren(1);
file_put_contents('../tpl/treeDataDetail.json', json_encode($treeData));

$treeData['children'] = $root->getChildren(0);
file_put_contents('../tpl/treeData.json', json_encode($treeData));
?>