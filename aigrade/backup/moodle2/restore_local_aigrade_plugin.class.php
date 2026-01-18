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
 * Restore task for local_aigrade plugin
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore plugin class for local_aigrade
 */
class restore_local_aigrade_plugin extends restore_local_plugin {

    /**
     * Define the structure for restoring the plugin data
     *
     * @return array of restore_path_element
     */
    protected function define_module_plugin_structure() {
        
        $paths = [];
        
        // Define the path for the aigrade config element.
        $paths[] = new restore_path_element('local_aigrade_config', 
            $this->get_pathfor('/aigrade_config'));
        
        return $paths;
    }
    
    /**
     * Process the aigrade config element
     *
     * @param array $data The data from the backup file
     */
    public function process_local_aigrade_config($data) {
        global $DB;
        
        $data = (object)$data;
        $oldid = $data->id;
        
        // Store data temporarily - we'll process it after the module is fully restored
        if (!isset($this->aigrade_configs)) {
            $this->aigrade_configs = [];
        }
        
        $this->aigrade_configs[] = $data;
        
        //error_log('AI Grade Restore Debug - Stored config for later processing, old ID: ' . $oldid);
    }
    
    /**
     * Process after execution - when the module is fully created
     */
    public function after_restore_module() {
        global $DB;
        
        if (empty($this->aigrade_configs)) {
            return;
        }
        
        // Get the course module ID
        $cmid = $this->task->get_moduleid();
        
        //error_log('AI Grade Restore Debug - after_execute - CM ID: ' . $cmid);
        
        // Get the assignment instance ID from the course module
        $cm = $DB->get_record('course_modules', ['id' => $cmid], '*', MUST_EXIST);
        $newassignmentid = $cm->instance;
        
        //error_log('AI Grade Restore Debug - after_execute - Assignment ID: ' . $newassignmentid);
        
        // Process all stored configs
        foreach ($this->aigrade_configs as $data) {
            $oldid = $data->id;
            $data->assignmentid = $newassignmentid;
            
            // Check if config already exists
            $existing = $DB->get_record('local_aigrade_config', ['assignmentid' => $newassignmentid]);
            
            if ($existing) {
                //error_log('AI Grade Restore Debug - Updating existing record ID: ' . $existing->id);
                $data->id = $existing->id;
                $data->timemodified = time();
                $DB->update_record('local_aigrade_config', $data);
            } else {
                //error_log('AI Grade Restore Debug - Creating new record');
                unset($data->id);
                $data->timecreated = time();
                $data->timemodified = time();
                $data->rubricfile = null;
                
                $newid = $DB->insert_record('local_aigrade_config', $data);
                //error_log('AI Grade Restore Debug - New record ID: ' . $newid);
                $this->set_mapping('local_aigrade_config', $oldid, $newid);
            }
        }
    }
}
