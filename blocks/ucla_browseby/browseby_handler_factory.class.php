<?php

/**
 *  Loads all the handlers and stuff.
 **/
class browseby_handler_factory {
    var $loaded = false;

    /**
     *  One place to hold a list of types that should be available,
     *  if possible, make it dynamic?
     **/
    static function get_available_types() {
        $custom = get_config('block_ucla_browseby', 'available_types');
        if ($custom && is_array($custom)) {
            return $custom;
        }

        return array(
            'subjarea', 'division', 'instructor', 'collab'
        );
    }

    function __construct() {
        $this->load_types();
    }

    function get_type_handler($type) {
        $hcn = $type . '_handler';

        if (class_exists($hcn)) {
            $handler = new $hcn();
        } else {
            return false;
        }

        return $handler;
    }

    function load_types() {
        if ($this->loaded)  {
            return true;
        }

        $handlerpath = dirname(__FILE__) . '/handlers/';
        if (file_exists($handlerpath)) {
            $files = glob($handlerpath . '/*.class.php');

            foreach ($files as $file) {
                require_once($file);
            }
        } else {
            debugging('could not load handlers');
        }

        $this->loaded = true;
        return true;
    }
}
