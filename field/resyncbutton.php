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

class JFormFieldResyncbutton extends JFormFieldSpacer
{
    public function getLabel()
    {
        return '';
    }

    public function getInput()
    {
        $info = [
            'teamLeaderID'=>(int)$this->element['leader'],
            'groupID'=>(string)$this->element['groupid']
        ];
        return $this->buttonHTML($info);
    }

    protected function buttonHTML($info)
    {
        $jsonInfo = htmlentities(json_encode($info), ENT_QUOTES);
        return <<<eof
<a class="btn btn-small hasTooltip teamrole-resync" data-info="{$jsonInfo}" title="" href="javascript:void(0);" data-original-title="Use this if team members shown here are missing from MI.">
<span class="icon-refresh"></span> Resync Team Members
</a>
eof;
    }
}
