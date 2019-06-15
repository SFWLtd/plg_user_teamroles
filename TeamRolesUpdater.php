<?php
/**
* @version		1.3
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

    public function resync()
    {
        $comparitor = clone $this->userInfo;
        $this->setComparitorUserInfo($comparitor);

        $currentParentSetting = $this->userInfo->iAmAParent;

        //switch off their team leader flag and update the Moodle team...
        $this->userInfo->updated->iAmAParent = !$currentParentSetting;
        $this->updateRoles();

        //...then switch it back on and update again.
        $this->userInfo->iAmAParent = !$currentParentSetting;
        $this->userInfo->updated->iAmAParent = $currentParentSetting;
        $this->updateRoles();

        //we're done so don't really need to set this back to original value, but be polite to the calling method.
        $this->userInfo->iAmAParent = $currentParentSetting;
    }

    /**
     * User has been been given group leader rights, so need to give him parent roles for everyone in his group.
     * So look at all the other users in the group and grant him parent connection to them.
     */
    private function giveMeParentRoleForGroup($group)
    {
        foreach (TeamRolesUserInfo::usersInGroup($group) as $user) {
            if ($this->userInfo->loadTeamRoleToggleFromProfile($user)) {
                self::joomdleAddParentRole(JFactory::getUser($user)->get('username'), $this->userInfo->username);
            }
        }
    }

    /**
     * User has been had his group leader rights revoked, so need to unpick all of his parent roles.
     * So look at all the other users in the group and remove his parent connection to them.
     */
    private function removeMyParentRoleForGroup($group)
    {
        foreach (TeamRolesUserInfo::usersInGroup($group) as $user) {
            self::joomdleRemoveParentRole(JFactory::getUser($user)->get('username'), $this->userInfo->username);
        }
    }

    /**
     * User has been added to a group, so need to implement the parent roles for that group to point to him.
     * So look at the other users in the group, from them, anyone who is marked as having the privs need to gain parent connection with this user.
     */
    private function giveMeChildRoleForGroup($group)
    {
        foreach (TeamRolesUserInfo::usersInGroup($group) as $user) {
            if (in_array($this->userInfo->configAccess, JAccess::getAuthorisedViewLevels($user))) {
                $username = JFactory::getUser($user)->get('username');
                $teamLeaderInfo = new TeamRolesUserInfo([], $user, $username);  //config params arg not needed in this context.
                $toggle = $teamLeaderInfo->loadTeamRoleToggleFromProfile($this->userInfo->userID);
                if ($toggle) {
                    self::joomdleAddParentRole($this->userInfo->username, $username);
                }
            }
        }
    }

    /**
     * User has been removed from a group, so need to unpick the parent roles pointing to him that are associated with that group.
     * So look at the other users in the group, from them, anyone who is marked as having the privs need to lose the parent connection with this user.
     */
    private function removeMyChildRoleForGroup($group)
    {
        foreach (TeamRolesUserInfo::usersInGroup($group) as $user) {
            if (in_array($this->userInfo->configAccess, JAccess::getAuthorisedViewLevels($user))) {
                self::joomdleRemoveParentRole($this->userInfo->username, JFactory::getUser($user)->get('username'));
            }
        }
    }

    public static function joomdleAddParentRole($child, $parent)
    {
        JoomdleHelperContent::call_method('add_parent_role', $child, $parent);
    }
    public static function joomdleRemoveParentRole($child, $parent)
    {
        //NB: remove_parent_role was not part of the standard Joomdle package until 1.0.6.
        JoomdleHelperContent::call_method('remove_parent_role', $child, $parent);
    }
}
