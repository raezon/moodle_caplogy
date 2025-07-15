<?php
defined('MOODLE_INTERNAL') || die();

use core_course_category;

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
        global $USER, $PAGE, $OUTPUT, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $timefrom = time();
        $timeto = $timefrom + 30 * 24 * 60 * 60; // 30 jours

        // Récupérer les événements dans les 30 jours (globaux ou personnels)
        $sql = "SELECT e.*
                FROM {event} e
                WHERE e.timestart BETWEEN :timefrom AND :timeto
                AND (e.userid = :userid OR e.courseid = 1)"; // courseid=1 = site home

        $params = [
            'timefrom' => $timefrom,
            'timeto' => $timeto,
            'userid' => $USER->id
        ];

        $events = $DB->get_records_sql($sql, $params);

        $calendarevents = [];
        foreach ($events as $event) {
            $calendarevents[] = [
                'title' => format_string($event->name),
                'description' => format_text($event->description, FORMAT_HTML),
                'date' => userdate($event->timestart, '%d %B %Y, %H:%M'),
            ];
        }

        // Récupérer les cours où l'utilisateur est inscrit et qui commencent aujourd’hui
        $courses = enrol_get_users_courses($USER->id, true, '*');
        $today_yday = usergetdate($timefrom)['yday'];
        $today_courses = [];

        foreach ($courses as $course) {
            if (usergetdate($course->startdate)['yday'] == $today_yday) {
                $today_courses[] = [
                    'fullname' => format_string($course->fullname),
                    'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false)
                ];
            }
        }

        // Récupérer les catégories racines (écoles)
        $categories = core_course_category::get_all(true, 0, true);
        $schools = [];
        foreach ($categories as $cat) {
            $schools[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name),
                'description' => format_text($cat->description, FORMAT_HTML),
                'url' => (new moodle_url('/course/index.php', ['categoryid' => $cat->id]))->out(false),
            ];
        }

        $allcoursesurl = (new moodle_url('/my/courses.php'))->out(false);

        // Préparer le contexte pour le template Mustache
        $templatecontext = [
            'calendarevents' => $calendarevents,
            'todaycourses' => $today_courses,
            'hascourses' => !empty($today_courses),
            'allcoursesurl' => $allcoursesurl,
            'schools' => $schools,
        ];

        $this->content->text = $OUTPUT->render_from_template('block_dashboard/content', $templatecontext);
        $this->content->footer = '';

        $PAGE->requires->css(new moodle_url('/blocks/dashboard/styles.css'));

        return $this->content;
    }
}
