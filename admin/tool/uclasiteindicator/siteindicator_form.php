<?php
/**
 * UCLA Site Indicator 
 * 
 * @todo        need to create admin interface
 * 
 * @package     ucla
 * @subpackage  uclasiteindicator
 * @author      Alfonso Roman
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->libdir.'/formslib.php');

class siteindicator_form extends moodleform {
    function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        
        $uclaindicator = $this->_customdata['uclaindicator'];
        $rolegroups = $uclaindicator->get_site_rolegroups();

        $records = $DB->get_records('role', null, '', 'id, name');
        foreach($records as $rec) {
            $rolelist[$rec->id] = $rec->name;
        }
        
        $mform->addElement('header','siteindicator_types', 'Indicator role assignments');
        
        foreach($rolegroups as $k => $v) {
            $select = &$mform->addElement('select', $k, $v, $rolelist, array('size' => '15'));
            $select->setMultiple(true);
            $select->setSelected($uclaindicator->get_roles_for_group($k));
        }
        
        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', get_string('revert'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
//        $this->add_action_buttons(true, 'Update role assignments');
    }

}