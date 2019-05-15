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
class SchedulerTaskProvider extends SchedulerTaskProviderAbstract {

   private static $logger;

   // Origin task list. No modifications have to be done on it if it is not null
   protected $todoTaskList;
   // List of pool of candidate task for attribution (has time, has priority deadline, don't depend of an other task)
   private $candidateTaskPoolList;
   // List of user candidate task for attribution (candidateTaskList inner joined with user tasks)
   private $userCandidateTaskList;

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
      return T_("Daily breakdown scheduling");
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
     * It separate task according to their deadline,
     * it keep only the first created "pool" and
     * it remove tasks of the pool which depend of other tasks of the pool.
     * @param type $tasksIdArray : array of task id
     */
    public function createCandidateTaskList($tasksIdArray) {
        
//        self::$logger->error("-----------------------todoTaskList-------------------------");
//        self::$logger->error($tasksIdArray);
        
        // If it hasn't be done, initialize todoTaskList 
        if (null == $this->todoTaskList) {
            $this->todoTaskList = $tasksIdArray;
        }

        if (null != $tasksIdArray) {
            
            $tasksIdArray = $this->removeConstrainedTasks($tasksIdArray);

            // ---------- Make pools of tasks according to deadlines ----------

            $tasksPerDeadLine = null;
            foreach ($tasksIdArray as $taskId) {
                $task = IssueCache::getInstance()->getIssue($taskId);
                $tasksPerDeadLine[$task->getDeadLine()][] = $task->getId();
            }

            // Sort by timestamp from older date to recent date
            ksort($tasksPerDeadLine, SORT_NUMERIC);
            
            // If some task havn't deadline
            if(null != $tasksPerDeadLine[null])
            {
               // Keep tasks without dead line
               $taskWithoutDeadLine[null] = $tasksPerDeadLine[null];
               // Remove tasks without dead line from the array
               unset($tasksPerDeadLine[null]);
               // Add tasks without dead line to the end of array
               $tasksPerDeadLine = $tasksPerDeadLine + $taskWithoutDeadLine;
            }
            
            // Replace key by numbers
            $this->candidateTaskPoolList = array_values($tasksPerDeadLine);

            
//        self::$logger->error("-----------------------candidateTaskPoolList-------------------------");
//        self::$logger->error($this->candidateTaskPoolList);
        }
    }

    /**
     * Get next user task according to the cursor and user task list
     * 
     * @param type $userId
     * @param type $cursor : id of previous retourned task
     * @return task id : If the next task doesn't exist, return the first. If no task, return null
     */
    public function getNextUserTask($assignedUserTasks, $cursor = NULL) {
        
//        self::$logger->error("------------------------assignedUserTasks------------------------");
//        self::$logger->error($assignedUserTasks);

        // If candidateTaskList hasn't been setted
        if (null == $this->candidateTaskPoolList) {
            return null;
        }

        $this->createUserCandidateTaskList($assignedUserTasks);
//        self::$logger->error("------------------------userCandidateTaskList------------------------");
//        self::$logger->error($this->userCandidateTaskList);
        
        // If user has no more task
        if (null == $this->userCandidateTaskList) {
            return null;
        }

        // ---------- Initialisation ----------
        // If cursor is not defined
        if (null == $cursor) {
            // Define cursor to the first position
            $cursorPosition = 0;
            $cursor = $this->todoTaskList[$cursorPosition];
        } else {
            // Search cursor position in todoTaskList
            $cursorPosition = array_search($cursor, $this->todoTaskList);
        }

        $nextCursorPosition = $cursorPosition;


        // ---------- Process : search next task ----------

        $process = true;
        $nbTodoTasks = count($this->todoTaskList);
        do {
            // Pass to next task
            //$nextCursorPosition += 1;
            $nextCursorPosition = ($nextCursorPosition + 1) % $nbTodoTasks;

//            // If cursor position is higher than number of task in todoTaskList
//            if (count($this->todoTaskList) <= $nextCursorPosition) {
//                // Define cursor to the first position
//                $nextCursorPosition = 0;
//            }

            $nextTask = $this->todoTaskList[$nextCursorPosition];


            // If task of the cursor exist in user candidate task list
            if (in_array($nextTask, $this->userCandidateTaskList)) {
                // We go out 
                $process = false;
            }
        } while ($process);
        
//        self::$logger->error("------------------------$nextTask------------------------");
//        self::$logger->error("     ".$nextTask."     ");
        return $nextTask;
    }

    /**
     * Create the user candidate task list
     * It is an inner join of user tasks and first candidate tasks list containing user assigned task
     * @param type $assignedUserTasks : task assigned to the user
     */
    private function createUserCandidateTaskList($assignedUserTasks) {


        $userCandidateTaskList = null;
        // For each candidate task list
        foreach($this->candidateTaskPoolList as $candidateTaskPool)
        {
            // Inner join candidate task list to assigned user task
            $userCandidateTaskList = array_intersect($assignedUserTasks, $candidateTaskPool);
            // If there is task in userCandidateTaskList
            if(null != $userCandidateTaskList)
            {
                break;
            }
        }
        
        $this->userCandidateTaskList = $userCandidateTaskList;
    }
}

SchedulerTaskProvider::staticInit();
