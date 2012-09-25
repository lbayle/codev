<?php
/*
   This file is part of CodevTT

   CodevTT is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   CodevTT is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with CodevTT.  If not, see <http://www.gnu.org/licenses/>.
*/

interface IssueSelectionFilter {

   public function getName();
   public function getDesc();

   /**
    * result of the Indicator
    *
    * @param IssueSelection $inputIssueSel input task list
    * @params array $params any other data needed by the Filter to compute the result
    * @return IssueSelection[] an array of one or more IssueSelection
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL);



}

?>
