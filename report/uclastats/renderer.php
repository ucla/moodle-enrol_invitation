<?php
/**
 * UCLA stats console renderering methods are defined here
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

/**
 * UCLA stats console renderer class
 */
class report_uclastats_renderer extends plugin_renderer_base {
    /**
     * Renders the provided widget and returns the HTML to display it.
     *
     * There are special renderes for results list and results.
     *
     * @param renderable $widget instance with renderable interface
     * @return string
     */
    public function render(renderable $widget) {
        $rendermethod = 'render_'.get_class($widget);
        if (method_exists($this, $rendermethod)) {
            return $this->$rendermethod($widget);
        }
        // pass to report renderer if method not found here
        return $this->render_report($widget);
    }

    /**
     * Displays page header.
     *
     * @global mixed $OUTPUT
     * @param string $report
     * @return string
     */
    public function render_header($report = null) {
        global $OUTPUT;
        $title = get_string('pluginname', 'report_uclastats');
        // display report title header, if any
        if (!empty($report)) {
            $title .=  ': ' . get_string($report, 'report_uclastats');
        }
        return $OUTPUT->heading($title);
    }

    /**
     * Displays report and a way to run or select cached results.
     *
     * @param uclastats_base $report
     * @param int $resultid             Default to null. If passed, then will
     *                                  also display specified cache result (if
     *                                  it belongs to report)
     * @return string
     */
    public function render_report(renderable $report, $resultid = null) {
        global $OUTPUT, $PAGE;
        $ret_val = '';

        // display report help
        $ret_val .= html_writer::tag('p', get_string(get_class($report) .
                '_help', 'report_uclastats'), array('class' => 'report-help'));

        // if displaying results, then display parameters used and other info
        if (!empty($resultid)) {
            $ret_val .= $report->display_result($resultid);
        }

        // if user has capability, display ability to run report
        if (has_capability('report/uclastats:query', $PAGE->context)) {
            $run_form = $report->get_run_form();

            // unfortunately forms display by themselves, so we need to use
            // output buffering
            ob_start();
            $run_form->display();
            $ret_val .= ob_get_contents();
            ob_end_clean();
        }

        // display list of cached results
        $ret_val .= $report->display_cached_results($resultid);

        return $ret_val;
    }

    /**
     * Displays block with list of available stats reports.
     *
     * @param array $reports
     * @param string $current_report    Null by default. If given, then will
     *                                  highlight given report.
     */
    public function render_report_list($reports, $current_report = null) {
        global $OUTPUT;

        // create links for each report
        foreach ($reports as $index => $report) {
            $reports[$index] = html_writer::link(
                    new moodle_url('/report/uclastats/view.php',
                            array('report' => $index)), $report);
            // if current report is active, bold it
            if ($index == $current_report) {
                $reports[$index] = html_writer::tag('strong', $reports[$index]);
            }
        }

        // Render list contents to HTML
        $content = $OUTPUT->list_block_contents(null, $reports);

        $bc = new block_contents();
        $bc->content = $content;
        $bc->title = get_string('report_list', 'report_uclastats');
        $bc->attributes = array('class' => 'block uclastats-report-list');

        echo $OUTPUT->block($bc, BLOCK_POS_RIGHT);
    }
}