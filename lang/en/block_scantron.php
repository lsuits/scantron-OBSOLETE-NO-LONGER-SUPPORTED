<?php

$string['pluginname'] = 'Scantron Answers';

// General strings
$string['view'] = 'View';
$string['delete'] = 'Delete';
$string['view_answers'] = 'View Answers';
$string['course_not_found'] = 'Course not found';
$string['no_permission'] = 'You do not have permission to perform this action';

// Strings for block_scantron.php
$string['manage_files'] = 'Manage Scantron Files';

// Strings for manage.php
$string['upload'] = 'Upload';
$string['key_file'] = 'Key File';
$string['files'] = 'Uploaded Files';
$string['grade_item'] = 'Grade Item';
$string['student_file'] = 'Student File';
$string['upload_success'] = 'Successfully uploaded scantron for {$a}';
$string['no_files'] = 'You have not uploaded any Scantron files yet';
$string['input_error'] = 'You must provide a Key File, a Students File, and a Grade Item';
$string['no_items'] = 'This course has no scantron-associatable grade items, so you cannot upload any new scantron files';
$string['upload_error'] = 'There was a problem processing the files you uploaded. Please try again or contact support.';
$string['key_file_invalid'] = 'The Key File you provided was invalid. Please provide a valid version or contact support.';
$string['students_file_invalid'] = 'The Students File you provided was invalid. Please provide a valid version or contact support.';

// Strings for view.php
$string['exam'] = 'Exam';
$string['student'] = 'Student';
$string['exam_not_found'] = 'Exam not found';
$string['no_exam_selected'] = 'Please select an exam';
$string['no_student_selected'] = 'Please select a student';
$string['neither_selected'] = 'Please select an exam and a student';
$string['no_exams'] = 'Scantron data has not yet been uploaded for this course';
$string['no_answers_for_student'] = '{$a->fullname} has no scantron data for {$a->itemname}';

// Strings for delete.php
$string['delete_exam'] = 'Delete Exam';
$string['confirm_delete'] = 'Are you sure you want to delete the scantron data associated with the grade item {$a}?';

?>
