window.addEvent('domready',function(){
    document.id('jform_params_displaytype').getChildren('input').each(function(el){
        el.setStyle('clear','left');
        if(el.checked) {
            plg_system_eprivacy_switchtype(el);
        }        
    });
    $$('.longtermcookie').each(function(el){
        if(el.checked) {
            plg_system_eprivacy_longtermcookieduration(el);
        }   
    });
});
var plg_system_eprivacy_switchtype = function(el) {
    var eloption = el;
    var displaytype = 'display' + el.value;
    $$('.displayspecific').each(function(el){
        var parent = el.getParent('li');
        if(el.hasClass(displaytype)) {
            parent.show();
        } else {
            parent.hide();
        }        
    });
    plg_system_eprivacy_typeoptions(eloption);
}
var plg_system_eprivacy_typeoptions = function(el) {
    $$('.typeconfig').each(function(cel){
        var parentpanel = cel.getParent('div.panel');
        if(cel.hasClass(el.value)) {
            parentpanel.show();
        } else {
            parentpanel.hide();
        }
    });
}
var plg_system_eprivacy_longtermcookieduration = function(el){
    if(el.value == 1) {
        if(document.id('jform_params_longtermcookie').getParent('li').isDisplayed()) {
            document.id('jform_params_longtermcookieduration').getParent('li').show();
        }
    } else {
        document.id('jform_params_longtermcookieduration').getParent('li').hide();        
    }
}