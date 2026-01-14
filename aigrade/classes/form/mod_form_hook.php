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
 * Form hook to properly load rubric files
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aigrade\form;

defined('MOODLE_INTERNAL') || die();

class mod_form_hook {
    
    /**
     * Prepare draft area for rubric files
     *
     * @param int $assignmentid
     * @param int $contextid
     * @return int draft item id
     */
    public static function prepare_rubric_draft_area($assignmentid, $contextid) {
        $draftitemid = file_get_submitted_draft_itemid('aigrade_rubric');
        
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            'local_aigrade',
            'rubric',
            $assignmentid,
            array('subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 10485760)
        );
        
        return $draftitemid;
    }
}
