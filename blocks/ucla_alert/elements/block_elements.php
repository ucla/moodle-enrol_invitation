<?php

///
// Elements that make up the header and body of the alert block.
// 
// All elements are created from html_element base class -- all the classes
// generally render pretty formatted html with css properties or styles applied

/**
 * Alert block header UI renderer (primarily for SITE block)
 * 
 * This consists of the color header and a the section right below it.
 * 
 */
class alert_html_header extends html_element {
    
    public function __construct($header, $section) {

        // Get the color header
        $box = new alert_html_header_box($header->item);
        $box->add_class('alert-header-' . $header->color);
        
        // Add the section
        $content = array(
            $box,
            new alert_html_section($section),
        );
        
        parent::__construct('div', $content, array());
    }
}

/**
 * Alert block color header renderer.  Creates the header from markup text
 * 
 * The color header is usually made up of a title (BIG text) and
 * subtitle (small text) right below it.
 * 
 */
class alert_html_header_box extends alert_html_box_content {
    
    /**
     * Creates a site header box
     * 
     * @param string $text to be parsed into a title and subtitle
     */
    public function __construct($text) {
        // Content is parsed
        $content = alert_text_parser::parse_header($text);
        parent::__construct($content, array('class' => 'header-box'));
    }
}

/**
 * Creates color header title UI with BIG text 
 */
class alert_html_header_title extends html_element {
    public function __construct($content = null) {
        parent::__construct('div', $content, array('class' => 'header-title'));
    }
}

/**
 * Creates color header subtitle with small text 
 */
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
 * A tweet 
 */

class alert_html_box_tweet extends alert_html_box_content {
    public function __construct($content = null) {
        
        $tweet = str_replace(alert_text_parser::BOX_TWITTER, '', $content->username);
        $a = new html_element('a', $content->username);
        $a->add_attrib('href', 'http://twitter.com/' . $tweet);
        
        // Convert links and hashtags 
        $text = preg_replace('/((ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)/', '<a href="$1" target="_blank">$1</a>', $content->text);
        $text = preg_replace('/@([a-zA-Z0-9_]+)/', '<a href="http://twitter.com/$1" target="_blank" class="username">@$1</a>', $text);
        $text = preg_replace('/#([a-zA-Z0-9_]+)/', '<a href="http://search.twitter.com/search?q=%23$1" target="_blank" class="hashtag">#$1</a>', $text);

        $quote = new html_element('div', $text, array('class' => 'alert-tweet-quote'));
        
        $datestr = date('F j, Y, g:i a', strtotime($content->date));
        $date = new html_element('div', $datestr, array('class' => 'alert-tweet-date'));
        
        parent::__construct(array($a, $quote, $date), array('class' => 'alert-tweet-box'));
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
