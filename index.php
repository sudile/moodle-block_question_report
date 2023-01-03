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
 * Display user activity reports for a course (totals)
 *
 * @package    report
 * @subpackage matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */


require('../../config.php');

$id = required_param('id', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$attempt = optional_param('attempt', 0, PARAM_INT);
$course = get_course($id);

$PAGE->set_url(new moodle_url('/report/matrixreport/index.php'));
$PAGE->set_pagelayout('report');

require_login($course);
$context = context_course::instance($course->id);

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('report_matrixreport');
if ($cmid === 0) {
    $helper = new \report_matrixreport\quiz_helper($course->id);
    $overview = new \report_matrixreport\output\overview();
    $overview->set_quiz_list($helper->get_quiz_list());
    echo $renderer->render_overview($overview);
} else {
    global $USER, $CFG;
    require_once('../../mod/quiz/lib.php');
    $cm = get_coursemodule_from_id('quiz', $cmid, $course->id, false, MUST_EXIST);
    $attempts = array_values(quiz_get_user_attempts([$cm->instance], $USER->id));
    if (count($attempts) !== 0) {
        $attemptview = new \report_matrixreport\output\attempt();
        $attemptview->set_cm($cm);
        $attemptview->set_course($course);
        $attemptview->set_attempts($attempts);
        foreach ($attempts as $attemptobj) {
            if ($attemptobj->id == $attempt) {
                $attemptview->set_current_attempt($attemptobj);
                break;
            }
        }
        if ($attemptview->get_current_attempt() == null) {
            $attemptview->set_current_attempt($attempts[count($attempts)-1]);
        }

        require_once($CFG->dirroot . '/question/engine/bank.php');
        $attemptobj = $attemptview->get_current_attempt();
        $x = question_engine::load_questions_usage_by_activity($attemptobj->uniqueid);
        $result = [];
        foreach ($x->get_slots() as $slot) {
            $question = $x->get_question($slot);
            $questionattempt = $x->get_question_attempt($slot);
            $result[] = ['fraction' => $questionattempt->get_fraction(), 'name' => $question->name];
        }
        $attemptview->set_result($result);
        echo $renderer->render_attempt($attemptview);
    }
}

echo $OUTPUT->footer();