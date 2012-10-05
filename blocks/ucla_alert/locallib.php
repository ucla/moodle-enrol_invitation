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
    
    public function add_attribs($attribs) {
        foreach($attribs as $k => $v) {
            $this->add_attrib($k, $v);
        }
        
        return $this;
    }
    
    public function add_attrib($name, $val) {
        
        if(key_exists($name, $this->attribs)) {
            $this->attribs[$name] .= ' ' . $val;
        } else {
            $this->attribs[$name] = $val;
        }
        
        return $this;
    }
    
    function add_class($class) {
        return $this->add_attrib('class', $class);
    }
    
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
                if($c instanceof html_tag) {
                    $out .= $c->render();
                }
            }
            
        // Handle plain ol strings
        } else if(is_string($content)) {
            $out = trim($content);
        }
        
        return $out;
    }
    
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

class alert_html_header extends html_element {
    
    public function __construct($text) {
        
        $content = array(
            new alert_html_header_box($text),
            new alert_html_section_item($text),
        );
        
        parent::__construct('div', $content, array());
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

class alert_html_header_box extends alert_html_box_content {
    public function __construct($text) {
        $content = alert_text_parser::parse_header($text);
        parent::__construct($content, array('class' => 'header-box'));
    }
}

class alert_html_section_title extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'box-section'));
    }
}

class alert_html_box_title extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'box-title'));
    }
}

class alert_html_box_text extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'box-text'));
    }
}

class alert_html_box_content extends html_element {
    public function __construct($content = null, $attributes = array('class' => 'box-boundary')) {
        parent::__construct('div', $content, $attributes);
    }
}

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

class alert_html_section_item extends alert_html_box_content {

    public function __construct($text) {
        
        $content = alert_text_parser::parse_item($text);
        parent::__construct($content);
    }
}


class alert_html_section extends alert_html_box_content {
    public function __construct($section) {
        // Expect:
        // $section = {
        //      'title' => 'section_title'
        //      'items' => array('item_raw_text')
        // }
        
        // Give section a title
        $content = array(new alert_html_section_title($section->title));
        
        // Add the items
        foreach($section->items as $item_raw) {
            $content[] = new alert_html_section_item($item_raw);
        }
        
        parent::__construct($content);
    }
}


class alert_text_parser {
    const STRING_BRACES =   0;
    const STRING_INNER =    1;
    
    const BOX_TITLE =   ':title';
    const BOX_LIST =    ':list';
    const BOX_LINK =    ':link';
    
    const HEADER_TITLE =    ':header';
    const HEADER_SUB =      ':subheader';
    
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
    
    public static function parse_header($text) {
        $lines = explode("\n", $text);
        
        $output = array();
        
        foreach($lines as $line) {
            $l = trim($line);
            
            if(strpos($l, self::HEADER_TITLE) === 0) {
                $output[] = new alert_html_header_title(trim(str_replace(self::HEADER_TITLE, '', $l)));
                continue;
            } else if(strpos($l, self::HEADER_SUB) === 0) {
                $output[] = new alert_html_header_subtitle(trim(str_replace(self::HEADER_SUB, '', $l)));
                continue;
            }
            
        }
        
        return $output;
    }
    
    public static function parse_braces($content) {
        if(is_string($content) && preg_match('/\{(.+)\}/', $content, $matches)) {

            return array(
                trim(str_replace($matches[self::STRING_BRACES], '', $content)), 
                trim($matches[self::STRING_INNER])
            );
        }
        
        return array($content, 0);
    }
}

abstract class ucla_alert {
    const DB_TABLE = 'ucla_alert';
    
    const ENTITY_META =     10;
    const ENTITY_HEADER =   20;
    const ENTITY_SECTION =  30;
    const ENTITY_ITEM =     40;
    
    const TEMPLATE_HEADER = 50;
    
    public function __construct() {
        // empty
    }
    
    abstract public function render();
}

abstract class ucla_alert_block extends ucla_alert {

    protected $courseid;

    public function __construct($courseid) {
        parent::__construct();
        
        $this->courseid = $courseid;
    }

    protected function get_body() {
        global $DB;
        
        $records = $DB->get_records(self::DB_TABLE, 
                array(
                    'courseid' => $this->courseid,
                    'entity' => self::ENTITY_SECTION,
                    'visible' => true,
                ));
        
        $html = '';
        
        foreach($records as $rec) {
            $data = json_decode($rec->data);
            $html .= $data->html;
        }
        
        return $html;
    }
}


class ucla_alert_block_course extends ucla_alert_block {
    public function render() {
        ;
    }
}


class ucla_alert_block_site extends ucla_alert_block {

    public function __construct($courseid) {
        parent::__construct($courseid);
    }

    protected function get_header() {
        global $DB;
        
        $record = $DB->get_records(self::DB_TABLE, 
                array(
                    'courseid' => $this->courseid,
                    'entity' => self::ENTITY_HEADER,
                    'visible' => true,
                ));
        
        $data = json_decode($record->data);
        
        return $data->html;
    }

    public function render() {
        
//        $html = $this->get_header() . $this->get_body();
        
//        return $html;
        
        $t = ":header hello world!
            :subheader " . time() ."
            ccle is doing awesome!";
        
        $foo = new alert_html_header($t);
        
//        $out = new alert_html_box_content(' test ');
        
        $text = array(":title this is a title
            this is regular text that overflows
            text on a new line
            
            double spaced text
            :list{red} this is a list
            :list another list
            :link{http://www.google.com} google.com");
        
        $section = new stdClass();
        $section->title = 'ccle notices';
        $section->items = $text;
        
        $bar = new alert_html_section($section);
        
       
        return $foo->render() . $bar->render();
    }
}

