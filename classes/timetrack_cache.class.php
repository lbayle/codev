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
require_once('classes/time_track.class.php');

/**
 * usage: TimeTrackCache::getInstance()->getTimeTrack($id);
 */
class TimeTrackCache extends Cache {

   /**
    * The singleton pattern
    * @static
    * @return TimeTrackCache
    */
   public static function getInstance() {
      return parent::createInstance(__CLASS__);
   }

   /**
    * Get TimeTrack class instance
    * @param int $id The time track id
    * @param resource $details The details
    * @return TimeTrack The time track attached to the id
    */
   public function getTimeTrack($id, $details = NULL) {
      return parent::get($id, $details);
   }

   /**
    * Create a TimeTrack
    * @param int $id The id
    * @param resource $details The details
    * @return TimeTrack The object
    */
   protected function create($id, $details = NULL) {
      return new TimeTrack($id, $details);
   }

}

?>
