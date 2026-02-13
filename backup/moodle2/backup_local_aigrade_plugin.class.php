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
 * Backup task for local_aigrade plugin
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup plugin class for local_aigrade - extends assign activity
 */
class backup_local_aigrade_plugin extends backup_local_plugin {

    /**
     * Define the structure for backing up the plugin data
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure() {
        
        // Get the parent element (the assign module).
        $plugin = $this->get_plugin_element();
        
        // Create the plugin wrapper element.
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        
        // Add it to the parent.
        $plugin->add_child($pluginwrapper);
        
        // Define the aigrade config element.
        // Note: We backup the configuration but not the rubric files.
        // Teachers will need to re-upload rubrics after restore.
        $aigradeconfig = new backup_nested_element('aigrade_config', ['id'], [
            'enabled',
            'instructions',
            'instructions_with_rubric',
            'instructions_without_rubric',
            'grade_level',
            'rubricfile',
            'timecreated',
            'timemodified'
        ]);
        
        // Add config to wrapper.
        $pluginwrapper->add_child($aigradeconfig);
        
        // Set source to populate the data - only if this assignment has AI Grade config.
        $aigradeconfig->set_source_table('local_aigrade_config', ['assignmentid' => backup::VAR_ACTIVITYID]);
        
        // Note: Files (rubric) are NOT backed up to avoid restore complexity.
        // Teachers must re-upload rubrics after course restore.
        
        return $plugin;
    }
}
