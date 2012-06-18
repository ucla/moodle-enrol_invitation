<?php

require_once(dirname(__FILE__) . '/registrar_query.base.php');

abstract class registrar_stored_procedure extends registrar_query {
    /**
     *  Returns the array describing the parameters are needed for the
     *  stored procedure.
     *  @example array('term', 'srs')
     *  @return array
     **/
    abstract function get_query_params();

    /**
     *  Returns the stored procedure itself.
     *  @return string
     **/
    function get_stored_procedure() {
        $classname = get_class($this);
        if ($classname == 'registrar_stored_procedure') {
            throw new registrar_stored_procedure_exception('bad-oo');
        }

        return str_replace('registrar_', '', get_class($this));
    }

    /**
     *  Try not to use this function. The caller should correctly index
     *  the array that is passed in.
     **/
    function unindexed_key_translate($args) {
        $spargs = array();
        foreach ($this->get_query_params() as $key => $strkey) {
            if (isset($args[$strkey])) {
                $newarg = $args[$strkey];
            } else if (isset($args[$key])) {
                $newarg = $args[$key];
            } else {
                debugging('badly indexed parameters');
                return false;
            }

            $spargs[$strkey] = $newarg;
        }

        return $spargs;
    }

    function remote_call_generate($args) {
        $storedproc = $this->get_stored_procedure();
      
        $spargs = $this->unindexed_key_translate($args);
        if (!$spargs) {
            return false;
        }

        foreach ($spargs as $strkey => $val) {
            try {
                if (!ucla_validator($strkey, $val)) {
                    return false;
                }
            } catch (moodle_exception $e) {
                // Not a registered validation
            }
        }

        $procsql = "EXECUTE $storedproc ";
        if (!empty($spargs)) {
            $procsql .= "'" . implode("', '", $spargs) . "'";
        }

        return $procsql;
    }

    /**
     *  By default, most stored procedures don't need validation.
     **/
    function validate($new, $old) {
        return true;
    }
} 
