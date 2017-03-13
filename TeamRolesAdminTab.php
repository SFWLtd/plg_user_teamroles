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

    public function __construct(TeamRolesUserInfo $userInfo)
    {
        JFormHelper::addFieldPath(__DIR__ . '/field');
        $this->userInfo = $userInfo;
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
            $xml[] = "<field type='team' name='team-{$groupID}' label='Team Lead for {$teamGroupData['groupName']}'>";
            foreach ($teamGroupData['users'] as $userID) {
                $username = JFactory::getUser($userID)->get('username');
                $xml[] = "<member userid='{$userID}'>{$username}</member>";
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
