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
 * External services for AI Grade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_aigrade_grade_bulk' => [
        'classname'   => 'local_aigrade\external\grade_bulk',
        'methodname'  => 'execute',
        'description' => 'Grade all ungraded submissions with AI',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/aigrade:grade',
    ],
    'local_aigrade_grade_single' => [
        'classname'   => 'local_aigrade\external\grade_single',
        'methodname'  => 'execute',
        'description' => 'Grade a single submission with AI',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'local/aigrade:grade',
    ],
];
