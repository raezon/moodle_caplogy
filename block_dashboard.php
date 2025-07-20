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
        global $USER, $PAGE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $month = (int) date('n');
        $year = (int) date('Y');

        // Récupérer les événements pour ce mois/année
        $eventdays = $this->get_upcoming_events_for_month($month, $year);

        // Autres données (exemple, modifie au besoin)
        $schools = $this->get_current_schools();

        $templatecontext = [
            'schools' => $schools,
            'year' => $year,
            'month' => $month,
            'eventdays' => $eventdays,
        ];

        $this->content->text = $OUTPUT->render_from_template('block_dashboard/content', $templatecontext);
        $this->content->footer = '';

        // Charger CSS et JS externes
        $PAGE->requires->css(new moodle_url('/blocks/dashboard/assets/styles.css'));
        $PAGE->requires->js(new moodle_url('/blocks/dashboard//assets/calendar.js'));
        $PAGE->requires->css(new moodle_url('/blocks/dashboard/assets/calendar.css'));

        // Passer les données JS dynamiques via require_js_call_amd ou js_init_code (ici simple json via js_init_code)
        $jsonevents = json_encode($eventdays);
        $jsonyear = json_encode($year);
        $jsonmonth = json_encode($month);

        $PAGE->requires->js_init_code(<<<JS
            window.dashboardCalendarData = {
                events: $jsonevents,
                year: $jsonyear,
                month: $jsonmonth
            };
        JS
        );

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
