<?php

require_once(dirname(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))))) . '/config.php');
require_once($CFG->dirroot."/mod/voiceauthoring/lib.php");

$id = optional_param('id', SITEID, PARAM_INT);
require_course_login($id);
@header('Content-Type: text/html; charset=utf-8');
$vtAction=new vtAction($USER->email);
$vtUser = new VtUser(NULL);
$vtUserRigths = new VtRights(NULL);

$vtUserRigths->setProfile ('moodle.recorder.instructor');
$type="record";

$dbResource = $DB->get_record("voiceauthoring_resources", array("course" => 0));

if($dbResource === false)// the resource is not yet created
{
  $result = $vtAction->createRecorder("Voice Authoring associated to the course 0");//create the resource on the vt
  if( $result != null && $result->error != "error")
  {
    if( storeResource($result->getRid(),0,"recorder","wysiwig_recorder") )
    {
         $rid = $result->getRid();
    }
  }
  $mid=0;
}
else
{
    $rid = $dbResource->rid;
    $mid = $dbResource->mid+1;
}

$dbResource->mid = $mid;

$DB->update_record("voiceauthoring_resources",$dbResource);
$resource = voicetools_api_get_resource($rid);

if( $resource )
{
    $message=new vtMessage(null);
    $message->setMid($mid);

    $result = $vtAction->getVtSession($resource,$vtUser,$vtUserRigths,$message);

    if($result === false)
    {
        $error = "There is a problem to display the voice authoring";
    }
    $version = '2011031102';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8"/>
<title><?php print_string("modulename","voiceauthoring");?></title>
<script type="text/javascript">var wwwroot="<?php echo $CFG->wwwroot; ?>";</script>
<script type="text/javascript" src="../../tiny_mce_popup.js?v=3.3.9.2"></script>
<script type="text/javascript" src="js/wimba.js?v=<?php echo $version; ?>"></script>
<script type="text/javascript" src="../../utils/validate.js"></script>
<script type="text/javascript" src="../../utils/form_utils.js"></script>
<link href="css/wimba.css" rel="stylesheet" type="text/css" />
</head>
<body>
<form onsubmit="insertWimba();return false;" action="#">
<input type="hidden" id="f_mid" name="f_mid" value="<?php echo $mid?>">
<input type="hidden" id="f_rid" name="f_rid" value="<?php echo $rid?>">
<div class="clearfix">
    <span class="wimba_boxTopTitle"> Please record a message:</span>
    <p style="padding-left:15px;" id="applet_container"></p>
    <script type="text/javascript">
      this.focus();
    </script>
    <script type="text/javascript" src="<?php echo $CFG->voicetools_servername; ?>/ve/record.js"></script>
    <script type="text/javascript">
        var w_p = new Object();
        w_p.nid="<?php echo $result->getNid();?>";
        w_p.language = "en";
        w_p.autostart = "true";
        tinyMCEPopup._onDOMLoaded = function() {};

        if (window.w_ve_record_tag) w_ve_record_tag(w_p, document.getElementById("applet_container"));
        else document.write("Applet should be there, but the Blackboard Collaborate Voice server is down");
    </script>
</div>

<div class="mceActionPanel">
    <input type="submit" id="insert" name="insert" value="Insert" />
    <input type="button" id="cancel" name="cancel" value="Cancel" onclick="tinyMCEPopup.close();" />
</div>
</form>

</body>
</html>
