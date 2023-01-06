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
 * Display question attempts for current user for a course (totals)
 *
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */


use block_question_report\output\attempt;
use block_question_report\output\overview;
use block_question_report\quiz_helper;
use block_question_report\util;

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$attempt = optional_param('attempt', 0, PARAM_INT);
$course = get_course($id);

$PAGE->set_url(new moodle_url('/blocks/question_report/index.php'));
require_login($course);
$context = context_course::instance($course->id);
$PAGE->set_context($context);

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('block_question_report');
if ($cmid === 0) {
    $helper = new quiz_helper($course->id);
    $overview = new overview();
    $overview->set_quiz_list($helper->get_quiz_list());
    echo $renderer->render_overview($overview);
} else {
    global $USER, $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quiz/lib.php');
    $cm = get_coursemodule_from_id('quiz', $cmid, $course->id, false, MUST_EXIST);
    $attempts = array_values(quiz_get_user_attempts([$cm->instance], $USER->id));
    if (count($attempts) !== 0) {
        $context = context_module::instance($cm->id);
        $attemptview = new attempt();
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
            $attemptview->set_current_attempt($attempts[count($attempts) - 1]);
        }
        $attemptobj = $attemptview->get_current_attempt();

        $users = util::get_quiz_users($cm->instance);
        $averageattempt = util::average_users_attempts($cm->instance, $users);
        $attemptview->set_averageresult($averageattempt);

        $result = util::load_attempt($cm->instance, $attemptobj->uniqueid);
        $attemptview->set_result($result);
        echo $renderer->render_attempt($attemptview);
    }
}

echo $OUTPUT->footer();
