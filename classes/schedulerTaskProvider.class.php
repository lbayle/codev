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
class SchedulerTaskProvider {

    /**
     * @var Logger The logger
     */
    private static $logger;
    // Origin task list. No modifications have to be done on it if it is not null
    private $todoTaskList;
    // List of candidate task for attribution (has time, has priority deadline, don't depend of an other task)
    private $candidateTaskList;
    // List of user candidate task for attribution (candidateTaskList inner joined with user tasks)
    private $userCandidateTaskList;

    /**
     * Initialize complex static variables
     * @static
     */
    public static function staticInit() {
        self::$logger = Logger::getLogger(__CLASS__);
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
        // If it hasn't be done, initialize todoTaskList 
        if (null == $this->todoTaskList) {
            $this->todoTaskList = $tasksIdArray;
        }

        if (null != $tasksIdArray) {

            // ---------- Make pools of tasks according to deadlines ----------

            $tasksPerDeadLine = null;
            foreach ($tasksIdArray as $taskId) {
                $task = IssueCache::getInstance()->getIssue($taskId);
                $tasksPerDeadLine[$task->getDeadLine()][] = $task->getId();
            }

            // Sort by timestamp from older date to recent date
            ksort($tasksPerDeadLine, SORT_NUMERIC);
            // Keep tasks without dead line
            $taskWithoutDeadLine[null] = $tasksPerDeadLine[null];
            // Remove tasks without dead line from the array
            unset($tasksPerDeadLine[null]);
            // Add tasks without dead line to the end of array
            $tasksPerDeadLine = $tasksPerDeadLine + $taskWithoutDeadLine;
            // Replace key by numbers
            $tasksPerDeadLine = array_values($tasksPerDeadLine);

            // ---------- In the first pool, remove tasks which depend of another task ----------

            $firstPool = $tasksPerDeadLine[0];

            // For each task of the pool
            foreach ($firstPool as $taskIdKey => $taskId) {
                $task = IssueCache::getInstance()->getIssue($taskId);
                $taskRelationships = $task->getRelationships();

                // If task is constrained by another task
                $taskConstrainersIds = $taskRelationships['' + Constants::$relationship_constrained_by];
                if (0 != count($taskConstrainersIds)) {
                    // For every constrainers
                    foreach ($taskConstrainersIds as $taskConstrainerId) {
                        // If constrainer belong to first pool array
                        if (in_array($taskConstrainerId, $firstPool)) {
                            // Remove the task wich is constrained
                            unset($firstPool[$taskIdKey]);
                        }
                    }
                }
            }

            $this->candidateTaskList = $firstPool;

//        self::$logger->error("-----------------------candidateTaskList-------------------------");
//        self::$logger->error($this->candidateTaskList);
        }
    }

    /**
     * Get next user task according to the cursor and user task list
     * 
     * @param type $userId
     * @param type $cursor : id of previous retourned task
     * @return task id : If the next task doesn't exist, return the first. If no task, return null
     */
    public function getNextUserTask($userId, $cursor = NULL) {

        // If candidateTaskList hasn't been setted
        if (null == $this->candidateTaskList) {
            return null;
        }

        $this->createUserCandidateTaskList($userId);
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
        do {
            // Pass to next task
            $nextCursorPosition += 1;

            // If cursor position is higher than number of task in todoTaskList
            if (count($this->todoTaskList) <= $nextCursorPosition) {
                // Define cursor to the first position
                $nextCursorPosition = 0;
            }

            $nextTask = $this->todoTaskList[$nextCursorPosition];


            // If task of the cursor exist in user candidate task list
            if (in_array($nextTask, $this->userCandidateTaskList)) {
                // We go out 
                $process = false;
            }
        } while ($process);

        return $nextTask;
    }

    /**
     * Create the user candidate task list
     * It is an inner join of user tasks and candidate tasks
     * @param type $userId
     */
    private function createUserCandidateTaskList($userId) {

        $user = null;
        // If it exist
        if (User::existsId($userId)) {
            $user = UserCache::getInstance()->getUser($userId);

            if (null != $user) {
                // Get user assigned issues
                $userTasksList = $user->getAssignedIssues();
                foreach ($userTasksList as $userTask) {
                    $userTaskIdList[] = $userTask->getId();
                }

          self::$logger->error("------------------------userTaskIdList------------------------");
          self::$logger->error($userTaskIdList);
                // Get inner joined array of candidate tasks and user tasks
                if(NULL != $userTaskIdList){
                   $this->userCandidateTaskList = $this->arraysInnerJoin($userTaskIdList, $this->candidateTaskList);
                }
                
            }
        } else {
            $this->userCandidateTaskList = null;
        }
    }

    private function arraysInnerJoin($array1, $array2) {
        $innerJoinedArray = null;
        foreach ($array1 as $key => $row) {
            if (in_array($row, $array2)) {
                $innerJoinedArray[$key] = $row;
            }
        }
        return $innerJoinedArray;
    }
}

SchedulerTaskProvider::staticInit();
?>