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

require_once(JPATH_SITE.'/components/com_joomdle/helpers/content.php');

class TeamRolesUpdater
{
    private $userInfo = null;

    public function setUserInfo(TeamRolesUserInfo $userInfo)
    {
        $this->userInfo = $userInfo;
    }

    public function setComparitorUserInfo(TeamRolesUserInfo $userInfo)
    {
        $this->userInfo->addComparitor($userInfo);
    }

    public function updateRoles()
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
