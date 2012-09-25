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

/**
 * usage: UserCache::getInstance()->getUser($id);
 */
class UserCache extends Cache {

   /**
    * The singleton pattern
    * @static
    * @return UserCache
    */
   public static function getInstance() {
      return parent::createInstance(__CLASS__);
   }

   /**
    * Get User class instance
    * @param int $id The user id
    * @param resource $details The details
    * @return User The user attached to the id
    */
   public function getUser($id, $details = NULL) {
      return parent::get($id, $details);
   }

   /**
    * Create a User
    * @param int $id The id
    * @param resource $details The details
    * @return User The object
    */
   protected function create($id, $details = NULL) {
      return new User($id, $details);
   }

}

?>
