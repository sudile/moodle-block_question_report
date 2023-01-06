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

use block_question_report\pod\matrix_row;
use block_question_report\pod\result_entry;
use qtype_matrix_question;
use question_engine;

class util {
    private static $attempts = [];

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

    public static function get_quiz_users(int $quizid): array {
        global $DB;
        return array_values($DB->get_fieldset_sql('SELECT userid FROM {quiz_attempts} WHERE quiz = ? GROUP BY userid',
            [$quizid]));
    }


    public static function average_users_attempts(int $quizid, array $userids): array {
        $map = [];
        $rowmap = [];
        foreach ($userids as $userid) {
            $res = self::average_user_attempts($quizid, $userid);
            foreach ($res['map'] as $questionid => $fraction) {
                if (!isset($map[$questionid])) {
                    $map[$questionid] = [];
                }
                $map[$questionid][] = $fraction;
            }
            foreach ($res['rowmap'] as $rowid => $fraction) {
                if (!isset($rowmap[$rowid])) {
                    $rowmap[$rowid] = [];
                }
                $rowmap[$rowid][] = $fraction;
            }
        }
        $result = ['map' => [], 'rowmap' => []];
        foreach ($map as $questionid => $fractions) {
            $result['map'][$questionid] = array_sum($fractions) / count($fractions);
        }
        foreach ($rowmap as $rowid => $fractions) {
            $result['rowmap'][$rowid] = array_sum($fractions) / count($fractions);
        }
        return $result;
    }

    /**
     * @param int $quizid
     * @param int $userid
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private static function average_user_attempts(int $quizid, int $userid): array {
        $attempts = array_values(quiz_get_user_attempts([$quizid], $userid));
        if (count($attempts) === 0) {
            return [];
        }
        $map = [];
        $rowmap = [];
        foreach ($attempts as $attemptobj) {
            $attempt = util::load_attempt($quizid, $attemptobj->uniqueid);
            foreach ($attempt as $result) {
                if (!isset($map[$result->questionid])) {
                    $map[$result->questionid] = [];
                }
                $map[$result->questionid][] = $result->fraction;
                foreach ($result->matrixrows as $row) {
                    if (!isset($rowmap[$row->id])) {
                        $rowmap[$row->id] = [];
                    }
                    $rowmap[$row->id][] = $row->fraction;
                }
            }
        }
        $result = ['map' => [], 'rowmap' => []];
        foreach ($map as $questionid => $fractions) {
            $result['map'][$questionid] = array_sum($fractions) / count($fractions);
        }
        foreach ($rowmap as $rowid => $fractions) {
            $result['rowmap'][$rowid] = array_sum($fractions) / count($fractions);
        }
        return $result;
    }

    /**
     * @param int $quizid
     * @param int $uniqueid
     * @return result_entry[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function load_attempt(int $quizid, int $uniqueid): array {
        global $CFG;
        if (isset(self::$attempts[$quizid . '-' . $uniqueid])) {
            return self::$attempts[$quizid . '-' . $uniqueid];
        }
        $x = question_engine::load_questions_usage_by_activity($uniqueid);
        $result = [];
        foreach ($x->get_slots() as $slot) {
            $questionattempt = $x->get_question_attempt($slot);
            $question = $questionattempt->get_question();
            $subpoints = [];
            if ($question->get_type_name() == 'matrix') {
                require_once($CFG->dirroot . '/question/type/matrix/question.php');
                if ($question instanceof qtype_matrix_question) {
                    $grading = $question->grading();
                    $data = $questionattempt->get_steps_with_submitted_response_iterator()->current()->get_all_data();
                    foreach ($question->rows as $row) {
                        $fraction = $grading->grade_row($question, $row, $data);
                        $subpoints[] = new matrix_row(
                            $row->id,
                            $fraction,
                            $row->shorttext,
                            util::feedback_for_grade($quizid, $fraction)
                        );
                    }
                }
            }
            $fraction = $questionattempt->get_fraction();
            if ($fraction === null) {
                continue;
            }
            $result[] = new result_entry(
                $slot,
                $fraction,
                $question->name,
                util::feedback_for_grade($quizid, $fraction),
                $question->id,
                $subpoints,
            );
        }
        self::$attempts[$quizid . '-' . $uniqueid] = $result;
        return $result;
    }
}
