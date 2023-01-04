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
 * Helper class
 *
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report;

class util {

    /**
     * Load the feedback from the question to evaluate partial steps or use the default set.
     *
     * @param int   $quizid
     * @param float $grade
     * @return string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function feedback_for_grade(int $quizid, float $grade): string {
        global $DB;
        $grade = max($grade * 10, 0);
        $feedback = $DB->get_record_select('quiz_feedback',
            'quizid = ? AND mingrade <= ? AND ? < maxgrade',
            [$quizid, $grade, $grade]);
        if ($feedback !== false) {
            return strip_tags($feedback->feedbacktext);
        }
        // Use defaults.
        if ($grade <= 0.5) {
            return get_string('default_below_50', 'block_question_report');
        } else if ($grade <= 0.575) {
            return get_string('default_below_57', 'block_question_report');
        } else if ($grade <= 0.725) {
            return get_string('default_below_72', 'block_question_report');
        } else if ($grade <= 0.875) {
            return get_string('default_below_87', 'block_question_report');
        }
        return get_string('default_below_100', 'block_question_report');
    }
}
