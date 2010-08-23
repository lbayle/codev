<?php

// -- Issues tracking

include_once "issue.class.php";

class IssueTracking {
  var $issueList; 
  
  var $startTimestamp;
  var $endTimestamp;
  var $displayedStatusList;
    
  public function IssueTracking() {
    $this->issueList = array();
  }
    
  public function initialize() {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_deferred;
    global $status_resolved;
    global $status_closed;
        
    $this->displayedStatusList = array($status_new, 
                                       $status_ack, 
                                       $status_feedback, 
                                       $status_analyzed,
                                       $status_accepted,
                                       $status_openned,
                                       $status_deferred,
                                       $status_resolved,
                                       $status_closed);
        
    $query = "SELECT id FROM `mantis_bug_table` ORDER BY id DESC";
    $result = mysql_query($query) or die("Query failed: $query");

    while($row = mysql_fetch_object($result))
    {
      $issue = new Issue ($row->id);
      $issue->computeDurations();
      $this->issueList[$row->id] = $issue; 
    }
  }
  
  // ------------------------------------------
  // Table Avancement / fiche
  public function forseingTableDisplay() {
    global $status_resolved;
    global $status_closed;

    echo "<table>\n";
    echo "<caption>Avancement / fiche</caption>";
    echo "<tr>\n";
    echo "<th>Mantis</th>\n";
    echo "<th>Fiche TC</th>\n";
    echo "<th>Description</th>\n";
    echo "<th>Date Submission</th>\n";
    echo "<th>ETA</th>\n";
    echo "<th>Effort Estim</th>\n";
    echo "<th title='Est effort from TimeTracking + deprecated mantis custom field'>Elapsed</th>\n";
    echo "<th>Remaining</th>\n";
    echo "<th>Current Status</th>\n";
    echo "<th>Release</th>\n";
    echo "<th>Derive</th>\n";
    echo "</tr>\n";
  
    foreach ($this->issueList as $bugId => $tmpIssue) {
      // REM do not display SuiviOp tasks
      if (!$tmpIssue->isSideTaskIssue()) {
        echo "<tr>\n";
        echo "<td>$tmpIssue->bugId</td>\n";
        echo "<td>$tmpIssue->tcId</td>\n";
        echo "<td>$tmpIssue->summary</td>\n";
        echo "<td>".date("d F Y", $tmpIssue->dateSubmission)."</td>\n";
        /*
          if ("(select)" == $tmpIssue->difficulty) {
          $res = ".";
          } else {
          $res = $tmpIssue->difficulty;
          }
          echo "\t\t<td>$res</td>\n";
        */
        echo "<td>".$tmpIssue->getEtaName()."</td>\n";
        echo "<td>".$tmpIssue->EffortEstim."</td>\n";
        echo "<td>".$tmpIssue->elapsed."</td>\n";
        echo "<td>".$tmpIssue->remaining."</td>\n";
        echo "<td>".$tmpIssue->getCurrentStatusName()."</td>\n";
        echo "<td>".$tmpIssue->release."</td>\n";
        $derive = $tmpIssue->getDrift();
        if ((0 > $derive) && 
            ($status_resolved != $tmpIssue->currentStatus) && 
            ($status_closed   != $tmpIssue->currentStatus)) {
          echo "<td style='background-color: #fcbdbd;'>".($derive)."</td>\n";
        } else {
          echo "<td>".($derive)."</td>\n";
        }
        echo "</tr>\n";
      }
    }
  }
    
