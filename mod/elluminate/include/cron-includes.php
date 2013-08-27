<?php
$elluminateRoot = $CFG->dirroot . '/mod/elluminate/';

//Cron
require_once($elluminateRoot . "elluminate/cron/action.php");
require_once($elluminateRoot . "elluminate/cron/utils.php");
require_once($elluminateRoot . "elluminate/cron/runner.php");

//Cron Actions
require_once($elluminateRoot . "elluminate/cron/recordingaddaction.php");
require_once($elluminateRoot . "elluminate/cron/recordingupdateaction.php");
require_once($elluminateRoot . "elluminate/cron/licensecheckaction.php");

//ELM specific cron
require_once($elluminateRoot . "elluminate/cron/elmrecordingaddaction.php");
