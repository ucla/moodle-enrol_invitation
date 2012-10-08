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
 * An alert header contains:
 *  alert_box[title, subtitle]
 *  alert_section[items, ...] 
 */
class alert_html_header extends html_element {
    
    public function __construct($text) {
        
        $items = explode('$$', $text);
        $header = array_shift($items);
        
        // Prepare items
        $section = new stdClass();
        $section->title = '';
        $section->items = $items;
        
        $content = array(
            new alert_html_header_box($header),
            new alert_html_section($section),
        );
        
        parent::__construct('div', $content, array());
    }

}

class alert_html_header_box extends alert_html_box_content {
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
 * A general boxing element
 */
class alert_html_box_content extends html_element {
    public function __construct($content = null, $attributes = array('class' => 'box-boundary')) {
        parent::__construct('div', $content, $attributes);
    }
}

/**
 * An item title element
 */
class alert_html_box_title extends alert_html_box_content {
    public function __construct($content = null) {
        parent::__construct($content, array('class' => 'box-title'));
    }
}

/**
 * An item text element
 */
class alert_html_box_text extends alert_html_box_content {
    public function __construct($content = null) {
        parent::__construct($content, array('class' => 'box-text'));
    }
}

/**
 * An item list element
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
 * An section item
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
        // Expect:
        // $section = {
        //      'title' => 'section_title'
        //      'items' => array('item_raw_text')
        // }
        
        // Give section a title
        $content = array(new alert_html_section_title($section->title));
        
        // Add the items
        foreach($section->items as $item_text) {
            $content[] = new alert_html_section_item(trim($item_text));
        }
        
        parent::__construct($content);
    }
}

class alert_edit_header extends html_element {
    public function __construct($headers, $section_text) {
        
        $items = explode('$$', $section_text);
        
        $allheaders = array();
        foreach($headers as $hed) {
            $box = new alert_html_header_box($hed->raw);
            $box->add_class('alert-header-'. $hed->color)
                ->add_class('box-boundary')
                ->add_attrib('rel', $hed->raw)
                ->add_attrib('visible', $hed->visible);
            
            $edit = new alert_edit_textarea_box($hed->raw, 2);
            $edit->add_class('alert-header-' . $hed->color);
            
            $allheaders[$hed->color] = new alert_html_box_content(array($box, $edit),
                    array('class' => 'alert-edit-header-wrapper alert-edit-element'));
        }

        // Create <li> list
        $ullist = array();
        foreach($items as $item_text) {
            $item = new html_element('li', new alert_html_section_item(trim($item_text)));
            $item->add_class('alert-edit-item')
                 ->add_content(new alert_edit_textarea_box($item_text))
                 ->add_attrib('rel', trim($item_text));
            
            $ullist[] = $item;
        }
        
        $ul = new html_element('ul', $ullist);
        
        parent::__construct('div', array($allheaders, $ul), 
                array('class' => 'alert-edit-header block-ucla-alert'));
    }
}

class alert_edit_section extends html_element {
    public function __construct($text) {

        $items = explode('$$', $text);
        
        $section = new stdClass();
        $section->title = array_shift($items);
        $section->items = $items;
        
        $title = new alert_html_section_title($section->title);
        
        // Create <li> list
        $ullist = array();
        foreach($section->items as $item_text) {
            $item = new html_element('li', new alert_html_section_item(trim($item_text)));
            $item->add_class('alert-edit-item')
                 ->add_class('alert-edit-element')
                 ->add_attrib('rel', trim($item_text))
                 ->add_content(new alert_edit_textarea_box($item_text));
            
            $ullist[] = $item;
        }
        
        $ul = new html_element('ul', $ullist);
        
        parent::__construct('div', array($title, $ul), 
                array('class' => 'alert-edit-section block-ucla-alert'));
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
        $save = new html_element('button', 'Save');
        $save->add_class('btn')
             ->add_class('btn-mini')
             ->add_class('btn-active')
             ->add_class('alert-edit-save');
        
        $cancel = new html_element('button', 'Cancel');
        $cancel->add_class('btn')
               ->add_class('btn-mini')
               ->add_class('btn-danger')
               ->add_class('alert-edit-cancel');
        
        parent::__construct('div', array($save, $cancel), 
                array('class' => 'alert-edit-button-box'));
    }
}

/**
 * Alert text parser
 */
class alert_text_parser {
    const STRING_BRACES =   0;
    const STRING_INNER =    1;
    
