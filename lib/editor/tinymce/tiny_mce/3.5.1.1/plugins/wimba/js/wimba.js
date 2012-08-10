function onOK() {

  var fields = ["f_mid", "f_rid"];
  var param = new Object();
  for (var i in fields) {
    var id = fields[i];
    var el = document.getElementById(id);
    param[id] = el.value;
  }
  __dlg_close(param);
  return false;
};

function insertWimba() {
    var fe, f = document.forms[0], h;
    var ed = tinyMCEPopup.editor;

    tinyMCEPopup.restoreSelection();

    if (!AutoValidator.validate(f)) {
        tinyMCEPopup.alert(ed.getLang('invalid_data'));
        return false;
    }

    fe = ed.selection.getNode();
    url = f.f_rid.value + '__' + f.f_mid.value;

    var onclick = "if(!YAHOO.util.Dom.get('"+url+"_iframe')) {";
        onclick += "var va = document.createElement('iframe'); va.setAttribute('id','"+url+"_va');";
        onclick += "va.setAttribute('frameborder', '0');";
        onclick += "va.setAttribute('width', '0px');";
        onclick += "va.setAttribute('height', '0px');";
        onclick += "va.setAttribute('style', 'display: none; z-index: 1000;');";
        onclick += "va.setAttribute('allowtransparency', 'true');";
        onclick += "va.setAttribute('frameBorder', '0');";
        onclick += "va.setAttribute('scrolling', 'no');";
        onclick += "var child = document.createElement('iframe'); child.setAttribute('id','"+url+"_iframe');";
        onclick += "child.setAttribute('src', '"+wwwroot+"/mod/voiceauthoring/displayWysiwyg.php?rid="+f.f_rid.value+"&mid="+f.f_mid.value+"');";
        onclick += "child.setAttribute('frameborder', '0');";
        onclick += "child.setAttribute('width', '0px');";
        onclick += "child.setAttribute('height', '0px');";
        onclick += "child.setAttribute('style', 'display: none;');";
        onclick += "YAHOO.util.Dom.get('"+url+"_image').parentNode.appendChild(va);";
        onclick += "YAHOO.util.Dom.get('"+url+"_image').parentNode.appendChild(child);";
        onclick += "}return false;";

    thtml = '<img title="Click to play" src="'+wwwroot+'/mod/voiceauthoring/lib/web/pictures/items/wimba_sound.png" id="'+url+'_image" onclick="'+onclick+'" />';

    ed.execCommand('mceInsertRawHTML', false, thtml);

    tinyMCEPopup.close();
}

