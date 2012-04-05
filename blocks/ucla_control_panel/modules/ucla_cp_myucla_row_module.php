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

/* This module is meant to be used in order to display an individual class's 
 * myucla links.
 */
class ucla_cp_myucla_row_module extends ucla_cp_module {

    /**
     *  @var elements
     * This is an array of ucla_cp_modules that contains the elements of this row.
     */
    var $elements;

    function __construct($tags = null, $capability = null, $options = null) {
        parent::__construct(null, null, $tags, $capability, $options);
    }

    //Adds element to the end of the row.
    function add_element($element) {
        $this->elements[] = $element;
    }

    //Returns an array containing the elements in the row
    function get_elements() {
        return $this->elements;
    }

    //Workaround so that it's not being treated as a tag
    function is_tag() {
        return false;
    }

}
