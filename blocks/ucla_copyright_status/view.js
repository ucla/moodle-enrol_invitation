/**
 * On select store strings with file ids to an element
 * 
 */
 
YUI().use('event-delegate', function(Y){
	 Y.delegate('change', function (e){
		var item = this.get('value');
		// event process function
		var item_value = this.get('value');
		var item_key = this.get('id');
		$('#d1').data(item_key, item_value);
	 }, '#id_cp_list', 'select');
});


/**
 * On button click save changes to database
 * 
 */

YUI().use('node-base', function(Y){
	var btnl_Click = function(e){
		$('#d1').data('action', 'edit');
		$.post('#', $('#d1').data());
		$('#changes_saved').text(M.str.block_ucla_copyright_status.changes_saved);
	};
	Y.on('click', btnl_Click, '#btn1');
});

