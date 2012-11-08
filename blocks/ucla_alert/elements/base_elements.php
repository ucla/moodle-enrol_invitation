<?php


class ucla_alert_banner extends ucla_alert {
    
    public function __construct($courseid) {
        parent::__construct($courseid);
    }

    protected function header() {
        global $DB;
        
        $header = $DB->get_record(self::DB_TABLE,
                array('courseid' => 1, 'entity' => self::ENTITY_HEADER, 'visible' => 1));
        $o = json_decode($header->json);

        // 
        $render = new alert_html_header_box($o->item);
        $render->add_class('alert-header-' . $o->color);
        $render->add_class('banner-alert-header');

        $section = $DB->get_record(self::DB_TABLE,
        array('courseid' => 1, 'entity' => self::ENTITY_HEADER_SECTION, 'visible' => 1));

        $sectionbox = new alert_html_section(json_decode($section->json));
        $sectionbox->add_class('banner-alert-section');
//        $render = new alert_html_header(
//                json_decode($header->json), 
//                json_decode($section->json)
//            );

        return array($render, $sectionbox);            
    }
    
    public function render() {

        // Get the header
        $header = $this->header();
        
        // Hold content here
        $content = new alert_html_box_content($header);
        $content->add_class('banner-ucla-alert-content');
        
        // Create the general banner box
        $bannerbox = new alert_html_box_content($content);
        $bannerbox->add_class('banner-ucla-alert');
        $bannerbox->add_class('block-ucla-alert');

        return $bannerbox->render();
    }
    
    public function alert() {
        return '';
    }
    
    /**
     * Load a course alert banner
     * 
     * @todo implement
     * 
     * @global type $DB
     * @param type $courseid
     * @return null|\ucla_alert_banner 
     */
    public static function load($courseid) {
        global $DB;
        // If there's something to show, find it...
        
        return null;
        
        $record = $DB->get_record(self::DB_TABLE, array(''));
        
        if($record) {
            return new ucla_alert_banner($courseid);
        } else {
            return null;
        }
    }
}

/**
 * An alert block renderer.
 * 
 * This renders the head and body of the sitewide and course alert block
 */
class ucla_alert_block extends ucla_alert {

    public function __construct($courseid) {
        parent::__construct($courseid);
    }

    /**
     * Render block header
     * 
     * @global type $DB
     * @return type 
     */
    protected function header() {
        
        if($this->is_site) {
            // Site block gets special headers
            global $DB;
            
            $header = $DB->get_record(self::DB_TABLE,
                array('courseid' => 1, 'entity' => self::ENTITY_HEADER, 'visible' => 1));
            $section = $DB->get_record(self::DB_TABLE,
                    array('courseid' => 1, 'entity' => self::ENTITY_HEADER_SECTION, 'visible' => 1));

            $render = new alert_html_header(
                    json_decode($header->json), 
                    json_decode($section->json)
                );

            $buffer = $render->render();
        } else {
            // Courses get simple alert block header
            $h = new alert_html_course_box('Course alerts');
            $buffer = $h->render();

        }
        
        return $buffer;
    }

    /**
     * Return rendered body of the block
     * 
     * @return string
     */
    protected function body() {
        global $DB;
        
        $buffer = '';
        
        // Grab sections
        $sections = $DB->get_records(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SECTION, 'visible' => 1));
        
        foreach($sections as $section) {
            switch($section->render) {
                case self::RENDER_CACHE:
                    // If content is cached, avoid overhead of rendering
                    $buffer .= $section->html;
                    break;
                case self::RENDER_REFRESH:
                    // Render content and then cache it
                    $html = new alert_html_section(json_decode($section->json));
                    $buffer .= $html->render();

                    $section->html = $html->render();
                    $section->render = self::RENDER_CACHE;

                    // Cache it
                    $DB->update_record(self::DB_TABLE, $section);
                    break;
            }
        }
        
        // Grab individual items
        $items = $DB->get_records(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_ITEM, 'visible' => 1));
        
        foreach($items as $item) {
            switch($item->render) {
                case self::RENDER_EXPIRE:
                    $jobj = json_decode($item->json);
                    $now = time();
                    
                    // Remove item that's expired
                    if($jobj->expires < $now){
                        $DB->delete_records(self::DB_TABLE, array('id' => $item->id));
                        break;
                    }
                    
                    // Check if we're allowed to display
                    if($jobj->starts >= $now) {
                        $buffer .= $item->html;
                    }
            }
        }
        
        return $buffer;
    }
    
    /**
     * Render contents of a block
     * 
     * @return string
     */
    public function render() {
        return $this->header() . $this->body();
    }
}

