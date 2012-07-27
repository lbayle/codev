<?php

/*
  This file is part of CodevTT.

  CodevTT is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  CodevTT is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with CoDevTT.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Logger.php');

include_once('classes/indicator_plugin.interface.php');


/* INSERT INCLUDES HERE */

/**
 * Description of days_per_job
 *
 */
class DaysPerJobIndicator implements IndicatorPlugin {

   private $logger;

   public function __construct() {
      $this->logger = Logger::getLogger(__CLASS__);

      $this->initialize();
   }

   public function initialize() {

      // get info from DB
   }

   public function getName() {
      return __CLASS__;
   }

   public function getDesc() {
      return T_("Working days per Job");
   }


   /**
    *
    * @param IssueSelection $inputIssueSel
    * @param array $params all other parameters needed by this indicator (timestamp, ...)
    * @return mixed array[]
    */
   public function execute(IssueSelection $inputIssueSel, array $params = NULL) {

      $this->logger->error("execute() in ISel ".$inputIssueSel->name);

      echo "NAME ".$inputIssueSel->name;
   }

}

?>
