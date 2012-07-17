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
 * Comparable interface
 */
interface Comparable {

   /**
    * Sort by asc
    * @static
    * @abstract
    * @param Comparable $objA The first object
    * @param Comparable $objB The second object
    * @return int 1 if $objB is higher, -1 if $objB is lower, 0 if equals
    */
   public static function compare(Comparable $objA, Comparable $objB);

}
