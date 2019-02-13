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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/extendedinfo/locallib.php');

// Allow admins only
require_login();
require_capability('moodle/site:config', context_system::instance());

$pluginname = get_string('pluginname', 'local_extendedinfo');

$instance = optional_param('instance', '', PARAM_TEXT);
$cid = optional_param('cid', 0, PARAM_INT);
$catid = optional_param('catid', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);

$urlparams = [ 'instance' => $instance, 'cid' => $cid, 'catid' => $catid ];

$baseurl = new moodle_url('/local/extendedinfo/manage.php', $urlparams);
$baseurl_noparams = new moodle_url('/local/extendedinfo/manage.php');

// Make sure query parameters are valid
local_extendedinfo_check_query_params($urlparams, $baseurl_noparams);

// Get display strings for instance based on query parameter
$strcat = $strcourse = $strmod = '';
$strcat_plural = $strcourse_plural = $strmod_plural = '';

$strcat = get_string('category');
$strcat_plural = get_string('categories');

$strcourse = get_string('course');
$strcourse_plural = get_string('courses');

$strmod = get_string('activitymodule');
$strmod_plural = get_string('activitymodules');

$strinstance = '';
if ($instance === 'category') {
    $strinstance = $strcat;
}
else if ($instance === 'course') {
    $strinstance = $strcourse;
}
else if ($instance === 'module') {
    $strinstance = $strmod;
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($baseurl);
$PAGE->set_pagetype('admin-extendedinfo');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_title(format_string($SITE->fullname) . ': ' . $pluginname);

// Process any deletions
if ($instance && $deleteid && confirm_sesskey()) {
    $DB->delete_records('local_extendedinfo', [ 'instance' => $instance, 'contextinstanceid' => $deleteid ]);

    // Update cache
    $cache = cache::make('local_extendedinfo', 'extendedinfovars');
    $cache->delete($instance . '/' . $deleteid);

    redirect($baseurl, get_string('delete_extended_info', 'local_extendedinfo', [ 'instance' => $strinstance, 'id' => $deleteid ]));
}

// Add plugin URL to navbar (breadcrumb)
$pluginurl = new moodle_url($CFG->wwwroot . '/admin/category.php', [ 'category' => 'extendedinfo' ]);
$PAGE->navbar->add($pluginname, $pluginurl);

// Setup table
$table = new flexible_table('local-extendedinfo-manage-display');

$table->define_columns([ 'col_contextinstanceid', 'col_name', 'col_actions' ]);
$table->define_headers([ get_string('idnumber'), get_string('name'), get_string('actions') ]);
$table->define_baseurl($baseurl);
$table->sortable(true);
$table->no_sorting('col_actions'); // Actions can't be sorted
$table->maxsortkeys = 1; // Only allow sorting by one column at a time

$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'local-extendedinfo-manage-display');
$table->set_attribute('class', 'generaltable generalbox');
$table->column_class('col_contextinstanceid', 'contextinstanceid');
$table->column_class('col_name', 'name');
$table->column_class('col_actions', 'actions');

$table->setup();

// Rows will be stored in a 2D array first so we can sort before outputting
$table_array = [];

/*
 * Categories
 */
if ($instance === 'category') {
    $PAGE->navbar->add($strcat_plural);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strcat_plural);

    // Get a recordset of all categories
    $sql = "SELECT id, name FROM {course_categories}";
    $cats = $DB->get_recordset_sql($sql);

    // Get a recordset of categories with extended info
    $records = $DB->get_recordset('local_extendedinfo', [ 'instance' => $instance ] );
    $catswithinfo = [];
    if ($records->valid()) {
        foreach ($records as $record) {
            $catswithinfo[] = $record->contextinstanceid;
        }
    }
    $records->close(); // IMPORTANT

    if ($cats->valid()) {
        foreach ($cats as $cat) {
            // Prepare action icons
            $editurl = new moodle_url('/local/extendedinfo/edit.php', [ 'instance' => $instance, 'contextinstanceid' => $cat->id ]);
            $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

            $courseurl = new moodle_url($baseurl_noparams, [ 'instance' => 'course', 'catid' => $cat->id ]);
            $courseaction = $OUTPUT->action_icon($courseurl, new pix_icon('t/grades', $strcourse_plural));

            $deleteurl = new moodle_url('/local/extendedinfo/manage.php', [ 'instance' => $instance, 'deleteid' => $cat->id, 'sesskey' => sesskey() ]);
            $deleteicon = new pix_icon('t/delete', get_string('delete'));
            $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('confirm_delete', 'local_extendedinfo', [ 'instance' => $strcat, 'id' => $cat->id ])));

            // Prepare table row for this category
            $row = [];
            $row['col_contextinstanceid'] = $cat->id;

            $icons = $editaction . ' ' . $courseaction;

            // Link the name to its category
            $catlink = new moodle_url($CFG->wwwroot . '/course/index.php', [ 'categoryid' => $cat->id ]);
            $catname = format_string($cat->name);
            $row['sortkey_name'] = trim($catname); // Sort name by this key
            $catname = html_writer::link($catlink, $catname);

            // Bold-italicize the courses that have extended info
            if (in_array($cat->id, $catswithinfo)) {
                $catname = '<b><em>' . $catname . '</em></b>';
                $icons .= ' ' . $deleteaction;
            }
            $row['col_name'] = $catname;

            // Don't forget the action icons
            $row['col_actions'] = $icons;

            $table_array[] = $row;
        }
    }
    else {
        echo '<p><em>' . get_string('no_instances_found', 'local_extendedinfo', $strcat_plural) . '</em></p>';
    }

    $cats->close(); // IMPORTANT
}
/*
 * Courses
 */
