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
 * Show NanoGong applet after clicking the corresponding image
 *
 * @author     Ning
 * @author     Gibson
 * @package    filter
 * @subpackage nanogong
 * @copyright  2012 The Gong Project
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version    4.2.1
 */

class filter_nanogong extends moodle_text_filter {
    public function filter($text, array $options = array()) {
        global $CFG, $PAGE, $COURSE, $DB;

        if (preg_match_all('/<img.*?class="mceNanogong".*?>/', $text, $imgs) == 0) {
            return $text;
        }

        foreach ($imgs[0] as $img) {
            $startpos = strpos($text, $img);
            $totallength = strlen($img);

            $path = "";
            if (preg_match('/title="(.*?)"/', $img, $title) > 0) {
                if (!empty($title[1])) {
                    $path = preg_replace("/.*?\.php\//", "", $title[1]);
                }
            }
            if (preg_match('/longdesc="(.*?)"/', $img, $longdesc) > 0) {
                if (!empty($longdesc[1])) {
                    $path = preg_replace("/.*?\.php\//", "", $longdesc[1]);
                }
            }
            if (empty($path)) continue;

            $params = preg_split("/\//", $path);

            $contextid = $params[0];
            $modulename = $params[1];
            $filearea = $params[2];
            if (count($params) == 4) {
                $itemid = 0;
                $nanogongname = $params[3];
            }
            else {
                $itemid = $params[3];
                $nanogongname = $params[4];
            }
            $nanogongid = substr($nanogongname, 0, 14);

            $newimg = '<span id= "' . $nanogongid . '" style="position:relative;" ><img src="' . $CFG->wwwroot . '/filter/nanogong/pix/icon.gif" style="vertical-align: middle" onclick="javascript:nanogong_show_applet(this, ' . $nanogongid . ', ' . $contextid . ', \'' . $modulename . '\', \'' . $filearea . '\', ' . $itemid . ', \'' . $nanogongname . '\', \''. $CFG->wwwroot . '\');" /></span>';

            $text = substr_replace($text, $newimg, $startpos, $totallength); 
        }

        $nanogongfiltercount = $contextid . '' . $itemid;
        return '<script type="text/javascript" src="' . $CFG->wwwroot . '/filter/nanogong/nanogongfilter.js" ></script ><div id="nanogongfilterdiv' . $nanogongfiltercount . '" style="position:absolute;top:-40px;left:-130px;z-index:100;visibility:hidden;"><applet id="nanogongfilter' . $nanogongfiltercount . '" archive="' . $CFG->wwwroot . '/filter/nanogong/nanogong.jar" code="gong.NanoGong" width="130" height="40"><param name="ShowTime" value="true" /><param name="ShowAudioLevel" value="false" /><param name="ShowRecordButton" value="false" /></applet></div>' . $text;
    }

}

?>
