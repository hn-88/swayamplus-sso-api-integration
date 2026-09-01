<?php
defined('MOODLE_INTERNAL') || die();

$definitions = [
    'tokens' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 600, // Slightly above your 550s manual expiry as a safety margin.
    ],
];
