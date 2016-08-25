<?php
/*
  This file is part of CoDevTT.

  CoDev-Timetracking is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CoDevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
 */


abstract class SchedulerTaskProviderAbstract {

   private static $logger;

   /**
    * Initialize static variables
    * @static
    */
   public static function staticInit() {
       self::$logger = Logger::getLogger(__CLASS__);
   }


   /**
    * One liner description for settings (radio-button text)
    */
   abstract public function getShortDesc();

   /**
    * detailed description
    */
   abstract public function getDesc();

   /**
    * Create the candidate Task list
    *
    * @param type $todoTasks : array of bugid
    */
   abstract public function createCandidateTaskList($todoTasks);

   /**
    * Get next user task
    *
    * @param array $assignedUserTasks : array of bugid
    * @param type $cursor : bugid of previous activity
    * @return task id : If the next task doesn't exist, return the first. If no task, return null
    */
   abstract public function getNextUserTask($assignedUserTasks, $cursor = NULL);


   /**
    * Remove tasks beeing constrained by another task.
    *
    * The constrained tasks should not be planified unless the
    * constraining task is resolved or fully planified.
    *
    * @param array $todoTasks
    * @return array
    */
   protected function removeConstrainedTasks(array $todoTasks) {
      //self::$logger->error("removeConstrainedTasks: INITIAL nb todoTasks: ".count($this->todoTaskList));
      //self::$logger->error("removeConstrainedTasks: nb todoTasks: ".count($todoTasks));

      /*
       * si planifiee et non terminee:
       *    la constrainingTask est présente dans $todoTasks
       *    => remove constrainedTask
       * si planifiée et terminee:
       *    la constrainingTask est absente de $todoTasks
       *    la constrainingTask est présente dans $this->todoTaskList (liste initiale)
       *    => keep constrainedTask
       * si non planifiee et non resolved
       *    la constrainingTask est absente de $todoTasks
       *    la constrainingTask est absente de $this->todoTaskList
       *    => remove constrainedTask (wait forever)
       * si non planifiee et resolved
       *    la constrainingTask est absente de $todoTasks
       *    la constrainingTask est absente de $this->todoTaskList
       *    => keep constrainedTask
       */


      // ---------- Remove tasks constrained by another task ----------
      foreach ($todoTasks as $taskIdKey => $taskId) {
          $task = IssueCache::getInstance()->getIssue($taskId);
          $taskRelationships = $task->getRelationships();

          // If task is constrained by another task
          $constrainingTasks = $taskRelationships[Constants::$relationship_constrained_by];
          if (is_array($constrainingTasks)) {
            foreach ($constrainingTasks as $constrainingBugid) {
               // constraining task is in the todoList: current task must wait
               if (in_array($constrainingBugid, $todoTasks)) {
                   //self::$logger->error("task $taskId removed: constrained by $constrainingBugid (found in todoList)");
                   unset($todoTasks[$taskIdKey]);
                   break; // no need to check other constraining tasks
               } else {
                  $issue = IssueCache::getInstance()->getIssue($constrainingBugid);
                  if ((!in_array($constrainingBugid, $this->todoTaskList)) &&
                      (!$issue->isResolved())) {
                      //self::$logger->error("task $taskId removed: constrained by $constrainingBugid (not resolved)");
                      unset($todoTasks[$taskIdKey]);
                      break; // no need to check other constraining tasks
                  }
               }
            }
          }
      }
      return $todoTasks;
   }

}
SchedulerTaskProviderAbstract::staticInit();
