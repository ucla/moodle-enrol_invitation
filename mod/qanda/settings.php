<?php

//Utilized in Settings and Upgrade Settings

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/qanda/lib.php');

    $settings->add(new admin_setting_heading('qanda_normal_header', get_string('qandaleveldefaultsettings', 'qanda'), ''));

    $settings->add(new admin_setting_configtext('qanda_entbypage', get_string('entbypage', 'qanda'),
                    get_string('entbypage', 'qanda'), 10, PARAM_INT));

    /*
      $settings->add(new admin_setting_configcheckbox('qanda_dupentries', get_string('allowduplicatedentries', 'qanda'),
      get_string('cnfallowdupentries', 'qanda'), 0));

      $settings->add(new admin_setting_configcheckbox('qanda_allowcomments', get_string('allowcomments', 'qanda'),
      get_string('cnfallowcomments', 'qanda'), 0));

      $settings->add(new admin_setting_configcheckbox('qanda_linkbydefault', get_string('usedynalink', 'qanda'),
      get_string('cnflinkqandas', 'qanda'), 1));

      $settings->add(new admin_setting_configcheckbox('qanda_defaultapproval', get_string('defaultapproval', 'qanda'),
      get_string('cnfapprovalstatus', 'qanda'), 1));


      if (empty($CFG->enablerssfeeds)) {
      $options = array(0 => get_string('rssglobaldisabled', 'admin'));
      $str = get_string('configenablerssfeeds', 'qanda').'<br />'.get_string('configenablerssfeedsdisabled2', 'admin');

      } else {
      $options = array(0=>get_string('no'), 1=>get_string('yes'));
      $str = get_string('configenablerssfeeds', 'qanda');
      }
      $settings->add(new admin_setting_configselect('qanda_enablerssfeeds', get_string('enablerssfeeds', 'admin'),
      $str, 0, $options));


      $settings->add(new admin_setting_configcheckbox('qanda_linkentries', get_string('usedynalink', 'qanda'),
      get_string('cnflinkentry', 'qanda'), 0));

      $settings->add(new admin_setting_configcheckbox('qanda_casesensitive', get_string('casesensitive', 'qanda'),
      get_string('cnfcasesensitive', 'qanda'), 0));

      $settings->add(new admin_setting_configcheckbox('qanda_fullmatch', get_string('fullmatch', 'qanda'),
      get_string('cnffullmatch', 'qanda'), 0));
     */


    $settings->add(new admin_setting_heading('qanda_levdev_header', get_string('entryleveldefaultsettings', 'qanda'), ''));




    //Update and get available formats
    $recformats = qanda_get_available_formats();
    $formats = array();
    //Take names
    foreach ($recformats as $format) {
        $formats[$format->id] = get_string("displayformat$format->name", "qanda");
    }
    asort($formats);

    $str = '<table>';
    foreach ($formats as $formatid => $formatname) {
        $recformat = $DB->get_record('qanda_formats', array('id' => $formatid));
        $str .= '<tr>';
        $str .= '<td>' . $formatname . '</td>';
        $eicon = "<a title=\"" . get_string("edit") . "\" href=\"$CFG->wwwroot/mod/qanda/formats.php?id=$formatid&amp;mode=edit\"><img class=\"iconsmall\" src=\"" . $OUTPUT->pix_url('t/edit') . "\" alt=\"" . get_string("edit") . "\" /></a>";
        if ($recformat->visible) {
            $vtitle = get_string("hide");
            $vicon = "t/hide";
        } else {
            $vtitle = get_string("show");
            $vicon = "t/show";
        }
        $vicon = "<a title=\"" . $vtitle . "\" href=\"$CFG->wwwroot/mod/qanda/formats.php?id=$formatid&amp;mode=visible&amp;sesskey=" . sesskey() . "\"><img class=\"iconsmall\" src=\"" . $OUTPUT->pix_url($vicon) . "\" alt=\"$vtitle\" /></a>";

        $str .= '<td align="center">' . $eicon . '&nbsp;&nbsp;' . $vicon . '</td>';
        $str .= '</tr>';
    }
    $str .= '</table>';

    $settings->add(new admin_setting_heading('qanda_formats_header', get_string('displayformatssetup', 'qanda'), $str));
}
