<?php

/**
 * A more flexible HTML writer class
 */
class html_element {
    
    private $tag;
    private $attribs;
    private $content;
    
    public function __construct($tag, $content = null, $attribs = array()) {
        $this->tag = $tag;
        
        $this->content = '';
        
        if(is_array($content)) {
            foreach($content as $c) {
                $this->add_content($c);
            }
        } else if ($content) {
            $this->add_content($content);
        }
        
        $this->attribs = $attribs;
    }
    
    /**
     * Add an array of attributes
     * 
     * @param array $attribs
     * @return \html_element
     */
    public function add_attribs($attribs) {
        foreach($attribs as $k => $v) {
            $this->add_attrib($k, $v);
        }
        
        return $this;
    }
    
    /**
     * Add a single attribute
     * 
     * If the attribute exists it is appended with a space
     * 
     * @param string $name
     * @param string $val
     * @return \html_element
     */
    public function add_attrib($name, $val) {
        
        if(key_exists($name, $this->attribs)) {
            $this->attribs[$name] .= ' ' . $val;
        } else {
            $this->attribs[$name] = $val;
        }
        
        return $this;
    }
    
    /**
     * Add a class
     * 
     * This does not override previous classes.
     * 
     * @param string $class
     * @return type
     */
    function add_class($class) {
        return $this->add_attrib('class', $class);
    }
    
    /**
     * Add renderable content
     * 
     * @param mixed $content array, object or string
     * @return \html_element
     */
    public function add_content($content) {
        $this->content .= $this->_add_content($content);
        return $this;
    }
    
    private function _add_content($content) {
        
        // Handle objects
        if(is_object($content)) {
            $out = $content->render();
        
        // Handle arrays of objects
        } else if (is_array($content)) {
            
            $out = '';

            foreach($content as $c) {
                $out .= $c->render();
//                if($c instanceof html_tag) {
//                    $out .= $c->render();
//                }
            }
            
        // Handle plain ol strings
        } else if(is_string($content)) {
            $out = trim($content);
        }
        
        return $out;
    }
    
    /**
     * Render the contents of this element.
     * 
     * @return string
     */
    public function render() {
        $out = '';
        $out .= '<' . $this->tag;
        
        // Write attributes
        if(!empty($this->attribs)) {
            foreach($this->attribs as $k => $v) {
                $out .= ' ' . $k . '="' . $v . '"';
            }
        }
        
        // end tag
        if(empty($this->content) && $this->tag != 'div') {
            $out .= ' />' . "\n";
        } else {
            $out .= '>';
            $out .= $this->content;
            $out .= '</' . $this->tag . '>' . "\n";
        }
        
        return $out;
    }
}

/**
 * Alert HTML header
 */
class alert_html_header extends html_element {
    
    public function __construct($header, $section) {

        $box = new alert_html_header_box($header->item);
        $box->add_class('alert-header-' . $header->color);
        
        $content = array(
            $box,
            new alert_html_section($section),
        );
        
        parent::__construct('div', $content, array());
    }
}

/**
 * Alert header box parser
 */
class alert_html_header_box extends alert_html_box_content {
    
    /**
     * Creates a site header box
     * 
     * @param string $text to be parsed into a title and subtitle
     */
    public function __construct($text) {
        $content = alert_text_parser::parse_header($text);
        parent::__construct($content, array('class' => 'header-box'));
    }
}

class alert_html_header_title extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'header-title'));
    }
}

class alert_html_header_subtitle extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'header-subtitle'));
    }
}

/**
 * A general boxing element with preset 'box-boundary' class
 */
class alert_html_box_content extends html_element {
    public function __construct($content = null, $attributes = array('class' => 'box-boundary')) {
        parent::__construct('div', $content, $attributes);
    }
}

/**
 * An item title element with preset 'box-title' class
 */
class alert_html_box_title extends alert_html_box_content {
    public function __construct($content = null) {
        parent::__construct($content, array('class' => 'box-title'));
    }
}

/**
 * An item text element with preset 'box-text' class
 */
class alert_html_box_text extends alert_html_box_content {
    public function __construct($content = null) {
        parent::__construct($content, array('class' => 'box-text'));
    }
}

