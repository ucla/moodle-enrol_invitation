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
     *  The block that is associated with this class.
     **/
    const PARENT_BLOCK = 'block_ucla_control_panel';

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
     *  @var moodle_url
     **/
    var $action;

    /**
     *  @var mixed
     **/
    var $options;

    /** 
     *  @var string
     *  This should not be used when declaring or creating a module.
     *  This is purely a prototyping tool, and will automatically
     *  be populated.
     **/
    var $associated_block = null;

    /**
     *  Currently cannot do nested categories.
     *
     *  This will construct your object. 
     *  If your action is simple (such as a link), you do not need to
     *  do additional programming and just instantiate a ucla_cp_module.
     *  
     **/
    function __construct($item_name=null, $action=null, $tags=null,
            $capability=null, $options=null) {

        if ($item_name != null) {
            $this->item_name = $item_name;
        } else {
            // Available PHP 5.1.0+
            if (!class_parents($this)) {
                throw new moodle_exception('You must specify an item '
                    . 'name if you are using the base ucla_cp_module '
                    . 'class!');
            }

            $this->item_name = $this->figure_name();
        }

        if ($tags == null) {
            $this->tags = $this->autotag();
        } else {
            $this->tags = $tags;
        }

        if ($action != null) {
            $this->action = $action;
        }

        if ($capability == null) {
            $this->required_cap = $this->autocap();
        } else {
            $this->required_cap = $capability;
        }

        if ($options === null) {
            $this->options = $this->autoopts();
        } else {
            $this->options = $options;
        }
    }

    /**
     *  This function is automatically called to generate some kind of name
     *  if you want this class to be automatically named to the class name.
     **/
    function figure_name() {
        $orig_name = get_class($this);

        $parents = class_parents($this);

        foreach ($parents as $parent) {
            if (method_exists($parent, 'figure_name')) {
                return substr($orig_name, strlen($parent) + 1);
            } 
        }

        return $orig_name;
    }

    /**
     *  This is the default function that is used to check if the module
     *  should be displayed or not.
     *  Currently only supports one capability per module.
     **/
    function validate($course, $context) {
        $hc = true;

        if ($this->required_cap != null) {        
            $hc = has_capability($this->required_cap, $context);
        } 
        
        return $hc;
    }

    /**
     *  Simple wrapper function.
     **/
    function is_tag() {
        return empty($this->action);
    }

    
    /**
     *  This function can be overwritten to allow a child class to
     *  define their tags in code instead when instantiated.
     **/
    function autotag() {
        return null;
    }

    /**
     *  This is similar to {@see autotag}, except for the capability
     *  that is used to check for validity.
     **/
    function autocap() {
        return null;
    }

    /**
     *  This is similar to {@see autotag}, except for the options
     *  that is set.
     **/
    function autoopts() {
        return array();
    }

    /**
     *  Simple wrapper function.
     **/
    function get_action() {
        return $this->action;
    }

    /**
     *  Simple function for differentiating different instances
     *  of the same type of control panel module.
     **/
    function get_key() {
        return $this->item_name;
    }

    /**
     *  This is a wrapper to get a set option from the current class.
     *  Options, unfortunately, are known by the viewer.
     **/
    function get_opt($option) {
        if (!isset($this->options[$option])) {
            return null;
        }

        return $this->options[$option];
    }

    /**
     *  This is to set options.
     **/
    function set_opt($option, $value) {
        $this->options[$option] = $value;
    }

    /**
     *  Returns the relevant block of th emodule.
     *  This function should not overwritten, nor should be changed.
     **/
    function associated_block() {
        if ($this->associated_block == null) {
            return self::PARENT_BLOCK;
        }
        return $this->associated_block;
    }

    /**
     *  Magic loader function.
     **/
    static function load($name) {
        $module_path = dirname(__FILE__) . '/modules/';
        if (!file_exists($module_path)) {
            debugging(get_string('badsetup', self::PARENT_BLOCK));
            return false;
        }

        $file_path = $module_path . $name . '.php';

        if (!file_exists($file_path)) {
            debugging(get_string('badmodule', self::PARENT_BLOCK, 
                $name));
            return false;
        }

        require_once($file_path);
        return true;
    }

    // Convenience wrapper to build a particular cp_module 
    static function build($args) {
        $params = get_class_vars(get_class());

        if (is_object($args)) {
            $args = get_object_vars($args);
        }

        $callparams = array();
        foreach ($params as $param => $noval) {
            if (isset($args[$param])) {
                $callparams[$param] = $args[$param];
            } else {
                $callparams[$param] = null;
            }
        }

        extract($callparams);

        return new ucla_cp_module($item_name, $action, $tags, 
            $required_cap, $options);
    }
}
