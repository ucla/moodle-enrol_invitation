//Load Event Handling for Recordings on Page
YUI().use('node', function(Y) {
    var elluminate_recordings_onClick = function(e) {
    	try{	
    		e.halt();
    		ajaxLink = e._currentTarget + "&ajax=1";
    		elluminate_ajaxload(ajaxLink, Y.one(e._currentTarget.parentElement));
    	}catch(err){
    		//Any type of error with the ajax call, just load the current link
    		window.location.href = e._currentTarget;
    	}
    };
    
    convertMP3Links = Y.all("#mp3not_available");
    convertMP3Links.on('click',elluminate_recordings_onClick);

    convertMP4Links = Y.all("#mp4not_available");
    convertMP4Links.on('click',elluminate_recordings_onClick);
});