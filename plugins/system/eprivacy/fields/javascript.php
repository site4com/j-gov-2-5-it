<?php
/**
 * @copyright	Copyright (C) 2010 Michael Richey. All rights reserved.
 * @license		GNU General Public License version 3; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldJavascript extends JFormField
{
	protected $type = 'Javascript';

	protected function getInput()
	{
                // start easy - the admin js
            $doc = JFactory::getDocument();
		$doc->addScript(JURI::root(true).'/media/plg_system_eprivacy/js/admin.js');
                // determine if reflection is available, if not disable the viewlevel selector.
                if(!class_exists('ReflectionClass',false) || !method_exists('ReflectionProperty','setAccessible')) {
                    $script = array("window.addEvent('domready',function(){");
                    $script[]="\tvar cookieACL = document.id('jform_params_cookieACL');";
                    $script[]="\tcookieACL.hide();";
                    $script[]="\tvar p = new Element('p',{";
                    $script[]="\t\thtml:'".str_replace("'","\'",JText::_('PLG_SYS_EPRIVACY_MISSINGREFLECTION'))."'";
                    $script[]="\t}).inject(cookieACL,'after');";
                    $script[]="});";
                    $doc->addScriptDeclaration(implode("\n",$script));
                }
		return '';
	}
        protected function getLabel() {
            return '';
        }
}
