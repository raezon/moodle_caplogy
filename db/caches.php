<?php
defined('MOODLE_INTERNAL') || die();

$definitions = [
    'airtable_events' => [
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 3600, // 1 heure
    ],
];
