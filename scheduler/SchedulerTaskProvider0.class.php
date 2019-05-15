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

/**
 * Provide methodes to get a task according to priority, dependances, user tasks
 */
class SchedulerTaskProvider0 extends SchedulerTaskProviderAbstract {

    private static $logger;

    // Origin task list. No modifications have to be done on it if it is not null
    protected $todoTaskList;

    private $candidateTaskList;

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
   public function getShortDesc() {
      return T_("Standard issue priority scheduling");
   }

   /**
    * detailed description
    */
   public function getDesc() {
      return "...";
   }

    public function __construct() {
        $this->todoTaskList = null;
    }

    /**
     * Create the candidate Task list
     * it remove tasks constrained by other tasks.
     * @param type $todoTasks : array of task id
     */
    public function createCandidateTaskList($todoTasks) {
        
        //self::$logger->error("==== createCandidateTaskList ");
        //self::$logger->error("todoTasks : ".implode(', ', $todoTasks));
        
        // If it hasn't be done, initialize todoTaskList 
        if (null == $this->todoTaskList) {
            //self::$logger->error("initializing todoTaskList (first call)");
            $this->todoTaskList = $todoTasks;
        }

        if (null != $todoTasks) {
            $this->candidateTaskList = $this->removeConstrainedTasks($todoTasks);
        }
        //self::$logger->error("candidateTaskList : ".implode(', ', $this->candidateTaskList));
    }

    /**
     * Get next user task
     *
     * 
     * @param type $assignedUserTasks
     * @param type $cursor : id of previous retourned task
     * @return task id : If the next task doesn't exist, return the first. If no task, return null
     */
    public function getNextUserTask($assignedUserTasks, $cursor = NULL) {

        //self::$logger->error("assignedUserTasks : ".implode(', ', $assignedUserTasks));

        // find highest priority task assigned to the user
        foreach ($this->candidateTaskList as $candidate) {
           if (in_array($candidate, $assignedUserTasks)) {
              // found
              $nextTask = $candidate;
              break;
           }
        }

        //self::$logger->error("nextTask = $nextTask");
        return $nextTask;
    }

}

SchedulerTaskProvider0::staticInit();