/**
 * Alert block editor renderer base class
 * 
 * This will create the shared alert block edit GUI
 */
class ucla_alert_block_editable extends ucla_alert {
    
    /**
     * Elements this alert is capable of displaying
     */
    protected $elements;
    
    public function __construct($courseid) {
        parent::__construct($courseid);
        
        $this->elements = array(
            'scratch',
            'section',
            'commit',
            'tutorial',
        );
        
        // If rendering the site block edit, then include headers
        if($this->is_site) {
            array_unshift($this->elements, 'headers');
        }
    }

    /**
     * Site headers
     * 
     * @global type $DB
     * @return \alert_edit_header 
     */
    protected function headers() {
        global $DB;
        
        // Get the headers
        $headers = $DB->get_records(self::DB_TABLE,
                array('courseid' => 1, 'entity' =>self::ENTITY_HEADER));
        $header_content = array();
        
        foreach($headers as $header) {
            $data = json_decode($header->json);
            $data->recordid = $header->id;
            $header_content[] = $data;
        }
        
        // Get header section
        $header_section = $DB->get_record(self::DB_TABLE,
                array('courseid' => 1, 'entity' => self::ENTITY_HEADER_SECTION));
        $header_section_data = json_decode($header_section->json);
        $header_section_data->recordid = $header_section->id;
        
        return new alert_edit_header($header_content, $header_section_data);
    }
    
    /**
     * Tutorial modules
     * 
     * This creates the tutorial 
     * 
     * @return \html_element that is renderable
     */
    protected function tutorial() {
        // Outer box to hold the content
        $box = new html_element('div');
        $box->add_class('block-ucla-alert edit-alert-tutorial');

        // Tutorial title
        $h1 = new html_element(
                'h1', 
                get_string('edit_tutorial_h1', 'block_ucla_alert')
            );
        
        // Show summary notes
        $summary = new html_element(
                'div', 
                get_string('edit_tutorial_summary', 'block_ucla_alert'), 
                array('class' => 'edit-alert-tutorial-summary')
            );
        
        // Tutorial on how markup works
        $markup = new html_element(
                'div', 
                get_string('edit_tutorial_markup', 'block_ucla_alert'), 
                array('class' => 'edit-alert-tutorial-summary')
            );

        // Small tutorial modules
        
        // Tutorial on how to create a title
        $titles_mod = new alert_html_section_item(get_string('edit_tutorial_title', 'block_ucla_alert'));
        // Tutorial on how to create a list
        $list_mod = new alert_html_section_item(get_string('edit_tutorial_list', 'block_ucla_alert'));
        // Tutorial on how to create a link
        $link_mod = new alert_html_section_item(get_string('edit_tutorial_link', 'block_ucla_alert'));
        
        // Add all the content
        $box->add_content(array(
            $h1, 
            $summary,
            $markup, 
            $titles_mod, 
            $list_mod, 
            $link_mod
        ));
        
        return $box;
    }
    
    /**
     * Returns default section
     * 
     * @return \alert_edit_section 
     */
    protected function section() {
        global $DB;
        
        // Add the default section
        $default_section = $DB->get_record(self::DB_TABLE,
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SECTION, 'visible' => 1));
        $default_section_data = json_decode($default_section->json);
        $default_section_data->recordid = $default_section->id;
        
        return new alert_edit_section($default_section_data);
    }
    
    /**
     * Retruns scratch pad
     * 
     * @return \alert_edit_section 
     */
    protected function scratch() {
        global $DB;
        
        // Add the scratch pad
        $scratch_pad = $DB->get_record(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SCRATCH));
        $scratch_pad_data = json_decode($scratch_pad->json);
        $scratch_pad_data->recordid = $scratch_pad->id;
        
        return new alert_edit_section_scratch($scratch_pad_data);
    }

    /**
     * Get render-able elements
     * 
     * @return \alert_edit_commit_box 
     */
    protected function get_elements() {
        $elements = array();
        
        foreach($this->elements as $element) {
            $elements[] = $this->$element();
        }
  
        return $elements;
    }

    protected function commit() {
        return new alert_edit_commit_box();
    }
    
    public function render() {
        // Create wrapping div
        $alert_edit = new html_element('div');
        $alert_edit->add_attrib('id', 'ucla-alert-edit');

        // Get renderable elements
        $elements = $this->get_elements();
        
        return $alert_edit->add_content($elements)->render();
    }
}