    const BOX_TITLE =   ':title';
    const BOX_LIST =    ':list';
    const BOX_LINK =    ':link';
    
    const HEADER_TITLE =    ':header';
    const HEADER_SUB =      ':subheader';
    const HEADER_FUNCTION = ':function';
    
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
            } else if(strpos($l, self::HEADER_FUNCTION) === 0) {
                $function = trim(str_replace(self::HEADER_FUNCTION, '', $l));
                
                if(method_exists('alert_text_parser', $function)) {
                    $output[] = alert_text_parser::$function();
                }
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

    
    public static function now() {
        $time = date("F j, Y - g:i a", time());
        return new alert_html_header_subtitle($time);
    }

    public static function date() {
        $time = date("F j, Y", time());
        return new alert_html_header_subtitle($time);
    }
}

/**
 * UCLA alert
 */
abstract class ucla_alert {
    const DB_TABLE = 'ucla_alert';
    
    const ENTITY_HEADER         = 20;
    const ENTITY_SECTION        = 30;
    const ENTITY_HEADER_SECTION = 40;
    
    const RENDER_CACHE             = 10;
    const RENDER_REFRESH           = 20;
    const RENDER_DAILY             = 30;
    
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

class ucla_alert_block_editable extends ucla_alert_block {
    
    public function __construct($courseid) {
        parent::__construct($courseid);
    }
    
    public function render() {
        
        $alert_edit = new html_element('div');
        $alert_edit->add_attrib('id', 'ucla-alert-edit');
        
        $raw_text = "ccle notices
$$
:title this is a title
this is regular text that overflows
text on a new line

double spaced text
:list{red} this is a list
:list another list
:link{http://www.google.com} google.com
$$
:title foo
another test";

        $disable_section = "scratch pad
$$
:title disabled item
this item is disabled when it's left in this area.";

        $header_text1 = ":header hello world!
:function date ";
        $header_text2 = ":header service alert
:subheader You will survive";
        $header_text3 = ":header ccle alert
:function now";
        $header_text4 = ":header maintenance alert
:subheader Scheduled NOW!";
        
        $section_header_text = "Alert block presents itself to the world!";
        
        $o1 = new stdClass();
        $o1->raw = $header_text1;
        $o1->color = 'default';
        $o1->visible = 1;

        $o2 = new stdClass();
        $o2->raw = $header_text2;
        $o2->color = 'yellow';
        $o2->visible = 0;
        
        $o3 = new stdClass();
        $o3->raw = $header_text3;
        $o3->color = 'red';
        $o3->visible = 0;
        
        $o4 = new stdClass();
        $o4->raw = $header_text4;
        $o4->color = 'blue';
        $o4->visible = 0;
        
        $allheaders = array(
            $o1, $o2, $o3, $o4
        );

        
        $mysections = array(
            new alert_edit_section($disable_section),
            new alert_edit_header($allheaders, $section_header_text),
            new alert_edit_section($raw_text),
        );
        
        return $alert_edit->add_content($mysections)->render();
        
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
$$
alert block presents itself to the world!";
        
        $foo = new alert_html_header($t);
        
        $text = array("
:title this is a title
this is regular text that overflows
text on a new line

double spaced text
:list{red} this is a list
:list another list
:link{http://www.google.com} google.com
",
":title foo
another test");
        
        $section = new stdClass();
        $section->title = 'ccle notices';
        $section->items = $text;
        
        $bar = new alert_html_section($section);
        
       
        return $foo->render() . $bar->render();
    }
}

