<?php
/// This file allows to manage the default behavior of the display formats

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = required_param('id', PARAM_INT);
$mode = optional_param('mode', '', PARAM_ALPHANUMEXT);

$url = new moodle_url('/mod/qanda/formats.php', array('id' => $id));
if ($mode !== '') {
    $url->param('mode', $mode);
}
$PAGE->set_url($url);

admin_externalpage_setup('managemodules'); // this is hacky, tehre should be a special hidden page for it

if (!$displayformat = $DB->get_record("qanda_formats", array("id" => $id))) {
    print_error('invalidqandaformat', 'qanda');
}

$form = data_submitted();
if ($mode == 'visible' and confirm_sesskey()) {
    if ($displayformat) {
        if ($displayformat->visible) {
            $displayformat->visible = 0;
        } else {
            $displayformat->visible = 1;
        }
        $DB->update_record("qanda_formats", $displayformat);
    }
    redirect("$CFG->wwwroot/$CFG->admin/settings.php?section=modsettingqanda#qanda_formats_header");
    die;
} elseif ($mode == 'edit' and $form and confirm_sesskey()) {

    $displayformat->popupformatname = $form->popupformatname;
    $displayformat->sortkey = $form->sortkey;
    $displayformat->sortorder = $form->sortorder;

    $DB->update_record("qanda_formats", $displayformat);
    redirect("$CFG->wwwroot/$CFG->admin/settings.php?section=modsettingqanda#qanda_formats_header");
    die;
}

$strmodulename = get_string("modulename", "qanda");
$strdisplayformats = get_string("displayformats", "qanda");

echo $OUTPUT->header();

echo $OUTPUT->heading($strmodulename . ': ' . get_string("displayformats", "qanda"));

echo $OUTPUT->box(get_string("configwarning", 'admin'), "generalbox box-align-center boxwidthnormal");
echo "<br />";

$yes = get_string("yes");
$no = get_string("no");

echo '<form method="post" action="formats.php" id="form">';
echo '<table width="90%" align="center" class="generalbox">';
?>
<tr>
    <td colspan="3" align="center"><strong>
            <?php echo get_string('displayformat' . $displayformat->name, 'qanda'); ?>
        </strong></td>
</tr>
<tr valign="top">
    <td align="right" width="20%"><?php echo html_writer::label(get_string('popupformat', 'qanda'), 'menupopupformatname'); ?></td>
    <td>
        <?php
        //get and update available formats
        $recformats = qanda_get_available_formats();

        $formats = array();

        //Take names
        foreach ($recformats as $format) {
            $formats[$format->name] = get_string("displayformat$format->name", "qanda");
        }
        //Sort it
        asort($formats);

        echo html_writer::select($formats, 'popupformatname', $displayformat->popupformatname, false);
        ?>
    </td>
    <td width="60%">
        <?php print_string("cnfrelatedview", "qanda") ?><br /><br />
    </td>
</tr>




<tr valign="top">
    <td align="right" width="20%"><label for="sortkey"><?php print_string('defaultsortkey', 'qanda'); ?></label></td>
    <td>
        <select size="1" id="sortkey" name="sortkey">
            <?php
            $sfname = '';
            $slname = '';
            $supdate = '';
            $screation = '';
            switch (strtolower($displayformat->sortkey)) {
                case 'creation':
                    $screation = ' selected="selected" ';
                    break;

                case 'update':
                    $supdate = ' selected="selected" ';
                    break;
            }
            ?>
            <option value="CREATION" <?php p($screation) ?>><?php p(get_string("sortbycreation", "qanda")) ?></option>
            <option value="UPDATE" <?php p($supdate) ?>><?php p(get_string("sortbylastupdate", "qanda")) ?></option>
        </select>
    </td>
    <td width="60%">
        <?php print_string("cnfsortkey", "qanda") ?><br /><br />
    </td>
</tr>
<tr valign="top">
    <td align="right" width="20%"><label for="sortorder"><?php print_string('defaultsortorder', 'qanda'); ?></label></td>
    <td>
        <select size="1" id="sortorder" name="sortorder">
            <?php
            $sasc = '';
            $sdesc = '';
            switch (strtolower($displayformat->sortorder)) {
                case 'asc':
                    $sasc = ' selected="selected" ';
                    break;

                case 'desc':
                    $sdesc = ' selected="selected" ';
                    break;
            }
            ?>
            <option value="asc" <?php p($sasc) ?>><?php p(get_string("ascending", "qanda")) ?></option>
            <option value="desc" <?php p($sdesc) ?>><?php p(get_string("descending", "qanda")) ?></option>
        </select>
    </td>
    <td width="60%">
        <?php print_string("cnfsortorder", "qanda") ?><br /><br />
    </td>
</tr>


<tr>
    <td colspan="3" align="center">
        <input type="submit" value="<?php print_string("savechanges") ?>" /></td>
</tr>
<input type="hidden" name="id"    value="<?php p($id) ?>" />
<input type="hidden" name="sesskey" value="<?php echo sesskey() ?>" />
<input type="hidden" name="mode"    value="edit" />
<?php
echo '</table></form>';

echo $OUTPUT->footer();
?>