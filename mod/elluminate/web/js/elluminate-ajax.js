
function elluminate_ajaxload(url, targetnode){
	YUI().use('io-base','node', function(Y) {
	    // Define a function to handle the response data.
	    function complete(id, o, args) {	    	
	        var data = o.responseText; // Response data.
	        targetnode.setHTML(data);
	    };
	    
	    function onStart(id, args){
	    	targetnode.setHTML("<img height='12px' width='12px' class='smallicon' src='pix/loading.gif'/>");
	    }

	    // Subscribe to event "io:complete", and pass an array
	    // as an argument to the event handler "complete", since
	    // "complete" is global.   At this point in the transaction
	    // lifecycle, success or failure is not yet known.
	    Y.on('io:start', onStart, Y );
	    Y.on('io:complete', complete, Y );

	    // Make an HTTP request to 'get.php'.
	    // NOTE: This transaction does not use a configuration object.
	    var request = Y.io(url);
	});
}