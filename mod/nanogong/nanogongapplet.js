// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.


/**
 * The JavaScript used in the NanoGong activity module
 *
 * @author     Ning
 * @author     Gibson
 * @package    mod
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

var currentnangongactivityid = '';
function nanogong_activity_count_click(id) {
    if (currentnangongactivityid == id) {
        currentnangongactivityid = '';
        return 0;
    }
    else {
        currentnangongactivityid = id;
        return 1;
    }
}


function nanogong_get_closest_div(obj) {
    var elem = document.getElementById('nanogonguniquediv');
    
    var objparent = obj.parentNode;
    while (objparent.tagName.toLowerCase() != 'div' || objparent === elem) {
        objparent = objparent.parentNode;
    }
    return objparent;
}

function nanogong_set_uniquediv_position(obj) {
    var elem = document.getElementById('nanogonguniquediv');
    var curtop = obj.offsetParent.offsetTop;
    var curleft = obj.offsetParent.offsetLeft;
    var closestdiv = nanogong_get_closest_div(obj);
    var maxwidth = closestdiv.offsetLeft + closestdiv.offsetWidth;

    if (maxwidth - curleft < 130) {
        curleft -= 130 - (maxwidth - curleft);
        curtop += 15;
    }
    else {
        curleft += obj.offsetWidth;
    }

    elem.style.top = curtop + 'px';
    elem.style.left = curleft + 'px';
}

function nanogong_show_applet_item(obj, id, contextid, modulename, filearea, itemid, name, baseurl) {
    var elem = document.getElementById('nanogonguniquediv');
    if (elem.style.visibility == 'visible') {
        elem.style.visibility = 'hidden';
    }
    var clickcount = nanogong_activity_count_click(id);
    if (clickcount) {
        var applet = document.getElementById('nanogongunique');
        var nanogongurl = baseurl + '/mod/nanogong/nanogongfile.php?contextid=' + contextid + '&modulename=' + modulename + '&filearea=' + filearea + '&itemid=' + itemid + '&name=' + name;
        applet.sendGongRequest('LoadFromURL', nanogongurl);
        applet.sendGongRequest('PlayMedia', 'audio');
        nanogong_set_uniquediv_position(obj);
        elem.style.visibility = 'visible';
    }
}

function nanogong_toUnicode(theString) {
    var unicodeString = '';
    for (var i=0; i < theString.length; i++) {
        var theUnicode = theString.charCodeAt(i).toString(16).toUpperCase();
        while (theUnicode.length < 4) {
            theUnicode = '0' + theUnicode;
        }
        theUnicode = '\\u' + theUnicode;
        unicodeString += theUnicode;
    }
    return unicodeString;
}


function nanogong_save_message(type, cmid, emptymessage, emptytitle) {
    // Find the applet object
    var applet = document.getElementById("nanogonginstance");
    var title = document.getElementById("nanogongtitle").value;
    var testtitle = title.split('\u0020').join('');
    title = nanogong_toUnicode(title);

    if (testtitle == null || testtitle == "") {
        alert(emptytitle);
    }
    else if (applet) {
        // Tell the applet to post the voice recording to the backend PHP code
        var url = 'audiofiles.php?id=' + cmid + '&type=' + type + '&title=' + title + '';
        var ret = applet.sendGongRequest(
            "PostToForm", url, "nanogong_upload_file",
            "", "nanogongaudio");
    
        if (ret == null || ret == "") {
            alert(emptymessage);
        }
        else if (ret == "1") {
            alert(emptytitle);
        }
        else {
            document.cookie = 'submissionarea=1';
            document.nanogongsubmit.submit();
        }
    }
}

function nanogong_save_audio_form(cmid, userid) {
    // Find the applet object
    var applet = document.getElementById("nanogonggradeinstance");
    if (applet) {
        // Tell the applet to post the voice recording to the backend PHP code
        var url = 'audiofiles.php?id=' + cmid + '&userid=' + userid;
        var ret = applet.sendGongRequest(
            "PostToForm", url, "nanogong_upload_file",
            "", "nanogongaudio");
    }
}

function nanogong_redirect_page(url) {
    window.location = url;
}

function nanogong_load_audio(voice){
    var applet = document.getElementById("nanogonggradeinstance");
    var url = applet.sendGongRequest('LoadFromURL', voice);
    applet.sendGongRequest('PlayMedia', 'audio');
}


function nanogong_get_catalog(url, pagenumber) {
    var catalog = document.getElementById("nanogongcatalog");
    var x = catalog.selectedIndex;
    var newurl = url + '&catalog=' + catalog.options[x].value + '&pagenumber=' + pagenumber;
    nanogong_redirect_page(newurl);
}

function nanogong_get_student(catalog, url, topage, pagenumber) {
    var student = document.getElementById("nanogongstudent");
    var x = student.selectedIndex;
    var newurl = url + '&catalog=' + catalog + '&pagenumber=' + pagenumber;
    if (student.options[x].value == 'p') {
        var page = topage - 1;
        newurl += '&studentid=0&topage=' + page;
    }
    else if (student.options[x].value == 'n') {
        var page = topage + 1;
        newurl += '&studentid=0&topage=' + page;
    }
    else {
        newurl += '&studentid=' + student.options[x].value + '&topage=' + topage;
    }
    nanogong_redirect_page(newurl);
}

function nanogong_check_delete_item_from_message(baseurl, deletealertmessage) {
    var studentlistbox = document.getElementById("nanogongstudentlistbox");
    var x = studentlistbox.selectedIndex;
    if (x > -1) {
        var fileurl = studentlistbox.options[x].value;
        var start = fileurl.indexOf('name=') + 5;
        var filename = fileurl.substr(start, 18);
        var newurl = baseurl + '&checkdelete=1&nanogongcheckfilename=' + filename;
        nanogong_redirect_page(newurl);
    }
    else {
        alert(deletealertmessage);
    }
}

function nanogong_set_studentdiv_position(obj, x) {
    var elem = document.getElementById('nanogongstudentdiv');
    var studentlist = document.getElementById('nanogongstudentlistbox');
    var teacherlist = document.getElementById('nanogongteacherlistbox');
    var listsize = 0;
    if (teacherlist) {
        listsize = teacherlist.size
    }
    else if (studentlist) {
        listsize = studentlist.size;
    }
    var curtop = obj.offsetParent.offsetTop + obj.offsetTop;
    if (listsize <= 20) {
        curtop += x * 17;
    }
    else {
        curtop += obj.offsetHeight / 2;
    }
    var curleft = obj.offsetParent.offsetLeft + obj.offsetWidth + 20;
    elem.style.top = curtop + 'px';
    elem.style.left = curleft + 'px';
}

function nanogong_load_from_message(obj, times, submiton, listid) {
    var studentdiv = document.getElementById("nanogongstudentdiv");
    if (studentdiv.style.visibility == "visible") {
        studentdiv.style.visibility = "hidden";
    }
    var studentlistbox = document.getElementById(listid);
    var submittedtime = document.getElementById("nanogongsubmittedtime");
    var x = studentlistbox.selectedIndex;
    var url = studentlistbox.options[x].value;
    var applet = document.getElementById("nanogongstudentmessage");
    var ret = applet.sendGongRequest('LoadFromURL', url);
    applet.sendGongRequest('PlayMedia', 'audio');
    var time = parseInt(times.substr(x*11+1, 10));
    var d = new Date(time * 1000);
    var dnames = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
    var mnames = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");

    var currday = d.getDay();
    var currdate = d.getDate();
    var currmonth = d.getMonth();
    var curryear = d.getFullYear();
    
    var ap = "";
    var currhour = d.getHours();
    
    if (currhour < 12){
        ap = "AM";
    }
    else {
        ap = "PM";
    }
    if (currhour == 0) {
        currhour = 12;
    }
    if (currhour > 12) {
        currhour = currhour - 12;
    }
    currhour = currhour + "";
    if (currhour.length == 1) {
        currhour = "0" + currhour;
    }

    var currmin = d.getMinutes();

    currmin = currmin + "";

    if (currmin.length == 1) {
        currmin = "0" + currmin;
    }

    var mydate = dnames[currday] + ', ' + currdate + ' ' + mnames[currmonth] + ' ' + curryear + ', ' + currhour + ':' + currmin + ' ' + ap;

    submittedtime.innerHTML = submiton + mydate;

    nanogong_set_studentdiv_position(obj, x);
    studentdiv.style.visibility = "visible";
}

function nanogong_set_pagenumber(catalog, url) {
    var pagenumber = document.getElementById("nanogongpagenumber");
    var x = pagenumber.selectedIndex;
    var newurl = url + '&catalog=' + catalog + '&pagenumber=' + pagenumber.options[x].value;
    nanogong_redirect_page(newurl);
}

function nanogong_set_pagenumber_chronological(reverse, listall, url) {
    var pagenumber = document.getElementById("nanogongpagechronological");
    var x = pagenumber.selectedIndex;
    var newurl = url + '&toreverse=' + reverse + '&tolistall=' + listall + '&pagenumber=' + pagenumber.options[x].value;
    nanogong_redirect_page(newurl);
}

function toggleDiv(divid, iconid){
    if(document.getElementById(divid).style.display == 'none') {
        document.getElementById(divid).style.display = 'block';
        document.getElementById(iconid + "_view").style.display = 'none';
        document.getElementById(iconid + "_hide").style.display = 'block';
        document.cookie = divid + '=1';
    }
    else {
        document.getElementById(divid).style.display = 'none';
        document.getElementById(iconid + "_view").style.display = 'block';
        document.getElementById(iconid + "_hide").style.display = 'none';
        document.cookie = divid + '=0';
        if (divid == 'submissionarea') {
            document.getElementById("nanogongstudentdiv").style.visibility = 'hidden';
        }
    }
  }

