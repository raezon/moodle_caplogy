<?php
require_once($CFG->dirroot . '/blocks/calendar_upcoming/block_calendar_upcoming.php');


class block_dashboard extends block_base
{
    public function init()
    {
        $this->title = get_string('pluginname', 'block_dashboard');
    }

    public function get_required_capabilities()
    {
        return ['block/dashboard:view'];
    }
    public function get_content()
    {
        global $USER, $PAGE, $OUTPUT, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

  

        $this->content = new stdClass();

        $time = time();
        $courseid = SITEID;

        // Render the upcoming calendar block.
        $calendarblock = new \block_calendar_upcoming();
        $calendarblock->page = $PAGE; // Fix: inject current page context
        $calendarblock->course = $PAGE->course ?? (object) ['id' => SITEID]; // Fix: force valid course id
        $calendarblock->init();
        $calendarcontent = $calendarblock->get_content();



        // Date du jour
        $today = usergetdate($time);
        $today_yday = $today['yday'];

        // Récupération des cours où l'utilisateur est inscrit
        $courses = enrol_get_users_courses($USER->id, true, '*');
        $today_courses = [];

        foreach ($courses as $course) {
            if (usergetdate($course->startdate)['yday'] == $today_yday) {
                $today_courses[] = [
                    'fullname' => format_string($course->fullname),
                    'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false)
                ];
            }
        }

        $allcoursesurl = (new moodle_url('/my/courses.php'))->out(false);

        // Préparation des données pour le template Mustache
        $templatecontext = [
            // 'calendar' => $calendar_html,
            'calendar' => $calendarcontent->text ?? '',
            'todaycourses' => $today_courses,
            'hascourses' => !empty($today_courses),
            'allcoursesurl' => $allcoursesurl
        ];

        // Rendu avec template
        $this->content->text = $OUTPUT->render_from_template('block_dashboard/content', $templatecontext);
        $this->content->footer = '';

        // Chargement CSS personnalisé optionnel
        $PAGE->requires->css(new moodle_url('/blocks/dashboard/styles.css'));

        return $this->content;
    }
}
