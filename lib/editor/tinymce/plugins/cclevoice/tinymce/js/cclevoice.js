tinyMCEPopup.requireLangPack();

var cclevoiceDialog = {
    init : function(ed) {
    },
    insert : function(userid) {
        var message = document.getElementById("messageAlert");
        var formtextareaid = tinyMCE.activeEditor.id.substr(3);
        var itemidname = '';
        var formtextareatmp = formtextareaid.split("_");
        if (formtextareatmp.length == 2 && !isNaN(formtextareatmp[1])) {
            itemidname = formtextareatmp[0] + '[' + formtextareatmp[1] + '][itemid]';
        }
        else {
            itemidname = formtextareaid + '[itemid]';
        }
        var itemid = window.top.document.getElementsByName(itemidname).item(0);
        var contextid = document.getElementById('context_id');
        var myfilename = document.getElementById('myfilename');
        var wwwroot = document.getElementById('wwwroot');
        if (itemidname) {
           itemid = itemid.value;
           contextid = contextid.value;
           myfilename = myfilename.value;
           wwwroot = wwwroot.value
           // it will store in mdl_question with the "@@PLUGINFILE@@/myfile.mp3" for the filepath
           var h = '<a href="'+wwwroot+'/draftfile.php/'+contextid+'/user/draft/'+itemid+'/'+myfilename+'">'+myfilename+'</a>';
          // Insert the contents from the input into the document
           tinyMCEPopup.execCommand('mceInsertContent', false,h);
        }
        tinyMCEPopup.restoreSelection();
        tinyMCEPopup.close();
    }
};

tinyMCEPopup.onInit.add(cclevoiceDialog.init, cclevoiceDialog);