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

require_once(dirname(__FILE__).'/registrar_query.base.php');

class registrar_ccle_getclasses extends registrar_query {
    var $unindexed_key_translate = array('term' => 0, 'srs' => 1);

    function validate($new, $old) {
        
        $tests = array('srs', 'term');
        foreach ($tests as $criteria) {
            if (!isset($new[$criteria])
                    && $new[$criteria] != $old[$criteria]) {
                return false;
            }
        }
               
        return true;
    }

    function remote_call_generate($args) {
        $k = null;

        $m = 0;
        $ls = array('term', 'srs');

        foreach ($ls as $l) {
            if (isset($args[$m])) {
                $k = $m;
            } else if (isset($args[$l])) {
                $k = $l;
            }

            if (ucla_validator($l, $args[$k])) {
                ${$l} = $args[$k];
            } else {
                return false;
            }

            $m++;
        }

        return "EXECUTE ccle_getClasses '$term', '$srs'";
    }
}
