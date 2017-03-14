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
JFormHelper::loadFieldClass('spacer');

class JFormFieldTeam extends JFormFieldSpacer
{
    protected function getTeamUsers()
    {
        $output = [];
        foreach ($this->element->xpath('member') as $teamMember) {
            $output[] = (object)[
                'userID'    => (string)$teamMember['userid'],
                'name'      => trim((string)$teamMember) ?: $userID,
                'username'  => (string)$teamMember['username'] ?: $userID,
                'enabled'   => (string)$teamMember['enabled'] === 'true',
            ];
        }
        return $output;
    }

    public function getLabel()
    {
        $style = "display:inline-block; overflow:visible; white-space:nowrap; width:1px;";
        $this->element['label'] = "Team lead for <b style='{$style}'>{$this->element['label']}</b>";
        return parent::getLabel()."<p>Team Members:</p>";
        
        $style = "display:inline-block; overflow:visible; white-space:nowrap; width:1px;";
        $content = "Team lead for <b style='{$style}'>{$this->element['label']}</b><br>Team Members:";
        $this->element['label'] = "<div style='text-align:left; display:inline-block;'>{$content}</div>";
        return parent::getLabel();

    }

    public function getInput()
    {
        $list = $this->listTeamMembers();
        return <<<eof
<p>&nbsp;</p>
<ul>{$list}</ul>
eof;
    }

    protected function listTeamMembers()
    {
        $teamUsersHTML = '';
        foreach ($this->getTeamUsers() as $teamUser) {
            if ($this->element['admin']) {
                $teamUsersHTML[] = "<li><a href='index.php?option=com_users&task=user.edit&id={$teamUser->userID}'>{$teamUser->name} ({$teamUser->username})</a></li>";
            } else {
                $teamUsersHTML[] = "<li><span>{$teamUser->name}</span></li>";
            }
        }
        return implode('', $teamUsersHTML);
    }
}
