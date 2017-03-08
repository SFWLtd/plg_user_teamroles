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
        if (!$userID) {
            return;
        }

        $this->userID = $userID;
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
