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

namespace local_extendedinfo\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Simple task to run the local_extendedinfo cron.
 */
class extendedinfo_cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cron_name', 'local_extendedinfo');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        $component = $this->get_component();

        mtrace('');
        mtrace('// BEGIN ' . $component);

        global $CFG, $DB;

        // IMPORTANT to note that cron needs to run for deleted cats/courses/modules to actually be removed from system. On subsequent run of this task, THEN the entry in {local_extendedinfo} will be removed.
        mtrace('');
        mtrace('  ' . get_string('cron_cleaningup', 'local_extendedinfo'));

        $count = 0;

        // LEFT JOIN necessary to see if one field is NULL, while other isn't
        $sql = "SELECT lei.id, lei.instance, lei.contextinstanceid FROM {local_extendedinfo} lei LEFT JOIN {course_categories} cc ON lei.contextinstanceid=cc.id AND lei.instance='category' LEFT JOIN {course} c ON lei.contextinstanceid=c.id AND lei.instance='course' LEFT JOIN {course_modules} cm ON lei.contextinstanceid=cm.id AND lei.instance='module' WHERE (cc.id IS NULL AND lei.instance='category') OR (c.id IS NULL AND lei.instance='course') OR (cm.id IS NULL AND lei.instance='module')";

        $rs = $DB->get_recordset_sql($sql);

        if ($rs->valid()) {
            // Cache stores with keys to delete
            $cache = \cache::make('local_extendedinfo', 'extendedinfovars');

            foreach ($rs as $recordtodelete) {
                // Skip over -1 IDs, meaning it's Dashboard or /course/index.php
                if ($recordtodelete->contextinstanceid != -1) {
                    mtrace('  --' . get_string('cron_removing', 'local_extendedinfo') . ' ' . $recordtodelete->id . ' (' . $recordtodelete->instance . ': ' . $recordtodelete->contextinstanceid . ')');
                    $DB->delete_records('local_extendedinfo', [ 'id' => $recordtodelete->id ]);

                    $cachekey = $recordtodelete->instance . '/' . $recordtodelete->contextinstanceid;
                    mtrace('    * ' . get_string('cron_removing_cachekey', 'local_extendedinfo') . ': ' . $cachekey);
                    $cache->delete($cachekey);

                    ++$count;
                }
            }
        }

        if ($count == 0) {
            mtrace('  --' . get_string('cron_nothing_to_delete', 'local_extendedinfo'));
        }

        $rs->close(); // IMPORTANT

        mtrace('');
        mtrace('// END ' . $component);
        mtrace('');
    }
}
