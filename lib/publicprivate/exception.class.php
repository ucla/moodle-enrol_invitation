<?php

if(method_exists('Exception', 'getPrevious'))
{
    include_once($CFG->libdir.'/publicprivate/exception.base.class.php');
}
else
{
    include_once($CFG->libdir.'/publicprivate/exception.compat.class.php');
}

?>
