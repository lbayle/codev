<?php /*
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
*/ ?>

<?php

require_once('Logger.php');
if (NULL == Logger::getConfigurationFile()) {
	Logger::configure(dirname(__FILE__).'/../log4php.xml');
	$logger = Logger::getLogger("default");
	$logger->info("LOG activated !");
}



// ===================================================
class ProjectVersion {
	public $projectId;
	public $version;
	public $elapsed;
	public $remaining;
	public $mgrEffortEstim;
	public $effortEstim;  // BI
	public $effortAdd;    // BS
	
	private $issueList;
	private $progress;

	public function __construct($projectId, $version) {

		$this->logger = Logger::getLogger(__CLASS__);

		$this->projectId = $projectId;
		$this->version = $version;

		$this->elapsed   = 0;
		$this->remaining = 0;
		$this->mgrEffortEstim = 0;
		$this->effortEstim   = 0;
		$tgis->effortAdd     = 0;    // BS
		
		$this->issueList = array();
		$this->progress  = NULL;
	}

	/**
	 *
	 * @param int $bugid
	 */
	public function addIssue($bugid) {

		if (NULL == $this->issueList[$bugid]) {

			$issue = IssueCache::getInstance()->getIssue($bugid);
			$this->issueList[$bugid] = $issue;
			$this->elapsed   += $issue->elapsed;
			$this->remaining += $issue->remaining;
			$this->mgrEffortEstim += $issue->mgrEffortEstim;
			$this->effortEstim    += $issue->effortEstim;
			$this->effortAdd    += $issue->effortAdd;
			
			$this->logger->debug("$this->projectId [$this->version] : addIssue($bugid) version = <".$issue->getTargetVersion()."> elapsed=".$issue->elapsed." RAF=".$issue->remaining);
		}

	}

	/**
	 *
	 * @return Ambigous <number, NULL>
	 */
	public function getProgress() {

		if (NULL == $this->progress) {

			// compute total progress
			
			if (0 == $this->elapsed) {
				$this->progress = 0;  // if no time spent, then no work done.
			} elseif (0 == $this->remaining) {
				$this->progress = 1;  // if no Remaining, then Project is 100% done.
			} else {
				$this->progress = $this->elapsed / ($this->elapsed + $this->remaining);
			}

			$this->logger->debug("Project $this->projectId [$this->version] : progress = ".$this->progress." = $this->elapsed / ($this->elapsed + ".$this->remaining.")");
		}

		return $this->progress;
	}

	/**
	 *
	 */
	public function getIssueList() {
		return $this->issueList;
	}

	/**
	 *
	 */
	public function getFormattedIssueList() {
		$formattedList = "";

		foreach ($this->issueList as $bugid => $issue) {
			if ("" != $formattedList) {
				$formattedList .= ', ';
			}
			$formattedList .= issueInfoURL($bugid, $issue->summary);
		}
		return $formattedList;
	}

	/**
	 * @return array(nbDays, percent)
	 */
	public function getDriftMgr() {

        $values = array();

        if ((0 != $this->mgrEffortEstim) && (0 != $this->elapsed)) {        
            // ((elapsed + RAF) - estim) / estim
            $nbDaysDrift = $this->elapsed + $this->remaining - $this->mgrEffortEstim;
    		$percent =  $nbDaysDrift / $this->mgrEffortEstim;
            
            $values['nbDays'] = $nbDaysDrift;
            $values['percent'] = $percent;
        } else {
            $values['nbDays'] = 0;
            $values['percent'] = 0;
        	
        }
        return $values;
	}

	/**
	 * @return array(nbDays, percent)
	 */
	public function getDrift() {

        $values = array();
        
        $myEstim = $this->effortEstim + $this->effortAdd;

        if ((0 != $myEstim) && (0 != $this->elapsed)) {        
            // ((elapsed + RAF) - estim) / estim
            $nbDaysDrift = $this->elapsed + $this->remaining - $myEstim;
    		$percent =  $nbDaysDrift / $myEstim;
            
            $values['nbDays'] = $nbDaysDrift;
            $values['percent'] = $percent;
        } else {
            $values['nbDays'] = 0;
            $values['percent'] = 0;
        	
        }
        return $values;
	}
	
	/**
	 * 
	 * 
	 * 
	 * @param unknown_type $percent  100% = 1
	 * @param unknown_type $threshold  5% = 0.05
	 */
	public function getDriftColor($percent, $threshold = 0.05) {
		
		if (abs($percent) < $threshold) {
			return NULL; // no drift
		}
		
		if ($percent > 0) {
			$color = "fcbdbd";
		} else {
			$color = "bdfcbd";
		}
		return $color;
	}
}

?>