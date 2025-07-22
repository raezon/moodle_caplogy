<?php
require_once(__DIR__ . '/../../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/tonblock/ma_page.php'));
$PAGE->set_title('Test page personnalisée');
$PAGE->set_heading('Bienvenue sur ma page personnalisée');

// Données à passer au template Mustache
$data = [
    'choix_retards' => [
        ['value' => 'oui', 'label' => 'Oui'],
        ['value' => 'non', 'label' => 'Non'],
        ['value' => 'parfois', 'label' => 'Parfois'],
    ],
    'choix_depart_top' => [
        ['value' => 'oui', 'label' => 'Oui'],
        ['value' => 'non', 'label' => 'Non'],
    ],
    'etat_etudiants' => [
        ['value' => 'ras', 'label' => "RAS"],
        ['value' => 'absent', 'label' => "Absent"],
        ['value' => 'present', 'label' => "Présent"],
    ],
];

$PAGE->requires->js(new moodle_url('/blocks/dashboard/assets/validation/signature.js'));
$PAGE->requires->css(new moodle_url('/blocks/dashboard/assets/validation/styles.css'));

// Rendu Mustache
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_dashboard/form_validation', $data);
echo $OUTPUT->footer();
