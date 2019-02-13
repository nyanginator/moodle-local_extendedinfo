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
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/local/extendedinfo/locallib.php');

// Allow admins only
require_login();
require_capability('moodle/site:config', context_system::instance());

$pluginname = get_string('pluginname', 'local_extendedinfo');

$instance = optional_param('instance', '', PARAM_TEXT);
$contextinstanceid = optional_param('contextinstanceid', 0, PARAM_INT);
$cid = optional_param('cid', 0, PARAM_INT);
$catid = optional_param('catid', 0, PARAM_INT);
$numvars = optional_param('numvars', 0, PARAM_INT);

// If coming here thru Extended Info button, return URL will be set
$returnurl = optional_param('returnurl', '', PARAM_TEXT);

$urlparams = [ 'instance' => $instance, 'contextinstanceid' => $contextinstanceid, 'cid' => $cid, 'catid' => $catid, 'numvars' => $numvars, 'returnurl' => $returnurl ];

$pluginurl = new moodle_url($CFG->wwwroot . '/admin/category.php', [ 'category' => 'extendedinfo' ]);
$editurl = new moodle_url('/local/extendedinfo/edit.php', $urlparams);

$manageurl = new moodle_url('/local/extendedinfo/manage.php', $urlparams);
$manageurl_noparams = new moodle_url('/local/extendedinfo/manage.php');

// Make sure query parameters are valid
local_extendedinfo_check_query_params($urlparams, $manageurl_noparams);

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

    // List of Categories edit link is on plugin page
    if ($contextinstanceid == -1) {
        $strinstance = get_string('frontpagecategorynames');
        $manageurl = $pluginurl;
    }
}
else if ($instance === 'course') {
    $strinstance = $strcourse;
}
else if ($instance === 'module') {
    $strinstance = $strmod;
}
else if ($instance === 'dashboard') {
    $strinstance = get_string('myhome');

    // Dashboard edit link is on plugin page
    $manageurl = $pluginurl;
}

// If there's a return URL, we should return there instead of manage.php
if ($returnurl !== '') {
    $manageurl = $returnurl;
}

