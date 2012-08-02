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

include_once('classes/issue_selection.class.php');


/**
 *
 * 
 */
interface IndicatorPlugin {


   public function getName();
   public function getDesc();

   /**
    * returns the SMARTY .html filename that will display the results.
    *
    * The file must be included in the main SMARTY page:
    * {include file="indicator_plugins/myIndicator.html"}
    */
   public function getSmartyFilename();

   /**
    * result of the Indicator
    *
    * @param IssueSelection $inputIssueSel task list
    * @params array $params all other parameters needed by this indicator (timestamp, ...)
    * @return mixed (standard PHP structure)
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL);

   /**
    * Send the result of the execute() method to SMARTY.
    * The SMARTY template is furnished by getSmartyFilename().
    *
    * Add to smartyHelper:
    * $smartyHelper->assign('myIndicator', $myIndic->getSmartyObject());
    *
    * @return array structure for SMARTY template
    */
   public function getSmartyObject();


}

?>
