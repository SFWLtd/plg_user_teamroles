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
require_once(__DIR__.'/TeamRolesUpdater.php');
require_once(__DIR__.'/TeamRolesUserInfo.php');
require_once(__DIR__.'/TeamRolesAdminTab.php');

class plgUserTeamRoles extends JPlugin
{
    private $teamRolesUpdater = null;

    public function onUserBeforeSave($oldUser, $isNew, $newUser)
    {
        $userInfo = new TeamRolesUserInfo($this->params, $oldUser['id'], $oldUser['username']);
        $this->teamRolesUpdater = new TeamRolesUpdater();
        $this->teamRolesUpdater->setUserInfo($userInfo);
    }

    public function onUserAfterSave($user, $isnew, $success, $msg)
    {
        if (!$success) {
            return;
        }
        JAccess::clearStatics();
        $comparitor = new TeamRolesUserInfo($this->params, $user['id'], $user['username']);
        $this->teamRolesUpdater->setComparitorUserInfo($comparitor);
        $this->teamRolesUpdater->updateRoles();
    }

    /**
     * Display parent/child info on the user management form, plus a button to re-sync them with Moodle.
     */
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm)) {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }

        $userID = isset($data->id) ? $data->id : 0;

        // Only show on the admin panel and for existing users.
        $formName = $form->getName();
        if (in_array($formName, 'com_users.user', 'com_users.profile') || !$userID) {
            return true;
        }

        $userInfo = new TeamRolesUserInfo($this->params, $data->id, $data->username);
        $teamRolesAdminTab = new TeamRolesAdminTab($userInfo, $formName === 'com_users.user');
        $teamRolesAdminTab->addTeamTabToAdminForm($form);
        return true;
    }
}
