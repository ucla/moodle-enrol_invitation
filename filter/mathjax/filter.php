<?php

defined('MOODLE_INTERNAL') || die();

class filter_mathjax extends moodle_text_filter {
    public function __construct($context, array $localconfig) {
        global $PAGE, $CFG;
        
        if (isset($PAGE)) {
            if (file_exists($CFG->dirroot . '/filter/mathjax/js/MathJax.js') &&
                file_exists($CFG->dirroot . '/filter/mathjax/js/config') &&
                file_exists($CFG->dirroot . '/filter/mathjax/js/jax') &&
                file_exists($CFG->dirroot . '/filter/mathjax/js/fonts') &&
                file_exists($CFG->dirroot . '/filter/mathjax/js/extensions')) {
                $mathjaxpath = '/filter/mathjax/js';
            } else if (substr($PAGE->url, 0, 6) === 'https:') {
                $mathjaxpath = 'https://d3eoax9i5htok0.cloudfront.net/mathjax/latest';
            } else {
                $mathjaxpath = 'http://cdn.mathjax.org/mathjax/latest';
            }
            
            $url = new moodle_url($mathjaxpath . '/MathJax.js',
                  array('config' => 'TeX-AMS-MML_HTMLorMML',
                        'delayStartupUntil' => 'configured'));
            
            $PAGE->requires->js($url);
            $PAGE->requires->js_init_call('M.filter_mathjax.init',
                array('mathjaxroot' => (string)(new moodle_url($mathjaxpath))));
        }
        
        parent::__construct($context, $localconfig);
    }
    
    public function filter($text, array $options = array()) {
        // The presence of these indicates MathJax ought to process the block:
        //   inline: \( ... \)
        //   block: $$ ... $$, \[ ... \]
        //   tex environments: \begin{...} ... \end{...}
        //   mathml: <math> ... </math>
        
        if (preg_match('/\$\$.+?\$\$|\\\\\\[.+?\\\\\\]|\\\\\\(.+?\\\\\\)|\\\\begin\\{|<math/s', $text)) {
            return '<div class="filter-mathjax">'.$text.'</div>';
        }
        return $text;
    }
}