// Make sure instance ID is valid. -1 is allowed in special cases: Dashboard and List of Categories (i.e. /course/index.php)
if ($contextinstanceid == 0 || $contextinstanceid < -1) {
    redirect($manageurl, get_string('invalid_param', 'local_extendedinfo', 'contextinstanceid'), null, \core\output\notification::NOTIFY_ERROR);
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url($editurl);
$PAGE->set_pagetype('admin-extendedinfo');
$PAGE->set_pagelayout('admin');
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_title(format_string($SITE->fullname) . ': ' . $pluginname);

class extendedinfo_edit_form extends moodleform {
    protected $isadding;
    protected $numvars;
    protected $editurl;

    protected $instance;
    protected $contextinstanceid;
    protected $vars;

    function __construct($actionurl, $isadding, $numvars, $editurl, $instance, $contextinstanceid) {
        $this->isadding = $isadding;
        $this->numvars = $numvars;
        $this->editurl = $editurl;
        $this->instance = $instance;
        $this->contextinstanceid = $contextinstanceid;
        parent::__construct($actionurl);
    }

    function definition() {
        $mform =& $this->_form;

        for ($i = 0; $i < $this->numvars; ++$i) {

            $mform->addElement('text', 'varname-' . $i, get_string('name'), [ 'maxlength' => 255 ]);
            $mform->setType('varname-' . $i, PARAM_TEXT);
            $mform->addRule('varname-' . $i, null, 'required');

            $mform->addElement('textarea', 'varvalue-' . $i, get_string('value', 'local_extendedinfo'), 'wrap="virtual" rows="1" cols="80"');
            
            // Important to use PARAM_RAW here, otherwise HTML tags get stripped, see /lib/moodlelib.php definitions
            $mform->setType('varvalue-' . $i, PARAM_RAW);

            $mform->addElement('checkbox', 'deletevar-' . $i, get_string('delete'));
            $mform->setDefault('deletevar-' . $i, 0);

            $mform->addElement('html', '<hr>');
        }

        // Get query params in an array (false = don't escape characters)
        parse_str($this->editurl->get_query_string(false), $query);

        // Add Variable button
        $query['numvars'] = $this->numvars+1;
        $mform->addElement('html', '<div class="form-group">');
        $mform->addElement('html', '<a class="btn btn-default" href="' . (new moodle_url($this->editurl, $query)) . '">'. get_string('add_variable', 'local_extendedinfo') . '</a>');

        // Remove Variable button
        $query['numvars'] = ($this->numvars == 1) ? $this->numvars : $this->numvars-1; // Must have at least one variable
        $mform->addElement('html', ' <a class="btn btn-default" href="' . (new moodle_url($this->editurl, $query)) . '">' . get_string('remove_variable', 'local_extendedinfo') . '</a>');
        $mform->addElement('html', '</div>');

        // Submit button
        $submitlabel = null; // Default of null results in "Save changes"
        if ($this->isadding) {
            $submitlabel = get_string('add_extended_info', 'local_extendedinfo');
        }

        $this->add_action_buttons(true, $submitlabel);
    }

    function definition_after_data(){
        $mform =& $this->_form;
    }

    // Get data from form, validate it, and save to this form object
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $mform =& $this->_form;

        $i = 0;
        $vars = [];
        $todelete = [];
        while (isset($data['varname-' . $i]) && isset($data['varvalue-' . $i])) {
            $data['varname-' . $i] = trim($data['varname-' . $i]);

            preg_match('/^[a-zA-Z0-9\-\.]+$/', $data['varname-' . $i], $matches);
            if (count($matches) != 1 || strpos($data['varname-' . $i], ' ') !== false) {
                $errors['varname-' . $i] = get_string('alphanumerical');
            }
            else if (isset($vars[$data['varname-' . $i]])) {
                $errors['varname-' . $i] = get_string('duplicate_variable_name', 'local_extendedinfo');
            }
            else {
                $vars[$data['varname-' . $i]] = $data['varvalue-' . $i];
            }

            if ($mform->getElementValue('deletevar-' . $i)) {
                $todelete[] = $data['varname-' . $i];
            }

            ++$i;
        }

        // Delete vars that had "Delete" checkbox ticked
        if (count($errors) == 0) {
            foreach ($todelete as $td) {
                unset($vars[$td]);
            }

            // Delete entire DB record if it's the last one
            if (count($vars) == 0) {
                global $DB;
                $DB->delete_records('local_extendedinfo', [ 'instance' => $this->instance, 'contextinstanceid' => $this->contextinstanceid ]);

                // Update cache
                $cache = cache::make('local_extendedinfo', 'extendedinfovars');
                $cache->delete($this->instance . '/' . $this->contextinstanceid);
            }
        }

        $this->vars = $vars;

        return $errors;
    }

    // Copies data from this form (object) and prepares it for saving to DB
    function get_data() {
        $data = parent::get_data();

        if ($data) {
            $data->instance = $this->instance;
            $data->contextinstanceid = $this->contextinstanceid;
            ksort($this->vars); // Always in alphabetical order
            $data->vars = json_encode($this->vars);
        }

        return $data;
    }
}

$record = $DB->get_record('local_extendedinfo', [ 'instance' => $instance, 'contextinstanceid' => $contextinstanceid ]);

$olddata = [];
if ($record) {
    $isadding = false;
    $vars = json_decode($record->vars);
    $i = 0;
    foreach ($vars as $key => $value) {
        $olddata['varname-' . $i] = $key;
        $olddata['varvalue-' . $i] = $value;
        ++$i;
    }

    // If we've added/removed vars (haven't saved yet), count may be different
    $numvars = ($numvars != 0) ? $numvars : $i;
}
else {
    $isadding = true;
    $record = new stdClass;
    $numvars = ($numvars != 0) ? $numvars : 1;
}

$mform = new extendedinfo_edit_form($PAGE->url, $isadding, $numvars, $editurl, $instance, $contextinstanceid);

if ($record) {
    $mform->set_data($olddata);
}

