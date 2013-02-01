M.block_ucla_rearrange = M.block_ucla_rearrange || {};
M.block_ucla_rearrange.init = function(Y) {

    Y.on('domready', function() {

        try {
            // Get value of serialized data
            var s = Y.one('#serialized').get('value');

            M.block_ucla_rearrange.initialdata = $("#serialized").val();
            var warningmessage = M.util.get_string('changesmadereallygoaway', 'moodle');
            M.core_formchangechecker.report_form_dirty_state = function() {
                if(M.block_ucla_rearrange.initialdata != $("#serialized").val()) {
                    return warningmessage;
                }
                else {
                    return;
                }
            }
            window.onbeforeunload = M.core_formchangechecker.report_form_dirty_state;

            // If the form is submitted, don't trigger onbeforeunload action
            Y.one('#mform1').on('submit', function(e) {
                window.onbeforeunload = null
            })    
        } catch (err) {
            // Ignore errors.  When you end up here, it means 
            // the form has already been submitted and IDs are no longer in scope
        }

    });
}