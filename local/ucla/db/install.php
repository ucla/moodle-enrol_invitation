<?php

require_once(dirname(__FILE__) . '/../locallib.php');

function xmldb_local_ucla_install() {
    // Do stuff eventually
    $result = ucla_verify_configuration_setup();

    // Maybe add some tables we need?
    return $result;
}

function xmldb_local_ucla_install_recovery() {
    // Do stuff eventually

    return true;
}

// EOF
