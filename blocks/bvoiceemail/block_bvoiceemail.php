<?PHP 

require_once($CFG->libdir.'/datalib.php');

class block_bvoiceemail extends block_base {

  function init() {
    $this->title = "Voice E-Mail";
    $this->version = 2011011800;
  }

  function applicable_formats() {
      return array('site-index' => false,                 'course-view' => true               );

  }

  function get_content() {

    global $CFG, $USER, $COURSE, $DB;
    if ($this->content !== NULL) {
      return $this->content;
    }

    $this->content = new stdClass;
    $this->content->text = '';
    $this->content->footer = '';

    $block = $DB->get_record("block_instances", array('id' => $this->instance->id));
    $config_block = unserialize(base64_decode($block->configdata));

    $this->page->requires->css('/mod/voiceemail/css/StyleSheet.css');
    $this->page->requires->js('/blocks/bvoiceemail/block.js');

    $content = html_writer::start_tag('div', array('class' => 'vmail_block'));
    if(!isset($config_block) || (
        isset($config_block->all_users_enrolled ) && $config_block->all_users_enrolled == "0" && 
        isset($config_block->instructor ) && $config_block->instructor == "0" &&
        isset($config_block->student ) && $config_block->student == "0" &&
        isset($config_block->recipient) && $config_block->recipient == "0" )
        ){
        $content .= html_writer::tag('span', get_string('nothing_selected', 'voiceemail'), array('class' => 'TextMinor'));
    } else {
        $content .= html_writer::tag('span', get_string('block_send_sentence', 'voiceemail'), array('class' => 'TextMinor'));
    }
    $pstyle = array('style' => 'padding-left:15px;margin-bottom:0 !important;padding-top:10px;');
    if(empty($config_block) || (isset($config_block->all_users_enrolled) && $config_block->all_users_enrolled == "1")) {
        $content .= html_writer::start_tag('p', $pstyle);
        $content .= html_writer::tag('a', get_string('block_send_vmail_all', 'voiceemail'),
                                    array('href' => '#', 'class' => 'TextRegular', 'onclick' => "openWimbaPopup('../mod/voiceemail/manageActionBlock.php', 'all', {$COURSE->id}, {$this->instance->id})"));
        $content .= html_writer::end_tag('p');
    }
    if(empty($config_block) || (isset($config_block->instructor) && $config_block->instructor == "1")) {
        $content .= html_writer::start_tag('p', $pstyle);
        $content .= html_writer::tag('a', get_string('block_send_vmail_instructors', 'voiceemail'),
                                    array('href' => '#', 'class' => 'TextRegular', 'onclick' => "openWimbaPopup('../mod/voiceemail/manageActionBlock.php', 'instructors', {$COURSE->id}, {$this->instance->id})"));
        $content .= html_writer::end_tag('p');
    }
    if(empty($config_block) || (isset($config_block->student) && $config_block->student == "1")) {
        $content .= html_writer::start_tag('p', $pstyle);
        $content .= html_writer::tag('a', get_string('block_send_vmail_students', 'voiceemail'),
                                    array('href' => '#', 'class' => 'TextRegular', 'onclick' => "openWimbaPopup('../mod/voiceemail/manageActionBlock.php', 'students', {$COURSE->id}, {$this->instance->id})"));
        $content .= html_writer::end_tag('p');
    }
    if(empty($config_block) || (isset($config_block->recipient) && $config_block->recipient == "1")) {
        $content .= html_writer::start_tag('p', $pstyle);
        $content .= html_writer::tag('a', get_string('block_send_vmail_selected', 'voiceemail'),
                                    array('href' => '#', 'class' => 'TextRegular', 'onclick' => "openWimbaPopup('../mod/voiceemail/listAvailableRecipients.php', 'selected', {$COURSE->id}, {$this->instance->id})"));
        $content .= html_writer::end_tag('p');
    }

    $content .= html_writer::end_tag('div');
    $this->content->text = $content;

    return $this->content;
  }
  
  function instance_allow_multiple() {
    return true;
  }


  function instance_allow_config() {
    return true;
  }
  function has_config() {
    return true;
  }
 
}

