<?php
/**
* @version		1.2
* @package		plg_user_teamroles
* @author       Simon Champion
* @copyright	SFW Ltd
* @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @requires     Joomdle >= 1.0.6
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class TeamRolesUserInfo
{
    public $userID = 0;
    public $username = '';

    public $userGroups = [];
    public $iAmAParent = false;

    public $updated = null;

    public $configGroups;
    public $configAccess;

    public function __construct($params, $userID, $username)
    {
        if (!(int)$userID) {
            return;
        }

        $this->userID = (int)$userID;
        $this->username = $username;

        $this->configGroups = $this->subGroupsOfConfigTopGroup($params->get('topgroup'));
        $this->configAccess = $params->get('parent');

        //get the users groups, and filter it to only include the ones that are configured for parent/child relationships.
        $this->userGroups   = array_intersect(JAccess::getGroupsByUser($userID), $this->configGroups);

        //check whether the user has parent access level
        $this->iAmAParent   = in_array($this->configAccess, JAccess::getAuthorisedViewLevels($userID));
    }

    /**
     * Get all subgroups of a given usergroup.
     * (I'm sure there must be a standard Joomla way of doing this but for the life of me I can't find it)
     */
    private function subGroupsOfConfigTopGroup($topGroup)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('id'));
        $query->from($db->quoteName('#__usergroups'));
        $query->where($db->quoteName('parent_id').'='.(int)$topGroup);

        $db->setQuery($query);
        return $db->loadColumn();
    }

    public function loadTeamRoleToggleFromProfile($teamMemberID)
    {
        $teamMemberID = (int)$teamMemberID;
        if (!$teamMemberID || $teamMemberID === $this->userID) {
            return true;
        }
        $db = JFactory::getDbo();
        $db->setQuery("SELECT profile_value FROM #__user_profiles"
            ." WHERE user_id = {$this->userID} AND profile_key = 'teamroles.show.{$teamMemberID}' "
            ." ORDER BY ordering");
        $result = $db->loadResult();
        if ($result === null) {
            return true;    //not set yet, so default to true.
        }
        return !!$result;
    }

    public function saveTeamRoleToggleToProfile($teamMemberID, $value)
    {
        $value = (bool)$value;
        $teamMemberID = (int)$teamMemberID;
        if (!$teamMemberID || $teamMemberID == $this->userID) {
            return true;
        }

        $db = JFactory::getDbo();
        $db->setQuery("DELETE FROM #__user_profiles WHERE user_id={$this->userID} AND profile_key = 'teamroles.show.{$teamMemberID}'");
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }

        $saveValue = $value ? 1 : 0;
        $db->setQuery("INSERT INTO #__user_profiles SET "
            ." user_id={$this->userID},"
            ." profile_key='teamroles.show.{$teamMemberID}',"
            ." profile_value={$saveValue},"
            ." ordering=1");
        if (!$db->query()) {
            throw new Exception($db->getErrorMsg());
        }
        return $value;
    }

    public function addComparitor(TeamRolesUserInfo $updated)
    {
        $this->updated = $updated;

        if (!$this->userID) {
            $this->userID = $updated->userID;
            $this->username = $updated->username;

            $this->configGroups = $updated->configGroups;
            $this->configAccess = $updated->configAccess;
        }
    }

    public function sameAsComparitor()
    {
        return !($this->isParentRoleChanging() || $this->isGroupListChanging());
    }
    public function isParentRoleChanging()
    {
        return ($this->iAmAParent != $this->updated->iAmAParent);
    }
    public function isBecomingAParent()
    {
        return (!$this->iAmAParent && $this->updated->iAmAParent);
    }
    public function isNoLongerAParent()
    {
        return ($this->iAmAParent && !$this->updated->iAmAParent);
    }
    public function isGroupListChanging()
    {
        return ($this->userGroups != $this->updated->userGroups);
    }

    public function groupsAdded()
    {
        return array_diff($this->updated->userGroups, $this->userGroups);
    }
    public function groupsRemoved()
    {
        return array_diff($this->userGroups, $this->updated->userGroups);
    }
    public function groupsUnchanged()
    {
        return array_intersect($this->userGroups, $this->updated->userGroups);
    }
}
