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
 * AI Grading interface for bulk grading - AJAX endpoint
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_sesskey();
require_capability('local/aigrade:grade', $context);

$assignment = new assign($context, $cm, $course);

// Check if AI grading is enabled for this assignment
$aiconfig = $DB->get_record('local_aigrade_config', ['assignmentid' => $assignment->get_instance()->id]);

if (!$aiconfig || !$aiconfig->enabled) {
    echo json_encode(['success' => false, 'error' => get_string('aigrade_disabled', 'local_aigrade')]);
    die();
}

// Handle the grading action
if ($action === 'grade') {
    
    $grader = new \local_aigrade\grader($assignment, $context, $aiconfig);
    $result = $grader->grade_submissions();
    
    echo json_encode($result);
    die();
}

echo json_encode(['success' => false, 'error' => get_string('error_invalid_action', 'local_aigrade')]);
