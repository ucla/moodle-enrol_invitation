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

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ccle_courseinstructorsget extends registrar_query {
    var $unindexed_key_translate = array('term' => 0, 'srs' => 1);

    function validate($new, $old) {
        if (!isset($new['srs']) && $new['srs'] != $old['srs']) {
            return false;
        }

        if (empty($new['ucla_id'])) {
            return false;
        }

        return true;
    }

    function remote_call_generate($args) {
        if (isset($args[0])) {
            $term = $args[0];
        } else {
            $term = $args['term'];
        }

        if (isset($args[1])) {
            $srs = $args[1];
        } else {
            $srs = $args['srs'];
        }

        if (!ucla_validator('term', $term) || !ucla_validator('srs', $srs)) {
            return false;
        }  

        return "EXECUTE ccle_courseinstructorsget '$term', '$srs'";
    }
}
