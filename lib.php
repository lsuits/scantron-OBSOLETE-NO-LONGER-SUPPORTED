<?php

require_once('../../lib/lsulib.php');

abstract class ScantronFile {
    function __construct($filename) {
        $this->lines = file($filename);
    }

    abstract public function validate();
    abstract public function extract_data();
}

class KeyFile extends ScantronFile {
    function validate() {
        $num_lines = count($this->lines);

        // Ensure that the file contains at least one answer
        if ($num_lines < 17) {
            return false;
        }

        // Ensure that the file has the number of answers it advertises on line 5
        if (!((int)$this->lines[4] == $num_lines - 16)) {
            return false;
        }

        return true;
    }

    // Parses the key file to extract answers from form 1 of the test.
    // Next, the answers sequences for forms 2, 3, and 4 are unscrambled.
    //
    // An array of the four form answer strings is returned. The answers are
    // delimited by | character, and multiple answers per test questions are
    // supported.
    //
    // Sample form answer string: C|AC|A|B|D|C|AB|C|D|A|D|B|C|A
    function extract_data() {
        $num_forms = (int) $this->lines[6];

        $form_1_answers = array();

        $extra_form_seqs = array();
        $extra_form_answers = array();

        foreach (range(1, $num_forms - 1) as $n) {
            $extra_form_seqs[] = array();
            $extra_form_answers[] = array();
        }

        $answer_lines = array_slice($this->lines, 16);

        $base_step = ($num_forms - 1) * 4 + 5;

        $letter_steps = array(
            $base_step + 7 * 0 => 'A',
            $base_step + 7 * 1 => 'B',
            $base_step + 7 * 2 => 'C',
            $base_step + 7 * 3 => 'D',
            $base_step + 7 * 4 => 'E'
        );

        foreach ($answer_lines as $line) {
            $answer = $line[0];

            $tmp_answer = '';

            if ($answer == 'V') {
                foreach ($letter_steps as $number => $letter) {
                    if ($line[$number] . $line[$number + 1] != ' 0') {
                        $tmp_answer .= $letter;
                    }
                }

                $form_1_answers[] = $tmp_answer;
            } else {
                $form_1_answers[] = $line[0];
            }

            $line_arr = str_split($line);

            foreach($extra_form_seqs as $k => $extra_form) {
                $seq_num = (int) implode('', array_slice($line_arr, 5 + $k * 4, 3));

                $extra_form_seqs[$k][] = $seq_num;
            }
        }

        // For each form, find the index at which the appropriate answer is held
        // in $form_1_answers
        foreach ($form_1_answers as $n => $answer) {
            foreach ($extra_form_seqs as $i => $extra_form) {
                $answer = $form_1_answers[array_search($n + 1, $extra_form_seqs[$i])];

                $extra_form_answers[$i][] = $answer;
            }
        }

        $ret = array();

        $ret[] = implode('|', $form_1_answers);

        foreach ($extra_form_answers as $answers) {
            $ret[] = implode('|', $answers);
        }

        return implode(';', $ret);
    }
}

class StudentsFile extends ScantronFile {
    function validate() {
        if (count($this->lines) == 0) {
            return false;
        }

        // Each student should have at least one answer
        foreach ($this->lines as $line) {
            if (strlen($line) < 38) {
                return false;
            }
        }

        return true;
    }

    function extract_data() {
        global $DB;

        $students_data = array();

        foreach ($this->lines as $line) {
            $chars = str_split($line);

            $form_number = $chars[0];

            $lsuid = implode('', array_slice($chars, 6, 9));
            $answers = implode('', array_slice($chars, 36, count($chars) - 37));

            $userid = $DB->get_field('user', 'id', array('idnumber' => $lsuid));

            $student_data = new stdClass;
            $student_data->userid = $userid;
            $student_data->answers = $answers;
            $student_data->form_number = $form_number;

            $students_data[] = $student_data;
        }

        return $students_data;
    }
}

// Generates the HTML required to render a scantron sheet
function generate_form_facsimile($examid, $userid, $courseid) {
    global $DB;

    $out = '';

    $params = array('examid' => $examid, 'userid' => $userid);
    $answers_record = $DB->get_record('block_scantron_answers', $params);

    $form_number = $answers_record->form_number;

    $params = array('examid' => $examid);
    $all_form_data = $DB->get_field('block_scantron_keys', 'answers', $params);

    $all_form_data_arr = explode(';', $all_form_data);

    $key = explode('|', $all_form_data_arr[$form_number - 1]);

    $answers = str_split($answers_record->answers);

    $fullname = fullname($DB->get_record('user', array('id' => $userid)));

    $shortname = $DB->get_field('course', 'shortname', array('id' => $courseid));
    $dept_and_number = get_formatted_shortname($shortname);

    $itemid = $DB->get_field('block_scantron_exams', 'itemid', array('id' => $examid));
    $itemname = $DB->get_field('grade_items', 'itemname', array('id' => $itemid));

    $subject =  $dept_and_number . ' ' . $itemname;

    $out .= "
                    <table class = 'scantron'>
                      <thead>
                        <tr>
                          <td colspan = '6'>
                            <span class = 'scantron_name'>
                              <span class = 'scinvisitext'>Name: </span>
                              $fullname
                            </span>
                            <span class = 'scantron_subject'>
                              <span class = 'scinvisitext'>Subject: </span>
                              $subject
                            </span>
                            <span class = 'scantron_date'>
                              <span class = 'scinvisitext'>Date: </span>
                            </span>
                          </td>
                        </tr>
                      </thead>
                      <tbody>
    ";

    foreach (range(1, count($key)) as $i) {
        $img_lines = '';

        foreach (str_split($i) as $n) {
            $img_lines .= "<img src = 'images/$n.gif' alt='$n' />";
        }

        $correct_answers = str_split($key[$i - 1]);
        $student_answer = $answers[$i - 1];

        $a_style = $b_style = $c_style = $d_style = $e_style = '';
        $a_span = $b_span = $c_span = $d_span = $e_span = '';

        if (in_array($student_answer, $correct_answers)) {
            $lower = strtolower($student_answer);

            ${$lower . '_style'} = ' scantron_selected_correct_answer';
            ${$lower . '_span'} = "<span>$student_answer</span>";
        } else {
            $s_lower = strtolower($student_answer);
            $c_lowers = array_map('strtolower', $correct_answers);

            ${$s_lower . '_style'} = ' scantron_selected_incorrect_answer';
            ${$s_lower . '_span'} = "<span>$student_answer</span>";

            foreach ($c_lowers as $c_i => $c_lower) {
                ${$c_lower . '_style'} = ' scantron_correct_answer';
                ${$c_lower . '_span'} = "<span>$correct_answers[$c_i]!</span>";
            }
        }

        $out .= "
                        <tr>
                          <td class = 'scantron_question_number'>
                            $img_lines
                          </td>
                          <td class = 'scantron_cola$a_style'>
                            $a_span
                          </td>
                          <td class = 'scantron_colb$b_style'>
                            $b_span
                          </td>
                          <td class = 'scantron_colc$c_style'>
                            $c_span
                          </td>
                          <td class = 'scantron_cold$d_style'>
                            $d_span
                          </td>
                          <td class = 'scantron_cole$e_style'>
                            $e_span
                          </td>
                        </tr>
        ";
    }

    $out .= "
                      </tbody>
                    </table>
    ";

    return $out;

}
