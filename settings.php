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

defined('MOODLE_INTERNAL') || die;

if (is_siteadmin()) {
    $ADMIN->add('localplugins', new admin_category('extendedinfo', new lang_string('pluginname','local_extendedinfo')));
    
    $ADMIN->add('extendedinfo', new admin_externalpage('local_extendedinfo_categories', get_string('view') . ': ' . get_string('categories'), $CFG->wwwroot . '/local/extendedinfo/manage.php?instance=category'));
    $ADMIN->add('extendedinfo', new admin_externalpage('local_extendedinfo_courses', get_string('view') . ': ' . get_string('courses'), $CFG->wwwroot . '/local/extendedinfo/manage.php?instance=course'));
    $ADMIN->add('extendedinfo', new admin_externalpage('local_extendedinfo_modules', get_string('view') . ': ' . get_string('activitymodules'), $CFG->wwwroot . '/local/extendedinfo/manage.php?instance=module'));

    $ADMIN->add('extendedinfo', new admin_externalpage('local_extendedinfo_dashboard', get_string('myhome'), $CFG->wwwroot . '/local/extendedinfo/edit.php?instance=dashboard&contextinstanceid=-1'));
    $ADMIN->add('extendedinfo', new admin_externalpage('local_extendedinfo_categorynames', get_string('frontpagecategorynames'), $CFG->wwwroot . '/local/extendedinfo/edit.php?instance=category&contextinstanceid=-1'));
}