else if ($instance === 'course') {
    if ($catid) {
        $PAGE->navbar->add($strcat_plural, new moodle_url($baseurl_noparams, [ 'instance' => 'category' ]));
        $PAGE->navbar->add($strcourse_plural . ' (' . $strcat . ' ID: ' . $catid . ')');
    }
    else {
        $PAGE->navbar->add($strcourse_plural);
    }

    echo $OUTPUT->header();

    if ($catid) {
        echo $OUTPUT->heading($strcourse_plural . ' (' . $strcat . ' ID: ' . $catid . ')');
    }
    else {
        echo $OUTPUT->heading($strcourse_plural);
    }

    // Get array of courses, filtered by category if specified
    if ($catid) {
        $sql = "SELECT c.id, c.fullname FROM {course} c JOIN {course_categories} cc ON c.category = cc.id WHERE cc.id = ?";
        $courses = $DB->get_recordset_sql($sql, [ $catid ]);
        $coursesexist = ($courses->valid()) ? true : false;
    }
    else {
        // Get an array of all courses (exluding "site" course)
        $courses = get_courses();
        $site = get_site();
        if (array_key_exists($site->id, $courses)) {
            unset($courses[$site->id]);
        }
        $coursesexist = (count($courses) > 0) ? true : false;
    }

    // Get a recordset of courses with extended info
    $records = $DB->get_recordset('local_extendedinfo', [ 'instance' => $instance ] );
    $courseswithinfo = [];
    if ($records->valid()) {
        foreach ($records as $record) {
            $courseswithinfo[] = $record->contextinstanceid;
        }
    }
    $records->close(); // IMPORTANT

    if ($coursesexist) {
        foreach ($courses as $course) {
            // Prepare action icons
            $editurl = new moodle_url('/local/extendedinfo/edit.php', [ 'instance' => $instance, 'contextinstanceid' => $course->id, 'catid' => $catid ]);
            $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

            $modurl = new moodle_url($baseurl_noparams, [ 'instance' => 'module', 'cid' => $course->id, 'catid' => $catid ]);
            $modaction = $OUTPUT->action_icon($modurl, new pix_icon('t/grades', $strmod_plural));

            $deleteurl = new moodle_url('/local/extendedinfo/manage.php', [ 'instance' => $instance, 'deleteid' => $course->id, 'catid' => $catid, 'sesskey' => sesskey() ]);
            $deleteicon = new pix_icon('t/delete', get_string('delete'));
            $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('confirm_delete', 'local_extendedinfo', [ 'instance' => $strcourse, 'id' => $course->id ])));

            // Prepare table row for this course
            $row = [];
            $row['col_contextinstanceid'] = $course->id;

            $icons = $editaction . ' ' . $modaction;

            // Link the name to its course
            $courselink = new moodle_url($CFG->wwwroot . '/course/view.php', [ 'id' => $course->id ]);
            $coursefullname = format_string($course->fullname);
            $row['sortkey_name'] = trim($coursefullname); // Sort name by this key
            $coursefullname = html_writer::link($courselink, $coursefullname);

            // Bold-italicize the courses that have extended info
            if (in_array($course->id, $courseswithinfo)) {
                $coursefullname = '<b><em>' . $coursefullname . '</em></b>';
                $icons .= ' ' . $deleteaction;
            }
            $row['col_name'] = $coursefullname;

            // Don't forget the action icons
            $row['col_actions'] = $icons;

            $table_array[] = $row;
        }
    }
    else {
        echo '<p><em>' . get_string('no_instances_found', 'local_extendedinfo', $strcourse_plural) . '</em></p>';
    }

    // We have a recordset, if catid was specified
    if ($catid) {
        $courses->close(); // IMPORTANT
    }
}
/*
 * Modules
 */
