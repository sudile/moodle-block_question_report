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
 * Used to store modinfo and slot information for qtype_matrix elements.
 *
 * @package    report
 * @subpackage matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace report_matrixreport\pod;

use cm_info;

class quiz_object {

    private $info;
    private $slots;
    private $matrixentries;

    public function __construct(cm_info $info, array $slots) {
        $this->info = $info;
        $this->slots = $slots;
    }

    public function get_name(): string {
        return $this->info->get_name();
    }

    public function get_cmid(): int {
        return $this->info->id;
    }

    public function get_description(): string {
        return $this->info->content;
    }

    /**
     * @return matrix[]
     * @throws \dml_exception
     */
    public function load_matrix_questions(): array {
        global $DB;
        $result = [];
        foreach ($this->slots as $slot) {
            if ($slot->qtype == 'matrix') {
                $record = $DB->get_record('qtype_matrix', ['questionid' => $slot->questionid]);
                $matrix = new matrix($record);
                $matrix->set_cols($DB->get_records('qtype_matrix_cols', ['matrixid' => $record->id]));
                $matrix->set_rows($DB->get_records('qtype_matrix_rows', ['matrixid' => $record->id]));
                $result[] = $matrix;
            }
        }
        return $result;
    }
}
