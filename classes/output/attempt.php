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
 * @package    block_question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report\output;


use block_question_report\pod\result_entry;
use core\chart_bar;
use core\chart_series;
use moodle_exception;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

class attempt implements renderable, templatable {

    private $attempt = null;
    private $attempts = [];
    private $course = null;
    private $cm = null;
    /**
     * @var result_entry[]
     */
    private $result = [];
    private $averageresult = [];

    public function set_current_attempt(object $attempt): void {
        $this->attempt = $attempt;
    }

    public function get_current_attempt(): ?object {
        return $this->attempt;
    }

    public function set_attempts(array $attempts): void {
        $this->attempts = $attempts;
    }

    public function set_course(object $course): void {
        $this->course = $course;
    }

    public function set_cm(object $cm): void {
        $this->cm = $cm;
    }

    /**
     * @param result_entry[] $result
     * @return void
     */
    public function set_result(array $result): void {
        $this->result = $result;
    }

    /**
     * @param array $result
     * @return void
     */
    public function set_averageresult(array $result): void {
        $this->averageresult = $result;
    }

    /**
     * @throws moodle_exception
     */
    public function export_for_template(renderer_base $output): array {
        $data = [
            'quizname' => $this->cm->name,
            'attempts' => [],
            'results' => [],
            'back' => new moodle_url('/blocks/question_report/index.php', ['id' => $this->course->id])
        ];
        foreach ($this->attempts as $attempt) {
            $data['attempts'][] = [
                'id' => $attempt->attempt,
                'url' => new moodle_url('/blocks/question_report/index.php',
                    ['id' => $this->course->id, 'cmid' => $this->cm->id, 'attempt' => $attempt->id]),
                'date' => usertime($attempt->timefinish),
                'active' => $attempt->id == $this->attempt->id
            ];
        }
        $rowmetrics = [];
        $rowgroupmetrics = [];
        foreach ($this->result as $result) {
            $output = [];
            foreach ($result->matrixrows as $matrixrow) {
                $rowmetrics[$matrixrow->name][] = $matrixrow->fraction;
                $rowgroupmetrics[$matrixrow->name][] = $this->averageresult['rowmap'][$matrixrow->id];
                $output[] = [
                    'name' => $matrixrow->name,
                    'color' => $this->make_color(1 - $matrixrow->fraction),
                    'percentage' => $matrixrow->fraction * 100,
                    'fraction' => round($matrixrow->fraction * 100, 2) . '%',
                    'avg' => $this->averageresult['rowmap'][$matrixrow->id] * 100,
                    'avgcolor' => $this->make_color(1 - $this->averageresult['rowmap'][$matrixrow->id])
                ];
            }
            if ($result->fraction >= 1) {
                $result->fraction = 1;
            }
            $data['results'][] = [
                'id' => $result->id,
                'name' => $result->name,
                'feedback' => $result->feedback,
                'subpoints' => $output,
                'fraction' => round($result->fraction * 100, 2) . '%',
                'color' => $this->make_color(1 - $result->fraction),
                'percentage' => $result->fraction * 100,
                'avg' => $this->averageresult['map'][$result->questionid] * 100,
                'avgcolor' => $this->make_color(1 - $this->averageresult['map'][$result->questionid])
            ];
        }
        $labels = [];
        $series = [];
        $groupseries = [];
        foreach ($rowmetrics as $label => $rowmetric) {
            $labels[] = $label;
            $series[] = array_sum($rowmetric) / count($rowmetric);
        }
        foreach ($rowgroupmetrics as $rowgroupmetric) {
            $groupseries[] = array_sum($rowgroupmetric) / count($rowgroupmetric);
        }
        $chart = new chart_bar();
        $chart->add_series(new chart_series(get_string('user', 'block_question_report'), $series));
        $chart->add_series(new chart_series(get_string('group', 'block_question_report'), $groupseries));
        $chart->set_labels($labels);
        $chart->set_legend_options(['position' => 'bottom']);  // Change legend position to left side.
        global $OUTPUT;
        $data['chart'] = $OUTPUT->render($chart);
        return $data;
    }

    private function make_color(float $value, float $min = 0, float $max = 0.5): string {
        $ratio = $value;
        if ($min > 0 || $max < 1) {
            if ($value < $min) {
                $ratio = 1;
            } else if ($value > $max) {
                $ratio = 0;
            } else {
                $range = $min - $max;
                $ratio = ($value - $max) / $range;
            }
        }

        $hue = ($ratio * 1.2) / 3.60;
        $rgb = $this->hsl_to_rgb($hue, 1, .5);

        $r = round($rgb['r']);
        $g = round($rgb['g']);
        $b = round($rgb['b']);

        return "rgb($r,$g,$b)";
    }

    private function hsl_to_rgb(float $h, float $s, float $l): array {
        if ($s == 0) {
            $r = $l;
            $g = $l;
            $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue2rgb($p, $q, $h + 1 / 3);
            $g = $this->hue2rgb($p, $q, $h);
            $b = $this->hue2rgb($p, $q, $h - 1 / 3);
        }

        return ['r' => round($r * 255), 'g' => round($g * 255), 'b' => round($b * 255)];
    }

    private function hue2rgb($p, $q, $t): float {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    }
}
