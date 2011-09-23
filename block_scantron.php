<?php

class block_scantron extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_scantron');
    }

    function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        $_s = function($key) { return get_string($key, 'block_scantron'); };

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;

        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);

        $params = array('id' => $COURSE->id);

        // Only print the manage link if the user is a teacher
        if (has_capability('moodle/course:update', $context, $USER->id)) {
            $url = new moodle_url('/blocks/scantron/manage.php', $params);
            $manage_link = html_writer::link($url, $_s('manage_files'));

            $this->content->items[] = $manage_link;
        }

        $url = new moodle_url('/blocks/scantron/view.php', $params);
        $view_link = html_writer::link($url, $_s('view_answers'));

        $this->content->items[] = $view_link;

        return $this->content;
    }
}
