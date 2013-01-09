<?php
/**
 * Strings
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

$string['pluginname'] = 'UCLA stats console';
$string['uclastats:view'] = 'View UCLA stats console cached queries';
$string['uclastats:query'] = 'Run UCLA stats console queries';
$string['uclastats:manage'] = 'Manage UCLA stats console cached queries (delete or lock results)';

$string['parameters'] = 'Parameters: {$a}';
$string['lastran'] = 'Last ran by {$a->who} on {$a->when}';

// report strings
$string['index_welcome'] = 'Please select a report.';
$string['report_list'] = 'Report list';
$string['run_report'] = 'Run report';

// parameter strings
$string['term'] = 'Term';

// cached results strings
$string['cached_results_table'] = 'Cached results';
$string['header_param'] = 'Parameters';
$string['header_results'] = 'Results';
$string['header_lastran'] = 'Last ran';
$string['header_actions'] = 'Actions';
$string['view_results'] = 'View results';

// strings for sites_per_term
$string['sites_per_term'] = 'Sites per term';
$string['sites_per_term_help'] = 'Returns number of Registrar course sites built on the current server.';
$string['site_count'] = 'Site count';

// error strings
$string['nocachedresults'] = 'No cached results found';
$string['invalidterm'] = 'Invalid term';
$string['invalidreport'] = 'Invalid report';
$string['resultnotbelongtoreport'] = 'Requested result does not belong to current report';

// strings for unit testing
$string['param1'] = 'Parameter 1';
$string['param2'] = 'Parameter 2';
$string['result1'] = 'Result 1';
$string['result2'] = 'Result 2';
$string['uclastats_base_mock'] = 'UCLA stats base class';
$string['uclastats_base_mock_help'] = 'Text explaining what report does.';