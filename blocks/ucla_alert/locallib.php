<?php

///
// Contains base classes that render the alert block

/**
 * A more flexible HTML writer class
 */
class html_element {
    
    private $tag;
    private $attribs;
    private $content;
    
    /**
     * Create an HTML element
     * 
     * @param string $tag 
     * @param mixed $content
     * @param array $attribs 
     */
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
 * Alert text parser
 */
class alert_text_parser {
    const STRING_BRACES =   0;
    const STRING_INNER =    1;

    // Item tokens
    const BOX_TITLE =   '#';
    const BOX_LIST =    '*';
    const BOX_LINK =    '>';
    
    // Twitter token
    const BOX_TWITTER = '@';
    
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
        
        // Store HTML element objects to be passed on to a renderer later
        $output = array();
        
        $h = '';
        
        foreach($lines as $line) {
            $l = $line;
            
            // Render titles
            if(strpos($l, self::BOX_TITLE) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_title(trim(str_replace(self::BOX_TITLE, '', $l)));
                continue;
            
            // Render a list item
            } else if(strpos($l, self::BOX_LIST) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_list(trim(str_replace(self::BOX_LIST, '', $l)));
                continue;
                
            // Render a link
            } else if(strpos($l, self::BOX_LINK) === 0) {
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                $output[] = new alert_html_box_link(trim(str_replace(self::BOX_LINK, '', $l)));
                continue;
                
            // Render a tweet
            } else if(preg_match('/^@([A-Za-z]+[A-Za-z0-9]+)$/', $l)) {
                // Match twitter token
                
                if(!empty($h)) {
                    $output[] = new alert_html_box_text($h);
                    $h = '';
                }
                
                // Treat it as a regular link for non-js browsers.  
                // The YUI script will override this.
                $l = trim($l);
                $out = '{http://twitter.com/' . str_replace(self::BOX_TWITTER, '', $l) . '}' . $l;
                $tweet = new alert_html_box_link($out);
                
                // Add identifier for YUI
                $tweet->add_class('box-twitter-link');
                
                $output[] = $tweet;
                continue;
                
            // Ignore HEADER tokens... for now
            } else if(strpos($l, self::HEADER_TITLE) === 0) {
                continue;
            } else if(strpos($l, self::HEADER_SUB) === 0) {
                continue;
            }
            
            // Looks like we have a newline
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
 * UCLA alert base class
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
    const RENDER_EXPIRE            = 50;
    
    // Course ID
    protected $courseid;
    protected $is_site;
    
    public function __construct($courseid) {
        $this->courseid = $courseid;
        
        // Boolean is TRUE when this is the sitewide block
        $this->is_site = (intval($courseid) === intval(SITEID));
    }
    
    abstract public function render();
    
    /**
     * Install default block elements.  This happens only when you install 
     * the block.
     * 
     * @param bool $empty if set to true, it will not install the default item
     */
    public function install($empty = false) {
        global $DB;
        
        // Install SITE headers
        if($this->is_site) {
            $this->install_site_headers();
        }
        
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
            
            // Default item to show
            $default_item = empty($empty) ? array(get_string('section_item_default', 'block_ucla_alert')) : array();
            
            $data = array(
                'title' => get_string($title, 'block_ucla_alert'),
                'visible' => 1,
                'entity' => self::ENTITY_SECTION,
                'items' => $default_item,
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

    static public function handle_alert_post($data) {
        global $DB;

        // Make sure that there's an expiration date and that it makes sense..
        if(empty($data->expires) || strtotime($data->expires) < time()) {
            
            // If expiration time does not make sense, then expire in an hour
            $expires = time() + 60 * 60;

        } else {
            $expires = strtotime($data->expires);
        }
        
        // Make sure there's a start date
        if(empty($data->starts)) {
            // Start it now
            $starts = time();
        } else {
            $starts = strtotime($data->starts);
        }

        // Store expiration date in json
        $json = array(
            'expires' => $expires,
            'starts' => $starts
        );
        
        // Render the HTML item
        $html = new alert_html_section_item(trim($data->text));
        $html->add_class('alert-item-expires');
        $html = $html->render();
        
        foreach($data->courses as $courseid) {
            
            $record = array(
                'courseid' => $courseid,
                'entity' => $data->entity,
                'render' => self::RENDER_EXPIRE,
                'html' => $html,
                'json' => json_encode((object)$json),
                'visible' => 1,
            );
            
            // If block doesn't exist, add it to the course
            if(!$DB->record_exists(self::DB_TABLE, array('courseid' => $courseid))) {
                // Get the course
                $course = $DB->get_record('course', array('id' => $courseid));
                
                // Add the block
                $page = new moodle_page();
                $page->set_course($course);
                $page->blocks->add_regions(array(BLOCK_POS_RIGHT));
                $page->blocks->add_block('ucla_alert', BLOCK_POS_RIGHT, -10, 0, 'course-view-*');
                
                // Still need to install base elements
                // This happens automatically when the block is installed 
                // via the 'add block' dropdown -- but not when you add
                // the block this way...
                $alert = new ucla_alert_block($courseid);
                
                // Install block without any default items.  This will ensure
                // that block is hidden after alert expires
                $alert->install(true);
            }

            // Now add the item
            $DB->insert_record(self::DB_TABLE, $record);
        }
    }

    /**
     * Run once to install the default SITE headers
     */
    private function install_site_headers() {
        global $DB;
        
        /// Sanity check..
        if($DB->record_exists(self::DB_TABLE, array('courseid' => SITEID, 'entity' => self::ENTITY_HEADER))) {
            return true;
        }

        // Install headers
        $data = array(
            'visible' => 0,
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
            'visible' => 0
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
        
        // Install blue <empty> header and make it default 
        $data['visible'] = 0;
        $data['color'] = 'empty';
        $data['item'] = get_string('header_empty', 'block_ucla_alert');
        
        $record['json'] = json_encode($data);
        $record['visible'] = 1;
        
        $DB->insert_record(self::DB_TABLE, (object)$record);

        // Install section conditionally
        if(!$DB->record_exists(self::DB_TABLE, 
                array('courseid' => SITEID, 'entity' => self::ENTITY_HEADER_SECTION))) {
            
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

        return true;
    }
}

// Load everything else
require_once(dirname(__FILE__) . '/elements/block_elements.php');
require_once(dirname(__FILE__) . '/elements/edit_elements.php');
require_once(dirname(__FILE__) . '/elements/base_elements.php');