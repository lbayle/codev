<?php
/*
   This file is part of CoDev-Timetracking.

   CoDev-Timetracking is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CoDev-Timetracking is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CoDev-Timetracking.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once('classes/cache.php');
require_once('classes/issue.class.php');

/**
 * usage: IssueCache::getInstance()->getIssue($id);
 */
class IssueCache extends Cache {

   /**
    * The singleton pattern
    * @static
    * @return IssueCache
    */
   public static function getInstance() {
      return parent::createInstance(__CLASS__);
   }

   /**
    * Get Issue class instance
    * @param int $id The issue id
    * @return Issue The issue attached to the id
    */
   public function getIssue($id) {
      return parent::get($id);
   }

   /**
    * Create Issue
    * @param int $id The id
    * @param resource $details The details
    * @return Issue The object
    */
   protected function create($id, $details = NULL) {
      return new Issue($id);
   }

}

?>
