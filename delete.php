<?php

require_once('../../config.php');
require_once($CFG->libdir . '/quick_template.php');

//
// Validate params and user
//

$_s = function($key, $a=NULL) {return get_string($key, 'block_scantron', $a);};

$courseid = required_param('id', PARAM_INT);
$examid = required_param('examid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));

require_login($course);

if (!$course) {
    print_error('course_not_found', 'block_scantron');
}

$context = get_context_instance(CONTEXT_COURSE, $courseid);

require_capability('moodle/course:update', $context, $USER->id);

//
// Process form data if it was submitted
//

$form_data = data_submitted();

if ($form_data) {
    $params = array('id' => $examid);
    $exam_courseid = $DB->get_field('block_scantron_exams', 'courseid', $params);

    if ($courseid != $exam_courseid) {
        print_error('no_permission', 'block_scantron');
    }

    if ($form_data->delete_confirm == 'true') {
        $DB->delete_records('block_scantron_keys', array('examid' => $examid));
        $DB->delete_records('block_scantron_answers', array('examid' => $examid));
        $DB->delete_records('block_scantron_exams', array('id' => $examid));
    }

    $params = array('id' => $courseid);
    $redirect_url = new moodle_url('/blocks/scantron/manage.php', $params);

    redirect($redirect_url);
}

//
// Prepare and print header
//

$blockname = $_s('pluginname');
$header = $_s('delete_exam');

$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/scantron/delete.php');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

//
// Prepare page data
//

$exam = $DB->get_record('block_scantron_exams', array('id' => $examid));
$itemname = $DB->get_field('grade_items', 'itemname', array('id' => $exam->itemid));
$confirm_str = $_s('confirm_delete', $itemname);

//
// Print page template and footer
//

$tpl_data = array(
    'examid' => $examid,
    'courseid' => $courseid,
    'confirm_str' => $confirm_str,
);

quick_render('delete.tpl', $tpl_data, 'block_scantron');

echo $OUTPUT->footer();
