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
 * Block to display Course module details.
 *
 * @package   block_test_block
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');

/**
 * Test Block.
 * Displays the module id, activity name, date of creation and the competion status(if completed).
 */
class block_test_block extends block_base {

    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_test_block');
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $USER, $DB;

        // login check...
        isloggedin();
        if ($this->content !== null) {
            return $this->content;
        }

        // Current course details
        $course = $this->page->course;

        $this->content = new stdClass;

        // list of modules...
        $modulelist = $DB->get_records_sql("SELECT cm.id, cm.instance, m.name as modname, cm.added AS creation_date
        FROM {modules} m, {course_modules} cm WHERE cm.course = ? AND cm.module = m.id
        AND cm.visible = 1 AND cm.deletioninprogress=0", array($course->id));
        // list of completed modules...
        $sql = "SELECT coursemoduleid FROM {course_modules_completion} WHERE completionstate = 1 AND userid = ? AND coursemoduleid in(
            SELECT cm.id from {course_modules} cm WHERE cm.course = ? AND cm.visible = 1 AND cm.deletioninprogress=0)";
        $completedmodules = $DB->get_records_sql($sql, array($USER->id, $course->id));
        $completedmodules = array_keys($completedmodules);

        $outputstring = "";
        foreach ($modulelist as $cm) {
            $modrecord = $DB->get_record($cm->modname, array('id' => $cm->instance));
            $launchurl = $CFG->wwwroot."/mod/".$cm->modname."/view.php?id=".$cm->id;
            $finallink = "<a class='modulename' href='".$launchurl."'>".$modrecord->name."</a>";
            $outputstring .= "<div>".$cm->id . " - " . $finallink . " - " . date('d-M-Y', $cm->creation_date);
            // if completed add status
            if (in_array($cm->id, $completedmodules)) {
                $outputstring .= " - Completed";
            }
            $outputstring .= "</div>";
        }
        $this->content->text = $outputstring;
        return $this->content;
    }

    /**
     * Available on - course page only
     */
    public function applicable_formats() {
        return array('course' => true);
    }

    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;
        $DB->update_record('block_instances', [
            'id' => $this->instance->id,
            'configdata' => base64_encode(serialize($data)), 'timemodified' => time()
        ]);
    }

    /**
     * Replace the instance's configuration data with those currently in $this->config;
     */
    public function instance_config_commit($nolongerused = false) {
        $this->instance_config_save($this->config);
    }
}
