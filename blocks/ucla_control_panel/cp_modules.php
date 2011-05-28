<?php

require_once(dirname(__FILE__) . '/ucla_cp_module.php');

$modules[] = new ucla_cp_module('ucla_cp_mod_common');
$modules[] = new ucla_cp_module('add_file', array('ucla_cp_mod_common'));
