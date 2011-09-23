<?php

require_once('../../config.php');
require_once($CFG->libdir . '/uploadlib.php');
require_once($CFG->libdir . '/quick_template.php');

require_once('lib.php');

INI_SET("auto_detect_line_endings", true);

$_s = function($key, $a=NULL) {return get_string($key, 'block_scantron', $a);};

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid));

require_login($course);

$context = get_context_instance(CONTEXT_COURSE, $courseid);

require_capability('moodle/course:update', $context, $USER->id);

$tpl_data = array();
$tpl_data['courseid'] = $courseid;

//
// Process form data if it was submitted
//

$upload_success = false;

$form_data = data_submitted();

if ($form_data) {
    $missing_key_file = !$_FILES['key_file']['tmp_name'];
    $missing_students_file = !$_FILES['students_file']['tmp_name'];
    $missing_itemid = !$form_data->itemid;

    $error_link = $CFG->wwwroot . '/blocks/scantron/manage.php?id=' . $courseid;

    if ($missing_key_file || $missing_students_file || $missing_itemid) {
        print_error('input_error', 'block_scantron', $error_link);
    }

    $um = new upload_manager('', '', '', $courseid, '', 0, true);

    if (!$um->preprocess_files()) {
        print_error('upload_error', 'block_scantron', $error_link);
    }

    $key_file = new KeyFile($um->files['key_file']['tmp_name']);

    if (!$key_file->validate()) {
        print_error('key_file_invalid', 'block_scantron', $error_link);
    }

    $key_answers = $key_file->extract_data();

    $students_file = new StudentsFile($um->files['students_file']['tmp_name']);

    if (!$students_file->validate()) {
        print_error('students_file_invalid', 'block_scantron', $error_link);
    }

    $students_data = $students_file->extract_data();

    $exam = new stdClass;
    $exam->userid = $USER->id;
    $exam->courseid = $courseid;
    $exam->itemid = clean_param($form_data->itemid, PARAM_INT);

    $examid = $DB->insert_record('block_scantron_exams', $exam);

    $key = new stdClass;
    $key->examid = $examid;
    $key->answers = $key_answers;

    $DB->insert_record('block_scantron_keys', $key);

    foreach ($students_data as $data) {
        $answers = new stdClass;
        $answers->userid = $data->userid;
        $answers->examid = $examid;
        $answers->form_number = $data->form_number;
        $answers->answers = $data->answers;

        $DB->insert_record('block_scantron_answers', $answers);
    }

    $upload_success = true;
    $tpl_data['upload_success'] = $upload_success;

    $success_itemid = $exam->itemid;
}

//
// Prepare and print header
//

$blockname = $_s('pluginname');
$header = $_s('manage_files');

$PAGE->set_context($context);

$PAGE->navbar->add($header);
$PAGE->set_title($blockname);
$PAGE->set_heading($SITE->shortname . ': ' . $blockname);
$PAGE->set_url('/blocks/scantron/manage.php');

echo $OUTPUT->header();
echo $OUTPUT->heading($header);

//
// Prepare page data
//

$where = "courseid = $courseid AND itemtype = 'manual'";
$items = $DB->get_records_select_menu('grade_items', $where, array(), '', 'id, itemname');

if ($upload_success) {
    $itemname = $items[$success_itemid];

    $tpl_data['success_str'] = $_s('upload_success', $itemname);

}

$exams = $DB->get_records('block_scantron_exams', array('courseid' => $courseid));

$tpl_data['exams'] = $exams;

$taken_items = array();

if ($exams) {
    foreach ($exams as $exam) {
        $taken_items[$exam->itemid] = $items[$exam->itemid];

        unset($items[$exam->itemid]);
    }
}

$tpl_data['items'] = $items;

$select_options = array();

if ($items) {
    $tpl_data['item_select'] = html_writer::select($items, 'itemid');
}

if ($exams) {
    $files_table = new html_table();
    $files_table->head = array($_s('grade_item'), $_s('view'), $_s('delete'));
    $files_table->data = array();

    $page_prefix = $CFG->wwwroot . '/blocks/scantron/';

    foreach ($exams as $exam) {
        $params = array('id' => $courseid, 'examid' => $exam->id);

        $view_url = new moodle_url('/blocks/scantron/view.php', $params);
        $delete_url = new moodle_url('/blocks/scantron/delete.php', $params);

        $view_link = html_writer::link($view_url, $_s('view'));
        $delete_link= html_writer::link($delete_url, $_s('delete'));

        $first_cell = new html_table_cell($taken_items[$exam->itemid]);
        $view_cell = new html_table_cell($view_link);
        $delete_cell = new html_table_cell($delete_link);

        $row = new html_table_row(array($first_cell, $view_cell, $delete_cell));

        $files_table->data[] = $row;
    }

    $tpl_data['files_table_src'] = html_writer::table($files_table, true);
}

//
// Print page template and footer
//

quick_render('manage.tpl', $tpl_data, 'block_scantron');

echo $OUTPUT->footer();
