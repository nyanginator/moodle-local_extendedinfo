<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Helper functions for local_extendedinfo
 *
 * @package    local_extendedinfo
 * @copyright  Nicholas Yang
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Check validity of URL query parameters and redirect in case of errors.
 *
 * @param $params Associative array of query params to check
 * @param $redirecturl URL to redirect to in case of error
 */
function local_extendedinfo_check_query_params($params, $redirecturl) {
    global $CFG, $DB;

    $instance = isset($params['instance']) ? $params['instance'] : '';
    $contextinstanceid = isset($params['contextinstanceid']) ? $params['contextinstanceid'] : 0;
    $cid = isset($params['cid']) ? $params['cid'] : 0;
    $catid = isset($params['catid']) ? $params['catid'] : 0;
    
    // First check that the instance type is valid
    if (!in_array($instance, [ 'category', 'course', 'module', 'dashboard' ])) {
        $pluginurl = new moodle_url($CFG->wwwroot . '/admin/category.php', [ 'category' => 'extendedinfo' ]);
        redirect($pluginurl, get_string('invalid_param', 'local_extendedinfo', 'instance'), null, \core\output\notification::NOTIFY_ERROR);
    }
    // Now check that the instance actually exists in the database
    else {
        $redirect = false;
        $error = '';

        // 'contextinstanceid' will only be set when on edit.php page
        if (isset($params['contextinstanceid'])) {
            if ($contextinstanceid > 0) {
                if ($instance === 'category') {
                    $record = $DB->get_record('course_categories', [ 'id' => $contextinstanceid ]);
                    $error = get_string('category') . ' ID';
                }
                else if ($instance === 'course') {
                    $record = $DB->get_record('course', [ 'id' => $contextinstanceid ]);
                    $error = get_string('course') . ' ID';
                }
                else if ($instance === 'module') {
                    $record = $DB->get_record('course_modules', [ 'id' => $contextinstanceid ]);
                    $error = get_string('activitymodule') . ' ID';
                }

                if (!$record) {
                    $redirect = true;
                    unset($params['contextinstanceid']); // Remove invalid parameter
                }
            }
        }

        // $catid/$cid will have values when the view is filtered down (e.g. courses of a specific category)
        if (isset($params['catid'])) {
            if ($catid > 0) {
                $record = $DB->get_record('course_categories', [ 'id' => $catid ]);
                if (!$record) {
                    $redirect = true;
                    $error = get_string('category');
                    unset($params['catid']); // Remove invalid parameter
                }
            }
        }

        if (isset($params['cid'])) {
            if ($cid > 0) {
                $record = $DB->get_record('course', [ 'id' => $cid ]);

                if (!$record) {
                    $redirect = true;
                    $error = get_string('course');
                    unset($params['cid']); // Remove invalid parameter
                }
            }
        }

        if ($redirect) {
            // Clean up URL
            unset($params['numvars']);
            unset($params['contextinstanceid']);
            unset($params['returnurl']);

            redirect(new moodle_url($redirecturl, $params), get_string('invalid_instance', 'local_extendedinfo', $error), null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}
