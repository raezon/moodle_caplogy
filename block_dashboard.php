<?php
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib/airtable_helper.php');


use core_course_category;

use core_course\external\course_summary_exporter;
use core_calendar\local\api as calendar_api;

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

    /**
     * Retourne les données formatées des 3 derniers cours consultés par l'utilisateur.
     *
     * @param int $userid
     * @param int $limit
     * @return array
     */
    private function fetch_last_accessed_courses_data(int $userid, int $limit = 3): array
    {
        global $OUTPUT;

        // Récupérer tous les cours inscrits à l’utilisateur
        $courses = enrol_get_users_courses($userid, true, '*');

        // Trier par lastaccess décroissant
        usort($courses, function ($a, $b) {
            return $b->lastaccess <=> $a->lastaccess;
        });

        // Ne garder que les $limit premiers
        $courses = array_slice($courses, 0, $limit);

        $data = [];

        foreach ($courses as $course) {
            $summary = strip_tags($course->summary);
            if (strlen($summary) > 100) {
                $summary = substr($summary, 0, 100) . '...';
            }

            // Récupérer l’image officielle via course_summary_exporter
            $imageurl = course_summary_exporter::get_course_image($course);

            // Si pas d’image, image par défaut Moodle
            if (!$imageurl) {
                $imageurl = $OUTPUT->image_url('course/defaultcourseimage', 'theme')->out(false);
            }

            $data[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'summary' => $summary,
                'image' => $imageurl,
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            ];
        }

        return $data;
    }

    public function get_content()
    {
        global $USER, $PAGE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $month = (int) date('n');
        $year = (int) date('Y');

        // Récupérer les événements pour ce mois/année
        $eventdays = $this->get_upcoming_events_for_month($month, $year);

        // last access courses
        $lastCourses = $this->fetch_last_accessed_courses_data($USER->id);
        // Autres données (exemple, modifie au besoin)
        $schools = $this->get_current_schools();

        $allcoursesurl = (new moodle_url('/my/courses.php'))->out(false);

        $templatecontext = [
            'schools' => $schools,
            'allcoursesurl'=>$allcoursesurl,
            'lastCourses'=>$lastCourses,
            'year' => $year,
            'month' => $month,
            'eventdays' => $eventdays,
        ];
        $airtable_events = block_dashboard_get_airtable_events();
        $json_airtable_events = json_encode($airtable_events);

        // Charger JS FullCalendar depuis CDN
        $PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js'));


        $PAGE->requires->js_init_code(<<<JS
                window.dashboardCalendarData = {
                    events: $json_airtable_events,
                    year: $year,
                    month: $month
                };
                JS
        );

        // Charger ton JS personnalisé (celui qui instancie FullCalendar)
        $PAGE->requires->js(new moodle_url('/blocks/dashboard/assets/calendar.js'));


        $this->content->text = $OUTPUT->render_from_template('block_dashboard/content', $templatecontext);
        $this->content->footer = '';

      


        // Charger CSS et JS externes
        $PAGE->requires->css(new moodle_url('/blocks/dashboard/assets/styles.css'));


        return $this->content;
    }

    /**
     * Récupère les événements du mois spécifié
     *
     * @param int $month
     * @param int $year
     * @return array Associatif [date => [{name, time}, ...]]
     */
    private function get_upcoming_events_for_month($month, $year)
    {
        global $DB;

        $starttime = strtotime("$year-$month-01 00:00:00");
        $endtime = strtotime(date("Y-m-t 23:59:59", $starttime));

        $sql = "SELECT e.* FROM {event} e
                WHERE e.timestart BETWEEN :starttime AND :endtime
                ORDER BY e.timestart ASC";
        $params = ['starttime' => $starttime, 'endtime' => $endtime];

        $events = $DB->get_records_sql($sql, $params);


        $eventmap = [];
        foreach ($events as $event) {
            $date = userdate($event->timestart, '%Y-%m-%d');
            $eventmap[$date][] = [
                'name' => format_string($event->name),
                'time' => userdate($event->timestart, '%H:%M'),
            ];
        }
        return $eventmap;
    }

    /**
     * Récupère les catégories racines (= écoles)
     *
     * @return array
     */
    private function get_current_schools()
    {
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
        return $schools;
    }
}
