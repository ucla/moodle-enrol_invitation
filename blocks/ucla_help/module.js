/**
 * @namespace ucla
 */
M.block_ucla_help = M.block_ucla_help || {};

/**
 * This function is called to initialize form.
 *
 * @param {Object} Y YUI instance
 */
M.block_ucla_help.init = function(Y) {
    console.log('block_ucla_help.init called');
    ajaxifyForm('help_form', 'ucla-help-overlay');
}

/**
 * Code originally from:
 * 
 * AJAX Form Submit
 * http://www.micahcarrick.com/ajax-form-submit.html
 */
function ajaxifyForm(formId, updateId) {
    console.log('ajaxifyForm called');
    var formObj = document.getElementById(formId);

    // this is the callback for the form's submit event
    var submitFunc = function (e) {
    
        // prevent default form submission
        YAHOO.util.Event.preventDefault(e);
        
        // define a YUI callback object
        var callback = {
            success: function(o) { 
                document.getElementById(updateId).innerHTML = o.responseText; 
            },
            failure: function(o) { 
                //silently just fail
                //alert("AJAX request failed!");
                console.log('AJAX request failed!');
            }
        }
        
        // connect to the form and submit it via AJAX
        YAHOO.util.Connect.setForm(formObj);
        YAHOO.util.Connect.asyncRequest(formObj.method, formObj.action, callback);
    }
    
    // call our submit function when the submit event occurs
    YAHOO.util.Event.addListener(formObj, "submit", submitFunc);
}