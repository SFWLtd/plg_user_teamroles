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

class TeamRolesAdminTab
{
    protected $baseXML = <<<'eof'
<?xml version="1.0" encoding="utf-8"?>
<form>
	<fields name="team">
		<fieldset name="team" label="Team Role">
            {teamFields}
        </fieldset>
    </fields>
</form>
eof;

    protected $userInfo;
    protected $adminMode = false;

    public function __construct(TeamRolesUserInfo $userInfo, $adminMode)
    {
        JFormHelper::addFieldPath(__DIR__ . '/field');
        $this->userInfo = $userInfo;
        $this->adminMode = $adminMode;
    }

    public function addTeamTabToAdminForm($form)
    {
        if (!$this->userInfo->iAmAParent) {
            return '';
        }

        $fieldsXML = $this->getTeamDataFields();

        $teamXML = str_replace('{teamFields}', $fieldsXML, $this->baseXML);
        $form->load($teamXML, false);
    }

    protected function getTeamDataFields()
    {
        $teamData = $this->getTeamData();

        $xml = [];
        foreach ($teamData as $groupID=>$teamGroupData) {
            $xml[] = "<field type='team' ".($this->adminMode ? "admin='true'" : "")." name='team-{$groupID}' label='{$teamGroupData['groupName']}'>";
            foreach ($teamGroupData['users'] as $userID) {
                $user = JFactory::getUser($userID);
                $username = $user->get('username');
                $name = $user->get('name');
                $xml[] = "<member userid='{$userID}' username='{$username}'>{$name}</member>";
            }
            $xml[] = "</field>";
        }
        return implode('',$xml);
    }

    protected function getTeamData()
    {
        $output = [];
        foreach ($this->userInfo->userGroups as $groupID) {
            $users = JAccess::getUsersByGroup($groupID);
            $output[$groupID] = [
                'groupName'=>$this->loadGroupName($groupID),
                'users'=>$users
            ];
        }
        return $output;
    }
    

    protected function loadGroupName($groupID)
    {
        $db = JFactory::getDBO();
        $query = 'SELECT title FROM #__usergroups WHERE id = ' . (int)$groupID;
        $db->setQuery($query);
        return $db->loadResult();
    }
}
