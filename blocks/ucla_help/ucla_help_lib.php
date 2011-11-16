<?php
/**
 * Collection of classes/functions used across multiple scripts for the UCLA 
 * Help and Feedback block.
 * 
 * Else, can be called displayed in a site or course context.
 *
 * @package    ucla
 * @subpackage ucla_help
 * @copyright  2011 UC Regents    
 * @author     Rex Lorenzo <rex@seas.ucla.edu>                                      
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */
//require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/../../lib/adminlib.php');

/**
 * Dervived from admin_setting_emoticons. Used to allow 
 * admins to edit the 'ucla_help' support contact settings.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_ucla_help_support_contact extends admin_setting {

    /**
     * Calls parent::__construct with specific args
     */
    public function __construct() {
        global $CFG;
        parent::__construct('block_ucla_help/support_contacts', 
                get_string('settings_support_contacts', 'block_ucla_help'), '', '');
    }

    /**
     * Return the current setting(s)
     *
     * @return array Current settings array
     */
    public function get_setting() {

        $manager = get_support_contacts_manager();        
        $config = $manager->get_support_contacts();
        
        return $this->prepare_form_data($config);
    }

    /**
     * Saves support contact into key-value pairs in 
     * $CFG->block_ucla_help->support_contacts array. Ignores support contacts
     * for contexts that are defined in the config.php file.
     *
     * @param array $data Array of settings to save
     * @return bool
     */
    public function write_setting($data) {
        
        $manager = get_support_contacts_manager();
        $support_contacts = $this->process_form_data($data);
        
        if ($support_contacts === false) {
            return false;
        }
        
        if ($this->config_write($this->name, $manager->encode_stored_config($support_contacts))) {
            return ''; // success
        } else {
            return get_string('errorsetting', 'admin') . $this->visiblename . html_writer::empty_tag('br');
        }
    }

    /**
     * Return XHTML field(s) for options
     *
     * @param array $data Array of options to set in HTML
     * @return string XHTML string for the fields and wrapping div(s)
     */
    public function output_html($data, $query='') {
        global $block_ucla_help_support_contacts, $OUTPUT;

        $out  = html_writer::start_tag('table', array('border' => 1, 'class' => 'generaltable'));
        $out .= html_writer::start_tag('thead');
        $out .= html_writer::start_tag('tr');
        $out .= html_writer::tag('th', '');
        $out .= html_writer::tag('th', get_string('settings_support_contacts_table_context', 'block_ucla_help'));
        $out .= html_writer::tag('th', get_string('settings_support_contacts_table_contact', 'block_ucla_help'));
        $out .= html_writer::tag('th', '');
        $out .= html_writer::end_tag('tr');
        $out .= html_writer::end_tag('thead');
        $out .= html_writer::start_tag('tbody');
        $i = 0; $row_num = 1; $cur_context = '';
        foreach((array) $data as $field => $value) {
            if ($i == 0) {  
                // on first element, so start a new row
                $out .= html_writer::start_tag('tr');
                $out .= html_writer::tag('td', sprintf('%d.', $row_num));
                $row_num++;
                
                // save context for later on
                $cur_context = $value;
            }
            
            $out .= html_writer::tag('td',
                html_writer::empty_tag('input',
                    array(
                        'type'  => 'text',
                        'class' => 'form-text',
                        'name'  => $this->get_full_name().'['.$field.']',
                        'value' => $value,
                    )
                ), array('class' => 'c'.$i)
            );
            
            if ($i == 1) {
                // on last element, so end row
                
                // if context ws defined in config file, then give warning that
                // user cannot change setting
                if (!empty($block_ucla_help_support_contacts[$cur_context])) {
                    $cell_text = html_writer::tag('div', 'Defined in config.php', array('class' => 'form-overridden'));
                } else {
                    $cell_text = '';
                }                                
                $out .= html_writer::tag('td', $cell_text);
                
                $out .= html_writer::end_tag('tr');
                $i = 0;
            } else {
                $i++;
            }
        }
        $out .= html_writer::end_tag('tbody');
        $out .= html_writer::end_tag('table');

        return format_admin_setting($this, $this->visiblename, $out, $this->description, false, '', NULL, $query);
    }

    /**
     * Converts the array of support_contacts provided by 
     * {@see $support_contacts_manager} into admin settings form data
     *
     * @see self::process_form_data()
     * @param array $support_contacts   array of support_contacts as returned by 
     *                                  {@see support_contacts_manager}
     * @return array of form fields and their values
     */
    protected function prepare_form_data(array $support_contacts) {
        
        $form = array();
        $i = 0;
        foreach ((array) $support_contacts as $context => $support_contact) {
            $form['context'.$i]            = $context;
            $form['support_contact'.$i]       = $support_contact;
            $i++;
        }
        // add one more blank field set for new support contact
        $form['context'.$i]            = '';
        $form['support_contact'.$i]       = '';

        return $form;
    }

    /**
     * Converts the data from admin settings form into an array of 
     * support_contacts
     *
     * @see self::prepare_form_data()
     * @param array $form array of admin form fields and values
     * @return false|array of support_contacts
     */
    protected function process_form_data(array $form) {
        $NUM_FORM_ELEMENTS = 2;
        
        $count = count($form); // number of form field values
        if ($count % $NUM_FORM_ELEMENTS) {
            // we must get two fields per support_contact
            return false;
        }

        $support_contacts = array();
        $count = $count / $NUM_FORM_ELEMENTS;        
        for ($i = 0; $i < $count; $i++) {
            $context         = clean_param(trim($form['context'.$i]), PARAM_NOTAGS);
            $support_contact = clean_param(trim($form['support_contact'.$i]), PARAM_NOTAGS);

            // make sure that entries exists
            if (!empty($context) && !empty($support_contact)) {
                $support_contacts[$context] = $support_contact;
            }            
        }
        return $support_contacts;
    }
}

/**
 * Factory function for support_contacts_manager
 *
 * @return support_contacts_manager singleton
 */
function get_support_contacts_manager() {
    static $singleton = null;

    if (is_null($singleton)) {
        $singleton = new support_contacts_manager();
    }

    return $singleton;
}

/**
 * Dervived from emoticon_manager(). Used to encode and decode support_contacts
 * array for block_ucla_block config table.
 *
 * @see admin_setting_support_contacts
 */
class support_contacts_manager {

    /**
     * Returns the current support_contacts
     *
     * @return array of support_contacts
     */
    public function get_support_contacts() {
        global $block_ucla_help_support_contacts;
        
        $support_contacts = get_config('block_ucla_help', 'support_contacts');
        $support_contacts = $this->decode_stored_config((string) $support_contacts);

        // now merge with values from config file to overwrite user set ones
        $support_contacts = array_merge((array) $support_contacts, 
                (array) $block_ucla_help_support_contacts);        
        
        return $support_contacts;
    }

    /**
     * Encodes the array of support contacts into a string storable in config table
     *
     * @see self::decode_stored_config()
     * @param array $contacts array of support contacts objects
     * @return string
     */
    public function encode_stored_config(array $contacts) {
        return json_encode($contacts);
    }

    /**
     * Decodes the string into an array of support contacts
     *
     * @see self::encode_stored_config()
     * @param string $encoded
     * @return string|null
     */
    public function decode_stored_config($encoded) {
        // make sure that decoded string is an array
        $decoded = (array) json_decode((string) $encoded);
        return $decoded;
    }
}
