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
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Specialised restore task for the question_report block
 * (requires encode_content_links in some configdata attrs)
 */
class restore_question_report_block_task extends restore_block_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
    }

    public function get_fileareas(): array {
        return ['content'];
    }

    public function get_configdata_encoded_attributes(): array {
        return ['text']; // We need to encode some attrs in configdata.
    }

    public static function define_decode_contents(): array {

        $contents = [];

        $contents[] = new restore_question_report_block_decode_content('block_instances', 'configdata', 'block_instance');

        return $contents;
    }

    public static function define_decode_rules(): array {
        return [];
    }
}

/**
 * Specialised restore_decode_content provider that unserializes the configdata
 * field, to serve the configdata->text content to the restore_decode_processor
 * packaging it back to its serialized form after process
 */
class restore_question_report_block_decode_content extends restore_decode_content {

    protected $configdata; // Temp storage for unserialized configdata.

    protected function get_iterator() {
        global $DB;

        // Build the SQL dynamically here.
        $fieldslist = 't.' . implode(', t.', $this->fields);
        $sql = "SELECT t.id, $fieldslist
                  FROM {" . $this->tablename . "} t
                  JOIN {backup_ids_temp} b ON b.newitemid = t.id
                 WHERE b.backupid = ?
                   AND b.itemname = ?
                   AND t.blockname = 'question_report'";
        $params = [$this->restoreid, $this->mapping];
        return ($DB->get_recordset_sql($sql, $params));
    }

    protected function preprocess_field($field) {
        $this->configdata = unserialize_object(base64_decode($field));
        return isset($this->configdata->text) ? $this->configdata->text : '';
    }

    protected function postprocess_field($field) {
        $this->configdata->text = $field;
        return base64_encode(serialize($this->configdata));
    }
}
