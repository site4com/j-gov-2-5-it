<?php

/**
 * @package plugin System - EU e-Privacy Directive
 * @copyright (C) 2010-2011 RicheyWeb - www.richeyweb.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * System - EU e-Privacy Directive Copyright (c) 2011 Michael Richey.
 * System - EU e-Privacy Directive is licensed under the http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * ePrivacy system plugin
 */
class plgSystemePrivacy extends JPlugin {
    public $_cookieACL;
    public $_defaultACL;
    public $_prep;
    public $_eprivacy;
    public $_clear;
    public $_country;
    public $_display;
    public $_displayed;
    public $_displaytype;
    public $_config;
    public $_exit;
    public $_eu;
    
    function plgSystemePrivacy(&$subject, $config) {
        $this->_cookieACL=false;
        $this->_defaultACL= false;
        $this->_groupadded = false;
        $this->_prep = false;
        $this->_eprivacy = false;
        $this->_clear = array();
        $this->_country = false;
        $this->_display = true;
        $this->_displayed = false;
        $this->_displaytype = 'message';
        $this->_exit = false;
        $this->_eu=array(
            /* special cases - we run these just to be safe */
            'Anonymous Proxy','Satellite Provider',
            /* member states */
            'Austria','Belgium','Bulgaria','Cyprus','Czech Republic','Denmark','Estonia','Finland','France','Germany',
            'Greece','Hungary','Ireland','Italy','Latvia','Lithuania','Luxembourg','Malta','Netherlands','Poland',
            'Portugal','Romania','Slovakia','Slovenia','Spain','Sweden','United Kingdom',
            /* overseas member state territories */
            'Virgin Islands, British'/*United Kingdom*/,
            'French Guiana','Guadeloupe','Martinique','Reunion'/*France*/
        );
        parent::__construct($subject, $config);
    }    
    function onAfterInitialise() {
        $app = JFactory::getApplication();    
        if ($this->_exitEarly(true)) return; 
        
        $this->_displaytype = $this->params->get('displaytype','message');        
        $userconfig = JComponentHelper::getParams('com_users');
        $this->_defaultACL = $userconfig->get('guest_usergroup',1);
        $this->_cookieACL = $this->params->get('cookieACL',$userconfig->get('guest_usergroup',1));
                
        // this shouldn't affect logged in users
        if($this->_isGuest()) return;
        
        // guest accepted - yay
        if($this->_getAccept()) return;
        
        // guest/user just declined
        if($this->_getDecline()) return;
        
        // guests who have accepted
        if ($app->getUserState('plg_system_eprivacy', false)) {
            $this->_groupadded = true;
            $this->_display = false;
            $this->_eprivacy = true;
            return;
        }        
        
        // guests who have already accepted and have a cookie
        if($this->_hasLongTermCookie()) return; 
        
        
        // are they in a country where eprivacy is required?                
        if($this->params->get('geoplugin',false)) {
            $this->_useGeoPlugin();
        } else {            
            $app->setUserState('plg_system_eprivacy_non_eu',false);
        }
        
        if(!$this->_eprivacy) {
            $this->_cleanHeaders();
        }
        return true;
    }

