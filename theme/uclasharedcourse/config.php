<?php
/**
 * Configuration for UCLA's Shared Server theme.
 *
 * For full information about creating Moodle themes, see:
 *  http://docs.moodle.org/en/Development:Themes_2.0
 *
 * @copyright 2010 UC Regents
 */

$THEME->name = 'uclasharedcourse';
//$tn = 'theme_' . $THEME->name;

$THEME->parents = array(
    'uclashared',
    'base',
);

//$THEME->sheets = array(
//    'admin',    // custom admin style changes
//    'base',
//    'core',     // custom core stlye changes
//    'general',
//    'responsive',
//);

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->enable_dock = true;
