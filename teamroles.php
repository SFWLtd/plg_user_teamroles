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
}
