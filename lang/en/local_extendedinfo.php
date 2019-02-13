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

$string['pluginname'] = 'Extended Info';
$string['privacy:metadata'] = 'The Extended Info local plugin does not store any personal data.';

// Cron
$string['cron_name'] = 'Delete Extended Info of deleted cats/courses/mods';
$string['cron_cleaningup'] = 'Cleaning up Extended Info of deleted cats/courses/modules ...';
$string['cron_removing'] = 'Removing Extended Info with ID';
$string['cron_removing_cachekey'] = 'Removing cache key';
$string['cron_nothing_to_delete'] = 'No Extended Info to delete!';

// Editing
$string['add_extended_info'] = 'Add Extended Info';
$string['edit_extended_info'] = 'Edit Extended Info';
$string['saved_extended_info'] = 'Saved Extended Info for {$a->instance}';
$string['value'] = 'Value';
$string['add_variable'] = 'Add variable';
$string['remove_variable'] = 'Remove variable';
$string['duplicate_variable_name'] = 'Duplicate variable names not allowed.';
$string['op_update'] = 'update';
$string['op_adding'] = 'adding';
$string['cancelled_op'] = 'Cancelled {$a->op} of Extended Info for {$a->instance}';

// Managing
$string['delete_extended_info'] = 'Deleted Extended Info for {$a->instance} ID: {$a->id}.';
$string['confirm_delete'] = 'Are you sure you want to delete Extended Info for {$a->instance} ID: {$a->id}?';
$string['invalid_param'] = 'Invalid/missing URL query parameter: {$a}';
$string['invalid_instance'] = 'Invalid {$a}.';
$string['edit_instance_extended_info'] = 'Edit {$a} Extended Info';
$string['no_instances_found'] = 'No {$a} found!';
