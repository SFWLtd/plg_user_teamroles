<?php
/**
* @version		1.1
* @package		plg_user_teamroles
* @author       Simon Champion
* @copyright	SFW Ltd
* @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @requires     Joomdle >= 1.0.6
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
require_once(JPATH_SITE.'/components/com_joomdle/helpers/content.php');

class plgUserTeamRoles extends JPlugin
{
    const MOODLE_CONTEXT_USER = 30;

    private $userInfo = null;

    public function onUserBeforeSave($oldUser, $isNew, $newUser)
    {
        $this->userInfo = new TeamRolesUserInfo($this->params, $oldUser['id'], $oldUser['username']);
    }

    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        if (!$success) {
            return;
        }
        JAccess::clearStatics();
        $this->userInfo->addComparitor(new TeamRolesUserInfo($this->params, $user['id'], $user['username']));
        $this->updateRoles();
    }

    private function updateRoles()
    {
        if ($this->userInfo->sameAsComparitor()) {
            //nothing changed, so bail out early.
            return;
        }
        foreach ($this->userInfo->groupsAdded() as $group) {
            if ($this->userInfo->updated->iAmAParent) {
                $this->giveMeParentRoleForGroup($group);
            } else {
                $this->giveMeChildRoleForGroup($group);
            }
        }
        foreach ($this->userInfo->groupsRemoved() as $group) {
            if ($this->userInfo->iAmAParent) {
                $this->removeMyParentRoleForGroup($group);
            } else {
                $this->removeMyChildRoleForGroup($group);
            }
        }
        if ($this->userInfo->isParentRoleChanging()) {
            foreach ($this->userInfo->groupsUnchanged() as $group) {
                if ($this->userInfo->isBecomingAParent()) {
                    $this->giveMeParentRoleForGroup($group);
                }
                if ($this->userInfo->isNoLongerAParent()) {
                    $this->removeMyParentRoleForGroup($group);
                }
            }
        }
    }

    /**
     * User has been been given group leader rights, so need to give him parent roles for everyone in his group.
     * So look at all the other users in the group and grant him parent connection to them.
     */
    private function giveMeParentRoleForGroup($group)
    {
        foreach ($this->usersInGroup($group) as $user) {
            $this->joomdleAddParentRole(JFactory::getUser($user)->get('username'), $this->userInfo->username);
        }
    }

    /**
     * User has been had his group leader rights revoked, so need to unpick all of his parent roles.
     * So look at all the other users in the group and remove his parent connection to them.
     */
    private function removeMyParentRoleForGroup($group)
    {
        foreach ($this->usersInGroup($group) as $user) {
            $this->joomdleRemoveParentRole(JFactory::getUser($user)->get('username'), $this->userInfo->username);
        }
    }

    /**
     * User has been added to a group, so need to implement the parent roles for that group to point to him.
     * So look at the other users in the group, from them, anyone who is marked as having the privs need to gain parent connection with this user.
     */
    private function giveMeChildRoleForGroup($group)
    {
        foreach ($this->usersInGroup($group) as $user) {
            if (in_array($this->userInfo->configAccess, JAccess::getAuthorisedViewLevels($user))) {
                $this->joomdleAddParentRole($this->userInfo->username, JFactory::getUser($user)->get('username'));
            }
        }
    }

    /**
     * User has been removed from a group, so need to unpick the parent roles pointing to him that are associated with that group.
     * So look at the other users in the group, from them, anyone who is marked as having the privs need to lose the parent connection with this user.
     */
    private function removeMyChildRoleForGroup($group)
    {
        foreach ($this->usersInGroup($group) as $user) {
            if (in_array($this->userInfo->configAccess, JAccess::getAuthorisedViewLevels($user))) {
                $this->joomdleRemoveParentRole($this->userInfo->username, JFactory::getUser($user)->get('username'));
            }
        }
    }

    private function joomdleAddParentRole($child, $parent)
    {
        JoomdleHelperContent::call_method('add_parent_role', $child, $parent);
    }
    private function joomdleRemoveParentRole($child, $parent)
    {
        //NB: remove_parent_role is not part of the standard Joomdle package in current version (1.0.5). Should be in place for 1.0.6, but please ensure method exists.
        JoomdleHelperContent::call_method('remove_parent_role', $child, $parent);
    }

    private function usersInGroup($group)
    {
        return JAccess::getUsersByGroup($group);
    }

}

//-----------------------------------------------------------------------------

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
