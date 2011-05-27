<?php
/* 
 * Update from clas_requestor
 * This creates the TERM pulldown menu
 */

function print_term_pulldown_box($submit_on_change=false) {
    global $CFG;

    $selected_term = optional_param('term',NULL,PARAM_CLEAN) ? optional_param('term',NULL,PARAM_CLEAN) : $CFG->classrequestor_selected_term;

    $pulldown_term = "<select name=\"term\"" . ($submit_on_change ? " onchange=\"this.form.submit()\"" : "") . ">\n";

    // creating the pulldown_term string here because it's used by three different scripts
    foreach ($CFG->classrequestor_terms as $term) {
        if ($term == $selected_term) {
            $pulldown_term .= "<option value=\"$term\" SELECTED>$term</option>\n";
        } else {
            $pulldown_term .= "<option value=\"$term\">$term</option>\n";
        }
    }
    $pulldown_term .= "</select>\n";
    print $pulldown_term;
}
?>