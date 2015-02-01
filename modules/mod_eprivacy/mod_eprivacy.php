<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_footer
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');
$app = JFactory::getApplication();
$dispatcher = & JDispatcher::getInstance();
JPluginHelper::importPlugin('system', 'eprivacy', true, $dispatcher);
$plugin = JPluginHelper::getPlugin('system','eprivacy');
$pluginparams = new JRegistry();
$pluginparams->loadString($plugin->params);
if(!$app->getUserState('plg_system_eprivacy_non_eu',false)) {
    $lang = JFactory::getLanguage();
    $lang->load('plg_system_eprivacy',JPATH_ADMINISTRATOR);
    if($pluginparams->get('lawlink',1)) {
        $langtag = explode('-',$lang->getTag());
        $langtag = strtoupper($langtag[0]);
        if(in_array($langtag,array('BG','ES','CS','DA','DE','ET','EL','EN','FR','GA','IT','LV','LT','HU','MT','NL','PL','PT','RO','SK','SL','FI','SV'))) {
            $linklang = $langtag;
        } else {
            $linklang = 'EN';
        }
    }
    $uri = $_SERVER['REQUEST_URI'];
    $query_string = explode('&', $_SERVER['QUERY_STRING']);
    if (count($query_string) && strlen($query_string[0])) {
        $uri.='&eprivacy=1';
    } else {
        $uri.='?eprivacy=1';
    }   
//    switch($pluginparams->get('displaytype','message')) {
//        case 'module':
//            $reconsider='plg_system_eprivacy_showmessage();';
//            break;
//        case 'modal':
//            $reconsider.='plg_system_eprivacy_showmessage();plg_system_eprivacy_modalIt(\''.plgSystemePrivacy::_getURI().'\');';
//            break;
//    }
    $moduleclass_sfx = htmlspecialchars($params->get('moduleclass_sfx'));
    require JModuleHelper::getLayoutPath('mod_eprivacy', $params->get('layout', 'default'));
}