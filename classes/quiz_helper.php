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
 * Helper to interact with quiz instances which use matrix question type (qtype_matrix).
 *
 * @package    report
 * @subpackage matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace report_matrixreport;

use report_matrixreport\pod\quiz_object;

class quiz_helper {

    private $courseid;

    private $quizlist;

    public function __construct(int $courseid) {
        $this->courseid = $courseid;
        $this->quizlist = $this->load_quiz_instances();
    }

    /**
     * @return \cm_info[]
     */
    public function get_quiz_list(): array {
        return $this->quizlist;
    }

    /**
     * @return \cm_info[]
     * @throws \moodle_exception
     */
    private function load_quiz_instances(): array {
        $result = [];
        $modinfo = get_fast_modinfo($this->courseid);
        $sections = $modinfo->get_section_info_all();
        foreach ($sections as $i => $section) {
            if ($section->uservisible && !empty($modinfo->sections[$i])) {
                // Print out section heading or something?
                foreach ($modinfo->sections[$i] as $cmid) {
                    $mod = $modinfo->cms[$cmid];
                    if (empty($mod->uservisible)) {
                        continue;
                    }
                    if ($mod->modname == 'quiz') {
                        $result[] = $mod;
                    }
                }
            }
        }
        return $result;
    }
}
