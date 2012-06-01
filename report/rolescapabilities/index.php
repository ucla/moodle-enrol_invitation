<?php

/**
 * Displays a color-coded view of roles's capabilities
 *
 * @package    report
 * @subpackage log
 * @copyright  2011 onwards Daniel Neis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/roles/lib.php');

require_login(get_site());

$url = new moodle_url('/report/rolescapabilities/index.php');
$PAGE->set_url($url);
$PAGE->requires->css('/report/rolescapabilities/styles.css');
$PAGE->set_title(get_string('rolescapabilities', 'report_rolescapabilities'));
$PAGE->set_heading(get_string('rolescapabilities', 'report_rolescapabilities'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('rolescapabilities', 'report_rolescapabilities'));

echo '<div id="legendcontainer">',
       '<h3>', get_string('legend_title', 'report_rolescapabilities'), '</h3>',
       '<dl id="legend">',
         '<dt><span class="notset">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('notset', 'role'), '</dd>',

         '<dt><span class="allow">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('allow', 'role'), '</dd>',

         '<dt><span class="prevent">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('prevent', 'role'), '</dd>',

         '<dt><span class="deny">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></dt>',
         '<dd>', get_string('prohibit', 'role'), '</dd>',
       '</dl>',
     '</div>';

list($usql, $params) = $DB->get_in_or_equal(explode(',',$CFG->report_rolescapabilities_available_roles));
$sql = "SELECT id, name
          FROM {role}
         WHERE id $usql
      ORDER BY sortorder ASC";
$available_roles = $DB->get_records_sql($sql, $params);

if ($data = data_submitted()) {
    $roles_ids = $data->roles_ids;
} else {
    $roles_ids = array();
}

echo '<div id="optionscontainer">',
     '<form action="index.php" method="post">',
     '<select multiple="multiple" name="roles_ids[]" size="10" id="roles_ids">';
foreach ($available_roles as $rid => $r) {
    $selected = '';
    if (!empty($roles_ids)) {
        $selected = in_array($rid, $roles_ids) ? 'selected="selected"' : '';
    }
    echo "<option value=\"{$rid}\" {$selected}>{$r->name}</option>";
}
echo '</select>',
     '<p><input type="submit" value="', get_string('show'), '" /></p>',
     '</form>',
     '</div>';

if (empty($available_roles)) {
    echo $OUTPUT->heading(get_string('no_roles_available', 'report_rolescapabilities'));
}

class rolescapabilities_table extends capability_table_base {

    public function __construct($context, $id, $roleids) {
        global $DB, $CFG;

        parent::__construct($context, $id);

        $this->allrisks = get_all_risks();
        $this->risksurl = get_docs_url(s(get_string('risks', 'role')));

        $this->allpermissions = array(
            CAP_INHERIT => 'inherit',
            CAP_ALLOW => 'allow',
            CAP_PREVENT => 'prevent' ,
            CAP_PROHIBIT => 'prohibit',
        );

        $this->strperms = array();
        foreach ($this->allpermissions as $permname) {
            $this->strperms[$permname] =  get_string($permname, 'role');
        }

        list($usql, $params) = $DB->get_in_or_equal($roleids);
        $sql = "SELECT id,shortname, name
                  FROM {role}
                 WHERE id {$usql}
              ORDER BY sortorder";
        $this->roles = $DB->get_records_sql($sql, $params);

    }

    protected function add_header_cells() {
        $th = '';
        foreach ($this->roles as $rid => $r) {
            $th .= "<th class=\"role\">{$r->name}</th>";
        }
        $th .= '<th>'.get_string('risks', 'role').'</th>';
        echo $th;
    }

    protected function num_extra_columns() {
        return sizeof($this->roles) + 1;
    }

    protected function add_row_cells($capability) {
        global $DB;

        foreach ($this->roles as $role) {
            $permission = $DB->get_records_menu('role_capabilities',
                                                array('roleid' => $role->id,
                                                     'contextid' => $this->context->id,
                                                     'capability' => $capability->name),
                                                '', 'capability,permission');
            if ($permission) {
                echo '<td class="role cap', $permission[$capability->name] , '">';
                switch($permission[$capability->name]) {
                    case CAP_ALLOW: 
                        echo get_string('allow', 'role');
                        break;
                    case CAP_PREVENT: 
                        echo get_string('prevent', 'role');
                        break;
                    case CAP_PROHIBIT: 
                        echo get_string('prohibit', 'role');
                        break;
                }                
                echo '</td>';
            } else {
                echo '<td class="role capnotset"></td>';
            }
        }
        echo '<td>';
    /// One cell for all possible risks.
        foreach ($this->allrisks as $riskname => $risk) {
            if ($risk & (int)$capability->riskbitmask) {
               echo $this->get_risk_icon($riskname);
            }
        }
        echo '</td>';
    }

    /**
     * Print a risk icon, as a link to the Risks page on Moodle Docs.
     *
     * @param string $type the type of risk, will be one of the keys from the
     *      get_all_risks array. Must start with 'risk'.
     */
    function get_risk_icon($type) {
        global $OUTPUT;
        if (!isset($this->riskicons[$type])) {
            $iconurl = $OUTPUT->pix_url('i/' . str_replace('risk', 'risk_', $type));
            $text = '<img src="' . $iconurl . '" alt="' . get_string($type . 'short', 'admin') . '" />';
            $action = new popup_action('click', $this->risksurl, 'docspopup');
            $this->riskicons[$type] = $OUTPUT->action_link($this->risksurl, $text, $action, array('title'=>get_string($type, 'admin')));
        }
        return $this->riskicons[$type];
    }
}

if (empty($roles_ids)) {
    echo $OUTPUT->heading(get_string('no_roles_selected', 'report_rolescapabilities'));
} else {
    $report = new rolescapabilities_table(get_context_instance(CONTEXT_SYSTEM), 0, $roles_ids);
    $report->display();
}
echo $OUTPUT->footer();
