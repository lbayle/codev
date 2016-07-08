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
 *
 * @author fr20648
 */
interface SchedulerTaskProviderInterface {

    /**
     * Create the candidate Task list
     *
     * @param type $todoTasks : array of bugid
     */
    public function createCandidateTaskList($todoTasks);

    /**
     * Get next user task
     *
     * @param array $assignedUserTasks : array of bugid
     * @param type $cursor : bugid of previous activity
     * @return task id : If the next task doesn't exist, return the first. If no task, return null
     */
    public function getNextUserTask($assignedUserTasks, $cursor = NULL);

}