/**
 * An item list element with preset 'box-list' class
 */
class alert_html_box_list extends alert_html_box_content {
    
    static $colors = array('blue');

    public function __construct($content = null) {
        list($content, $color) = alert_text_parser::parse_braces($content);
        
        parent::__construct($content, array('class' => 'box-list'));
        
        if(!empty($color)) {
            if(in_array($color, self::$colors)) {
                $this->add_class('box-list-'. $color);
            } else {
                $this->add_attrib('style', 'border-color: ' . $color);
            }
        }
    }
}

/**
 * An item link
 */
class alert_html_box_link extends alert_html_box_content {
    public function __construct($content = null) {
        list($content, $link) = alert_text_parser::parse_braces($content);
        
        // Make sure content is not empty
        if(empty($content)) {
            $content = $link;
        }
        
        $a = new html_element('a', $content);
        $a->add_attrib('href', $link);
        
        parent::__construct($a, array('class' => 'box-link'));
    }
}

/**
 * A section title
 */
class alert_html_section_title extends alert_html_box_content {
    public function __construct($content = null) {
        parent::__construct($content, array('class' => 'box-section-title'));
    }
}

/**
 * A section item parser
 */
class alert_html_section_item extends alert_html_box_content {

    public function __construct($text) {
        
        $content = alert_text_parser::parse_item($text);
        parent::__construct($content);
    }
}

/**
 * An alert section renderer
 */
class alert_html_section extends alert_html_box_content {
    public function __construct($section) {

        // Give section a title
        $content = array(new alert_html_section_title($section->title));
        
        // Add the items
        foreach($section->items as $item_text) {
            $content[] = new alert_html_section_item(trim($item_text));
        }
        
        parent::__construct($content);
    }
}

class alert_html_course_box extends html_element {
    public function __construct($content = null) {
        $title = new html_element('div', $content, array('class' => 'course-title'));
        parent::__construct('div', $title, array('class' => 'course-title-box'));
    }
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class alert_edit_header extends html_element {
    public function __construct($headers, $section) {

        $allheaders = array();
        
        foreach($headers as $header) {
            $box = new alert_html_header_box($header->item);
            $box->add_class('alert-header-'. $header->color)
                ->add_class('box-boundary');
            
            $edit = new alert_edit_textarea_box($header->item, 2);
            $edit->add_class('alert-header-' . $header->color);
            
            $box_header = new alert_html_box_content(array($box, $edit));
            $box_header->add_attribs(array(
                'rel' => $header->item,
                'render' => 'header',
                'visible' => $header->visible,
                'color' => $header->color,
                'recordid' => $header->recordid,
                'entity' => $header->entity,
                'class' => 'alert-edit-header-wrapper alert-edit-element'
            ));
            
            $allheaders[] = $box_header;
        }

        parent::__construct('div', array($allheaders, new alert_edit_section($section)), 
                array('class' => 'alert-edit-header block-ucla-alert'));
    }
}

class alert_edit_section extends html_element {
    public function __construct($section) {

        $title = new alert_html_section_title($section->title);
        
        // Create <li> list
        $ullist = array();
        foreach($section->items as $item_text) {
            $ullist[] = new alert_edit_section_li($item_text);
        }
        
        $ul = new html_element('ul', $ullist);
        $ul->add_attrib('title', trim($section->title))
           ->add_attrib('entity', $section->entity)
           ->add_attrib('visible', $section->visible)
           ->add_attrib('recordid', $section->recordid);
        
        parent::__construct('div', array($title, $ul), 
                array('class' => 'alert-edit-section block-ucla-alert'));
    }
}

class alert_edit_section_li extends html_element {
    public function __construct($text) {
        $item = new alert_html_section_item(trim($text));
        $edit = new alert_edit_textarea_box($text);
        
        $attribs = array(
            'class' => 'alert-edit-item alert-edit-element',
            'rel' => trim($text),
            'render' => 'item'
        );
        
        parent::__construct('li', array($item, $edit), $attribs);
    }
    
}

class alert_edit_section_scratch extends alert_edit_section {
    public function __construct($section) {
        parent::__construct($section);
        
        $add = new html_element('button', get_string('scratch_button_add', 'block_ucla_alert'));
        $add->add_class('btn')
//            ->add_class('btn-mini')
            ->add_class('btn-primary')
            ->add_class('alert-edit-add');
        
        $div = new html_element('div', $add, array('class' => 'alert-edit-scratch-add'));
        $div->add_attrib('rel', get_string('scratch_item_new', 'block_ucla_alert'));
        
        $this->add_content($div);
    }
}

class alert_edit_textarea_box extends html_element {
    public function __construct($text, $rows = 8) {
        
        $textarea = new html_element('textarea');
        $textarea->add_class('alert-edit-textarea')
                 ->add_attrib('rows', $rows)
                 ->add_content($text);
        
        parent::__construct('div', array($textarea, new alert_edit_button_box()), 
                array('class' => 'alert-edit-text-box'));
    }
}

class alert_edit_button_box extends html_element {
    public function __construct() {
        $save = new html_element('button', get_string('item_edit_save', 'block_ucla_alert'));
        $save->add_class('btn')
             ->add_class('btn-mini')
             ->add_class('btn-success')
             ->add_class('alert-edit-save');
        
        $cancel = new html_element('button', get_string('item_edit_cancel', 'block_ucla_alert'));
        $cancel->add_class('btn')
               ->add_class('btn-mini')
               ->add_class('btn-danger')
               ->add_class('alert-edit-cancel');
        
        parent::__construct('div', array($save, $cancel), 
                array('class' => 'alert-edit-button-box'));
    }
}

class alert_edit_commit_box extends html_element {
    public function __construct() {
        $save = new html_element('button', get_string('alert_commit_save', 'block_ucla_alert'));
        $save->add_class('btn')
             ->add_class('btn-success')
             ->add_class('alert-edit-save');
        
        $cancel = new html_element('button', get_string('item_edit_cancel', 'block_ucla_alert'));
        $cancel->add_class('btn')
               ->add_class('btn-danger')
               ->add_class('alert-edit-cancel');
        
        parent::__construct('div', array($save, $cancel), 
                array('class' => 'alert-edit-commit-box'));
    }
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/**
 * Alert text parser
 */
class alert_text_parser {
    const STRING_BRACES =   0;
    const STRING_INNER =    1;
    
