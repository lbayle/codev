
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

/**
 * Description of MoveIssueTimetracks
 *
 * @author lob
 */
class MoveIssueTimetracks extends IndicatorPluginAbstract {


    private static $logger;
    private static $domains;
    private static $categories;
    // params from PluginDataProvider
    private $inputIssueSel;
    private $teamid;
    private $sessionUserId;
    // internal
    protected $execData;

    /**
     * Initialize static variables
     * @static
     */
    public static function staticInit() {
        self::$logger = Logger::getLogger(__CLASS__);

        // A plugin can be displayed in multiple domains
        self::$domains = array(
            self::DOMAIN_ADMIN,
        );
        // A plugin should have only one category
        self::$categories = array(
            self::CATEGORY_ADMIN
        );
    }

    public static function getName() {
        return T_('Move Issue Timetracks');
    }

    public static function getDesc($isShortDesc = true) {
        return T_('Move timetracks of issues');
    }

    public static function getAuthor() {
        return 'CodevTT (GPL v3)';
    }

    public static function getVersion() {
        return '1.0.0';
    }

    public static function getDomains() {
        return self::$domains;
    }

    public static function getCategories() {
        return self::$categories;
    }

    public static function isDomain($domain) {
        return in_array($domain, self::$domains);
    }

    public static function isCategory($category) {
        return in_array($category, self::$categories);
    }

    public static function getCssFiles() {
        return array(
        );
    }

    public static function getJsFiles() {
        return array(
            'js_min/datepicker.min.js',
            'js_min/datatable.min.js',
        );
    }

    /**
     *
     * @param \PluginDataProviderInterface $pluginMgr
     * @throws Exception
     */
    public function initialize(PluginDataProviderInterface $pluginDataProv) {


        if (NULL != $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID)) {
            $this->sessionUserId = $pluginDataProv->getParam(PluginDataProviderInterface::PARAM_SESSION_USER_ID);
        } else {
            throw new Exception("Missing parameter: " . PluginDataProviderInterface::PARAM_SESSION_USER_ID);
        }

        // set default pluginSettings
    }

    /**
     * User preferences are saved by the Dashboard
     *
     * @param type $pluginSettings
     */
    public function setPluginSettings($pluginSettings) {

        if (NULL != $pluginSettings) {
            
        }
    }
    
    /**
     * Get timetracks corresponding to filters
     * @param integer[] $users
     * @param timestamp $beginDate
     * @param timestamp $endDate
     * @param integer $task
     * @return timetraks
     */
    public function getTimetracks($users, $beginDate, $endDate, $task)
    {
        $timetracks = null;
        if ($users != null && $beginDate != null && $endDate != null && $task != null) {
            $formatedUsers = implode( ', ', $users);
            
            $query = "SELECT * FROM `codev_timetracking_table` " .
                    "WHERE date >= $beginDate AND date <= $endDate " .
                    "AND userid IN ($formatedUsers)" .
                    "AND bugid = $task " .
                    "ORDER BY date;";
            
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
                echo "<span style='color:red'>ERROR: Query FAILED</span>";
                exit;
            }

            $jobs = new Jobs();
            while ($row = SqlWrapper::getInstance()->sql_fetch_object($result)) {
                $tt = TimeTrackCache::getInstance()->getTimeTrack($row->id, $row);

                $user = UserCache::getInstance()->getUser($tt->getUserId());
                $issue = IssueCache::getInstance()->getIssue($tt->getIssueId());

                if (!is_null($tt->getCommitterId())) {
                    $committer = UserCache::getInstance()->getUser($tt->getCommitterId());
                    $committer_name = $committer->getName();
                    $commit_date = date('Y-m-d H:i:s', $tt->getCommitDate());
                } else {
                    $committer_name = ''; // this info does not exist before v1.0.4
                    $commit_date = '';
                }

                $timeTracks[$row->id] = array(
                    'id' => $row->id,
                    'user' => $user->getName(),
                    'date' => date('Y-m-d', $tt->getDate()),
                    'job' => $jobs->getJobName($tt->getJobId()),
                    'duration' => $tt->getDuration(),
                    'committer' => $committer_name,
                    'commit_date' => $commit_date,
                    'task_id' => $issue->getId(),
                    'task_extRef' => $issue->getTcId(),
                    'task_summary' => $issue->getSummary(),
                );
            }
        }
        return $timeTracks;
    }
    
    /**
     * Move all selected timetracks to destination task
     * @param integer[] $timetracksIds
     * @param integer[] $destinationTaskId
     */
    public function moveTimetracks($timetracksIds, $destinationTaskId)
    {
        if($timetracksIds != null && $destinationTaskId != null && $destinationTaskId != 0)
        {
            $formatedTimetracksIds = implode( ', ', $timetracksIds);
            $destinationTask = new Issue($destinationTaskId);
            
            // Move destination Task Creation date to older timetrack date
            foreach($timetracksIds as $timetrackId)
            {
                $timetrack = new TimeTrack($timetrackId);
                if($timetrack->getDate() < $destinationTask->getDateSubmission())
                {
                    $destinationTask->setDateSubmission($timetrack->getStartTimestamp());
                }
            }
            
            // Move all selected timetracks to destination task
            $query = "UPDATE codev_timetracking_table SET bugid='$destinationTaskId' " .
                    "WHERE id IN ($formatedTimetracksIds) ";
            
            $result = SqlWrapper::getInstance()->sql_query($query);
            if (!$result) {
                echo "<span style='color:red'>ERROR: Query FAILED</span>";
                exit;
            }
            
        }
    }

    /**
     *
     * Table Repartition du temps par status
     */
    public function execute() {
        // get team
        $teamList = Team::getTeams(true);

        // get users of first team
        $teamUserList = TeamCache::getInstance()->getTeam(key($teamList))->getMembers();

        $this->execData = array(
            'teamList' => $teamList,
            'teamUserList' => $teamUserList
        );
        return $this->execData;
    }

    /**
     *
     * @param boolean $isAjaxCall
     * @return array
     */
    public function getSmartyVariables($isAjaxCall = false) {

        $availableTeams = SmartyTools::getSmartyArray($this->execData['teamList'], NULL);
        $teamUserList = SmartyTools::getSmartyArray($this->execData['teamUserList'], NULL);

        $smartyVariables = array(
            'moveIssueTimetracks_availableTeams' => $availableTeams,
            'moveIssueTimetracks_teamUserList' => $teamUserList,
        );

        if (false == $isAjaxCall) {
            $smartyVariables['moveIssueTimetracks_ajaxFile'] = self::getSmartySubFilename();
            $smartyVariables['moveIssueTimetracks_ajaxPhpURL'] = self::getAjaxPhpURL();
        }
        return $smartyVariables;
    }

    public function getSmartyVariablesForAjax() {
        return $this->getSmartyVariables(true);
    }

}

// Initialize static variables
MoveIssueTimetracks::staticInit();
