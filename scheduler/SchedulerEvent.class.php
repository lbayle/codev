<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Description of SchedulerEvent
 *
 * @author fr20648
 */
class SchedulerEvent {

   private static $logger;

   public static function staticInit() {
      self::$logger = Logger::getLogger(__CLASS__);
   }

   public $bugid;
   public $userid;
   public $startTimestamp;
   public $endTimestamp;
   public $color;

   public function __construct($bugId, $userId, $startT, $endT) {
      $this->bugid = $bugId;
      $this->userid = $userId;
      $this->startTimestamp = $startT;
      $this->endTimestamp = $endT;

//      if ($startT > $endT) {
//         self::$logger->error("bugid=$bugId: Activity startDate $startT (".date('Y-m-d',$startT).") > endDate $endT (".date('Y-m-d',$endT).")");
//      }
   }


   /**
    *
    * @return type
    */
   public function getDxhtmlData($teamid, $session_userid, $isManager, $isExtRef = FALSE){
      $issue = IssueCache::getInstance()->getIssue($this->bugid);
      if (!$isExtRef) {
         $text = $this->bugid;
      } else {
         $text = $issue->getTcId();
         if (NULL == $text) {
            $text = 'm'.$this->bugid;
         }
      }
      $pushdata = array(
          'text'        => $text,
          'bugid'       => $this->bugid,
          'start_date'  => date('Y-m-d H:i:s', $this->startTimestamp),
          'end_date'    => date('Y-m-d H:i:s', $this->endTimestamp),
          'user_id'     => $this->userid,
          'color'       => $this->color,
          'tooltipHtml' => $this->getTooltip($teamid, $session_userid, $isManager),
      );
      return $pushdata;
   }

   private function getTooltip_minimal() {
      $issue = IssueCache::getInstance()->getIssue($this->bugid);

      $finalTooltipAttr = array();

      $extRef = $issue->getTcId();
      if (!empty($extRef)) {
         $finalTooltipAttr[T_('Task')] = $extRef.' (m'.$issue->getId().')';
      } else {
         $finalTooltipAttr[T_('Task')] = $issue->getId();
      }
      $finalTooltipAttr[T_('Summary')] = $issue->getSummary();
      //$finalTooltipAttr[T_('Assigned to')] = implode(', ', getAssignedUsers($issue->getId(), $timePerUserPerTaskList, FALSE));

      if ($issue->getDeadline() > 0) {
         $finalTooltipAttr[T_('Deadline')] = date('Y-m-d', $issue->getDeadline());
      }

      $htmlTooltip =
                 '<table style="margin:0;border:0;padding:0;background-color:white;"><tbody>';
      foreach ($finalTooltipAttr as $key => $value) {
         $htmlTooltip .= '<tr>'.
            '<td valign="top" style="color:blue;width:35px;">'.$key.'</td>'.
            '<td>'.nl2br(htmlspecialchars($value)).'</td>'.
            '</tr>';
      }
      $htmlTooltip .= '</tbody></table>';
      return $htmlTooltip;
   }

   private function getTooltip($teamid, $session_userid, $isManager) {

      $issue = IssueCache::getInstance()->getIssue($this->bugid);
      $finalTooltipAttr = array();

      $tooltipAttr = $issue->getTooltipItems($teamid, $session_userid, $isManager);
      
      unset($tooltipAttr[T_('Status')]);
      unset($tooltipAttr[T_('Priority')]);
      unset($tooltipAttr[T_('Severity')]);
      unset($tooltipAttr[T_('External ID')]);
      unset($tooltipAttr[T_('Backlog')]);
      unset($tooltipAttr[T_('Deadline')]);

      // insert in front
      $extRef = $issue->getTcId();
      if (!empty($extRef)) {
         $finalTooltipAttr[T_('Task')] = $extRef.' (m'.$issue->getId().')';
      } else {
         $finalTooltipAttr[T_('Task')] = $issue->getId();
      }
      if (array_key_exists(T_('Project'), $tooltipAttr)) {
         $finalTooltipAttr[T_('Project')] = $tooltipAttr[T_('Project')];
      }
      $finalTooltipAttr[T_('Summary')] = $issue->getSummary();

      $finalTooltipAttr += $tooltipAttr;
      $finalTooltipAttr[' '] = ' ';

      $finalTooltipAttr[T_('Attributes')] =
         T_('Status').': '.$issue->getCurrentStatusName().' - '.
         T_('Priority').': '.$issue->getPriorityName().' - '.
         T_('Severity').': '.$issue->getSeverityName();

      $finalTooltipAttr[T_('Backlog')] = $issue->getDuration().' '.T_('days');

      if ($issue->getDeadline() > 0) {
         $finalTooltipAttr[T_('Deadline')] = date('Y-m-d', $issue->getDeadline());
      }

      $driftColor = NULL;
      $driftMgrColor = NULL;
      if (array_key_exists('DriftColor', $tooltipAttr)) {
         $driftColor = $tooltipAttr['DriftColor'];
         unset($finalTooltipAttr['DriftColor']);
      }
      if (array_key_exists('DriftMgrColor', $tooltipAttr)) {
         $driftMgrColor = $tooltipAttr['DriftMgrColor'];
         unset($finalTooltipAttr['DriftMgrColor']);
      }
      $htmlTooltip =
                 '<table style="margin:0;border:0;padding:0;background-color:white;"><tbody>';
      foreach ($finalTooltipAttr as $key => $value) {
         $htmlTooltip .= '<tr>'.
                     '<td valign="top" style="color:blue;width:35px;">'.$key.'</td>';
         if ($driftColor != NULL && $key == T_('Drift')) {
            $htmlTooltip .= '<td><span style="background-color:#'.$driftColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
         } else if (!is_null($driftMgrColor) && $key == T_('DriftMgr')) {
            $htmlTooltip .= '<td><span style="background-color:#'.$driftMgrColor.'">&nbsp;&nbsp;'.$value.'&nbsp;&nbsp;</span></td>';
         } else {
            $htmlTooltip .= '<td>'.nl2br(htmlspecialchars($value)).'</td>';
         }
         $htmlTooltip .= '</tr>';
      }
      $htmlTooltip .= '</tbody></table>';
      return $htmlTooltip;
   }

}
SchedulerEvent::staticInit();

