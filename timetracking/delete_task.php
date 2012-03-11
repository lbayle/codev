<?php

if (!isset($_SESSION)) { 
    session_start();
    header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
} 

include_once '../path.inc.php';
include_once 'i18n.inc.php';

if (!isset($_SESSION['userid'])) {
  echo T_("Sorry, you need to <a href='../'>login</a> to access this page.");
  exit;
}

include_once "super_header.inc.php";

include_once "issue.class.php";

$logger = Logger::getLogger("delete_task");

$trackid  = $_POST['trackid'];

// increase remaining (only if 'remaining' already has a value)
$query = "SELECT bugid, jobid, duration FROM `codev_timetracking_table` WHERE id = $trackid;";
$result = mysql_query($query);

if (!$result) {
    $logger->error("Query FAILED: $query");
    $logger->error(mysql_error());
    header('HTTP/1.0 419 Query FAILED');
    exit;
}

while($row = mysql_fetch_object($result)) {
    // REM: only one line in result, while should be optimized
    $bugid = $row->bugid;
    $duration = $row->duration;
    $job = $row->jobid;
}

$issue = IssueCache::getInstance()->getIssue($bugid);
// do NOT decrease remaining if job is job_support !
if ($job != $job_support) {
    if (NULL != $issue->remaining) {
        $remaining = $issue->remaining + $duration;
        $issue->setRemaining($remaining);
    }
}

// delete track
# TODO use TimeTrack::delete($trackid) 
$query = "DELETE FROM `codev_timetracking_table` WHERE id = $trackid;";
$result = mysql_query($query);
if (!$result) {
    $logger->error("Query FAILED: $query");
    $logger->error(mysql_error());
    header('HTTP/1.0 419 Query FAILED');
    exit;
}

echo $trackid;

?>
