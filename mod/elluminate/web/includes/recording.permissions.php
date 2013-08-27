<?php
$recordingPermissions = $ELLUMINATE_CONTAINER['recordingPermissions'];
$recordingPermissions->setContext($context);
$recordingPermissions->userid = $USER->id;
$recordingPermissions->cm = $cm;
$recordingPermissions->pageSession = $pageSession;
