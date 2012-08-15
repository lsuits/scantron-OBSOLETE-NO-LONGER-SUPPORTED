<?php

require_once('../../config.php');
require_once('lib.php');

//
// Validate params and user
//

$_s = function($key, $a=NULL) {return get_string($key, 'block_scantron', $a);};

$courseid = required_param('id', PARAM_INT);
$param_examid = optional_param('examid', NULL, PARAM_INT);
$param_userid = optional_param('userid', NULL, PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid));

require_login($course);

if (!$course) {
    print_error('course_not_found', 'block_scantron');
}

$context = get_context_instance(CONTEXT_COURSE, $courseid);
$student_roles = explode(',', $CFG->gradebookroles);

$is_teacher = has_capability('moodle/course:update', $context, $USER->id);
$is_student = false;

if (is_siteadmin($USER->id)) {
    $is_teacher = true;

    $enrolled_students = get_role_users($student_roles, $context);
} else if ($is_teacher) {
    $teacher_groups = groups_get_user_groups($course->id, $USER->id);

    $enrolled_students = array();

    foreach (array_values($teacher_groups) as $key => $groupid) {
        $group_students = get_role_users($student_roles, $context, false, '',
            'u.lastname ASC', true, $groupid);

        foreach ($group_students as $student_userid => $student_data) {
            $enrolled_students[$student_userid] = $student_data;
        }
    }
} else {
    $is_student = true;
    $enrolled_students = array($USER->id => $USER);
}

$course_link = $CFG->wwwroot . '/course/view.php?id=' . $courseid;

// Check if a student is trying to view another student's answers
if ($param_userid && $is_student) {
    print_error('no_permission', 'block_scantron', $course_link);
}

if (!$is_teacher && !$is_student) {
    print_error('no_permission', 'block_scantron', $course_link);
}

// No suspicious activity, so set student's userid
if ($is_student && !$is_teacher) {
    $param_userid = $USER->id;
}

//
// Prepare and print header
//

$blockname = $_s('pluginname');
$header = $_s('view_answers');

$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/scantron/view.php');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

//
// Prepare page data
//

$exams = $DB->get_records('block_scantron_exams', array('courseid' => $courseid));

$exam_options = array();

foreach ($exams as $examid => $exam) {
    $params = array('id' => $exam->itemid);
    $itemname = $DB->get_field('grade_items', 'itemname', $params);

    $exam_options[$examid] = $itemname;
}

$exam_select = html_writer::select($exam_options, 'examid', $param_examid);

if ($is_teacher) {
    $student_options = array();

    foreach ($enrolled_students as $userid => $student) {
        $fullname = $student->firstname . ' ' . $student->lastname;

        $student_options[$userid] = $fullname;
    }

    $student_select = html_writer::select($student_options, 'userid', $param_userid);
}

if (!$param_examid && !$param_userid) {
    $error_str = $_s('neither_selected');
} else if (!$param_examid) {
    $error_str = $_s('no_exam_selected');
} else if (!$param_userid) {
    $error_str = $_s('no_student_selected');
} else {
    $error_str = '';
}

$form_facsimile = '';

if (!$error_str) {
    $params = array('examid' => $param_examid, 'userid' => $param_userid);
    $user_data_for_exam = $DB->get_record('block_scantron_answers', $params);

    if (!$user_data_for_exam) {
        $a = new stdClass;
        $a->fullname = $student_options[$param_userid];
        $a->itemname = $exam_options[$param_examid];

        $error_str = $_s('no_answers_for_student', $a);
    } else {
        $form_facsimile = generate_form_facsimile(
            $param_examid, $param_userid, $courseid
        );
    }
}

//
// Print page markup and footer
//

if ($exam_options) {
    echo html_writer::start_tag('div', array('id' => 'block_scantron_select_row'));
    echo html_writer::start_tag('form', array(
        'method' => 'POST', 'action' => 'view.php?id=' . $courseid
    ));

    echo $_s('exam') . ': ' . $exam_select;

    if ($is_teacher) {
        echo $_s('student') . ': ' . $student_select;
    }

    echo html_writer::empty_tag('input', array(
        'type' => 'submit',
        'value' => $_s('view')
    ));

    echo html_writer::end_tag('form');
    echo html_writer::end_tag('div');
    echo "<br />";
} else {
    echo html_writer::tag('div', $_s('no_exams'), array(
        'id' => 'block_scantron_view_error'
    ));
}

if ($error_str) {
    echo $OUTPUT->notification($error_str);
} else {
    $inivis = array('class' => 'scinvisitext');

    echo $form_facsimile;
    echo html_writer::tag('div',
        html_writer::tag('ul',
        html_writer::tag('p', 'Key', $inivis) .
        html_writer::tag('li',
            html_writer::tag('span', 'Blank Cell: ', $inivis) .
            'Unmarked Answer'
        ) .
        html_writer::tag('li',
            html_writer::tag('span', 'Letter: ', $inivis) .
            'Correct Student Response',
            array('class' => 'correct_response')
        ) .
        html_writer::tag('li',
            html_writer::tag('span', 'Exclamation Mark: ', $inivis) .
            'Correct Answer',
            array('class' => 'correct_answer')
        ) .
        html_writer::tag('li',
            html_writer::tag('span', 'Strikethrough: ', $inivis) .
            'Incorrect Student Response',
            array('class' => 'incorrect_response')
        )),
        array('class' => 'scantron_key')
    );
}

echo $OUTPUT->footer();