else if ($instance === 'module') {
    if ($catid) {
        $PAGE->navbar->add($strcat_plural, new moodle_url($baseurl_noparams, [ 'instance' => 'category' ]));
        $PAGE->navbar->add($strcourse_plural . ' (' . $strcat . ' ID: ' . $catid . ')', new moodle_url($baseurl_noparams, [ 'instance' => 'course', 'catid' => $catid ]));
    }
    else if ($cid) {
        $PAGE->navbar->add($strcourse_plural, new moodle_url($baseurl_noparams, [ 'instance' => 'course' ]));
    }

    if ($cid) {
        $PAGE->navbar->add($strmod_plural . ' (' . $strcourse . ' ID: ' . $cid . ')');
    }
    else {
        $PAGE->navbar->add($strmod_plural);
    }

    echo $OUTPUT->header();

    if ($cid) {
        echo $OUTPUT->heading($strmod_plural . ' (' . $strcourse . ' ID: ' . $cid . ')');
    }
    else {
        echo $OUTPUT->heading($strmod_plural);
    }

    // Get array of modules, filtered by course if specified
    $sql = "SELECT cm.id AS modid, m.name AS modinstance, cm.instance AS instanceid FROM {course_modules} cm JOIN {modules} m ON cm.module = m.id" . ($cid ? ' WHERE cm.course = ?' : '');
    $coursemods = $DB->get_recordset_sql($sql, [ $cid ]);

    // Get a recordset of mods with extended info
    $records = $DB->get_recordset('local_extendedinfo', [ 'instance' => $instance ] );
    $modswithinfo = [];
    if ($records->valid()) {
        foreach ($records as $record) {
            $modswithinfo[] = $record->contextinstanceid;
        }
    }
    $records->close(); // IMPORTANT

    if ($coursemods->valid()) {
        foreach ($coursemods as $coursemod) {
            // Prepare action icons
            $editurl = new moodle_url('/local/extendedinfo/edit.php', [ 'instance' => $instance, 'contextinstanceid' => $coursemod->modid, 'cid' => $cid, 'catid' => $catid ]);
            $editaction = $OUTPUT->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));

            $deleteurl = new moodle_url('/local/extendedinfo/manage.php', [ 'instance' => $instance, 'deleteid' => $coursemod->modid, 'cid' => $cid, 'catid' => $catid, 'sesskey' => sesskey() ]);
            $deleteicon = new pix_icon('t/delete', get_string('delete'));
            $deleteaction = $OUTPUT->action_icon($deleteurl, $deleteicon, new confirm_action(get_string('confirm_delete', 'local_extendedinfo', [ 'instance' => $strmod, 'id' => $coursemod->modid ])));

            // It's more telling with the module name, so grab that from the associated table (e.g. modinstance = page will grab it from {page} table)
            $modrecord = $DB->get_record($coursemod->modinstance, [ 'id' => $coursemod->instanceid ], 'name');

            // Prepare table row for this mod
            $icons = $editaction;

            $row = [];
            $row['col_contextinstanceid'] = $coursemod->modid;

            // Link the name to its module
            $modlink = new moodle_url($CFG->wwwroot . '/mod/' . $coursemod->modinstance . '/view.php', [ 'id' => $coursemod->modid ]);
            $modname = format_string($modrecord->name);
            $row['sortkey_name'] = trim($modname); // Sort name by this key
            $modname = html_writer::link($modlink, $modname);

            // Bold-italicize the courses that have extended info
            if (in_array($coursemod->modid, $modswithinfo)) {
                $modname = '<b><em>' . $modname . '</em></b>';
                $icons .= ' ' . $deleteaction;
            }
            $modname .= ' <small>(' . $coursemod->modinstance .')</small>';
            $row['col_name'] = $modname;

            $row['col_actions'] = $icons;

            $table_array[] = $row;
        }
    }
    else {
        echo '<p><em>' . get_string('no_instances_found', 'local_extendedinfo', $strmod_plural) . '</em></p>';
    }

    $coursemods->close(); // IMPORTANT
}

// Handle column sorting
$sort_columns = $table->get_sort_columns();
if (count($sort_columns)) {
    // There should be only one element since maxsortkeys = 1
    $col = key($sort_columns);
    $sortdir = $sort_columns[$col];

    if ($col === 'col_name') {
        array_multisort(array_column($table_array, 'sortkey_name'), $sortdir, SORT_STRING, $table_array);
    }
    else {
        array_multisort(array_column($table_array, $col), $sortdir, SORT_NUMERIC, $table_array);
    }
}

// Transfer data to table and output it
foreach ($table_array as $row) {
    $row_data = [];

    foreach ($row as $col => $cell) {
        // Skip the name sort key
        if ($col === 'sortkey_name') {
            continue;
        }

        $row_data[] = $cell;
    }

    $table->add_data($row_data);
}

$table->finish_output();

echo $OUTPUT->footer();