    // Item tokens
    const BOX_TITLE =   '#';
    const BOX_LIST =    '*';
    const BOX_LINK =    '>';
    
    // Header tokens
    const HEADER_TITLE =    '#';
    const HEADER_SUB =      '##';
    const HEADER_FUNCTION = '#!';
    
    /**
     * Parse item text
     * 
     * @param type $text to be parsed
     * @return array of renderable elements
     */
    public static function parse_item($text) {
        $lines = explode("\n", $text);
        
        $output = array();
        
        $h = '';
        
        foreach($lines as $line) {
            $l = trim($line);
            
            if(strpos($l, self::BOX_TITLE) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_title(trim(str_replace(self::BOX_TITLE, '', $l)));
                continue;
            
                
            } else if(strpos($l, self::BOX_LIST) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_list(trim(str_replace(self::BOX_LIST, '', $l)));
                continue;
                
            } else if(strpos($l, self::BOX_LINK) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_link(trim(str_replace(self::BOX_LINK, '', $l)));
                continue;
                
            } else if(strpos($l, self::HEADER_TITLE) === 0) {
                continue;
            } else if(strpos($l, self::HEADER_SUB) === 0) {
                continue;
            }
            
            $h .= $l . '<br/>';
        }
        
        // Collect trailing box text
        if(!empty($h)) {
            $output[] = new alert_html_box_text($h);
            $h = '';
        }
        
        return $output;
    }
    
