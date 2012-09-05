<?php
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
 * The NanoGong TinyMCE plugin
 *
 * @author     Ning
 * @author     Gibson
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

define('NO_MOODLE_COOKIES', false); // Session not used here
define('NO_UPGRADE_CHECK', true);  // Ignore upgrade check

require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/config.php');
require_once($CFG->libdir.'/filelib.php');

require_login();  // CONTEXT_SYSTEM level

function curPageURL() {
    $pageURL = 'http';
    // Bug report from Davo Smith
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }
    else {
        $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" height="160" width="200">

<head>
    <title>NanoGong</title>
    <script type="text/javascript" src="../../tiny_mce_popup.js"></script>
    <script type="text/javascript" src="js/nanogong.js"></script>
    <script type="text/javascript">
        function getmaxduration() {
            var maxduration = window.top.document.getElementsByName('nanogongmaxduration').item(0);
            if (maxduration) {
                var nanogongparam = '<param name="MaxDuration" value="' + maxduration.value + '" />';
                return nanogongparam;
            }
            else {
                return '<param name="MaxDuration" value="300" />';
            }
        }
    </script>
</head>

<body style="display: none" role="application">

<form>
    <div id="nanogongurl">
        <table align="center" width="100%">
            <tr>
                <td colspan="2" style="font-size:12px">
                    <p>Please record your voice using this applet.</p>
                </td>
            </tr>
            <tr>
                <td align="right" width="15%">
                    <img src="img/nanogong.gif" alt="NanoGong Sound" />
                </td>
                <td width="85%">
                    <applet id="nanogong" archive="<?php echo str_replace('nanogong.php', 'nanogong.jar', curPageURL()); ?>" code="gong.NanoGong" width="200" height="40">
                        <script type="text/javascript">document.write(getmaxduration());</script>
                    </applet>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="font-size:10px">
                    <p id="messageAlert">After you have finished recording, press Insert.</p>
                </td>
            </tr>
        </table>
    </div>
    <div id="nanogonginput">
        <input type="button" id="insert" name="insert" value="{#insert}" onclick="NanogongDialog.insert(<?php echo $USER->id; ?>);" />
        <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
    </div>
</form>

</body>

</html>
