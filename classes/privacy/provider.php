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
 * Privacy Subsystem implementation for local_aigrade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aigrade\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for local_aigrade.
 */
class provider implements
    \core_privacy\local\metadata\provider {
    
    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        
        // Data sent to external AI providers
        $collection->add_external_location_link('ai_provider', [
            'submissiontext' => 'privacy:metadata:ai_provider:submissiontext',
            'assignmentname' => 'privacy:metadata:ai_provider:assignmentname',
            'assignmentinstructions' => 'privacy:metadata:ai_provider:assignmentinstructions',
            'rubric' => 'privacy:metadata:ai_provider:rubric',
            'gradelevel' => 'privacy:metadata:ai_provider:gradelevel',
        ], 'privacy:metadata:ai_provider');

        return $collection;
    }
}