    /**
     * Parse header text
     * 
     * @param string $text to be parsed
     * @return array of renderable elements
     */
    public static function parse_header($text) {
        $lines = explode("\n", $text);
        
        $output = array();
        
        foreach($lines as $line) {
            $l = trim($line);
            
            if(preg_match('/^#[^#^!]/', $l)) {
                $output[] = new alert_html_header_title(trim(str_replace(self::HEADER_TITLE, '', $l)));
                continue;
            } else if(preg_match('/^##/', $l)) {
                $output[] = new alert_html_header_subtitle(trim(str_replace(self::HEADER_SUB, '', $l)));
                continue;
            } else if(preg_match('/^#!/', $l)) {
                $function = trim(str_replace(self::HEADER_FUNCTION, '', $l));
                
                // @todo: add parameter list parsing
                // #!{param1, param2}
                if(method_exists('alert_text_parser', $function)) {
                    $output[] = alert_text_parser::$function();
                }
                continue;
            }
        }
        
        return $output;
    }
    
    /**
     * Parse content inside braces
     * 
     * @param string $text to be parsed
     * @return list of text with braces removed, content in braces
     */
    public static function parse_braces($text) {
        if(is_string($text) && preg_match('/\{(.+)\}/', $text, $matches)) {

            return array(
                trim(str_replace($matches[self::STRING_BRACES], '', $text)), 
                trim($matches[self::STRING_INNER])
            );
        }
        
        return array($text, 0);
    }

    /**
     * Get current time in human format
     * 
     * @return \alert_html_header_subtitle
     */
    public static function now() {
        $time = date("F j, Y - g:i a", time());
        return new alert_html_header_subtitle($time);
    }

    /**
     * Get date and time in human format
     * 
     * @return \alert_html_header_subtitle
     */
    public static function date() {
        $time = date("F j, Y", time());
        return new alert_html_header_subtitle($time);
    }
}

/**
 * UCLA alert
 */
abstract class ucla_alert {
    const DB_TABLE = 'ucla_alerts';
    
    // Define entities
    const ENTITY_ITEM           = 10;
    const ENTITY_HEADER         = 20;
    const ENTITY_SCRATCH        = 30;
    const ENTITY_SECTION        = 40;
    const ENTITY_HEADER_SECTION = 50;
    
    // Define render modes
    const RENDER_CACHE             = 10;
    const RENDER_REFRESH           = 20;
    const RENDER_DAILY             = 30;
    const RENDER_ALWAYS            = 40;
    
    // Course ID
    protected $courseid;
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
    }
    
    abstract public function render();
    
    /**
     * Install default block entities
     */
    public function install() {
        global $DB;
        
        // Preinstall scratch
        if(!$DB->record_exists(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SCRATCH))) {

            // Prepare data
            $data = array(
                'title' => get_string('scratch_title', 'block_ucla_alert'),
                'visible' => 0,
                'entity' => self::ENTITY_SCRATCH,
                'items' => array(
                    get_string('scratch_item_default', 'block_ucla_alert'),
                    get_string('scratch_item_add', 'block_ucla_alert'),
                )
            );

            // Prepare record
            $record = array(
                'courseid' => $this->courseid,
                'entity' => self::ENTITY_SCRATCH,
                'render' => self::RENDER_CACHE,
                'json' => json_encode($data),
                'html' => '',
                'visible' => 0,
            );
            
            $DB->insert_record(self::DB_TABLE, (object)$record);
        }
        
