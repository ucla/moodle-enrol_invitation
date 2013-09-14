YUI().use('node','event-base', function(Y) {
	var elluminate_onPageLoad = function() {
		removeSubmit = Y.one("#removeSubmit");
		removeSubmit.on('click',elluminate_removeUser);
		
		addSubmit = Y.one("#addSubmit");
		addSubmit.on('click',elluminate_addUser);
	};	
	
	var elluminate_addUser = function(e){	
		Y.one("#addSubmit").set('disabled','true');
		Y.one("#submitvalue").set('value','add');
	    participantForm.submit();
	}  
	
	var elluminate_removeUser = function(e){
		Y.one("#removeSubmit").set('disabled','true');
	    Y.one("#submitvalue").set('value','remove');
	    
	    participantForm.submit();
	}  

	Y.on('domready', elluminate_onPageLoad);
});