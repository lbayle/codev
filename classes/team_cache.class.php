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
require_once('classes/team.class.php');

/**
 * usage: TeamCache::getInstance()->getTeam($id);
 */
class TeamCache extends Cache {

   /**
    * The singleton pattern
    * @static
    * @return TeamCache
    */
   public static function getInstance() {
      return parent::createInstance(__CLASS__);
   }

   /**
    * Get Team class instance
    * @param int $id The team id
    * @return Team The team attached to the id
    */
   public function getTeam($id) {
      return parent::get($id);
   }

   /**
    * Create a Team
    * @abstract
    * @param int $id The id
    * @return Team The object
    */
   protected function create($id) {
      return new Team($id);
   }

}

?>