        // Preinstall section
        if(!$DB->record_exists(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SECTION))) {

            $title = ($this->courseid == SITEID) ? 'section_title_site' : 'section_title_course';
            
            $data = array(
                'title' => get_string($title, 'block_ucla_alert'),
                'visible' => 1,
                'entity' => self::ENTITY_SECTION,
                'items' => array(
                    get_string('section_item_default', 'block_ucla_alert')
                ),
            );

            // Prepare record
            $record = array(
                'courseid' => $this->courseid,
                'entity' => self::ENTITY_SECTION,
                'render' => self::RENDER_REFRESH,
                'json' => json_encode($data),
                'html' => '',
                'visible' => 1,
            );
            
            $DB->insert_record(self::DB_TABLE, (object)$record);
        }
    }
    
    /**
     * Run once to install the default SITE headers
     */
    static public function install_once() {
        
        global $DB;

        // Install headers
        $data = array(
            'visible' => 1,
            'color' => 'default',
            'entity' => self::ENTITY_HEADER,
            'item' => get_string('header_default', 'block_ucla_alert'),
        );

        $record = array(
            'courseid' => SITEID,
            'entity' => self::ENTITY_HEADER,
            'render' => self::RENDER_REFRESH,
            'json' => json_encode($data),
            'html' => '',
            'visible' => 1
        );

        $DB->insert_record(self::DB_TABLE, (object)$record);
        
        // Install yellow
        $data['visible'] = 0;
        $data['color'] = 'yellow';
        $data['item'] = get_string('header_yellow', 'block_ucla_alert');
        
        $record['json'] = json_encode($data);
        $record['visible'] = 0;
        
        $DB->insert_record(self::DB_TABLE, (object)$record);
        
        // Install red
        $data['visible'] = 0;
        $data['color'] = 'red';
        $data['item'] = get_string('header_red', 'block_ucla_alert');
        
        $record['json'] = json_encode($data);
        $record['visible'] = 0;
        
        $DB->insert_record(self::DB_TABLE, (object)$record);
        
        // Install blue
        $data['visible'] = 0;
        $data['color'] = 'blue';
        $data['item'] = get_string('header_blue', 'block_ucla_alert');
        
        $record['json'] = json_encode($data);
        $record['visible'] = 0;
        
        $DB->insert_record(self::DB_TABLE, (object)$record);
        
        // Install header section
        $data = array(
            'title' => '',
            'visible' => 1,
            'entity' => self::ENTITY_HEADER_SECTION,
            'items' => array(
                get_string('header_section_item', 'block_ucla_alert'),
            )
        );

        // Prepare record
        $record = array(
            'courseid' => SITEID,
            'entity' => self::ENTITY_HEADER_SECTION,
            'render' => self::RENDER_REFRESH,
            'json' => json_encode($data),
            'html' => '',
            'visible' => 1,
        );

        $DB->insert_record(self::DB_TABLE, (object)$record);
    }
}

/**
 * An alert block renderer
 */
class ucla_alert_block extends ucla_alert {

    public function __construct($courseid) {
        parent::__construct($courseid);
        
        // Install 
        $this->install();
    }

    protected function header() {
        $h = new alert_html_course_box('Course alerts');
        return $h->render();
    }

    /**
     * Return rendered body of the block
     * 
     * @return string
     */
    protected function body() {
        global $DB;
        
        $buffer = '';
        
        $sections = $DB->get_records(self::DB_TABLE, 
                array('courseid' => $this->courseid, 'entity' => self::ENTITY_SECTION, 'visible' => 1));
        
        foreach($sections as $section) {
            switch($section->render) {
                case self::RENDER_CACHE:
                    $buffer .= $section->html;
                    break;
                case self::RENDER_REFRESH:
                    $html = new alert_html_section(json_decode($section->json));
                    $buffer .= $html->render();

                    $section->html = $html->render();
                    $section->render = self::RENDER_CACHE;

                    // Cache it
                    $DB->update_record(self::DB_TABLE, $section);
                    break;
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
 * Alert block editor
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
        );
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
  
        $elements[] = new alert_edit_commit_box();
        
        return $elements;
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

/**
 * An alert edit for the site
 */
class ucla_alert_block_editable_site extends ucla_alert_block_editable {
    public function __construct($courseid) {
        parent::__construct($courseid);
        
        $this->elements = array(
            'headers',
            'scratch',
            'section',
        );
    }
    
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
}

/**
 * A special renderer for the sitewide alert block
 */
class ucla_alert_block_site extends ucla_alert_block {

    public function __construct($courseid) {
        parent::__construct($courseid);
    }

    protected function header() {
        global $DB;
        
        $header = $DB->get_record(self::DB_TABLE,
                array('courseid' => 1, 'entity' => self::ENTITY_HEADER, 'visible' => 1));
        $section = $DB->get_record(self::DB_TABLE,
                array('courseid' => 1, 'entity' => self::ENTITY_HEADER_SECTION, 'visible' => 1));

        $render = new alert_html_header(
                json_decode($header->json), 
                json_decode($section->json)
            );
        
        return $render->render();
    }

    public function render() {
        return $this->header() . $this->body();
    }
}

