<?php
defined('MOODLE_INTERNAL') || die();

function shorten_title(string $text, int $maxLength = 10): string {
    // Utiliser mb_strlen pour supporter UTF-8
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }
    return mb_substr($text, 0, $maxLength) . '...';
}

/**
 * Récupère les événements Airtable en cache ou via l'API.
 */
function block_dashboard_get_airtable_events(): array {
    $cache = cache::make('block_dashboard', 'airtable_events');

    // recuéprer l'email de l'utilisateur connecter
    global $USER;
    $useremail = $USER->email; // email de l'utilisateur connecté
    $formula = urlencode('AND({Email Intervenant}="' . $useremail . '", {Type d\'activité}="Activité externe")');



    // Tenter de charger depuis le cache
    $events = $cache->get('events');
    if ($events !== false) {
        return $events;
    }

    // Pas dans le cache, faire appel à l’API
    $AIRTABLE_TOKEN = "patNPf6FQ83p9IvQ1.e8223111fa934558c1ddd1228af13c00ca605b026fbe49fb02eb68e2f931a780";
    $BASE_ID = "appOwmkD9vXJn77ie";
    $TABLE_ID = "tblpuHERz9nVwOQhM";
    $LIMIT = 20;

    $url = "https://api.airtable.com/v0/{$BASE_ID}/{$TABLE_ID}?filterByFormula={$formula}";
    $headers = [
        "Authorization: Bearer $AIRTABLE_TOKEN",
        "Content-Type: application/json",
    ];

    $records = [];
    $offset = null;

    while (count($records) < $LIMIT) {
        $finalUrl = $url;
        if ($offset) {
            $finalUrl .= "?offset=" . urlencode($offset);
        }

        $ch = curl_init($finalUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            debugging("Erreur $http_code : $response", DEBUG_DEVELOPER);
            break;
        }

        $data = json_decode($response, true);
        $records = array_merge($records, $data["records"]);

        if (!isset($data["offset"])) {
            break;
        }

        $offset = $data["offset"];
    }

    // Extraire les événements
    $events = [];
    foreach ($records as $rec) {
        $fields = $rec["fields"];
        $title = $fields["Type d'activité"] ?? "Sans titre";
        $matiere= $fields["Matière"] ?? "Sans titre";
        $ecole = $fields["Ecole d'intervention"] ?? "Sans titre";
        //const ecole = event.extendedProps.ecole || "Non précisé";
        //$title = shorten_title($fullTitle, 10);
        $start = rtrim($fields["Heure de début"] ?? "", "Z");
        $end = rtrim($fields["Heure de fin"] ?? "", "Z");

        if ($start && $end) {
            $events[] = [
                'title' => mb_substr($matiere[0], 0, 11) . "-" . "<br>" . $ecole,
                'typeActivite'=>$title,
                'matiere' => $matiere,
                'ecole'=>$ecole,
                'start' => $start,
                'end' => $end,
            ];
        }
    }

    // Stocker en cache pour 1h
    $cache->set('events', $events, time() + 3600); // expiration dans 1 heure

    return $events;
}
