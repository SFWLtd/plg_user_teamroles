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
                'on'        => (string)$teamMember['on'] === '1',
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
            $toggle = $this->toggleIcons(['teamLeaderID'=>(int)$this->element['leader'], 'teamMemberID'=>$teamUser->userID, 'groupID'=>(string)$this->element['groupid']], $teamUser->on);
            if ($this->element['admin']) {
                $teamUsersHTML[] = "<li>{$toggle}<a href='index.php?option=com_users&task=user.edit&id={$teamUser->userID}'>{$teamUser->name} ({$teamUser->username})</a></li>";
            } else {
                $teamUsersHTML[] = "<li>{$toggle}<span>{$teamUser->name}</span></li>";
            }
        }
        return implode('', $teamUsersHTML);
    }

    protected function toggleIcons($info, $on)
    {
        $offStyle = $on ? "display:inherit;" : "display:none;";
        $onStyle = $on ? "display:none;" : "display:inherit;";
        $info['on'] = !$on;
        $jsonInfo = htmlentities(json_encode($info), ENT_QUOTES);

return <<<eof
<span class="teamrole-toggle-wrapper" style="display: inline-block; padding-right: 5px;">
<a class="btn btn-micro hasTooltip teamrole-toggle teamrole-toggle-off" data-info="{$jsonInfo}" style="{$offStyle}" title="" href="javascript:void(0);" data-original-title="Should this user be listed in the MI panel?">
<span class="icon-publish"></span>
</a>
<a class="btn btn-micro hasTooltip teamrole-toggle teamrole-toggle-on" data-info="{$jsonInfo}" style="{$onStyle}" title="" href="javascript:void(0);" data-original-title="Should this user be listed in the MI panel?">
<span class="icon-unpublish"></span>
</a>
</span>
eof;
    }
}
