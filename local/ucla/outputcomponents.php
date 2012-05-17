<?php

/**
 *  Adds external link icons.
 **/
class ucla_html_writer extends html_writer {
    /**
     *  Hack to add external link icon.
     **/
    static function external_link($url, $text, $attr=null) {
        global $CFG;
        if (strpos($url->out(), $CFG->wwwroot) === false) {
            if (empty($attr)) {
                $attr['class'] = '';
            } else {
                $attr['class'] .= ' ';
            }

            $attr['class'] .= 'external-link';
            $attr['title'] = get_string('external-link', 'local_ucla');            
            $attr['target'] = '_blank';
        }

        return parent::link($url, $text, $attr);
    }
}

