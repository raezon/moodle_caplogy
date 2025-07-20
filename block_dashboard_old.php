<?php
defined('MOODLE_INTERNAL') || die();

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

    public function get_content()
    {
        global $USER, $PAGE, $OUTPUT, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $timefrom = time();
        $timeto = $timefrom + 30 * 24 * 60 * 60; // 30 jours

        // Récupérer les événements dans les 30 jours
        $month = date('n');
        $year = date('Y');
        $eventdays = $this->get_upcoming_events_for_month($month, $year, $USER);
        //  $calendar_data = $this->prepare_calendar_template_data($month, $year, $events);



        // Cours commençant aujourd’hui
        $today_courses = $this->get_current_course($USER, $timefrom);

        // last access courses
        $lastCourses = $this->fetch_last_accessed_courses_data($USER->id);


        $schools = $this->get_current_schools();

        $allcoursesurl = (new moodle_url('/my/courses.php'))->out(false);

        // 📦 Ajouter les articles du blog
        //  $blogposts = $this->get_blog_posts();

        // 📋 Préparer les données pour le template
        $templatecontext = [
            'eventdays' => $eventdays,
            'lastCourses' => $lastCourses,
            'hascourses' => !empty($today_courses),
            'allcoursesurl' => $allcoursesurl,
            'schools' => $schools,
            'blogposts' => [],
            'hasblog' => !empty($blogposts),
        ];

        $this->content->text = $OUTPUT->render_from_template('block_dashboard/content', $templatecontext);
        $this->content->footer = '';

        $PAGE->requires->css(new moodle_url('/blocks/dashboard/styles.css'));

        return $this->content;
    }

    /**
     * Summary of prepare_calendar_template_data
     * @param mixed $month
     * @param mixed $year
     * @param mixed $events
     * @return array{days: string[], monthname: mixed, weeks: array}
     */
    function prepare_calendar_template_data($month, $year, $events)
    {
        $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $calendar = [];

        $firstday = mktime(0, 0, 0, $month, 1, $year);
        $dayofweek = date('N', $firstday); // 1 = Lundi
        $numdays = date('t', $firstday);

        $currentday = 1;
        $week = [];

        for ($i = 1; $i < $dayofweek; $i++) {
            $week[] = ['day' => '', 'events' => []];
        }

        while ($currentday <= $numdays) {
            $dayevents = [];
            foreach ($events as $event) {
                if ((int) date('j', $event['timestart']) == $currentday) {
                    $dayevents[] = ['name' => $event['name']];
                }
            }

            $week[] = ['day' => $currentday, 'events' => $dayevents];

            if (count($week) == 7) {
                $calendar[] = $week;
                $week = [];
            }

            $currentday++;
        }

        while (count($week) < 7) {
            $week[] = ['day' => '', 'events' => []];
        }

        $calendar[] = $week;

        return [
            'monthname' => userdate($firstday, '%B %Y'),
            'days' => $days,
            'weeks' => $calendar
        ];
    }


    /**
     * Summary of get_upcoming_events_for_month
     * @param mixed $month
     * @param mixed $year
     * @param mixed $userid
     */
    function get_upcoming_events_for_month($month, $year, $userid = null)
    {
        global $USER, $DB;

        $now = time();

        // Requête pour tous les événements futurs visibles par l'utilisateur
        $sql = "SELECT e.* FROM {event} e";

        $params = [
            'now' => $now,
            'userid' => $USER->id
        ];

        // Exécuter la requête
        $events = $DB->get_records_sql($sql, $params);

        // Construire une carte jour => événements
        $eventmap = [];
        foreach ($events as $event) {
            $date = userdate($event->timestart, '%Y-%m-%d');
            $eventmap[$date][] = [
                'name' => format_string($event->name),
                'time' => userdate($event->timestart, '%H:%M'),
            ];
        }


        // Créer la structure du mois courant
        $monthstart = strtotime(date('Y-m-01', $now));


        // On part du lundi de la première semaine du mois
        $firstday = date('w', $monthstart);
        $firstday = ($firstday == 0) ? 6 : $firstday - 1; // Lundi = 0
        $gridstart = strtotime("-$firstday day", $monthstart);

        $year = date('Y'); // ou année ciblée
        $month = date('m'); // ou mois ciblé

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $eventdays = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            // Si des événements existent pour ce jour, on les prend, sinon tableau vide
            $events = isset($eventmap[$date]) ? $eventmap[$date] : [];

            $eventdays[] = [
                'date' => $date,
                'events' => $events,
            ];
        }

        // Regrouper chaque 7 jours dans une "semaine"
        $weeks = array_chunk($eventdays, 7);
        return $weeks;


    }

    // Fonction qui génère la grille du calendrier avec les événements associés.
    function render_custom_calendar($month, $year, $eventmap)
    {
        global $PAGE, $OUTPUT, $USER;

        // Prépare les événements personnalisés
        $events = build_custom_events($eventmap);

        // Paramètres du calendrier
        $calendar = new \core_calendar\type_standard();
        $calendardata = calendar_get_mini($month, $year, true, true, $USER->id);

        // Injecte tes événements dans les bons jours
        foreach ($events as $event) {
            $day = date('j', $event->timestart);
            $calendardata['events'][$day][] = $event;
        }

        // Rendu HTML
        return calendar_get_mini_html($month, $year, $events);
    }




    private function get_current_course($USER, $timefrom): array
    {

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


        return $today_courses;
    }


    private function get_current_schools(): array
    {


        // Catégories racines = écoles
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



    /**
     * Retourne l'URL de l'image d'aperçu du cours, ou une image par défaut.
     */
    private function get_course_overview_image_url(int $courseid): string
    {
        global $OUTPUT;

        $context = context_course::instance($courseid);
        $fs = get_file_storage();

        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'sortorder DESC, id DESC', false);

        if (!empty($files)) {
            $file = reset($files);
            return moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            )->out(false);
        }

        // Si aucune image d'aperçu, retourne l'image par défaut
        return $OUTPUT->image_url('course/defaultcourseimage', 'theme')->out(false);
    }


    /**
     * Récupère les 3 derniers articles de blog (cours dans la catégorie Blog).
     */
    /*private function get_blog_posts(): array
    {
        global $DB, $OUTPUT;

        $blogcategoryid = 10; // À adapter selon ton Moodle
        $blogcourses = $DB->get_records('course', ['category' => $blogcategoryid], 'startdate DESC', '*', 0, 3);

        $posts = [];

        foreach ($blogcourses as $course) {
            $imageurl = $OUTPUT->image_url('course/defaultcourseimage', 'theme')->out(false); // par défaut

            if (preg_match('/<img.*?src=["\'](.*?)["\']/', $course->summary, $matches)) {
                $imageurl = $matches[1];
            }

            $posts[] = [
                'title' => format_string($course->fullname),
                'description' => format_text($course->summary, FORMAT_HTML),
                'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
                'image' => $imageurl,
            ];
        }

        return $posts;
    }*/
}