// Cancelling the edit
if ($mform->is_cancelled()) {
    $op = get_string('op_update', 'local_extendedinfo');
    if ($isadding) {
        $op = get_string('op_adding', 'local_extendedinfo');
    }
    redirect($manageurl, get_string('cancelled_op', 'local_extendedinfo', [ 'op' => $op, 'instance' => $strinstance ]) . ($contextinstanceid != -1 ? ' ID: ' . $contextinstanceid : '') . '.', null, \core\output\notification::NOTIFY_WARNING);

}
// Saving the edit
else if ($data = $mform->get_data()) {
    if ($isadding) {
        $DB->insert_record('local_extendedinfo', $data);
    } else {
        $data->id = $record->id;
        $DB->update_record('local_extendedinfo', $data);
    }

    // Update cache every time the form is saved (and DB is updated)
    $cache = cache::make('local_extendedinfo', 'extendedinfovars');
    $cache->set($data->instance . '/' . $data->contextinstanceid, $data->vars);
    
    redirect($manageurl, get_string('saved_extended_info', 'local_extendedinfo', [ 'instance' => $strinstance ]) . ($contextinstanceid != -1 ? ' ID: ' . $contextinstanceid : '') . '.', null, \core\output\notification::NOTIFY_SUCCESS);
}
// Displaying the edit form
else {
    if ($isadding) {
        $strtitle = get_string('add_extended_info', 'local_extendedinfo');
    } else {
        $strtitle = get_string('edit_extended_info', 'local_extendedinfo');
    }

    $strtitle .= ' (' . $strinstance . ($contextinstanceid != -1 ? ': ' . $contextinstanceid . ')' : ')');

    // Get readable name
    if ($instance === 'category') {
        // On /course/index.php, it just lists all categories
        if ($contextinstanceid != -1) {
            $record = $DB->get_record('course_categories', [ 'id' => $contextinstanceid ]);
            $instancename = format_string($record->name);
        }
    }
    else if ($instance === 'course') {
        $record = $DB->get_record('course', [ 'id' => $contextinstanceid ]);
        $instancename = format_string($record->fullname);
    }
    else if ($instance === 'module') {
        $sql = "SELECT cm.id AS modid, m.name AS modinstance, cm.instance AS instanceid FROM {course_modules} cm JOIN {modules} m ON cm.module = m.id WHERE cm.id = ?";
        $record = $DB->get_record_sql($sql, [ $contextinstanceid ]);

        $instancename = format_string($DB->get_record($record->modinstance, [ 'id' => $record->instanceid ], 'name')->name);
    }
    
    // Add plugin URL to navbar breadcrumbs
    $PAGE->navbar->add($pluginname, $pluginurl);

    // Set navbar breadcrumbs based on query parameters
    if ($instance === 'category') {
        $PAGE->navbar->add($strcat_plural, new moodle_url($manageurl_noparams, ['instance' => 'category' ]));
    }
    else if ($instance === 'course' || $instance === 'module') {
        if ($catid) {
            $PAGE->navbar->add($strcat_plural, new moodle_url($manageurl_noparams, [ 'instance' => 'category' ]));
            $PAGE->navbar->add($strcourse_plural . ' (' . $strcat . ' ID: ' . $catid . ')', new moodle_url($manageurl_noparams, [ 'instance' => 'course', 'catid' => $catid ]));
        }
        else if ($instance === 'course' || ($instance === 'module' && $cid)) {
            $PAGE->navbar->add($strcourse_plural, new moodle_url($manageurl_noparams, [ 'instance' => 'course' ]));
        }

        if ($instance === 'module') {
            if ($cid) {
                $PAGE->navbar->add($strmod_plural . ' (' . $strcourse . ' ID: ' . $cid . ')', new moodle_url($manageurl_noparams, [ 'instance' => 'module', 'catid' => $catid, 'cid' => $cid ]));
            }
            else {
                $PAGE->navbar->add($strmod_plural, new moodle_url($manageurl_noparams, [ 'instance' => 'module', 'catid' => $catid ]));
            }
        }
    }

    // Add this page to navbar breadcrumb (no link)
    $PAGE->navbar->add($strtitle);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strtitle);

    if (isset($instancename)) {
        echo $OUTPUT->heading($instancename, 4);
    }

    $mform->display();

    echo $OUTPUT->footer();
}
