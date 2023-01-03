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
 * Contains class overview
 *
 * @package    report_matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace report_matrixreport\output;


use renderable;
use renderer_base;
use report_matrixreport\pod\quiz_object;
use templatable;

class attempt implements renderable, templatable {

    private $attempt = null;
    private $attempts = [];
    private $course = null;
    private $cm = null;
    private $result = [];

    public function set_current_attempt(object $attempt): void {
        $this->attempt = $attempt;
    }

    public function get_current_attempt(): ?object {
        return $this->attempt;
    }

    public function set_attempts(array $attempts): void {
        $this->attempts = $attempts;
    }

    public function set_course(object $course) : void {
        $this->course = $course;
    }

    public function set_cm(object $cm) : void {
        $this->cm = $cm;
    }

    public function set_result(array $result) : void {
        $this->result = $result;
    }

    public function export_for_template(renderer_base $output): array {
        $data = [
            'quizname' => $this->cm->name,
            'attempts' => [],
            'results' => $this->result
        ];
        foreach ($this->attempts as $attempt) {
            $data['attempts'][] = [
                'id' => $attempt->attempt,
                'url' => new \moodle_url('/report/matrixreport/index.php',
                    ['id' => $this->course->id, 'cmid' => $this->cm->id, 'attempt' => $attempt->id]),
                'date' => usertime($attempt->timefinish),
                'active' => $attempt->id == $this->attempt->id
            ];
        }
        return $data;
    }
}
