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

class JFormFieldButton extends JFormField
{
	protected $type = 'Button';
 
    public function getInput()
    {
        $onclick = !empty($this->onclick) ? ' onclick="' . $this->onclick . '"' : '';
        $buttonText = htmlspecialchars($this->element['caption'], ENT_COMPAT, 'UTF-8');
        return "<button id='{$this->id}' name='{$this->name.}' {$onclick}>{$buttonText}</button>";
    }
}
