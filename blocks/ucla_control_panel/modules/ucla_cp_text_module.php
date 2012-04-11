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

/* This module is meant to be used in order to display pure text (no links) 
 * within the control panel. The item_name of the module represents the pure text.
 */
class ucla_cp_text_module extends ucla_cp_module{
    
    function __construct($item_name=null,  $tags=null,
            $capability=null, $options=null) {

      parent::__construct($item_name, null, $tags, $capability, $options);
      //This function does not used localized strings, so default false makes more sense.
      $this->options['post'] = false;
    }    
    
    //Workaround so the renderer recognizes this as text
    function is_tag() {
        return false;
    }
}
