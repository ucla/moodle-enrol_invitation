// $Id: elltestconnection.js,v 1.2 2009-04-01 21:04:34 jfilip Exp $

function elltestConnection(obj, wwwroot) {
/// This function will open a popup window to test the server parameters for
/// successful connection.
	
	if ((obj.s__elluminate_server.value.length == 0) || (obj.s__elluminate_server.value == '')) {
        return false;
    }

    var queryString = "";

    queryString += "serverURL=" + escape(obj.s__elluminate_server.value);
    queryString += "&authUsername=" + escape(obj.s__elluminate_auth_username.value);
    queryString += "&authPassword=" + escape(obj.s__elluminate_auth_password.value);
    queryString += "&boundaryDefault=" + escape(obj.s__elluminate_boundary_default.value);
    queryString += "&maxTalkers=" + escape(obj.s__elluminate_max_talkers.value);
    queryString += "&prepopulate=" + escape(obj.s__elluminate_pre_populate_moderators.value);
    queryString += "&wsDebug=" + escape(obj.s__elluminate_ws_debug.value);

    return window.open(wwwroot + '/mod/elluminate/conntest.php?' + queryString, 'connectiontest', 'scrollbars=yes,resizable=no,width=640,height=300');
}

YUI().use('node', function(Y) {
    var onClick = function(e) {
      elltestConnection(document.getElementById('adminsettings'), M.cfg.wwwroot);       
   };
    
    Y.all('input.form-submit').after('click',onClick);
});
