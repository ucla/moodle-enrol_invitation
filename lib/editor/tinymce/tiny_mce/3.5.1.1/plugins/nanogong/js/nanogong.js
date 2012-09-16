// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * JavaScript for the NanoGong TinyMCE plugin
 *
 * @author     Ning
 * @author     Gibson
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

var NanogongDialog = {
    init : function(ed, url) {
        var ed = tinyMCEPopup.editor, n = ed.selection.getNode();
        tinyMCEPopup.resizeToInnerSize();
        tinyMCEPopup.storeSelection();
        
        if (n.nodeName == 'IMG') {
            var url = tinyMCEPopup.editor.dom.getAttrib(n, 'alt');
            var params = url.split('/');

            var contextid = params[1];
            var itemid = params[4];
            var filename = params[5];

            var soundurl = tinyMCEPopup.getWindowArg('plugin_url') + '/nanogonggetfile.php?contextid=' + contextid + '&itemid=' + itemid + '&filename=' + filename;
            
            var nanogongapplet = '';
            nanogongapplet += '<table align="center" width="100%">';
            nanogongapplet +=   '<tr>';
            nanogongapplet +=     '<td colspan="2" style="font-size:12px">';
            nanogongapplet +=       '<p>Press the play button to hear the recording.</p>';
            nanogongapplet +=     '</td>';
            nanogongapplet +=   '</tr>';
            nanogongapplet +=   '<tr>';
            nanogongapplet +=     '<td align="right" width="20%">';
            nanogongapplet +=       '<img src="img/nanogong.gif" alt="NanoGong Sound" />';
            nanogongapplet +=     '</td>';
            nanogongapplet +=     '<td width="80%">';
            nanogongapplet +=       '<applet archive="' + tinyMCEPopup.getWindowArg('plugin_url') + '/nanogong.jar" code="gong.NanoGong" width="160" height="40">';
            nanogongapplet +=         '<param name="ShowTime" value="true" /><param name="ShowAudioLevel" value="false" />';
            nanogongapplet +=         '<param name="ShowRecordButton" value="false" /><param name="SoundFileURL" value="' + soundurl + '" />';
            nanogongapplet +=       '</applet>';
            nanogongapplet +=     '</td>';
            nanogongapplet +=   '</tr>';
            nanogongapplet +=   '<tr>';
            nanogongapplet +=     '<td colspan="2" style="font-size:10px">';
            nanogongapplet +=       '<p>Click OK to continue.</p>';
            nanogongapplet +=     '</td>';
            nanogongapplet +=   '</tr>';
            nanogongapplet += '</table>';

            document.getElementById("nanogongurl").innerHTML = nanogongapplet;
            document.getElementById("nanogonginput").innerHTML = '<input type="button" id="cancel" name="cancel" value="OK" onclick="tinyMCEPopup.close();" />';
        }
    },

    insert : function(userid) {
        // Find the applet object
        var applet = document.getElementById("nanogong");
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

        if (applet && itemid) {
            itemid = itemid.value
            // Tell the applet to post the voice recording to the backend PHP code
            var cururl = tinyMCEPopup.getWindowArg('plugin_url');
            var url = cururl + '/nanogongsendaudio.php?userid=' + userid + '&itemid=' + itemid;
                       
            var ret = applet.sendGongRequest(
                "PostToForm", url, "nanogong_upload_file",
                "", "nanogongaudio");
        
            if (ret == null || ret == "") {
                message.textContent = "Nothing has been recorded yet.";
                message.innerText = "Nothing has been recorded yet.";
            }
            else {
                message.textContent = "Successful!";
                message.innerText = "Successful!";
                
                var rewriteurl = '@@PLUGINFILE@@/' + ret.substr(ret.length - 18, 18);
                // Insert the contents from the input into the document
                tinyMCEPopup.execCommand('mceInsertContent', false, tinyMCEPopup.editor.dom.createHTML('img', {
                    longdesc : rewriteurl,
                    'class' : 'mceNanogong',
                    src : tinyMCEPopup.getWindowArg('plugin_url') + '/img/nanogong.gif',
                    style : 'vertical-align: middle',
                    alt : ret
                }));
                tinyMCEPopup.restoreSelection();
                tinyMCEPopup.close();
            }
        }
        else {
            tinyMCEPopup.restoreSelection();
            tinyMCEPopup.close();
        }
    }
};

tinyMCEPopup.onInit.add(NanogongDialog.init, NanogongDialog);
