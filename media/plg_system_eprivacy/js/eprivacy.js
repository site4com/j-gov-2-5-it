var ePrivacy = new Class({
    Implements: [Options],
    options: {
        accepted:false,
        displaytype: 'message',
        policyurl: '',
        media:'',
        translations: {},
        autoopen:true,
        modalclass: '',
        modalwidth: '600',
        modalheight: '400',
        lawlink: ''
    },
    initialize: function(options) { 
        this.setOptions(options);        
        var decline = this.getDataValue();
        if(decline == 1 || decline == 2 || !this.options.autoopen) {
            this.hideMessage();
        } else {
            this.showMessage();
        }
        this.initElements();
        this.reloadAfterDecision();
    },  
    initElements: function() {
        var self = this;
        $$('button.plg_system_eprivacy_agreed').each(function(el){
            el.addEvent('click',function() {
                self.acceptCookies();
            });
        });   
        $$('button.plg_system_eprivacy_accepted').each(function(el){
            el.addEvent('click',function() {
                self.unacceptCookies();
            });
        }); 
        $$('button.plg_system_eprivacy_declined').each(function(el){
            el.addEvent('click',function() {
                self.declineCookies();
            });
        });    
        $$('button.plg_system_eprivacy_reconsider').each(function(el){
            el.addEvent('click',function() {
                self.undeclineCookies();
            });
        });          
    },
    acceptCookies: function() {
        var self = this;
        self.setDataValue(2);
        var myURI = new URI(window.location);
        if(myURI.getData('eprivacy_decline') == 1) {
            var data = myURI.get('data');
            delete data.eprivacy_decline;
            myURI.set('data',data);
        }
        myURI.setData('eprivacy', 1);
        window.location = myURI.toString();
    },
    unacceptCookies: function() { 
        var self = this;
        var r = confirm(self.options.translations.PLG_SYS_EPRIVACY_CONFIRMUNACCEPT);
        if(r==true) {
            self.setDataValue(1);
            var myURI = new URI(window.location);
            if(myURI.getData('eprivacy') == 1) {
                var data = myURI.get('data');
                delete data.eprivacy;
                myURI.set('data',data);
            }
            myURI.setData('eprivacy_decline',1);
            window.location = myURI.toString();
        }
    },
    declineCookies: function() {  
        var self = this;
        self.setDataValue(1);
        self.hideMessage();
    },
    undeclineCookies: function() {   
        var self = this;
        self.setDataValue(0);
        self.showMessage();
    },
    showMessage: function() {
        var self = this;    
        $$('div.plg_system_eprivacy_declined').each(function(el){el.hide();});
        $$('div.plg_system_eprivacy_accepted').each(function(el){el.hide();}); 
        switch(self.options.displaytype) {
            case 'message':
            case 'module':
                $$('div.plg_system_eprivacy_message').each(function(el){el.show();});
                break;
            case 'confirm':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();}); 
                this.displayConfirm();
                break;
            case 'modal':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});
                this.displayModal();
                break;
            case 'ribbon':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});
                this.displayRibbon();
                break;
            case 'cookieblocker':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});
                break;
        }
    },  
    hideMessage: function() {        
        var self = this; 
        if(self.getDataValue() == 1) {
            $$('div.plg_system_eprivacy_declined').each(function(el){el.show();});
            $$('div.plg_system_eprivacy_accepted').each(function(el){el.hide();}); 
        } else {     
            $$('div.plg_system_eprivacy_declined').each(function(el){el.hide();});
            $$('div.plg_system_eprivacy_accepted').each(function(el){el.show();});
        }  
        switch(self.options.displaytype) {
            case 'message':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});
                break; 
            case 'confirm':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});    
                break;
            case 'module':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();});
                break;
            case 'modal':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();}); 
                SqueezeBox.close();
                if(Browser.ie6 || Browser.ie7) { // mostly for IE7 - but IE6 just in case
                    if($$('.plg_system_eprivacy_modal').length) {
                        document.id('sbox-window').hide();
                    }
                }
                break;
            case 'ribbon':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();}); 
                $$('div.activebar-container').each(function(el){el.destroy();});
                break;
            case 'cookieblocker':
                $$('div.plg_system_eprivacy_message').each(function(el){el.hide();}); 
                $$('div.plg_system_eprivacy_declined').each(function(el){el.hide();});
                $$('div.plg_system_eprivacy_accepted').each(function(el){el.hide();}); 
                break;
        }
    },
    setDataValue: function(value) {
        if(Browser.ie6 || Browser.ie7) {        
            var element = document.getElementById('plg_system_eprivacy');
            element.setAttribute('plg_system_eprivacy_decline',value);
            element.save("oDataStore");
            return;
        } else {
            var mydomstorage=(window.localStorage || (window.globalStorage? globalStorage[location.hostname] : null));
            if(mydomstorage) {
                mydomstorage.plg_system_eprivacy_decline=value;
                return;
            }
            if (window.sessionStorage){
                sessionStorage.setItem('plg_system_eprivacy_decline',value);
                return;
            }
        }      
    },
    getDataValue: function() {
        var value = 0;
        if(Browser.ie6 || Browser.ie7) {  
            var element = document.getElementById('plg_system_eprivacy');
            element.load("oDataStore");
            value = element.getAttribute('plg_system_eprivacy_decline');
            return value;
        } else {
            var mydomstorage=(window.localStorage || (window.globalStorage? globalStorage[location.hostname] : null));
            if(mydomstorage) {
                value = mydomstorage.plg_system_eprivacy_decline;
                return value;
            }                
            if (window.sessionStorage){
                value = sessionStorage.getItem('plg_system_eprivacy_decline');
                return value;
            }
        }
        return value;
    },
    displayRibbon: function(){
        var self = this;  
        var ribbon = new Element('div',{'class':'activebar-container'}).inject(document.body);
        var message = new Element('p',{'html':self.options.translations.PLG_SYS_EPRIVACY_MESSAGE}).inject(ribbon,'bottom');
        var decline = new Element('button',{'html':self.options.translations.PLG_SYS_EPRIVACY_DECLINE,'class':'decline'}).inject(message,'top');
        var accept = new Element('button',{'html':self.options.translations.PLG_SYS_EPRIVACY_AGREE,'class':'accept'}).inject(message,'top');
        if(self.options.policyurl.length >0 || self.options.lawlink.length > 0) {
            var links = new Element('ul',{'class':'links'}).inject(message,'bottom');
            var link;
            if(self.options.policyurl.length > 0) {
                link = new Element('li').inject(links,'bottom');
                var policyurl = new Element('a',{'href':self.options.policyurl,'html':self.options.translations.PLG_SYS_EPRIVACY_POLICYTEXT}).inject(link,'bottom');
            }
            if(self.options.lawlink.length > 0) {
                link = new Element('li').inject(links,'bottom');
                var lawlink = new Element('a',{'href':self.options.lawlink,'html':self.options.translations.PLG_SYS_EPRIVACY_LAWLINK_TEXT}).inject(link,'bottom');
                lawlink.addEvent('click',function(){ window.open(lawlink.href); return false;});
            }
        }
        decline.addEvent('click',function(){self.declineCookies();});
        accept.addEvent('click',function(){self.acceptCookies();});        
        if(Browser.ie6 || Browser.ie7) {
            ribbon.setStyle('position','absolute');
        }
    },
    displayConfirm: function() {
        var self = this;
        if(self.getDataValue() != 1) {
            var r=confirm(self.options.translations.PLG_SYS_EPRIVACY_MESSAGE + ' ' + self.options.translations.PLG_SYS_EPRIVACY_JSMESSAGE);
            if (r==true) {
                self.acceptCookies();
            } else {
                self.declineCookies();
            } 
        }      
    },
    displayModal: function() {
        var self = this;
        if(self.getDataValue('decline') != 1) {   
            var c = new Element('div');
            var ctitle = new Element('h1',{'html':self.options.translations.PLG_SYS_EPRIVACY_MESSAGE_TITLE}).inject(c,'bottom');
            var cmessage = new Element('p',{'html':self.options.translations.PLG_SYS_EPRIVACY_MESSAGE}).inject(c,'bottom');     
            if(self.options.policyurl.length > 0) {            
                var cpolicy = new Element('p').inject(c,'bottom');
                var cpolicylink = new Element('a',{'href':self.options.policyurl,'html':self.options.translations.PLG_SYS_EPRIVACY_POLICYTEXT}).inject(cpolicy,'bottom');             
            }     
            if(self.options.lawlink.length > 0) {            
                var claw = new Element('p').inject(c,'bottom');
                var clawlink = new Element('a',{'href':self.options.lawlink,'html':self.options.translations.PLG_SYS_EPRIVACY_LAWLINK_TEXT}).inject(claw,'bottom');  
                clawlink.addEvent('click',function(){
                    window.open(this.href); return false;
                });           
            }
            var cagree = new Element('button',{'html':self.options.translations.PLG_SYS_EPRIVACY_AGREE,'class':'plg_system_eprivacy_agreed'}).inject(c,'bottom');
            cagree.addEvent('click',function(){self.acceptCookies()});
            var cdecline = new Element('button',{'html':self.options.translations.PLG_SYS_EPRIVACY_DECLINE,'class':'plg_system_eprivacy_declined'}).inject(c,'bottom');
            cdecline.addEvent('click',function(){self.declineCookies()});
            var modaloptions = {
                handler: 'adopt',
                classWindow: 'plg_system_eprivacy_modal',
                closable: false,
                closeBtn: false,
                size: {
                    x:parseInt(self.options.modalwidth),
                    y:parseInt(self.options.modalheight)
                }
            };
            if(self.options.modalclass.length > 0) modaloptions.classWindow = modaloptions.classWindow + ' ' + self.options.modalclass;
            SqueezeBox.initialize();
            SqueezeBox.open(c,modaloptions);
            document.id('sbox-btn-close').setStyle('display','none');
            if(Browser.ie6 || Browser.ie7) {  // IE sucks
                document.id('sbox-window').show();
            }
        }      
    },
    reloadAfterDecision:function(){
        var uri = new URI(window.location);
        var data = uri.getData();
        if(data.hasOwnProperty('eprivacy') || data.hasOwnProperty('eprivacy_decline')) {
            if(data.hasOwnProperty('eprivacy')) delete data['eprivacy'];
            if(data.hasOwnProperty('eprivacy_decline')) delete data['eprivacy_decline'];
            uri.setData(data);
            uri.go();
        }
    }
});
window.addEvent('domready',function(){
    if(!window.plg_system_eprivacy_options.accepted) {
        if(!document.__defineGetter__) {  
            if(!Browser.ie6 && !Browser.ie7) { // javascript cookies blocked only in IE8 and up
                Object.defineProperty(document, 'cookie', {
                    get: function(){return ''},
                    set: function(){return true}
                });
            }
        } else { // non IE browsers use this method to block javascript cookies
            document.__defineGetter__("cookie", function() { return '';} );
            document.__defineSetter__("cookie", function() {} );
        }
    }
    var plg_system_eprivacy_class = new ePrivacy(window.plg_system_eprivacy_options);
});