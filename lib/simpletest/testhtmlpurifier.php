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
 * Tests our HTMLPurifier hacks
 *
 * @package    core
 * @subpackage lib
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

class htmlpurifier_test extends UnitTestCase {

    function test_moodle_tags() {
        $text = '<nolink>xxx<em>xx</em><div>xxx</div></nolink>';
        $this->assertIdentical($text, purify_html($text));

        $text = '<tex>xxxxxx</tex>';
        $this->assertIdentical($text, purify_html($text));

        $text = '<algebra>xxxxxx</algebra>';
        $this->assertIdentical($text, purify_html($text));

        $text = '<span lang="de_DU" class="multilang">asas</span>';
        $this->assertIdentical($text, purify_html($text));

        $text = '<lang lang="de_DU">xxxxxx</lang>';
        $this->assertIdentical($text, purify_html($text));

        $text = "\n\raa\rsss\nsss\r";
        $this->assertIdentical($text, purify_html($text));
    }

    function test_tidy() {
        $text = "<p>xx";
        $this->assertIdentical('<p>xx</p>', purify_html($text));

        $text = "<P>xx</P>";
        $this->assertIdentical('<p>xx</p>', purify_html($text));

        $text = "xx<br>";
        $this->assertIdentical('xx<br />', purify_html($text));
    }

    function test_cleaning_nastiness() {
        $text = "x<SCRIPT>alert('XSS')</SCRIPT>x";
        $this->assertIdentical('xx', purify_html($text));

        $text = '<DIV STYLE="background-image:url(javascript:alert(\'XSS\'))">xx</DIV>';
        $this->assertIdentical('<div>xx</div>', purify_html($text));

        $text = '<DIV STYLE="width:expression(alert(\'XSS\'));">xx</DIV>';
        $this->assertIdentical('<div>xx</div>', purify_html($text));

        $text = 'x<IFRAME SRC="javascript:alert(\'XSS\');"></IFRAME>x';
        $this->assertIdentical('xx', purify_html($text));

        $text = 'x<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>x';
        $this->assertIdentical('xx', purify_html($text));

        $text = 'x<EMBED SRC="http://ha.ckers.org/xss.swf" AllowScriptAccess="always"></EMBED>x';
        $this->assertIdentical('xx', purify_html($text));

        $text = 'x<form></form>x';
        $this->assertIdentical('xx', purify_html($text));
    }
}


