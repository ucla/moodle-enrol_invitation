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
 * Control Panel Module class.
 *
 * @package    block
 * @subpackage ucla_control_panel
 * @copyright  UC Regents
 */
class ucla_cp_module {
    /** 
     *  This is the item identifier. It should relate to the lang file. 
     *
     *  @var string
     **/
    var $item_name;

    /**
     *  @var boolean
     *  This determines which group the object is in.
     **/
    var $tags;

    /**
     *  @var string
     **/
    var $required_cap;

    /**
     *  Currently cannot do nested categories...
     **/
    function __construct($item_name=null, $tags=null, $capability=null) {
        if ($item_name != null) {
            $this->item_name = $item_name;
        } else {
            // Available PHP 5.1.0+
            if (!class_parents($this)) {
                throw new moodle_exception('You must specify an item '
                    . 'name if you are using the base ucla_cp_module '
                    . 'class!');
            }

            $this->item_name = $this->figure_name(get_class($this));
        }

        if ($tags == null) {
            $this->tags = $this->autotag();
        } else {
            $this->tags = $tags;
        }

        if ($capability == null) {
            $this->required_cap = $this->autocap();
        } else {
            $this->required_cap = $capability;
        }
    }

    function figure_name($orig_name) {
        foreach ($parents as $parent) {
            if (method_exists($parent, 'figure_name')) {
                return substr($orig_name, strlen($parent));
            } 
        }

        return $orig_name;
    }

    function validate($course, $context) {
        $hc = true;

        if ($this->required_cap != null) {        
            $hc = has_capability($this->required_cap, $context);
        } 
        
        return $hc;
    }

    function autotag() {
        return null;
    }

    function autocap() {
        return null;
    }

    function get_action() {
        return null;
    }
}
