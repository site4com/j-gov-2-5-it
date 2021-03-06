<?php
/**
 * @subpackage	mod_eprivacy
 * @copyright	Copyright (C) 2005 - 2012 Michael Richey. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
?>
<div class="plg_system_eprivacy_module">
    <?php if($pluginparams->get('displaytype','message') != "message") : ?>
    <div class="plg_system_eprivacy_message">
        <?php if($params->get('showtitle',1)): ?>
            <h2><?php echo JText::_('PLG_SYS_EPRIVACY_MESSAGE_TITLE');?></h2>
        <?php endif; ?>
        <p><?php echo JText::_('PLG_SYS_EPRIVACY_MESSAGE'); ?></p>
        <?php if(strlen(trim($pluginparams->get('policyurl','')))) : ?>
            <p><a href="<?php echo trim($pluginparams->get('policyurl',''));?>"><?php echo JText::_('PLG_SYS_EPRIVACY_POLICYTEXT');?></a></p>
        <?php endif; ?>
        <?php if($pluginparams->get('lawlink',1)) : ?>
            <p><a href="http://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:32002L0058:<?php echo $linklang;?>:NOT" onclick="window.open(this.href);return false;"><?php echo JText::_('PLG_SYS_EPRIVACY_LAWLINK_TEXT'); ?></a></p>
        <?php endif; ?>
        <button class="plg_system_eprivacy_agreed"><?php echo JText::_('PLG_SYS_EPRIVACY_AGREE');?></button>
        <button class="plg_system_eprivacy_declined"><?php echo JText::_('PLG_SYS_EPRIVACY_DECLINE');?></button>
    </div>
    <div class="plg_system_eprivacy_declined">
        <p>
            <button class="plg_system_eprivacy_reconsider"><?php echo JText::_('PLG_SYS_EPRIVACY_RECONSIDER');?></button> 
            <?php echo JText::_('PLG_SYS_EPRIVACY_DECLINED'); ?>
        </p>
    </div>
    <?php endif; ?>
    <div class="plg_system_eprivacy_accepted">
        <p>
            <button class="plg_system_eprivacy_accepted"><?php echo JText::_('PLG_SYS_EPRIVACY_UNACCEPT');?></button> 
            <?php echo JText::_('PLG_SYS_EPRIVACY_UNACCEPT_MESSAGE'); ?>
        </p>
    </div>
</div>
<div id="plg_system_eprivacy"></div>