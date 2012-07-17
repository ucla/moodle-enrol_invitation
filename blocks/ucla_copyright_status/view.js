/**
 * On select copyright status store strings with file ids to an element
 * 
 */
 
YUI().use('event-delegate', function(Y){
	 Y.delegate('change', function (e){
		var item = this.get('value');
		// event process function
		var item_value = this.get('value');
		var item_key = this.get('id');
		$('#block_ucla_copyright_status_d1').data(item_key, item_value);
	 }, '#block_ucla_copyright_status_id_cp_list', 'select');
});


/**
 * On button click save changes to database
 * 
 */

YUI().use('node-base', function(Y){
	var btnl_Click = function(e){
		$('#block_ucla_copyright_status_d1').data('action', 'edit');
		$.post('#', $('#block_ucla_copyright_status_d1').data());
		$('#block_ucla_copyright_status_changes_saved').text(M.str.block_ucla_copyright_status.changes_saved);
	};
	Y.on('click', btnl_Click, '#block_ucla_copyright_status_btn1');
});

