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
use coding_exception;
use dml_exception;
use MoodleExcelWorkbook;
use qtype_matrix_question;
use question_engine;
use quiz_attempt;

class util {
    private static $attempts = [];

    /**
     * Load the feedback from the question to evaluate partial steps or use the default set.
     *
     * @param int   $quizid
     * @param float $grade
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function feedback_for_grade(int $quizid, float $grade): string {
        global $DB;
        $grade = max($grade * 10, 0);
        $feedback = $DB->get_record_select('quiz_feedback',
            'quizid = ? AND mingrade <= ? AND ? < maxgrade',
            [$quizid, $grade, $grade]);
        if ($feedback !== false && strip_tags($feedback->feedbacktext) !== "") {
            return strip_tags($feedback->feedbacktext);
        }
        // Use defaults.
        if ($grade <= 5.) {
            return get_string('default_below_50', 'block_question_report');
        } else if ($grade <= 5.75) {
            return get_string('default_below_57', 'block_question_report');
        } else if ($grade <= 7.25) {
            return get_string('default_below_72', 'block_question_report');
        } else if ($grade <= 8.75) {
            return get_string('default_below_87', 'block_question_report');
        }
        return get_string('default_below_100', 'block_question_report');
    }

    /**
     * Get all users who have submitted attempts which are FINISHED or ABANDONED
     *
     * @param int $quizid
     * @return array
     * @throws dml_exception
     */
    public static function get_quiz_users(int $quizid): array {
        global $DB;
        return array_values(
            $DB->get_fieldset_sql(
                'SELECT userid FROM {quiz_attempts} WHERE quiz = :quizid AND state IN (:state1, :state2) GROUP BY userid',
                ['quizid' => $quizid, 'state1' => quiz_attempt::FINISHED, 'state2' => quiz_attempt::ABANDONED]
            )
        );
    }


