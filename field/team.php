<?php
/**
* @version		1.2
* @package		plg_user_teamroles
* @author       Simon Champion
* @copyright	SFW Ltd
* @license		http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @requires     Joomdle >= 1.0.6
*/

defined('_JEXEC') or die;

jimport('joomla.form.formfield');

class JFormFieldTeam extends JFormField
{
	protected $type = 'Team';
 
	protected function getTeamUsers()
    {
        $output = [];
        foreach ($this->element->xpath('member') as $teamMember) {
            $output[] = (object)[
                'userID'    => (string)$teamMember['userID'],
                'username'  => trim((string)$teamMember) ?: $userID,
                'enabled'   => (string)$teamMember['enabled'] === 'true',
            ];
        }
        return $output;
	}

    public function getInput()
    {
        $list = $this->listTeamMembers();
        return <<<eof
<div>Team Members</div>
<ul>{$list}</ul>
eof;
    }

    protected function listTeamMembers()
    {
        $teamUsersHTML = '';
        foreach ($this->getTeamUsers() as $teamUser) {
            $teamUsersHTML[] = "<li><span>{$teamUser->username}</li>";
        }
        return implode('', $teamUsersHTML);
    }
}
