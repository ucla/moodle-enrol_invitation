<?php
define('NO_MOODLE_COOKIES', false);

require('../../../../../config.php');
require_once($CFG->dirroot . '/filter/poodll/poodllresourcelib.php');
require_once($CFG->dirroot.'/repository/lib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/lib/editor/tinymce/plugins/cclevoice/cclevoice.php');

if (isset($SESSION->lang)) {
    // Language is set via page url param.
    $lang = $SESSION->lang;
} else {
    $lang = 'en';
}

require_login();  // CONTEXT_SYSTEM level
$editor = get_texteditor('tinymce');
$plugin = $editor->get_plugin('cclevoice');
$itemid = optional_param('itemid', '', PARAM_TEXT); 
@header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php print_string('title', 'tinymce_cclevoice')?></title>
<script type="text/javascript" src="<?php echo $editor->get_tinymce_base_url(); ?>tiny_mce_popup.js"></script>
<script type="text/javascript" src="<?php echo $plugin->get_tinymce_file_url('js/cclevoice.js'); ?>"></script>

</head>
<body>
<div style="text-align: center;">
<?php
echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/poodll/flash/embed-compressed.js\"></script> ";
$usercontextid=get_context_instance(CONTEXT_USER, $USER->id)->id;
// load the recorder        
echo fetchMP3RecorderForSubmission('myfilename', $usercontextid ,'user','draft',$itemid);

?>
</div>
<form>
   <div>
      <input id="myfilename" type="hidden" name="myfilename" value="">
      <input type="hidden" name="contextid" value= "<?php echo $usercontextid;?>" id="context_id">
      <input type="hidden" name= "wwwroot" value="<?php echo $CFG->wwwroot;?>" id="wwwroot">
      <p id="messageAlert">After you have finished recording, press Insert.</p>
      <input type="button" id="insert" name="insert" value="{#insert}" onclick="cclevoiceDialog.insert(<?php echo $USER->id; ?>);" />
      <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
      <input type="hidden" name="action" value="download">
   </div>
</form>
</body>
</html>