  // ------------------------------------------
  // Table Repartition du temps par status
  public function durationsTableDisplay() {
    global $statusNames;
        
    echo "<table>\n";
    echo "<caption>R&eacute;partition du temps par status</caption>";
    echo "<tr>\n";
    echo "<th>Mantis</th>\n";
    echo "<th>Fiche TC</th>\n";
    echo "<th>Description</th>\n";
    foreach($this->displayedStatusList as $status) {
      echo "<th>".$statusNames[$status]."</th>\n";
    }
    echo "</tr>\n";
      
    foreach ($this->issueList as $bugId => $tmpIssue) {
      // REM do not display SuiviOp tasks
      if (!$tmpIssue->isSideTaskIssue()) {
        echo "<tr>\n";
        echo "<td>$tmpIssue->bugId</td>\n";
        echo "<td>$tmpIssue->tcId</td>\n";
        echo "<td>$tmpIssue->summary</td>\n";
        
        foreach($this->displayedStatusList as $status) {
          $res = getDurationLiteral($tmpIssue->statusList[$status]->duration);
          if ($status == $tmpIssue->currentStatus) {
            echo "<td>$res</td>\n";
          } else {
            echo "<td>$res</td>\n";
          }
        }
        echo "</tr>\n";
      }
    }
    echo "</table>\n";
  }

  public function durationsTableToCSV($fileName) {
    global $status_new;
    global $status_feedback;
    global $status_ack;
    global $status_analyzed;
    global $status_accepted;
    global $status_openned;
    global $status_resolved;
    global $status_closed;
    global $status_feedback_ATOS;
    global $status_feedback_FDJ;

    $fieldSep = ';';

    // Open file in 'overwrite mode'
    $fh = fopen($fileName, 'w') or die("can't open file");

    $stringData = "Fiche TC".$fieldSep."Description".$fieldSep."New".$fieldSep.
      "Acknowledge".$fieldSep."Feedback ATOS".$fieldSep."Feedback FDJ".$fieldSep."Analyze".$fieldSep."Accepted".$fieldSep."Openned".$fieldSep."Resolved".$fieldSep."Closed\n";
    fwrite($fh, $stringData);

    foreach ($this->issueList as $bugId => $tmpIssue) {
      $stringData  = $tmpIssue->tcId.$fieldSep;
      $stringData .= $tmpIssue->summary.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_new]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_ack]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_feedback_ATOS]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_feedback_FDJ]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_analyzed]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_accepted]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_openned]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_resolved]->duration.$fieldSep;
      $stringData .= $tmpIssue->statusList[$status_closed]->duration."\n";

      fwrite($fh, $stringData);
    }
    fclose($fh);
  }    
    
  public function estimationsToCSV($fileName) {
    $fieldSep = ';';

    // Open file in 'overwrite mode'
    $fh = fopen($fileName, 'w') or die("can't open file");

    $stringData = "Fiche TC".$fieldSep."Description".$fieldSep."Date Submission".$fieldSep.
      "Difficulty".$fieldSep."EffortEstim".$fieldSep."Elapsed".$fieldSep.
      "Remaining".$fieldSep."Current Status".$fieldSep."Release".$fieldSep."Derive\n";
    fwrite($fh, $stringData);

    foreach ($this->issueList as $bugId => $tmpIssue) {
      $stringData  = $tmpIssue->tcId.$fieldSep;
      $stringData .= $tmpIssue->summary.$fieldSep;

      $stringData .= date("d F Y", $tmpIssue->dateSubmission).$fieldSep;
         
      if ("(select)" == $tmpIssue->difficulty) {
        $stringData .= "".$fieldSep;
      } else {
        $stringData .= $tmpIssue->difficulty.$fieldSep;
      }
         
      $stringData .= $tmpIssue->EffortEstim.$fieldSep;
      $stringData .= $tmpIssue->elapsed.$fieldSep;
      $stringData .= $tmpIssue->remaining.$fieldSep;
      $stringData .= $tmpIssue->getCurrentStatusName().$fieldSep;
      $stringData .= ($tmpIssue->EffortEstim - $tmpIssue->elapsed - $tmpIssue->remaining).$fieldSep;
      $stringData .= $tmpIssue->release."\n";

      fwrite($fh, $stringData);
    }

    fclose($fh);
  }    
  
} // end class IssueTracking

?>
