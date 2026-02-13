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
 * Upgrade script for local_aigrade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_aigrade_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025012001) {
        // Define table local_aigrade_config to be created.
        $table = new xmldb_table('local_aigrade_config');

        // Adding fields to table local_aigrade_config.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('instructions', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('rubricfile', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_aigrade_config.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('assignmentid', XMLDB_KEY_FOREIGN, ['assignmentid'], 'assign', ['id']);

        // Adding indexes to table local_aigrade_config.
        $table->add_index('assignmentid_idx', XMLDB_INDEX_NOTUNIQUE, ['assignmentid']);

        // Conditionally launch create table for local_aigrade_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Aigrade savepoint reached.
        upgrade_plugin_savepoint(true, 2025012001, 'local', 'aigrade');
    }

    if ($oldversion < 2025012003) {
        // Define fields to be added to local_aigrade_config.
        $table = new xmldb_table('local_aigrade_config');
        
        // Add instructions_with_rubric field
        $field = new xmldb_field('instructions_with_rubric', XMLDB_TYPE_TEXT, null, null, null, null, null, 'instructions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add instructions_without_rubric field
        $field = new xmldb_field('instructions_without_rubric', XMLDB_TYPE_TEXT, null, null, null, null, null, 'instructions_with_rubric');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Migrate existing instructions to instructions_with_rubric
        $DB->execute("UPDATE {local_aigrade_config} 
                     SET instructions_with_rubric = instructions 
                     WHERE instructions IS NOT NULL AND instructions != '' 
                     AND (instructions_with_rubric IS NULL OR instructions_with_rubric = '')");

        // Aigrade savepoint reached.
        upgrade_plugin_savepoint(true, 2025012003, 'local', 'aigrade');
    }

    if ($oldversion < 2025012004) {
        // Add grade_level field to local_aigrade_config
        $table = new xmldb_table('local_aigrade_config');
        
        $field = new xmldb_field('grade_level', XMLDB_TYPE_CHAR, '10', null, null, null, '9', 'instructions_without_rubric');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Aigrade savepoint reached.
        upgrade_plugin_savepoint(true, 2025012004, 'local', 'aigrade');
    }

    if ($oldversion < 2025012005) {
        // Add AI name setting with default value
        $ai_name = get_config('local_aigrade', 'ai_name');
        if ($ai_name === false) {
            set_config('ai_name', 'AI', 'local_aigrade');
        }

        // Aigrade savepoint reached.
        upgrade_plugin_savepoint(true, 2025012005, 'local', 'aigrade');
    }

    if ($oldversion < 2026012000) {
        // Add grading_strictness field to local_aigrade_config
        $table = new xmldb_table('local_aigrade_config');
        
        // Field: grading_strictness - allows teachers to override grade-level strictness
        // Default 'standard' means use the grade-level appropriate strictness
        $field = new xmldb_field('grading_strictness', XMLDB_TYPE_CHAR, '20', null, null, null, 'standard', 'grade_level');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Aigrade savepoint reached.
        upgrade_plugin_savepoint(true, 2026012000, 'local', 'aigrade');
    }

    return true;
}