    /**
     * Average over all attempts over a quiz with a given user list
     *
     * @param int   $quizid
     * @param array $userids
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
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
        return self::avg_array($map, $rowmap);
    }

    /**
     * Average over user attempts, only used by average_users_attempts
     *
     * @param int $quizid
     * @param int $userid
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @see average_users_attempts
     */
    private static function average_user_attempts(int $quizid, int $userid): array {
        $attempts = array_values(quiz_get_user_attempts([$quizid], $userid));
        if (count($attempts) === 0) {
            return ['map' => [], 'rowmap' => []];
        }
        $map = [];
        $rowmap = [];
        foreach ($attempts as $attemptobj) {
            $attempt = self::load_attempt($quizid, $attemptobj->uniqueid);
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
        return self::avg_array($map, $rowmap);
    }

    private static function avg_array(array $map, array $rowmap): array {
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
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function load_attempt(int $quizid, int $uniqueid): array {
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
                if ($question instanceof qtype_matrix_question) {
                    $grading = $question->grading();
                    $data = $questionattempt->get_steps_with_submitted_response_iterator()->current()->get_all_data();
                    foreach ($question->rows as $row) {
                        $fraction = $grading->grade_row($question, $row, $data);
                        $subpoints[] = new matrix_row(
                            $row->id,
                            $fraction,
                            $row->shorttext,
                            self::feedback_for_grade($quizid, $fraction)
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
                self::feedback_for_grade($quizid, $fraction),
                $question->id,
                $subpoints,
            );
        }
        self::$attempts[$quizid . '-' . $uniqueid] = $result;
        return $result;
    }


    public static function load_attempt_feedback(int $uniqueid): array {
        $x = question_engine::load_questions_usage_by_activity($uniqueid);
        $result = [];
        $labels = [];
        foreach ($x->get_slots() as $slot) {
            $questionattempt = $x->get_question_attempt($slot);
            $question = $questionattempt->get_question();
            $behaviour = $questionattempt->get_behaviour();
            if ($behaviour !== null && $behaviour->get_name() == 'studentfeedbackdeferred') {
                if ($behaviour instanceof \qbehaviour_studentfeedbackdeferred) {
                    $data = $questionattempt->get_last_step_with_behaviour_var('_studentfeedback');
                    if ($data->has_behaviour_var('_studentfeedback')) {
                        $result[$question->id . '-behavior'] = $data->get_behaviour_var('_studentfeedback');
                        $labels[$question->id . '-behavior'] = $question->name;
                    }
                }
            }
            if ($question->get_type_name() == 'essay') {
                if ($question instanceof \qtype_essay_question) {
                    $current = $questionattempt->get_steps_with_submitted_response_iterator()->current();
                    $labels[$question->id . '-' . $question->name] = $question->name;
                    $result[$question->id . '-' . $question->name] = '';
                    if ($current !== null) {
                        $data = $current->get_all_data();
                        // The typecast to string is needed since the question_file_loader __ToString needs to be called to
                        // get the value field of this attempt.
                        $result[$question->id . '-' . $question->name] = strip_tags((string) $data["answer"]);
                    }
                }
            }
        }
        return ['labels' => $labels, 'results' => $result];
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws \moodle_exception
     */
    public static function craft_xlsx(int $courseid, int $cmid) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
        $minfo = get_fast_modinfo($courseid);
        $cm = $minfo->get_cm($cmid);
        $users = self::get_quiz_users($cm->instance);
        $rows = [];
        $additionalcolumns = [];
        $averagecolumns = [];
        $feedbackcolumns = [];
        foreach ($users as $userid) {
            $attempts = array_values(quiz_get_user_attempts([$cm->instance], $userid));
            if (count($attempts) !== 0) {
                foreach ($attempts as $attemptobj) {
                    $attemptresults = [];
                    $result = self::load_attempt($cm->instance, $attemptobj->uniqueid);
                    $rowmetrics = [];
                    $rowgroupmetrics = [];
                    foreach ($result as $resultentry) {
                        $output = [];
                        foreach ($resultentry->matrixrows as $matrixrow) {
                            $key = strtolower(trim($matrixrow->name));
                            $key = strtoupper($key[0]) . substr($key, 1);
                            $additionalcolumns[$resultentry->id . '-' . $resultentry->name . '-' . $key] = $resultentry->name . '-' . $key;
                            $rowmetrics[$key][] = $matrixrow->fraction;
                            $output[] = [
                                'name' => $matrixrow->name,
                                'fraction' => round($matrixrow->fraction * 100, 2) . '%',
                            ];
                        }
                        if ($resultentry->fraction >= 1) {
                            $resultentry->fraction = 1;
                        }
                        $attemptresults[] = [
                            'name' => $resultentry->name,
                            'subpoints' => $output,
                            'fraction' => round($resultentry->fraction * 100, 2) . '%',
                        ];
                    }
                    $usrfeedback = self::load_attempt_feedback($attemptobj->uniqueid);
                    foreach ($usrfeedback['labels'] as $label => $labelname) {
                        $feedbackcolumns[$label] = $labelname;
                    }

                    $series = [];
                    foreach ($rowmetrics as $label => $rowmetric) {
                        $averagecolumns[$label] = $label;
                        $series[$label] = round(array_sum($rowmetric) / count($rowmetric) * 100, 2);
                    }
                    $rows[] = [
                        'userid' => $userid,
                        'attemptid' => $attemptobj->attempt,
                        'timestart' => $attemptobj->timestart,
                        'timefinish' => $attemptobj->timefinish,
                        'results' => $attemptresults,
                        'avgs' => $series,
                        'feedback' => $usrfeedback['results']
                    ];
                }
            }
        }
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/excellib.class.php');
        $workbook = new MoodleExcelWorkbook('export.xlsx', 'Xslx');
        $worksheet = $workbook->add_worksheet('User Attempts');
        $defaults = ['attemptid', 'lastname', 'firstname', 'email', 'started', 'completed', 'timetaken'];
        $columncount = 0;
        // add columns for each question
        foreach ($defaults as $key => $column) {
            $worksheet->write_string(0,
                $columncount++,
                new \lang_string('table_' . $column, 'block_question_report'),
                $workbook->add_format(['size' => 14, 'bold' => 1]));
        }

        foreach ($additionalcolumns as $column) {
            $worksheet->write_string(0,
                $columncount++,
                $column,
                $workbook->add_format(['size' => 14, 'bold' => 1]));
        }
        foreach ($averagecolumns as $label) {
            $worksheet->write_string(0,
                $columncount++,
                $label,
                $workbook->add_format(['size' => 14, 'bold' => 1]));
        }
        foreach ($feedbackcolumns as $label) {
            $worksheet->write_string(0,
                $columncount++,
                $label,
                $workbook->add_format(['size' => 14, 'bold' => 1]));
        }
        $worksheet->set_column(0, $columncount, 20);

        $worksheet->set_column(count($defaults), count($additionalcolumns) + count($defaults), 20);
        $users = user_get_users_by_id($users);
        foreach ($rows as $rowid => $row) {
            $user = $users[$row['userid']];
            $columns = [
                $row['attemptid'],
                $user->lastname,
                $user->firstname,
                $user->email,
                userdate($row['timestart']),
                userdate($row['timefinish']),
                format_time($row['timestart'] - $row['timefinish'])
            ];
            $columncount = 0;
            foreach ($columns as $column) {
                $worksheet->write_string($rowid + 1, $columncount++, $column);
            }
            foreach ($row['results'] as $result) {
                foreach ($result['subpoints'] as $subpoint) {
                    $worksheet->write_string($rowid + 1, $columncount++, $subpoint['fraction']);
                }
            }
            foreach ($row['avgs'] as $avg) {
                $worksheet->write_string($rowid + 1, $columncount++, $avg . '%');
            }
            foreach ($row['feedback'] as $feedback) {
                $worksheet->write_string($rowid + 1, $columncount++, $feedback);
            }
        }
        $workbook->close();
        $workbook->send('export.xslx');
    }
}
