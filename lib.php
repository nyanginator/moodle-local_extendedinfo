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

/**
 * @package   local_extendedinfo
 * @copyright Nicholas Yang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns JSON-decoded Extended Info for specified instance and ID, grabbing from cache if available there.
 *
 * @param $instance Specify: course, course, or module.
 * @param $contextinstanceid ID instance.
 * @param $textformat int Specify: FORMAT_MOODLE, FORMAT_HTML, FORMAT_PLAIN, FORMAT_WIKI, FORMAT_MARKDOWN, or -1. -1 means to return the raw unformatted value.
 * @return array Array of Extended Info values indexed by their names. Null if info for the instance is not found.
 */
function local_extendedinfo_get($instance, $contextinstanceid, $textformat = FORMAT_HTML) {

    // Check cache. The key is defined as "instance/contextinstanceid".
    $cache = cache::make('local_extendedinfo', 'extendedinfovars');
    $cached = $cache->get($instance . '/' . $contextinstanceid);
    if ($cached) {
        $vars = subvars($cached);
    }
    // If no cached data found (i.e. caches were purged), then go to DB
    else {
        global $DB;
        $record = $DB->get_record('local_extendedinfo', [ 'instance' => $instance, 'contextinstanceid' => $contextinstanceid ]);

        if ($record) {
            // Remember to update cache
            $cache->set($record->instance . '/' . $record->contextinstanceid, $record->vars);

            $vars = subvars($record->vars);
        }
    }

    if (isset($vars)) {
        $vars_array = json_decode($vars, true);

        // Format (and translate) using format_text()
        global $CFG;
        if ($textformat != -1) {
        foreach ($vars_array as $key => $value) {
                $vars_array[$key] = format_text($value, $textformat);
            }
        }

        return $vars_array;
    }

    return null;
}

/**
 * Substitute in your custom variable placeholders.
 *
 * @param $content String to parse.
 * @return $content String with substituted variables.
 */
function subvars($content) {
    global $CFG;

    $subnames = [ 'wwwroot' ];

    for ($x = 0; $x < count($subnames); ++$x) {
        $var = $subnames[$x];

        // Add more if-else cases here to define more variables
        if ($var === 'wwwroot') {
            $subbed_var = $CFG->wwwroot;
        }

        $content = str_replace('[['. $var . ']]', $subbed_var, $content);
    }

    return $content;
}

/**
 * Render a button that links to Extended Info settings page.
 *
 * @return HTML for a button that links to the associated Extended Info page.
 */
function local_extendedinfo_settings_button() {
    if (!is_siteadmin()) {
        return;
    }

    global $CFG, $PAGE;

    $instance = '';

    if ($PAGE->pagelayout === 'coursecategory') {
        $instance = 'category';

        // If no ID, then it's the /course/index.php page (list of categories)
        $contextinstanceid = (is_object($PAGE->category) ? $PAGE->category->id : -1);
    }
    else if ($PAGE->pagelayout === 'course' || $PAGE->pagelayout === 'frontpage') {
        $instance = 'course';
        $contextinstanceid = (is_object($PAGE->course) ? $PAGE->course->id : -1);
    }
    else if ($PAGE->pagelayout === 'incourse') {
        $instance = 'module';
        $contextinstanceid = (is_object($PAGE->cm) ? $PAGE->cm->id : false);
    }
    else if ($PAGE->pagelayout === 'mydashboard') {
        // Dashboard has no ID
        $instance = 'dashboard';
        $contextinstanceid = -1; // -1 allows $contextinstanceid to be true to create link below
    }

    if ($instance !== '' && $contextinstanceid && $PAGE->pagelayout !== 'admin') {
        $eiurl = new moodle_url($CFG->wwwroot . '/local/extendedinfo/edit.php', [ 'instance' => $instance, 'contextinstanceid' => $contextinstanceid, 'returnurl' => $PAGE->url ]);

        return '<div class="extendedinfo-btn""><a class="btn btn-primary" href="' . $eiurl . '"><i class="fa fa-list-alt"></i> ' . get_string('pluginname', 'local_extendedinfo') . '</a></div>';
    }

    return '';
}