    function onBeforeCompileHead() {      
        if ($this->_exitEarly()) return true;    
        $this->_pagePrepJS($this->_displaytype,$this->_display);
        $this->_requestAccept();
        if(!$this->_eprivacy) $this->_cleanHeaders();    
        // did the user just decline
        $this->_getDecline();            
        return true;
    }
    function onBeforeRender() {  
        if ($this->_exitEarly()) return true;   
        // because JAT3 is lame!
        $this->onBeforeCompileHead();    
    }
    function onAfterRender() {  
        if ($this->_exitEarly()) return true;  
        if(!$this->_eprivacy) $this->_cleanHeaders();      
        // did the user just decline
        $this->_getDecline();
        return true;
    }
    function _cleanHeaders() {
        $hasheaders = false;
        foreach (headers_list() as $header) {
            if($hasheaders) continue;
            if (preg_match('/Set-Cookie/', $header)) {
                $hasheaders = true;
            }
        }        
        if(!$hasheaders) return;
        $phpversion = explode('.', phpversion());
        if ($phpversion[1] >= 3) {
            header_remove('Set-Cookie');
        } else {
            header('Set-Cookie:');
        }
    }
    function _requestAccept() {
        if(JFactory::getUser()->id) return true;
        switch($this->params->get('displaytype','message')) {
            case 'message':
                if($this->_display && !$this->_displayed) {
                    $this->_displayed=true;
                    $msg = $this->_setMessage();
                    $app = JFactory::getApplication();
                    $app->enqueueMessage($msg, $this->params->get('messagetype','message'));
                }
                break;
            case 'confirm':
            case 'module':
            case 'modal':   
            case 'ribbon':            
                break;
        }
    }
    function _pagePrepJS($type,$autoopen=true){  
        $doc = JFactory::getDocument();
        JHtml::_('behavior.mootools',true);
        $doc->addScript(JURI::root(true).'/media/plg_system_eprivacy/js/eprivacy.js'); 
        $this->loadLanguage('plg_system_eprivacy');       
        if($this->_prep) return;
        $options = array('displaytype'=>$type,'autoopen'=>($autoopen?true:false),'accepted'=>($this->_eprivacy?true:false));
        if($this->_config['geopluginjs']===true) {
            $options['geopluginjs']=true;
            $options['eu']=$this->_eu;
            $doc->addScript('http://www.geoplugin.net/javascript.gp');
        }
        if(in_array($type,array('message','confirm','module','modal','ribbon'))) {
            $this->_getCSS('module'); 
            $options['translations']=$this->_jsStrings($type);
        }        
        switch($type) {
            case 'message':
            case 'confirm':
            case 'module':
                break;
            case 'modal':
                JHtml::_('behavior.modal');
                $options['policyurl']=$this->params->get('policyurl','');
                $options['modalclass']=$this->params->get('modalclass','');
                $options['modalwidth']=$this->params->get('modalwidth',600);
                $options['modalheight']=$this->params->get('modalheight',400);
                if($this->params->get('lawlink',1)) {
                    $url=$this->_getLawLink();
                } else {
                    $url='';
                }
                $options['lawlink']=$url;
                break;
            case 'ribbon':                
                $this->_getCSS('ribbon');       
                $options['policyurl']=$this->params->get('policyurl',''); 
                if($this->params->get('lawlink',1)) {
                    $url=$this->_getLawLink();
                } else {
                    $url='';
                }
                $options['lawlink']=$url;
                break;
            case 'cookieblocker';
                break;
        }
        $doc->addStyleDeclaration("\n#plg_system_eprivacy { width:0px;height:0px;clear:none; }\n");
        $doc->addScriptDeclaration("\nwindow.plg_system_eprivacy_options = ".json_encode($options).";\n");
        $this->_prep = true;
    }    
    function _getLawLink() {
        $langtag = explode('-',JFactory::getLanguage()->getTag());
        $langtag = strtoupper($langtag[0]);
        if(in_array($langtag,array('BG','ES','CS','DA','DE','ET','EL','EN','FR','GA','IT','LV','LT','HU','MT','NL','PL','PT','RO','SK','SL','FI','SV'))) {
            $linklang = $langtag;
        } else {
            $linklang = 'EN';
        }
        $url='http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:'.$linklang.':NOT';  
        return $url;
    }
    function _getURI($type='eprivacy') {
        $uri = $_SERVER['REQUEST_URI'];
        $querystring = explode('&', $_SERVER['QUERY_STRING']);
        $query = array();
        if(count($querystring) && strlen(trim($querystring[0])) > 0) {
            foreach($querystring as $q) {
                $q = explode('=',$q);
                $query[$q[0]]=$q[1];
            }
        }
        foreach(array('eprivacy','eprivacy_decline') as $key) {
            if(isset($query[$key])) unset($query[$key]);
        }
        switch($type) {
            case 'eprivacy':
                $query['eprivacy']=1;
                break;
            case 'decline':
                $query['eprivacy_decline']=1;
                break;
            default;
                break;
        }
        foreach($query as $k=>$q) {
            $query[$k]=$k.'='.$q;
        }
        $url = $uri.(count($query)?'?'.implode('&',$query):'');
        return $url;
    }    
    function _setMessage() { 
        $uri=$this->_getURI();
        $msg = '<div class="plg_system_eprivacy_message">';
        $msg.= '<h2>'.JText::_('PLG_SYS_EPRIVACY_MESSAGE_TITLE').'</h2>';
        $msg.= '<p>'.JText::_('PLG_SYS_EPRIVACY_MESSAGE').'</p>';
        
        if(strlen(trim($this->params->get('policyurl','')))) {
            $msg.= '<p><a href="'.trim($this->params->get('policyurl','')).'">'.JText::_('PLG_SYS_EPRIVACY_POLICYTEXT').'</a></p>';
        }
        if($this->params->get('lawlink',1)) {
            $msg.= '<p><a href="'.$this->_getLawLink().'" onclick="window.open(this.href);return false;">'.JText::_('PLG_SYS_EPRIVACY_LAWLINK_TEXT').'</a></p>';
        }
        
        $msg.= '<button class="plg_system_eprivacy_agreed">' . JText::_('PLG_SYS_EPRIVACY_AGREE') . '</button>';   
        $msg.= '<button class="plg_system_eprivacy_declined">' . JText::_('PLG_SYS_EPRIVACY_DECLINE') . '</button>';  
        $msg.= '<div id="plg_system_eprivacy"></div>';
        $msg.= '</div>';
        $msg.= '<div class="plg_system_eprivacy_declined">';
        $msg.= JText::_('PLG_SYS_EPRIVACY_DECLINED');
        $msg.= '<button class="plg_system_eprivacy_reconsider">' . JText::_('PLG_SYS_EPRIVACY_RECONSIDER') . '</button>'; 
        $msg.= '</div>';
        return $msg;
    }
    function _useGeoPlugin() {
        require_once(JPATH_ROOT.DS.'plugins'.DS.'system'.DS.'eprivacy'.DS.'geoplugin'.DS.'geoplugin.class.php');
        if(function_exists('curl_init') || ini_get('allow_url_fopen')) {
            $geoplugin = new geoPlugin();
            $geoplugin->locate();
            if(!in_array(trim($geoplugin->countryName),$this->_eu)) {
                $this->_eprivacy = true;
                $this->_display = false;
                $this->_addViewLevel();
                JFactory::getApplication()->setUserState('plg_system_eprivacy',true);
                JFactory::getApplication()->setUserState('plg_system_eprivacy_non_eu',true);
            } else {
                JFactory::getApplication()->setUserState('plg_system_eprivacy_non_eu',false);
                $this->_country = trim($geoplugin->countryName);
                $this->_eprivacy = false;
                $this->_display = true;
            }
        } else {
            $this->_eprivacy = false;
            $this->_country = 'Geoplugin JS: Country Not Available to PHP';
            $this->_config = array('geopluginjs'=>true);
        }
    }    
    function _jsStrings($type){
        $strings = array(
            'message'=>array('CONFIRMUNACCEPT'),
            'module'=>array('CONFIRMUNACCEPT'),
            'modal'=>array('MESSAGE_TITLE','MESSAGE','POLICYTEXT','LAWLINK_TEXT','AGREE','DECLINE','CONFIRMUNACCEPT'),
            'confirm'=>array('MESSAGE','JSMESSAGE','CONFIRMUNACCEPT'),
            'ribbon'=>array('MESSAGE','POLICYTEXT','LAWLINK_TEXT','AGREE','DECLINE','CONFIRMUNACCEPT')
        );
        $jsvar = array();
        foreach($strings[$type] as $string) {
            $jsvar['PLG_SYS_EPRIVACY_'.$string]=JText::_('PLG_SYS_EPRIVACY_'.$string);
        }
        return $jsvar;
    }    
    function _getAccept() {    
        $app = JFactory::getApplication();
        if ($app->input->get('eprivacy', false)) {
            $this->_addViewLevel();
            $this->_eprivacy = true;
            $this->_display = false;
            $app->setUserState('plg_system_eprivacy', true);
            if($this->params->get('longtermcookie',0)) {
                $config = JFactory::getConfig();
                $name = 'plg_system_eprivacy';
                $value = date('Y-m-d');
                $expires=time()+60*60*24*(int)$this->params->get('longtermcookieduration',30);
                $path=strlen($config->get('cookie_path'))?$config->get('cookie_path'):'/';
                $domain=strlen($config->get('cookie_domain'))?$config->get('cookie_domain'):$_SERVER['HTTP_HOST'];
                $app->input->cookie->set($name,$value,$expires,$path,$domain,false,false);
            }            
            if($this->params->get('logaccept',false)) {
                $db = JFactory::getDbo();
                $query = $db->getQuery(true);
                $query->insert('#__plg_system_eprivacy_log');
                $query->columns('ip,state,accepted');
                $query->values($db->quote($_SERVER['REMOTE_ADDR']).','.$db->quote(($this->_country?$this->_country:'not detected')).','.$db->quote(JFactory::getDate()->toMySQL()));
                $db->setQuery($query);
                $db->query();
            }
            return true;
        }
        return false;
    }    
    function _getDecline() {
        $app = JFactory::getApplication();
        $jinput = $app->input;
        if($jinput->get('eprivacy_decline',false)) {
            $app->setUserState('plg_system_eprivacy',false);
            $this->_addViewLevel('remove');
            $this->_eprivacy = false;
            $this->_display = true;
            $this->_cleanHeaders();
            if (isset($_SERVER['HTTP_COOKIE'])) {
                $config = JFactory::getConfig();
                $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                $cookie_path = strlen($config->get('cookie_path'))?$config->get('cookie_path'):'/';
                $cookie_domain = strlen($config->get('cookie_domain'))?$config->get('cookie_domain'):$_SERVER['HTTP_HOST'];
                foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    $jinput->cookie->set($name, null, -3600);
                    $jinput->cookie->set($name, null, -3600, $cookie_path,$cookie_domain);
                    unset($_COOKIE[$name]);
                }
            }
            return true;
        }
        return false;
    }    
    function _exitEarly($initialise = false) {
        if($this->_exit) return true;
        $app = JFactory::getApplication();
        // plugin should only run in the front-end
        if($app->isAdmin()) {
            $this->_exit = true;
            return true;
        }

        // plugin should only run in HTML pages
        if(!$initialise){
            $doc = JFactory::getDocument();
            if($doc->getType()!='html') {
                $this->_exit = true;
                return true;
            }
        }
        // shouldn't run in raw output
        if($app->input->get('format','','cmd') == 'raw') {
            $this->_exit = true;
            return true;        
        }
        return false;
    }   
    function _isGuest() {
        $user = JFactory::getUser();
        if(!$user->guest) {     
            $this->_addViewLevel();
            $this->_display = false;
            $this->_eprivacy = true;   
            return true;                 
        }
        return false;
    }    
    function _hasLongTermCookie() {
        if($this->params->get('longtermcookie',false)) {
            $app = JFactory::getApplication();            
            $accepted = $app->input->cookie->get('plg_system_eprivacy',false);
            if($accepted) {
                $config = JFactory::getConfig();
                $this->_addViewLevel();
                $this->_eprivacy = true;
                $this->_display = false;
                $cookie_path = strlen($config->get('cookie_path'))?$config->get('cookie_path'):'/';
                $cookie_domain = strlen($config->get('cookie_domain'))?$config->get('cookie_domain'):$_SERVER['HTTP_HOST'];
                $app->input->cookie->set('plg_system_eprivacy',$accepted,time()+60*60*24*(int)$this->params->get('longtermcookieduration',30), $cookie_path,$cookie_domain);
                return true;                
            }
        }
        return false;
    }    
    function _reflectJUser($remove=false) {
        // this is kinda hacky - but reflection is so cool
        $user = JFactory::getUser();
        $JAccessReflection = new ReflectionClass('JUser');
        $_authLevels = $JAccessReflection->getProperty('_authLevels');
        $_authLevels->setAccessible(true);
        $groups = $_authLevels->getValue($user);
        switch($remove) {
            case 'remove':
                $key = array_search($this->_cookieACL,$groups);
                if($key) {
                    unset($groups[$key]);
                    $this->_groupadded = false;
                }        
                break;
            default:
                if(!array_search($this->_cookieACL,$groups)) {
                    $groups[]=$this->_cookieACL;
                    $this->_groupadded = true;
                }        
                break;
        }
        $_authLevels->setValue($user,$groups);
    }
    function _addViewLevel($remove=false) {
        if(!class_exists('ReflectionClass',false) || !method_exists('ReflectionProperty','setAccessible')) return;
        if($this->_defaultACL == $this->_cookieACL) return;
        switch($remove) {
            case 'remove':
                $this->_reflectJUser('remove');
                break;
            default:
                $this->_reflectJUser();
                break;
        }
    }
    function _getCSS($type) {
        $css = array();
        switch($type) {
            case 'ribbon':
                if($this->params->get('useribboncss',1)) {
                    JFactory::getDocument()->addStyleSheet(JURI::root(true).'/media/plg_system_eprivacy/css/ribbon.css');
                    $css = $this->params->get('ribboncss');
                    JFactory::getDocument()->addStyleDeclaration($css);
                }
                break;
            case 'module':
                if($this->params->get('usemodulecss',1)) {
                    $css = $this->params->get('modulecss');
                    JFactory::getDocument()->addStyleDeclaration($css);
                }
                break;
            default:
                break;
        }
        if(count($css)) {
        }
    }
}